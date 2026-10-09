@include('partials.room-monitoring')
<section class="card entry-form">
    <h2>Alokasi kamar otomatis</h2>
    <p>Angkatan yang sama diprioritaskan pada satu lantai, mulai lantai 5 lalu turun ke 4 dan 3. Teman sekamar harus memiliki gender dan agama yang sama. Penghuni yang sudah dialokasikan tetap di kamarnya.</p>
    <p>Simpan profil user sebelum alokasi. User dengan profil belum lengkap atau tanpa kamar yang sesuai akan dilaporkan dan belum ditempatkan.</p>
    <form method="POST" action="{{ url('/kamar/allocate') }}">@csrf<button class="btn">Alokasikan kamar otomatis</button></form>
</section>
<details class="card entry-form">
    <summary>Profil user untuk alokasi ({{ count($allocation_users) }})</summary>
    @forelse($allocation_users as $resident)
    <form method="POST" action="{{ url('/allocation-users/'.$resident->id) }}">@csrf @method('PATCH')
        <h3>{{ $resident->name }} {{ $resident->kamar_id ? '· Sudah dialokasikan' : '' }}</h3>
        <fieldset @disabled($resident->kamar_id)><div class="form-grid">
            <label>Angkatan<input name="angkatan" value="{{ $resident->angkatan }}" required maxlength="100"></label>
            <label>Gender<select name="gender" required><option value="">Pilih gender</option>@foreach(['Laki-laki', 'Perempuan'] as $gender)<option @selected($resident->gender === $gender)>{{ $gender }}</option>@endforeach</select></label>
            <label>Agama<input name="agama" value="{{ $resident->agama }}" required maxlength="50"></label>
        </div>@unless($resident->kamar_id)<button class="btn">Simpan profil {{ $resident->name }}</button>@endunless</fieldset>
    </form>
    @empty<p>Belum ada akun user.</p>@endforelse
</details>
<section class="card data-card" data-async-table data-room-live>
    <form class="search-form" method="GET"><label class="search"><input name="q" value="{{ request('q') }}" placeholder="Cari nomor kamar atau lantai" aria-label="Cari kamar"></label><button class="btn">Cari</button>@if(request('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => 1, 'per_page' => $records->perPage()]) }}">Reset</a>@endif</form>
    <div class="table-wrap"><table><thead><tr>@include('partials.sortable-header', ['column' => 'name', 'label' => 'Kamar'])@include('partials.sortable-header', ['column' => 'lantai', 'label' => 'Lantai'])@include('partials.sortable-header', ['column' => 'penghuni', 'label' => 'Penghuni'])@include('partials.sortable-header', ['column' => 'penghuni_count', 'label' => 'Kapasitas'])@include('partials.sortable-header', ['column' => 'status', 'label' => 'Status'])</tr></thead><tbody>
        @forelse($records as $room)<tr><td><strong>{{ $room->name }}</strong></td><td>{{ $room->lantai }}</td><td>@forelse($room->penghuni as $resident)<div class="resident-name">{{ $resident->name }}<small>{{ $resident->checked_in_at ? 'Check-in: '.\Carbon\Carbon::parse($resident->checked_in_at, config('app.timezone'))->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Belum check-in' }}</small></div>@empty<span>Belum ada penghuni</span>@endforelse</td><td>{{ $room->penghuni_count }} / 2</td><td><span class="badge room-status-{{ strtolower($room->status) }}">{{ $room->status }}</span></td></tr>
        @empty<tr><td colspan="5">Kamar tidak ditemukan.</td></tr>@endforelse
    </tbody></table></div>
    @include('partials.table-pagination')
</section>
@include('partials.leave-permissions')
@include('partials.complaints')
