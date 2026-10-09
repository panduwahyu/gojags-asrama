<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Gojags Asrama</title>
    <script src="{{ asset('js/asrama-theme.js') }}"></script>
    <link rel="icon" href="{{ asset('images/gojags.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/asrama.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asrama-dark.css') }}">
</head>
<body>
    <div class="login-theme"><button type="button" class="theme-toggle" data-theme-toggle aria-label="Aktifkan mode malam" aria-pressed="false"><span class="theme-moon">☾ Mode malam</span><span class="theme-sun">☀ Mode siang</span></button></div>
    <main class="login-landing">
        <section class="login-card card">
            <a class="brand" href="{{ url('/login') }}"><img src="{{ asset('images/gojags.png') }}" alt="Logo Gojags"><span>Gojags Asrama</span></a>
            <h1>Selamat datang kembali</h1><p>Masuk ke akun Anda untuk melanjutkan.</p>
            @if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <form method="POST" action="{{ url('/login') }}">@csrf
                <label>Email<input name="email" type="email" value="{{ old('email') }}" autocomplete="username" required placeholder="nama@example.id"></label>
                <label for="login-password">Kata sandi</label>
                <div class="password-field">
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi">
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="login-password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path data-password-slash d="m3 3 18 18" hidden/></svg>
                    </button>
                </div>
                <button class="btn full" type="submit">Login</button>
            </form>
        </section>
    </main>
    <script>
        const passwordInput = document.getElementById('login-password');
        const passwordToggle = document.querySelector('.password-toggle');
        passwordToggle.addEventListener('click', () => {
            const visible = passwordInput.type === 'password';
            passwordInput.type = visible ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            passwordToggle.setAttribute('aria-pressed', String(visible));
            passwordToggle.querySelector('[data-password-slash]').toggleAttribute('hidden', !visible);
        });
    </script>
</body>
</html>
