@include('partials.room-monitoring')
<section class="card entry-form">
    <h2>Alokasi kamar otomatis</h2>
    <p>Angkatan yang sama diprioritaskan pada satu lantai, mulai lantai 5 lalu turun ke 4 dan 3. Teman sekamar harus memiliki gender dan agama yang sama. Penghuni yang sudah dialokasikan tetap di kamarnya.</p>
    <p>Sinkronkan data dari api-gojags, lalu pilih batch peserta. Hanya peserta batch terpilih yang akan dialokasikan.</p>
    <form method="POST" action="{{ url('/kamar/sync') }}">@csrf<button class="btn">Sinkronisasi data peserta</button></form>
    <form method="POST" action="{{ url('/kamar/allocate') }}">@csrf
        <label>Batch peserta diklat<select name="batch_id" required @disabled($batches->isEmpty())><option value="">Pilih batch peserta</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((string) old('batch_id') === (string) $batch->id) @disabled(!$batch->participants_count)>{{ $batch->name }} ({{ $batch->participants_count }} peserta)</option>@endforeach</select></label>
        @if($batches->isEmpty())<p>Belum ada batch. Klik Sinkronisasi data peserta terlebih dahulu.</p>@endif
        <button class="btn" @disabled($batches->isEmpty())>Alokasikan kamar otomatis</button>
    </form>
</section>
<section class="card entry-form">
    <h2>QR check-in / check-out angkatan</h2>
    <p>QR berlaku 15 menit. QR baru menggantikan QR sebelumnya untuk batch dan aksi yang sama.</p>
    <form method="POST" action="{{ url('/kamar/qr') }}">@csrf
        <label>Batch QR<select name="batch_id" required><option value="">Pilih batch peserta</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @disabled(!$batch->participants_count)>{{ $batch->name }}</option>@endforeach</select></label>
        <label>Jenis QR<select name="action" required><option value="check-in">Check-in</option><option value="check-out">Check-out</option></select></label>
        <button class="btn" @disabled($batches->isEmpty())>Generate QR angkatan</button>
    </form>
</section>
<details class="card entry-form"><summary>Data peserta hasil sinkronisasi</summary>
    @foreach($batches as $batch)
        <h3>{{ $batch->name }} ({{ $batch->participants_count }} peserta)</h3>
        <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Angkatan</th><th>Gender</th><th>Agama</th><th>Status alokasi</th></tr></thead><tbody>
        @foreach($allocation_users->where('batch_id', $batch->id) as $resident)
            <tr><td>{{ $resident->name }}</td><td>{{ $resident->angkatan }}</td><td>{{ $resident->gender }}</td><td>{{ $resident->agama }}</td><td>{{ $resident->kamar_id ? 'Sudah dialokasikan' : 'Belum dialokasikan' }}</td></tr>
        @endforeach
        </tbody></table></div>
    @endforeach
</details>
<section class="card data-card" data-async-table data-room-live>
    <form class="search-form" method="GET"><label class="search"><input name="q" value="{{ request('q') }}" placeholder="Cari nomor kamar atau lantai" aria-label="Cari kamar"></label><button class="btn">Cari</button>@if(request('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => 1, 'per_page' => $records->perPage()]) }}">Reset</a>@endif</form>
    <div class="table-wrap"><table><thead><tr>@include('partials.sortable-header', ['column' => 'name', 'label' => 'Kamar'])@include('partials.sortable-header', ['column' => 'lantai', 'label' => 'Lantai'])@include('partials.sortable-header', ['column' => 'penghuni', 'label' => 'Penghuni'])@include('partials.sortable-header', ['column' => 'penghuni_count', 'label' => 'Kapasitas'])@include('partials.sortable-header', ['column' => 'status', 'label' => 'Status'])</tr></thead><tbody>
        @forelse($records as $room)<tr><td><strong>{{ $room->name }}</strong></td><td>{{ $room->lantai }}</td><td>@forelse($room->penghuni as $resident)<div class="resident-name">{{ $resident->name }}<small>{{ $resident->checked_in_at ? 'Check-in: '.\Carbon\Carbon::parse($resident->checked_in_at, config('app.timezone'))->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Belum check-in' }}</small></div>@empty<span>Belum ada penghuni</span>@endforelse</td><td>{{ $room->penghuni_count }} / 2</td><td><span class="badge room-status-{{ strtolower($room->status) }}">{{ $room->status }}</span></td></tr>
        @empty<tr><td colspan="5">Kamar tidak ditemukan.</td></tr>@endforelse
    </tbody></table></div>
    @include('partials.table-pagination')
</section>
