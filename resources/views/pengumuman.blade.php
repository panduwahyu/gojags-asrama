<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>GOJAGS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#7C3AED',   // ungu
                        secondary: '#4F46E5', // biru
                        darkbg: '#0F172A',
                        card: '#1E293B',
                        sidebar: '#020617'
                    }
                }
            }
        }
    </script>
<link rel="stylesheet" href="{{ asset('css/asrama-tables.css') }}">
<script src="{{ asset('js/asrama-tables.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/asrama-feedback.css') }}">
    <script src="{{ asset('js/asrama-feedback.js') }}" defer></script>
</head>

<body class="bg-darkbg text-gray-100 min-h-screen flex">

<!-- Sidebar -->
<aside id="sidebar"
  class="bg-sidebar w-64 fixed inset-y-0 left-0 transform 
         -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">

  <div class="p-6 text-xl font-bold text-white flex items-center gap-2">
    <img src="{{ asset('images/favicon.png') }}" alt="Logo" class="w-10 h-10">
    <span>GOJAGS</span>
</div>


  <!-- MENU VERTIKAL -->
  <nav class="flex flex-col px-3 mt-4">

    <button onclick="setActive('pengumuman', this)"
        class="sidebar-item active">
        Pengumuman
    </button>

    <div class="sidebar-divider"></div>

    <button onclick="setActive('checkin', this)"
        class="sidebar-item">
        Check In
    </button>

    <div class="sidebar-divider"></div>

    <button onclick="setActive('kamar', this)"
        class="sidebar-item">
        Laundry
    </button>

    <div class="sidebar-divider"></div>

    <button onclick="setActive('inventaris', this)"
        class="sidebar-item">
        Katering
    </button>

    <div class="sidebar-divider"></div>

    <button onclick="setActive('keluhan', this)"
        class="sidebar-item">
        Keluhan
    </button>

</nav>
</aside>


<!-- Overlay mobile -->
<div id="overlay" class="fixed inset-0 bg-black/50 hidden md:hidden"
     onclick="toggleSidebar()"></div>

<!-- Main Content -->
<div class="flex-1 md:ml-64 p-6">
    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-6">
        <button class="md:hidden text-white" onclick="toggleSidebar()">☰</button>
        <h1 id="pageTitle" class="text-2xl font-semibold">Pengumuman</h1>
    </div>

    <!-- Content -->
    <div id="contentArea">
        <!-- Pengumuman Content -->
        <div id="pengumuman" class="space-y-4">
            <div class="bg-card rounded-xl p-5 shadow">
                <h2 class="text-lg font-semibold text-primary">Pengumuman 1</h2>
                <p class="text-sm text-gray-400 mb-2">1/1/2026</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
            </div>

            <div class="bg-card rounded-xl p-5 shadow">
                <h2 class="text-lg font-semibold text-primary">Pengumuman 2</h2>
                <p class="text-sm text-gray-400 mb-2">2/1/2026</p>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut enim ad minim veniam.</p>
            </div>
        </div>

        <!-- Empty Pages -->
        <div id="checkin" class="hidden">
          <div class="bg-card rounded-xl p-6 shadow max-w-xl">

        <h2 class="text-xl font-semibold text-primary mb-2">
            Kamar 123
        </h2>

        <p class="text-gray-400 mb-5">
            Anda melakukan check-in pada 1/1/2026
        </p>

        <div class="flex gap-3">
            <button
                class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg font-semibold transition">
                Check-In
            </button>

            <button onclick="showCheckoutAlert()"
                class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg font-semibold transition">
                Check-Out
            </button>
        </div>

    </div>
        </div>
        <div id="kamar" class="hidden">
          <div class="bg-card rounded-xl p-6 shadow">

        <h2 class="text-xl font-semibold text-primary mb-5">
            Detail Tagihan Laundry
        </h2>

        <!-- TABLE -->
        <div class="overflow-x-auto">
            <table data-local-table="data pengumuman" class="w-full text-sm text-left">

                <thead class="text-gray-300 border-b border-gray-700">
                  <tr>
                      <th class="py-3">Tanggal Laundry</th>
                      <th class="py-3">Berat Laundry</th>
                      <th class="py-3">Besar Tagihan</th>
                      <th class="py-3">Status</th>
                  </tr>
                </thead>

                <tbody>

                  <tr class="border-b border-gray-800">
                  <td class="py-3">01/01/2026</td>
                  <td>2 Kg</td>
                  <td>Rp 14.000</td>
                  <td><span class="status unpaid">Belum Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>02/01/2026</td>
                  <td>3 Kg</td>
                  <td>Rp 21.000</td>
                  <td><span class="status pending">Pending</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>03/01/2026</td>
                  <td>1.5 Kg</td>
                  <td>Rp 10.500</td>
                  <td><span class="status paid">Sudah Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>04/01/2026</td>
                  <td>4 Kg</td>
                  <td>Rp 28.000</td>
                  <td><span class="status unpaid">Belum Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>05/01/2026</td>
                  <td>2.5 Kg</td>
                  <td>Rp 17.500</td>
                  <td><span class="status paid">Sudah Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>06/01/2026</td>
                  <td>3 Kg</td>
                  <td>Rp 21.000</td>
                  <td><span class="status pending">Pending</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>07/01/2026</td>
                  <td>2 Kg</td>
                  <td>Rp 14.000</td>
                  <td><span class="status paid">Sudah Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>08/01/2026</td>
                  <td>5 Kg</td>
                  <td>Rp 35.000</td>
                  <td><span class="status unpaid">Belum Dibayar</span></td>
                  </tr>

                  <tr class="border-b border-gray-800">
                  <td>09/01/2026</td>
                  <td>1 Kg</td>
                  <td>Rp 7.000</td>
                  <td><span class="status pending">Pending</span></td>
                  </tr>

                  <tr>
                  <td>10/01/2026</td>
                  <td>2 Kg</td>
                  <td>Rp 14.000</td>
                  <td><span class="status paid">Sudah Dibayar</span></td>
                  </tr>

                  </tbody>
            </table>
        </div>


    </div>
        </div>
        <div id="keluhan" class="hidden">
          <div class="bg-card rounded-xl p-6 shadow">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-5">
        <h2 class="text-xl font-semibold text-primary">
            Sistem Ticketing Keluhan
        </h2>

        <button onclick="openKeluhanForm()"
            class="bg-primary hover:bg-indigo-600 px-4 py-2 rounded-lg font-semibold">
            + Tambahkan Keluhan
        </button>
    </div>

    <!-- SORTING -->
    <div class="mb-4">
        <select onchange="sortKeluhan(this.value)"
            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm">

            <option value="latest">Terbaru</option>
            <option value="oldest">Terlama</option>
            <option value="status">Status</option>

        </select>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto">

        <table data-local-table="data pengumuman" class="w-full text-sm">

            <thead class="border-b border-gray-700 text-gray-300">
                <tr>
                    <th class="py-3 text-left">Judul Keluhan</th>
                    <th class="text-left">Tanggal</th>
                    <th class="text-left">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>

            <tbody id="keluhanTable">

                <tr class="border-b border-gray-800">
                    <td class="py-3">AC kamar tidak dingin</td>
                    <td>01/01/2026</td>
                    <td><span class="ticket open">Open</span></td>
                    <td class="text-right space-x-2">
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Hapus</button>
                    </td>
                </tr>

                <tr class="border-b border-gray-800">
                    <td>Air kamar mandi mati</td>
                    <td>02/01/2026</td>
                    <td><span class="ticket progress">Diproses</span></td>
                    <td class="text-right space-x-2">
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Hapus</button>
                    </td>
                </tr>

                <tr>
                    <td class="py-3">Lampu koridor rusak</td>
                    <td>03/01/2026</td>
                    <td><span class="ticket closed">Selesai</span></td>
                    <td class="text-right space-x-2">
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Hapus</button>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</div>
        </div>
        <div id="inventaris" class="hidden"></div>
    </div>
    <!-- ALERT CHECKOUT -->
<div id="checkoutAlert"
     class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50">

    <div class="bg-card rounded-xl p-6 w-[400px] shadow-lg">

        <h3 class="text-lg font-semibold text-red-400 mb-3">
            Konfirmasi Check-Out
        </h3>

        <p class="text-gray-300 mb-6">
            Anda yakin ingin melakukan check-out?
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="closeCheckoutAlert()"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">
                Batalkan
            </button>

            <button onclick="confirmCheckout()"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">
                Ya
            </button>
        </div>

    </div>
</div>
</div>

<!-- Styles -->
<style>
  .sidebar-item {
    @apply w-full text-left
           pl-6 pr-4 py-3
           text-gray-400 text-sm
           rounded-md
           transition
           hover:bg-primary/10
           hover:text-white
           relative;
}

/* Menu aktif */
.sidebar-item.active {
    @apply bg-primary/20 text-white font-semibold;
}

/* Garis indikator kiri */
.sidebar-item.active::before {
    content: "";
    @apply absolute left-0 top-1/2 -translate-y-1/2
           h-6 w-1 bg-primary rounded-r;
}

/* Divider antar menu */
.sidebar-divider {
    @apply h-px bg-gray-700/40 my-1 mx-3;
}

/* STATUS TICKET */
.ticket{
    @apply px-3 py-1 rounded-full text-xs font-semibold;
}

.ticket.open{
    @apply bg-red-500/20 text-red-400;
}

.ticket.progress{
    @apply bg-yellow-500/20 text-yellow-400;
}

.ticket.closed{
    @apply bg-green-500/20 text-green-400;
}

/* BUTTON */
.edit-btn{
    @apply bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs;
}

.delete-btn{
    @apply bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-xs;
}

</style>



<!-- Script -->
<script>
  function setActive(menu, el) {
    document.querySelectorAll('.sidebar-item')
      .forEach(item => item.classList.remove('active'));

    el.classList.add('active');

    document.querySelectorAll('#contentArea > div')
      .forEach(div => div.classList.add('hidden'));

    document.getElementById(menu).classList.remove('hidden');
    document.getElementById('pageTitle').innerText = el.innerText;

    if (window.innerWidth < 768) toggleSidebar();
  }
  function showCheckoutAlert() {
    document.getElementById('checkoutAlert').classList.remove('hidden');
}

function closeCheckoutAlert() {
    document.getElementById('checkoutAlert').classList.add('hidden');
}

function confirmCheckout() {
    window.AsramaFeedback.show("Check-out berhasil!");
    closeCheckoutAlert();
}

function openKeluhanForm(){
    document.getElementById('keluhanModal').classList.remove('hidden');
}

function closeKeluhanForm(){
    document.getElementById('keluhanModal').classList.add('hidden');
}

/* SORTING FRONTEND */
function sortKeluhan(type) {
    document.getElementById('keluhanTable').closest('table').dispatchEvent(new CustomEvent('table-sort', {
        detail: { index: type === 'status' ? 2 : 1, direction: type === 'latest' ? 'desc' : 'asc' }
    }));
}

</script>



@include('partials.feedback-modal')
</body>
<div id="keluhanModal"
class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50">

<div class="bg-card p-6 rounded-xl w-[420px]">

<h3 class="text-lg font-semibold mb-4">Tambah Keluhan</h3>

<input type="text"
placeholder="Judul Keluhan"
class="w-full bg-gray-800 p-2 rounded mb-3">

<textarea
placeholder="Deskripsi Keluhan"
class="w-full bg-gray-800 p-2 rounded mb-4"></textarea>

<div class="flex justify-end gap-3">
<button onclick="closeKeluhanForm()" class="bg-gray-600 px-4 py-2 rounded">
Batal
</button>

<button class="bg-primary px-4 py-2 rounded">
Kirim
</button>
</div>

</div>
</div>
</html>
