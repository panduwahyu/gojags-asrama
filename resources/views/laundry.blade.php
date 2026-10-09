@unless($laundryAdmin)
<h1>Laundry</h1>
<section class="card entry-form"><h2>Ajukan laundry</h2><p>Tuliskan pakaian atau barang yang akan dilaundry dan total jumlah stel.</p>
    <form method="POST" action="{{ url('/user/laundry') }}">@csrf
        <label>Pakaian / barang yang dilaundry<textarea name="detail" required maxlength="255" rows="3" placeholder="Contoh: kemeja, celana, dan seragam">{{ old('detail') }}</textarea></label>
        <label>Jumlah stel<input name="jumlah_stel" type="number" min="1" max="1000" step="1" value="{{ old('jumlah_stel') }}" required></label>
        <button class="btn">Ajukan laundry</button>
    </form>
</section>
@endunless
<section class="card data-card" data-async-table aria-label="Pengajuan laundry">
    <h2>{{ $laundryAdmin ? 'Pengajuan laundry user' : 'Riwayat pengajuan laundry' }}</h2>
    <form class="search-form" method="GET"><label class="search"><input name="q" value="{{ request('q') }}" placeholder="Cari laundry" aria-label="Cari laundry"></label><button class="btn">Cari</button>@if(request('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => 1, 'per_page' => $records->perPage()]) }}">Reset</a>@endif</form>
    <div class="table-wrap"><table><thead><tr>
        @if($laundryAdmin)
            @include('partials.sortable-header', ['column' => 'name', 'label' => 'Nama user'])
        @endif
        @include('partials.sortable-header', ['column' => 'detail', 'label' => 'Pakaian / barang'])
        @include('partials.sortable-header', ['column' => 'jumlah_stel', 'label' => 'Jumlah stel'])
        @include('partials.sortable-header', ['column' => 'date', 'label' => 'Tanggal'])
        @include('partials.sortable-header', ['column' => 'status', 'label' => 'Status'])
        @include('partials.sortable-header', ['column' => 'biaya', 'label' => 'Biaya laundry'])
        @if($laundryAdmin)<th>Perbarui status</th>@endif
    </tr></thead><tbody>
        @forelse($records as $record)
        <tr>
            @if($laundryAdmin)<td>{{ $record->name }}</td>@endif
            <td class="leave-reason">{{ $record->detail }}</td><td>{{ $record->jumlah_stel ?? '-' }}</td><td>{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td>
            <td><span class="badge {{ $record->status === 'Selesai' ? 'leave-status-disetujui' : 'leave-status-diajukan' }}">{{ $record->status }}</span></td>
            <td>{{ $record->biaya === null ? 'Belum ditentukan' : 'Rp '.number_format($record->biaya, 2, ',', '.') }}</td>
            @if($laundryAdmin)
            <td><form method="POST" action="{{ url('/laundry/'.$record->id) }}">@csrf @method('PATCH')
                <label>Status<select name="status" aria-label="Status {{ $record->name }}"><option @selected($record->status !== 'Selesai')>Diajukan</option><option @selected($record->status === 'Selesai')>Selesai</option></select></label>
                <label>Biaya laundry (Rp)<input type="number" name="biaya" min="0" max="9999999999.99" step="0.01" value="{{ $record->biaya }}" required></label>
                <button class="btn small">Simpan</button>
            </form></td>
            @endif
        </tr>
        @empty<tr><td colspan="{{ $laundryAdmin ? 7 : 5 }}">Belum ada pengajuan laundry.</td></tr>@endforelse
    </tbody></table></div>
    @include('partials.table-pagination')
</section>
