<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserLaundryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([10, 50, 100])],
            'sort' => ['sometimes', Rule::in(['id', 'detail', 'jumlah_stel', 'date', 'status', 'biaya'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = DB::table('laundry')->where('user_id', $request->user()->id);
        if ($request->filled('q')) {
            $search = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('detail', 'like', $search)->orWhere('status', 'like', $search));
        }
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);
        if ($sort !== 'id') $query->orderBy('id', $direction);
        $data = ['page' => 'user-laundry', 'records' => $query->paginate($request->integer('per_page', 10))->withQueryString()];
        return $request->expectsJson() ? response()->json($data)->header('Cache-Control', 'no-store') : view('asrama', $data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'detail' => ['required', 'string', 'max:255'],
            'jumlah_stel' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        DB::table('laundry')->insert($data + [
            'user_id' => $request->user()->id, 'name' => $request->user()->name,
            'status' => 'Diajukan', 'biaya' => null, 'date' => now('Asia/Jakarta')->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $message = 'Pengajuan laundry berhasil dikirim.';
        return $request->expectsJson() ? response()->json(['message' => $message], 201)
            : redirect('/user/laundry')->with('success', $message);
    }
}
