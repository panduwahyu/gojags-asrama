<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f97316">
    <script src="{{ asset('js/asrama-theme.js') }}"></script>
    <title>{{ $page === 'user' ? 'Beranda' : ucfirst(str_replace('user-', '', $page)) }} · Gojags Asrama</title>
    <link rel="icon" href="{{ asset('images/gojags.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/asrama.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asrama-dark.css') }}">
<link rel="stylesheet" href="{{ asset('css/asrama-tables.css') }}">
<script src="{{ asset('js/asrama-tables.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/asrama-feedback.css') }}">
    <script src="{{ asset('js/asrama-feedback.js') }}" defer></script>
</head>
<body>
@php
    $menus = ['beranda' => ['Beranda', 'home'], 'kamar' => ['Kamar', 'bed'], 'laundry' => ['Laundry', 'wash'], 'catering' => ['Catering', 'food']];
    if (auth()->user()->role === 'user') $menus = ['user' => ['Beranda', 'home'], 'user/kamar' => ['Kamar', 'bed'], 'user/laundry' => ['Laundry', 'wash'], 'user/catering' => ['Catering', 'food']];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" class="sprite" aria-hidden="true"><defs>
    <symbol id="home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></symbol>
    <symbol id="bed" viewBox="0 0 24 24"><path d="M3 18V6m18 12V9M3 15h18M3 9h7v6m0-6h8a3 3 0 0 1 3 3M3 18v3m18-3v3"/></symbol>
    <symbol id="wash" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="14" r="5"/><path d="M7 6h1m3 0h6m-9 8c3-3 5 3 8 0"/></symbol>
    <symbol id="food" viewBox="0 0 24 24"><path d="M4 3v6a3 3 0 0 0 6 0V3M7 3v18M20 21V3c-5 2-5 10 0 10"/></symbol>
    <symbol id="arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
    <symbol id="login" viewBox="0 0 24 24"><path d="M13 3h7v18h-7M3 12h12m-5-5 5 5-5 5"/></symbol>
    <symbol id="check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="search" viewBox="0 0 24 24"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></symbol>
</defs></svg>
<button class="mobile-menu" aria-label="Buka navigasi" aria-expanded="false" onclick="const s=document.querySelector('.sidebar');s.classList.toggle('open');this.setAttribute('aria-expanded',s.classList.contains('open'))">☰</button>
<aside class="sidebar">
    <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/gojags.png') }}" alt="Logo Gojags"><span>Asrama<small>GOJAGS · PUSDIKLAT BPS</small></span></a>
    <div class="nav-label">MENU UTAMA</div>
    <nav aria-label="Navigasi utama">
        @foreach($menus as $key => [$label, $icon])
            <a href="{{ url($key === 'beranda' ? '/' : '/'.$key) }}" class="nav-item {{ str_replace('-', '/', $page) === $key ? 'active' : '' }}" @if(str_replace('-', '/', $page) === $key) aria-current="page" @endif><svg><use href="#{{ $icon }}"/></svg>{{ $label }}<span class="nav-chevron">›</span></a>
        @endforeach
    </nav>
    <div class="sidebar-bottom"><form method="POST" action="{{ url('/logout') }}">@csrf<button class="btn full"><svg><use href="#login"/></svg>Logout</button></form></div>
</aside>
<div class="shell">
    <header class="topbar"><span>Gojags <b>Asrama</b></span><div class="topbar-actions"><span class="topbar-right"><i></i> Pusat Pendidikan dan Pelatihan BPS</span><button type="button" class="theme-toggle" data-theme-toggle aria-label="Aktifkan mode gelap" aria-pressed="false"><span class="theme-moon" aria-hidden="true">☾</span><span class="theme-sun" aria-hidden="true">☀</span><span class="theme-moon">Mode gelap</span><span class="theme-sun">Mode terang</span></button></div></header>
    <main>
        <div class="breadcrumb">Asrama <span>›</span> {{ $page === 'user' ? 'Beranda' : ucfirst(str_replace('user-', '', $page)) }}</div>
        @if($page === 'user')
            <section class="hero user-welcome"><div class="hero-copy"><span class="eyebrow">GOJAGS ASRAMA</span><h1>Selamat datang di<br>Gojags <em>Asrama.</em></h1><p class="welcome-name">{{ auth()->user()->name }}</p></div></section>
        @elseif($page === 'user-kamar')
            @include('user-room')
        @elseif($page === 'user-laundry')
            @include('laundry', ['laundryAdmin' => false])
        @elseif($page === 'user-catering')
            @include('catering', ['cateringAdmin' => false])
        @elseif($page === 'beranda')
            <section class="hero">
                <div class="hero-copy"><span class="eyebrow"><span></span> NYAMAN TINGGAL, FOKUS BELAJAR</span><h1>Selamat datang di<br>Gojags <em>Asrama.</em></h1><p>Kelola kebutuhan asrama dengan lebih mudah.<br>Mulai dari kamar, laundry, hingga catering dalam satu tempat.</p><a class="btn" href="{{ url('/kamar') }}">Jelajahi kamar <svg><use href="#arrow"/></svg></a><span class="hero-note">Hunian nyaman untuk perjalanan belajar Anda</span></div>
                <div class="building-art" aria-hidden="true"><div class="art-orbit"></div><div class="art-sun"></div><div class="building"><div class="building-sign">GOJAGS <b>ASRAMA</b></div><div class="windows">@for($i=0;$i<9;$i++)<span></span>@endfor</div><div class="door"></div></div><div class="tree tree-one"></div><div class="tree tree-two"></div><div class="art-ground"></div><div class="floating-label"><span>✓</span> Nyaman & terintegrasi</div></div>
            </section>
            <section class="stats" aria-label="Ringkasan data">
                @foreach(['kamar' => ['Total kamar', 'Unit kamar'], 'laundry' => ['Pesanan laundry', 'Pesanan tercatat'], 'catering' => ['Jadwal catering', 'Jadwal tercatat']] as $key => [$label, $caption])
                <a class="stat card" href="{{ url('/'.$key) }}"><span class="icon-box {{ $key }}"><svg><use href="#{{ $menus[$key][1] }}"/></svg></span><div><span>{{ $label }}</span><strong>{{ $counts[$key] }} <small>{{ $caption }}</small></strong></div><span class="stat-arrow">↗</span></a>
                @endforeach
            </section>
            <div class="section-title"><div><span class="overline">LAYANAN ASRAMA</span><h2>Semua kebutuhan, satu tempat</h2><p>Layanan yang mendukung kenyamanan Anda selama di asrama.</p></div><span class="section-tag">Mudah & praktis</span></div>
            <section class="services">
                @foreach(['kamar' => ['01', 'Kamar yang nyaman', 'Informasi kamar, ketersediaan, dan status hunian asrama.', 'Lihat kamar'], 'laundry' => ['02', 'Pakaian bersih, tanpa repot', 'Catat kebutuhan laundry dan pantau proses pencucian Anda.', 'Lihat laundry'], 'catering' => ['03', 'Hidangan untuk setiap hari', 'Temukan jadwal catering dan informasi menu harian asrama.', 'Lihat catering']] as $key => [$number, $title, $description, $link])
                <a class="service card" href="{{ url('/'.$key) }}"><div class="service-visual {{ $key }}"><svg><use href="#{{ $menus[$key][1] }}"/></svg><span>{{ $number }}</span><div class="visual-dot"></div></div><div class="service-body"><span class="overline">{{ strtoupper($key) }}</span><h3>{{ $title }}</h3><p>{{ $description }}</p><div class="service-link">{{ $link }}<svg><use href="#arrow"/></svg></div></div></a>
                @endforeach
            </section>
        @elseif($page === 'login')
            <section class="login-card card"><span class="icon-box kamar"><svg><use href="#login"/></svg></span><h1>Login ke Asrama</h1><p>Masuk dengan akun pengelola untuk mengelola layanan asrama.</p><form method="POST" action="{{ url('/login') }}">@csrf<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required placeholder="nama@bps.go.id"></label><label>Kata sandi<input type="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi"></label><button class="btn full">Login <svg><use href="#arrow"/></svg></button></form></section>
        @else
            <div class="section-title page-title"><div><span class="overline">LAYANAN ASRAMA</span><h1>{{ $page === 'user' ? 'Beranda' : ucfirst(str_replace('user-', '', $page)) }}</h1><p>{{ ['kamar' => 'Kelola informasi dan ketersediaan kamar asrama.', 'laundry' => 'Catat pesanan dan pantau status laundry.', 'catering' => 'Atur menu dan jadwal catering asrama.'][$page] }}</p></div><span class="section-tag">{{ $counts[$page] }} data tercatat</span></div>
            @if($page === 'kamar')
                @include('rooms')
            @elseif($page === 'laundry')
                @include('laundry', ['laundryAdmin' => true])
            @elseif($page === 'catering')
                @include('catering', ['cateringAdmin' => true])
            @else
            @auth
            <details class="card entry-form" @if($errors->any()) open @endif><summary>＋ Tambah {{ $page }}</summary><form method="POST" action="{{ url('/'.$page) }}">@csrf<div class="form-grid"><label>{{ $page === 'kamar' ? 'Nomor / nama kamar' : 'Nama pesanan' }}<input name="name" value="{{ old('name') }}" maxlength="100" required></label><label>{{ $page === 'catering' ? 'Menu / jumlah porsi' : 'Detail / keterangan' }}<input name="detail" value="{{ old('detail') }}" maxlength="255" required></label><label>Status<select name="status">@foreach($statuses as $status)<option @selected(old('status') === $status)>{{ $status }}</option>@endforeach</select></label><label>Tanggal<input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required></label></div><button class="btn">Simpan data</button></form></details>
            @else<div class="notice">Silakan <a href="{{ route('login') }}">login</a> untuk menambahkan data dan memperbarui status.</div>@endauth
            <section class="card data-card" data-async-table><form class="search-form" method="GET"><label class="search"><svg><use href="#search"/></svg><input name="q" value="{{ request('q') }}" placeholder="Cari {{ $page }}…" aria-label="Cari {{ $page }}"></label><button class="btn">Cari</button>@if(request('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => 1, 'per_page' => $records->perPage()]) }}">Reset</a>@endif</form><div class="table-wrap"><table><thead><tr>@include('partials.sortable-header', ['column' => 'name', 'label' => 'Nama'])@include('partials.sortable-header', ['column' => 'detail', 'label' => 'Keterangan'])@include('partials.sortable-header', ['column' => 'date', 'label' => 'Tanggal'])@include('partials.sortable-header', ['column' => 'status', 'label' => 'Status'])
                    @auth
                        @include('partials.sortable-header', ['column' => 'status', 'label' => 'Perbarui status'])
                    @endauth
                </tr></thead><tbody>@forelse($records as $record)<tr><td><strong>{{ $record->name }}</strong></td><td>{{ $record->detail }}</td><td>{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td><td><span class="badge">{{ $record->status }}</span></td>@auth<td><form class="status-form" method="POST" action="{{ url('/'.$page.'/'.$record->id) }}">@csrf @method('PATCH')<select name="status" aria-label="Status {{ $record->name }}">@foreach($statuses as $status)<option @selected($status === $record->status)>{{ $status }}</option>@endforeach</select><button class="btn small">Simpan</button></form></td>@endauth</tr>@empty<tr><td colspan="5"><div class="empty"><svg><use href="#{{ $menus[$page][1] }}"/></svg><h3>{{ request('q') ? 'Data tidak ditemukan' : 'Belum ada data '.$page }}</h3><p>{{ request('q') ? 'Coba gunakan kata pencarian lain.' : 'Data yang ditambahkan pengelola akan tampil di sini.' }}</p></div></td></tr>@endforelse</tbody></table></div>@include('partials.table-pagination')</section>
            @endif
        @endif
    </main>
    <footer><div class="footer-grid"><div><a class="brand footer-brand" href="{{ url('/') }}"><img src="{{ asset('images/gojags.png') }}" alt="Logo Gojags"><span>Gojags Asrama</span></a><p>Sistem pengelolaan asrama terintegrasi.<br>Mendukung kenyamanan tinggal dan belajar<br>di Pusat Pendidikan dan Pelatihan BPS.</p></div><div><h4>Jelajahi</h4>@foreach($menus as $key => [$label, $icon])<a href="{{ url($key === 'beranda' ? '/' : '/'.$key) }}">{{ $label }}</a>@endforeach</div><div><h4>Gojags Asrama</h4><p>Pusat Pendidikan dan Pelatihan<br>Badan Pusat Statistik</p></div></div><div class="footer-bottom"><span>© {{ now()->year }} Gojags Asrama. Pusdiklat BPS.</span><span>Dibuat untuk kenyamanan bersama.</span></div></footer>
</div>
@include('partials.feedback-modal')
</body>
</html>
