<?php

namespace App\Http\Controllers;

use App\Models\LeavePermission;
use App\Services\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AsramaController extends Controller
{
    public const STATUSES = [
        'kamar' => ['Kosong', 'Assigned', 'Terisi', 'Penuh'],
        'laundry' => ['Diajukan', 'Selesai'],
        'catering' => ['Terjadwal', 'Disiapkan', 'Selesai'],
    ];

    public function index(Request $request, string $page = 'beranda')
    {
        abort_unless(in_array($page, ['beranda', 'kamar', 'laundry', 'catering', 'login']), 404);
        if ($page === 'login') {
            return $request->user()
                ? redirect('/'.$request->user()->role)
                : view('auth.login');
        }
        $counts = collect(self::STATUSES)->map(fn ($statuses, $category) => DB::table($category)->count());
        if ($page === 'beranda') {
            $data = ['page' => $page, 'counts' => $counts];
            return $request->expectsJson() ? response()->json($data) : view('asrama', $data);
        }
        $query = DB::table($page);
        if ($page === 'kamar') {
            $countSql = RoomStatus::assignedSql();
            $query->select('kamar.*')->selectRaw($countSql.' AS penghuni_count')
                ->selectRaw(RoomStatus::checkedInSql().' AS checked_in_count')->selectRaw(RoomStatus::statusSql().' AS status');
        }
        if ($request->filled('q')) {
            $search = mb_substr($request->string('q')->toString(), 0, 100);
            $query->where(function ($q) use ($page, $search) {
                $q->where('name', 'like', "%{$search}%");
                if ($page !== 'kamar') $q->orWhere('detail', 'like', "%{$search}%");
                else $q->orWhere('lantai', 'like', "%{$search}%");
            });
        }
        $columns = $page === 'kamar' ? ['id', 'name', 'lantai', 'penghuni', 'penghuni_count', 'status'] : ['id', 'name', 'detail', 'date', 'status'];
        if ($page === 'laundry') $columns = array_merge($columns, ['jumlah_stel', 'biaya']);
        $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([10, 50, 100])],
            'sort' => ['sometimes', Rule::in($columns)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        if ($page === 'kamar' && $sort === 'penghuni') {
            $query->orderByRaw('(SELECT MIN(users.name) FROM kamar_slots JOIN users ON users.id = kamar_slots.user_id WHERE kamar_slots.kamar_id = kamar.id) '.$direction);
        } elseif ($page === 'kamar' && $sort === 'status') {
            $query->orderByRaw("CASE (".RoomStatus::statusSql().") WHEN 'Kosong' THEN 0 WHEN 'Assigned' THEN 1 WHEN 'Terisi' THEN 2 ELSE 3 END ".$direction);
        } elseif ($page === 'kamar' && $sort === 'penghuni_count') {
            $query->orderByRaw($countSql.' '.$direction);
        } else {
            $query->orderBy($sort, $direction);
        }
        if ($sort !== 'id') $query->orderBy('id', $direction);
        $data = ['page' => $page, 'records' => $query->paginate($request->integer('per_page', 10))->withQueryString(), 'counts' => $counts, 'statuses' => self::STATUSES[$page] ?? []];
        if ($page === 'kamar') {
            $data['monitoring'] = RoomStatus::monitoring();
            if (!$request->expectsJson()) $data['leave_permissions'] = LeavePermission::latestFor($request->user());
            if (!$request->expectsJson()) $data['complaints'] = ComplaintController::query($request)->orderByDesc('keluhan_asrama.id')->get();
            $people = DB::table('kamar_slots')->join('users', 'users.id', '=', 'kamar_slots.user_id')
                ->whereIn('kamar_id', $data['records']->pluck('id'))->select('kamar_id', 'users.id', 'users.name', 'kamar_slots.checked_in_at')->orderBy('users.name')->orderBy('users.id')->get()->groupBy('kamar_id');
            foreach ($data['records'] as $room) $room->penghuni = $people->get($room->id, collect())->values();
            $data['allocation_users'] = DB::table('users')->leftJoin('kamar_slots', 'users.id', '=', 'kamar_slots.user_id')
                ->where('role', 'user')->orderBy('users.id')
                ->get(['users.id', 'users.name', 'angkatan', 'gender', 'agama', 'kamar_slots.kamar_id']);
        }
        return $request->expectsJson() ? response()->json($data) : view('asrama', $data);
    }

    public function welcome(Request $request)
    {
        return $request->expectsJson()
            ? response()->json(['user' => $request->user()->only('id', 'name', 'email', 'role')])
            : view('asrama', ['page' => 'user']);
    }

    public function store(Request $request, string $category)
    {
        abort_unless(in_array($category, ['laundry', 'catering']), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'detail' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES[$category])],
            'date' => ['required', 'date'],
        ]);
        DB::table($category)->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data berhasil ditambahkan.'], 201);
        }
        return redirect('/'.$category)->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, string $category, int $id)
    {
        abort_unless(in_array($category, ['laundry', 'catering']), 404);
        $record = DB::table($category)->find($id);
        abort_unless($record, 404);
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES[$category])]]);
        if ($category === 'laundry') {
            $data += $request->validate(['biaya' => [Rule::requiredIf($data['status'] === 'Selesai'), 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2']]);
        }
        DB::table($category)->where('id', $id)->update($data + ['updated_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Status berhasil diperbarui.']);
        }
        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials)) {
            if ($request->expectsJson()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
            }
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        if ($request->expectsJson()) {
            return response()->json(['user' => $request->user()->only('id', 'name', 'email', 'role')]);
        }
        $request->session()->forget('url.intended');
        return redirect('/'.$request->user()->role);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Berhasil keluar.']);
        }
        return redirect('/login');
    }
}
