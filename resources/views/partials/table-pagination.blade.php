<nav class="pagination" aria-label="Navigasi halaman tabel">
    <label>Baris per halaman <select name="per_page" aria-label="Baris per halaman">@foreach([10, 50, 100] as $size)<option value="{{ $size }}" @selected($records->perPage() === $size)>{{ $size }}</option>@endforeach</select></label>
    @if($records->onFirstPage())<span aria-disabled="true">Awal</span><span aria-disabled="true">Sebelumnya</span>
    @else<a href="{{ $records->url(1) }}">Awal</a><a href="{{ $records->previousPageUrl() }}">Sebelumnya</a>@endif
    <span data-page-indicator role="status" aria-live="polite" aria-label="Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}">{{ $records->currentPage() }}/{{ $records->lastPage() }}</span>
    @if($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}">Berikutnya</a><a href="{{ $records->url($records->lastPage()) }}">Akhir</a>
    @else<span aria-disabled="true">Berikutnya</span><span aria-disabled="true">Akhir</span>@endif
</nav>
