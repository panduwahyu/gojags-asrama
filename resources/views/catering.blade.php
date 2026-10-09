@unless($cateringAdmin)<h1>Catering</h1>@endunless
@if($cateringAdmin)
<section class="card entry-form"><h2>Tambah menu catering</h2>
    <form method="POST" action="{{ url('/catering') }}">@csrf
        <div class="form-grid">
            <label>Nomor menu<input name="menu" type="number" min="1" max="10000" step="1" value="{{ old('menu') }}" required></label>
            <label>Penyedia<input name="penyedia" maxlength="255" value="{{ old('penyedia') }}" required></label>
            <label>Waktu catering (WIB)<input name="waktu_catering" type="datetime-local" value="{{ old('waktu_catering') }}" required></label>
        </div><button class="btn">Tambah menu</button>
    </form>
</section>
@endif
<section class="card data-card" data-async-table aria-label="Menu catering">
    <h2>Menu dan rating catering</h2><p>Rating menggunakan skala 1–5 bintang. Hasil menampilkan rata-rata dan jumlah penilai.</p>
    <form class="search-form" method="GET"><label class="search"><input name="q" value="{{ request('q') }}" aria-label="Cari catering" placeholder="Cari menu atau penyedia"></label><button class="btn">Cari</button>@if(request('q'))<a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => 1, 'per_page' => $records->perPage()]) }}">Reset</a>@endif</form>
    <div class="table-wrap"><table><thead><tr>
        @include('partials.sortable-header', ['column' => 'menu', 'label' => 'Menu'])
        @include('partials.sortable-header', ['column' => 'penyedia', 'label' => 'Penyedia'])
        @include('partials.sortable-header', ['column' => 'waktu_catering', 'label' => 'Waktu catering'])
        @include('partials.sortable-header', ['column' => 'rating_average', 'label' => 'Rata-rata rating'])
        @include('partials.sortable-header', ['column' => 'rating_count', 'label' => 'Jumlah penilai'])
        @unless($cateringAdmin)
            @include('partials.sortable-header', ['column' => 'my_rating', 'label' => 'Rating saya'])
            <th>Beri rating</th>
        @endunless
    </tr></thead><tbody>
        @forelse($records as $record)
        <tr><td>{{ $record->menu ? 'Menu '.$record->menu : $record->name }}</td><td>{{ $record->penyedia ?? $record->detail }}</td><td>{{ $record->waktu_catering ? \Carbon\Carbon::parse($record->waktu_catering)->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : 'Belum diatur' }}</td>
            <td>{{ $record->rating_average === null ? 'Belum ada rating' : number_format($record->rating_average, 2, ',', '.').' / 5 ★' }}</td><td>{{ $record->rating_count }}</td>
            @unless($cateringAdmin)
            <td>{{ $record->my_rating ? $record->my_rating.' / 5 ★' : 'Belum menilai' }}</td>
            <td><form method="POST" action="{{ url('/user/catering/'.$record->id.'/rating') }}">@csrf
                <label>Rating untuk {{ $record->name }}<select name="rating" required><option value="" disabled @selected(!$record->my_rating)>Pilih rating</option>@for($score = 1; $score <= 5; $score++)<option value="{{ $score }}" @selected($record->my_rating === $score)>{{ $score }} ★</option>@endfor</select></label>
                <button class="btn small">Simpan rating</button>
            </form></td>
            @endunless
        </tr>
        @empty<tr><td colspan="{{ $cateringAdmin ? 5 : 7 }}">Belum ada menu catering.</td></tr>@endforelse
    </tbody></table></div>
    @include('partials.table-pagination')
</section>
