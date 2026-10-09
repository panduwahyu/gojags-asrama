<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CateringController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user()->role === 'admin';
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', Rule::in([10, 50, 100])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in(['id', 'menu', 'penyedia', 'waktu_catering', 'rating_average', 'rating_count', 'my_rating', 'name', 'detail', 'date', 'status'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);
        $ratings = DB::table('catering_ratings')->select('catering_id')
            ->selectRaw('AVG(rating) AS rating_average, COUNT(*) AS rating_count')->groupBy('catering_id');
        $query = DB::table('catering')->leftJoinSub($ratings, 'scores', 'scores.catering_id', '=', 'catering.id')
            ->select('catering.*', 'scores.rating_average')->selectRaw('COALESCE(scores.rating_count, 0) AS rating_count')
            ->selectSub(DB::table('catering_ratings')->select('rating')->whereColumn('catering_id', 'catering.id')->where('user_id', $request->user()->id), 'my_rating');
        if ($request->filled('q')) {
            $search = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('detail', 'like', $search)->orWhere('penyedia', 'like', $search)->orWhere('menu', 'like', $search));
        }
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy(in_array($sort, ['rating_average', 'rating_count', 'my_rating']) ? $sort : 'catering.'.$sort, $direction);
        if ($sort !== 'id') $query->orderBy('catering.id', $direction);
        $records = $query->paginate($request->integer('per_page', 10))->withQueryString();
        $records->through(function ($record) {
            $record->waktu_catering = $record->waktu_catering ? Carbon::parse($record->waktu_catering, 'UTC')->toISOString() : null;
            $record->rating_average = $record->rating_average === null ? null : round((float) $record->rating_average, 2);
            $record->rating_count = (int) $record->rating_count;
            $record->my_rating = $record->my_rating === null ? null : (int) $record->my_rating;
            return $record;
        });
        $data = ['page' => $admin ? 'catering' : 'user-catering', 'records' => $records,
            'counts' => collect(AsramaController::STATUSES)->map(fn ($s, $table) => DB::table($table)->count()),
            'statuses' => AsramaController::STATUSES['catering']];
        return $request->expectsJson() ? response()->json($data)->header('Cache-Control', 'no-store') : view('asrama', $data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'menu' => ['required', 'integer', 'min:1', 'max:10000'],
            'penyedia' => ['required', 'string', 'max:255'],
            'waktu_catering' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
        $local = Carbon::createFromFormat('!Y-m-d\TH:i', $data['waktu_catering'], 'Asia/Jakarta');
        $data['waktu_catering'] = $local->copy()->utc()->format('Y-m-d H:i:s');
        DB::table('catering')->insert($data + [
            'name' => 'Menu '.$data['menu'], 'detail' => $data['penyedia'], 'date' => $local->toDateString(),
            'status' => 'Terjadwal', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $request->expectsJson() ? response()->json(['message' => 'Menu catering berhasil ditambahkan.'], 201)
            : redirect('/catering')->with('success', 'Menu catering berhasil ditambahkan.');
    }

    public function rate(Request $request, int $id)
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5']]);
        DB::transaction(function () use ($request, $id, $data) {
            abort_unless(DB::table('catering')->where('id', $id)->lockForUpdate()->first(), 404);
            $query = DB::table('catering_ratings')->where('catering_id', $id)->where('user_id', $request->user()->id);
            if ($query->exists()) $query->update($data + ['updated_at' => now()]);
            else DB::table('catering_ratings')->insert($data + ['catering_id' => $id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
        return $request->expectsJson() ? response()->json(['message' => 'Rating catering berhasil disimpan.'])
            : redirect('/user/catering')->with('success', 'Rating catering berhasil disimpan.');
    }
}
