<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><title>QR {{ $action }} - Gojags Asrama</title><link rel="stylesheet" href="{{ asset('css/asrama.css') }}"></head>
<body><main style="max-width:600px;margin:2rem auto;padding:24px">
    <section class="card entry-form" style="text-align:center">
        <h1>QR {{ $action }}</h1><h2>{{ $batch_name }}</h2>
        <img id="room-qr" src="{{ $qr_image }}" alt="QR {{ $action }} {{ $batch_name }}" width="320" height="320" style="max-width:100%;background:white">
        <p id="qr-expiry" role="status">Berlaku sampai {{ \Carbon\Carbon::parse($expires_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB.</p>
        <p>Peserta login, buka Kamar &gt; Check-in/Check-out, lalu scan QR melalui kamera.</p>
        <a class="btn" href="{{ url('/kamar') }}">Kembali ke alokasi kamar</a>
    </section>
</main>
<script>
const deadline = {{ \Illuminate\Support\Js::from($expires_at) }};
function updateExpiry() {
    if (Date.now() >= Date.parse(deadline)) {
        document.getElementById('room-qr').hidden = true;
        document.getElementById('qr-expiry').textContent = 'QR kedaluwarsa. Kembali ke alokasi kamar untuk membuat QR baru.';
    }
}
updateExpiry(); setInterval(updateExpiry, 1000);
</script></body></html>
