<h1>Kamar saya</h1>
@if($room)
<section class="card entry-form">
    <h2>Kamar {{ $room['name'] }}</h2><p>Lantai {{ $room['lantai'] }}</p>
    <p>{{ $room['checked_in_at'] ? 'Sudah check-in' : 'Assigned · Menunggu check-in' }}</p>
    @if($room['checked_in_at'])<p>Waktu check-in: {{ \Carbon\Carbon::parse($room['checked_in_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</p>@endif
    <p>Check-out akan melepaskan alokasi kamar Anda dan menyimpan waktu keluar pada riwayat.</p>
    <div style="display:flex;gap:1rem"><form method="POST" action="{{ url('/user/kamar/check-in') }}">@csrf<button class="btn" @disabled($room['checked_in_at'])>Check-in</button></form><form method="POST" action="{{ url('/user/kamar/check-out') }}">@csrf<button class="btn" @disabled(!$room['checked_in_at'])>Check-out</button></form></div>
</section>
@else<section class="card entry-form"><h2>Belum ada alokasi kamar</h2><p>Hubungi admin untuk penempatan kamar.</p></section>@endif
<section class="card entry-form">
    <h2>Riwayat check-in dan check-out</h2>
    @forelse($history as $stay)
    <article><h3>Kamar {{ $stay['kamar'] }} · Lantai {{ $stay['lantai'] }}</h3><p>Check-in: {{ \Carbon\Carbon::parse($stay['checked_in_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</p><p>Check-out: {{ $stay['checked_out_at'] ? \Carbon\Carbon::parse($stay['checked_out_at'])->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : 'Belum check-out' }}</p></article>
    @empty<p>Belum ada riwayat.</p>@endforelse
</section>
@include('partials.leave-permissions')
@include('partials.complaints')
