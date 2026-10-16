<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public static function query(Request $request)
    {
        $query = DB::table('keluhan_asrama')->join('users', 'users.id', '=', 'keluhan_asrama.user_id')
            ->select('keluhan_asrama.*', 'users.name as user_name');
        if ($request->user()->role === 'user') $query->where('keluhan_asrama.user_id', $request->user()->id);
        return $query;
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', Rule::in([10, 50, 100])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in(['id', 'user_name', 'judul', 'isi', 'status', 'created_at', 'resolved_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);
        $query = self::query($request);
        if ($request->filled('q')) {
            $search = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('judul', 'like', $search)->orWhere('isi', 'like', $search)
                ->orWhere('keluhan_asrama.status', 'like', $search)->orWhere('users.name', 'like', $search));
        }
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort === 'user_name' ? 'users.name' : 'keluhan_asrama.'.$sort, $direction);
        if ($sort !== 'id') $query->orderBy('keluhan_asrama.id', $direction);
        $records = $query->paginate($request->integer('per_page', 10));
        $records->through(function ($record) {
            foreach (['created_at', 'resolved_at'] as $field) $record->$field = $record->$field ? Carbon::parse($record->$field, 'UTC')->toISOString() : null;
            return $record;
        });
        return response()->json(['records' => $records])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:2000'],
        ]);
        DB::table('keluhan_asrama')->insert($data + [
            'user_id' => $request->user()->id, 'status' => 'Diajukan', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $message = 'Keluhan asrama berhasil dikirim.';
        return $request->expectsJson() ? response()->json(['message' => $message], 201)
            : redirect('/user/kamar?section=keluhan')->with('success', $message);
    }

    public function resolve(Request $request, int $id)
    {
        $request->validate(['status' => ['required', Rule::in(['Resolved'])]]);
        DB::transaction(function () use ($request, $id) {
            $record = DB::table('keluhan_asrama')->where('id', $id)->lockForUpdate()->first();
            abort_unless($record, 404);
            // Repeated requests preserve the original resolution time and administrator.
            if ($record->status === 'Diajukan') DB::table('keluhan_asrama')->where('id', $id)->update([
                'status' => 'Resolved', 'resolved_by' => $request->user()->id, 'resolved_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        $message = 'Keluhan ditandai Resolved.';
        return $request->expectsJson() ? response()->json(['message' => $message])
            : redirect('/kamar?section=keluhan')->with('success', $message);
    }
}
