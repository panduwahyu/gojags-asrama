@php
    $active = request('sort', 'id') === $column;
    $ascending = request('direction', 'desc') === 'asc';
@endphp
<th scope="col" aria-sort="{{ $active ? ($ascending ? 'ascending' : 'descending') : 'none' }}"><a data-sort href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $active && $ascending ? 'desc' : 'asc', 'page' => 1]) }}">{{ $label }} <span aria-hidden="true">{{ $active ? ($ascending ? '▲' : '▼') : '↕' }}</span></a></th>
