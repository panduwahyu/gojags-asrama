<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeavePermissionController extends Controller
{
    public static function query(Request $request)
    {
        $query = DB::table('izin_keluar')->join('users', 'users.id', '=', 'izin_keluar.user_id')
            ->select('izin_keluar.*', 'users.name as user_name', 'users.email as user_email');
        if ($request->user()->role === 'user') $query->where('izin_keluar.user_id', $request->user()->id);
        return $query;
    }

    public function index(Request $request)
    {
        $columns = ['id', 'user_name', 'waktu_keluar', 'waktu_kembali', 'alasan', 'status'];
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', Rule::in([10, 50, 100])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in($columns)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);
        $query = self::query($request);
        if ($request->filled('q')) {
            $search = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('users.name', 'like', $search)
                ->orWhere('alasan', 'like', $search)->orWhere('status', 'like', $search));
        }
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort === 'user_name' ? 'users.name' : 'izin_keluar.'.$sort, $direction);
        if ($sort !== 'id') $query->orderBy('izin_keluar.id', $direction);
        $records = $query->paginate($request->integer('per_page', 10));
        $records->through(function ($row) {
            foreach (['waktu_keluar', 'waktu_kembali'] as $field) {
                $row->$field = Carbon::parse($row->$field, 'UTC')->toISOString();
            }
            return $row;
        });
        return response()->json(['records' => $records])->header('Cache-Control', 'no-store');
    }

    private function form(Request $request): array
    {
        $data = $request->validate([
            'waktu_keluar' => ['required', 'date_format:Y-m-d\TH:i'],
            'waktu_kembali' => ['required', 'date_format:Y-m-d\TH:i', 'after:waktu_keluar'],
            'alasan' => ['required', 'string', 'max:2000'],
        ], ['waktu_kembali.after' => 'Waktu kembali harus setelah waktu keluar.']);
        foreach (['waktu_keluar', 'waktu_kembali'] as $field) {
            $data[$field] = Carbon::createFromFormat('!Y-m-d\TH:i', $data[$field], 'Asia/Jakarta')->utc()->format('Y-m-d H:i:s');
        }
        return $data;
    }

    private function response(Request $request, string $message, int $status = 200)
    {
        return $request->expectsJson() ? response()->json(['message' => $message], $status)
            : redirect($request->user()->role === 'admin' ? '/kamar?section=perizinan' : '/user/kamar?section=perizinan')->with('success', $message);
    }

    public function store(Request $request)
    {
        $data = $this->form($request);
        DB::table('izin_keluar')->insert($data + [
            'user_id' => $request->user()->id, 'status' => 'Diajukan', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->response($request, 'Izin keluar berhasil diajukan.', 201);
    }

    public function update(Request $request, int $id)
    {
        $data = $this->form($request);
        DB::transaction(function () use ($request, $id, $data) {
            $row = DB::table('izin_keluar')->where('id', $id)->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($row->status !== 'Diajukan') throw ValidationException::withMessages(['status' => 'Hanya izin berstatus Diajukan yang dapat diedit.']);
            DB::table('izin_keluar')->where('id', $id)->update($data + ['updated_at' => now()]);
        }, 3);
        return $this->response($request, 'Pengajuan izin berhasil diperbarui.');
    }

    public function review(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Disetujui', 'Ditolak'])]]);
        DB::transaction(function () use ($request, $id, $data) {
            $row = DB::table('izin_keluar')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($row->status !== 'Diajukan') throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
            DB::table('izin_keluar')->where('id', $id)->update($data + [
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        return $this->response($request, 'Izin keluar '.strtolower($data['status']).'.');
    }
}
