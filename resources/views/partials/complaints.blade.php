@php($complaintAdmin = auth()->user()->role === 'admin')
<section class="card entry-form" aria-label="Keluhan asrama">
    <h2>Keluhan asrama</h2>
    <p>{{ $complaintAdmin ? 'Tinjau keluhan penghuni dan tandai Resolved setelah ditangani.' : 'Sampaikan keluhan terkait asrama, lalu pantau status penanganannya di bawah.' }}</p>
    @unless($complaintAdmin)
    <form method="POST" action="{{ url('/user/keluhan') }}">@csrf
        <label>Judul keluhan<input name="judul" maxlength="150" value="{{ old('judul') }}" required></label>
        <label>Isi keluhan<textarea name="isi" maxlength="2000" rows="4" required placeholder="Jelaskan keluhan dan lokasi yang perlu ditangani.">{{ old('isi') }}</textarea></label>
        <button class="btn">Kirim keluhan</button>
    </form>
    @endunless
    <div class="table-wrap"><table data-local-table="keluhan asrama"><thead><tr>@if($complaintAdmin)<th>Nama user</th>@endif<th>Judul</th><th>Isi keluhan</th><th>Waktu diajukan</th><th>Status</th><th>Waktu resolved</th>@if($complaintAdmin)<th data-no-sort>Aksi</th>@endif</tr></thead><tbody>
        @foreach($complaints as $complaint)
        <tr>@if($complaintAdmin)<td>{{ $complaint->user_name }}</td>@endif<td class="leave-reason">{{ $complaint->judul }}</td><td class="leave-reason">{{ $complaint->isi }}</td>
            <td data-sort-value="{{ $complaint->created_at }}">{{ \Carbon\Carbon::parse($complaint->created_at, 'UTC')->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
            <td><span class="badge {{ $complaint->status === 'Resolved' ? 'leave-status-disetujui' : 'leave-status-diajukan' }}">{{ $complaint->status }}</span></td>
            <td data-sort-value="{{ $complaint->resolved_at }}">{{ $complaint->resolved_at ? \Carbon\Carbon::parse($complaint->resolved_at, 'UTC')->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : 'Belum diselesaikan' }}</td>
            @if($complaintAdmin)<td>@if($complaint->status === 'Diajukan')<form method="POST" action="{{ url('/keluhan/'.$complaint->id) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Resolved"><button class="btn small">Tandai Resolved</button></form>@else<span>Sudah ditangani</span>@endif</td>@endif
        </tr>
        @endforeach
    </tbody></table></div>
</section>
