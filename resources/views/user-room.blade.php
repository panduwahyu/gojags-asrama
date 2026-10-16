<h1>Check-in/Check-out</h1>
@if($room)
<section class="card entry-form">
    <h2>Kamar {{ $room['name'] }}</h2><p>Lantai {{ $room['lantai'] }}</p>
    <p>{{ $room['checked_in_at'] ? 'Sudah check-in' : 'Assigned · Menunggu check-in' }}</p>
    @if($room['checked_in_at'])<p>Waktu check-in: {{ \Carbon\Carbon::parse($room['checked_in_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</p>@endif
    <p>Check-out akan melepaskan alokasi kamar Anda dan menyimpan waktu keluar pada riwayat.</p>
    @if($batch)
    <p>{{ $batch->name }}</p>
    <div data-qr-scanner>
        <p>Arahkan kamera ke QR angkatan yang ditampilkan admin. Jenis QR menentukan check-in atau check-out.</p>
        <video hidden autoplay muted playsinline aria-label="Kamera pemindai QR" style="width:100%;max-width:360px;aspect-ratio:1;object-fit:cover;background:#000;border-radius:12px"></video>
        <p data-qr-status role="status" aria-live="polite"></p>
        <button type="button" class="btn" data-qr-start>Scan QR dengan kamera</button>
        <button type="button" class="btn" data-qr-stop hidden>Tutup kamera</button>
        <form method="POST" action="{{ url('/user/kamar/scan') }}">@csrf<input type="hidden" name="qr_token"></form>
    </div>
    @vite('resources/js/asrama-qr.js')
    @else
    <p>Akun Anda belum terhubung ke batch aktif. Hubungi admin untuk sinkronisasi peserta.</p>
    @endif
</section>
@else<section class="card entry-form"><h2>Belum ada alokasi kamar</h2><p>Hubungi admin untuk penempatan kamar.</p></section>@endif
<section class="card entry-form">
    <h2>Riwayat check-in dan check-out</h2>
    @forelse($history as $stay)
    <article><h3>Kamar {{ $stay['kamar'] }} · Lantai {{ $stay['lantai'] }}</h3><p>Check-in: {{ \Carbon\Carbon::parse($stay['checked_in_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</p><p>Check-out: {{ $stay['checked_out_at'] ? \Carbon\Carbon::parse($stay['checked_out_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Belum check-out' }}</p></article>
    @empty<p>Belum ada riwayat.</p>@endforelse
</section>
