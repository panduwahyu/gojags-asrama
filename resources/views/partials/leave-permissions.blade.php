@php($isLeaveAdmin = auth()->user()->role === 'admin')
<section class="card entry-form" aria-label="Izin keluar">
    <h2>Izin keluar</h2>
    <p>{{ $isLeaveAdmin ? 'Tinjau pengajuan izin keluar penghuni.' : 'Isi waktu keluar, waktu kembali, dan alasan. Pengajuan dapat diedit selama berstatus Diajukan.' }} Semua waktu menggunakan WIB.</p>
    @unless($isLeaveAdmin)
    <form method="POST" action="{{ url('/user/izin-keluar') }}">@csrf
        <h3>Ajukan izin keluar</h3>
        <div class="form-grid">
            <label>Waktu keluar (WIB)<input type="datetime-local" name="waktu_keluar" value="{{ old('waktu_keluar') }}" required></label>
            <label>Waktu kembali (WIB)<input type="datetime-local" name="waktu_kembali" value="{{ old('waktu_kembali') }}" required></label>
        </div>
        <label>Alasan<textarea name="alasan" required maxlength="2000" rows="3">{{ old('alasan') }}</textarea></label>
        <button class="btn">Ajukan izin</button>
    </form>
    @endunless
    @if($leave_permissions->isEmpty())<p>Belum ada pengajuan izin keluar.</p>@endif
    <div class="table-wrap"><table data-local-table="izin keluar"><thead><tr>@if($isLeaveAdmin)<th>Nama user</th>@endif<th>Waktu keluar</th><th>Waktu kembali</th><th>Alasan</th><th>Status</th><th data-no-sort>Aksi</th></tr></thead><tbody>
        @foreach($leave_permissions as $permission)
        <tr>
            @if($isLeaveAdmin)<td>{{ $permission->user_name }}</td>@endif
            <td data-sort-value="{{ $permission->waktu_keluar }}">{{ \Carbon\Carbon::parse($permission->waktu_keluar, 'UTC')->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
            <td data-sort-value="{{ $permission->waktu_kembali }}">{{ \Carbon\Carbon::parse($permission->waktu_kembali, 'UTC')->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
            <td class="leave-reason">{{ $permission->alasan }}</td>
            <td><span class="badge leave-status-{{ strtolower($permission->status) }}">{{ $permission->status }}</span></td>
            <td>
                @if($permission->status === 'Diajukan')
                    @if($isLeaveAdmin)
                    <form method="POST" action="{{ url('/izin-keluar/'.$permission->id) }}" class="leave-actions">@csrf @method('PATCH')
                        <button class="btn small" name="status" value="Disetujui">Setujui</button><button class="btn small" name="status" value="Ditolak">Tolak</button>
                    </form>
                    @else
                    <details><summary>Edit</summary><form method="POST" action="{{ url('/user/izin-keluar/'.$permission->id) }}">@csrf @method('PATCH')
                        <label>Waktu keluar (WIB)<input type="datetime-local" name="waktu_keluar" value="{{ \Carbon\Carbon::parse($permission->waktu_keluar, 'UTC')->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required></label>
                        <label>Waktu kembali (WIB)<input type="datetime-local" name="waktu_kembali" value="{{ \Carbon\Carbon::parse($permission->waktu_kembali, 'UTC')->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required></label>
                        <label>Alasan<textarea name="alasan" required maxlength="2000" rows="3">{{ $permission->alasan }}</textarea></label>
                        <button class="btn small">Simpan perubahan izin</button>
                    </form></details>
                    @endif
                @else<span>Sudah diproses</span>@endif
            </td>
        </tr>
        @endforeach
    </tbody></table></div>
</section>
