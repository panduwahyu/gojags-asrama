<?php

namespace App\Support;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class TableQuery
{
    /** @var list<string> */
    private array $searchable = [];

    /** @var array<string, string|Builder|Closure> */
    private array $sortable = ['id' => 'id'];

    /** @var array<string, class-string<BackedEnum>> */
    private array $statuses = [];

    private function __construct(private Builder $query) {}

    public static function for(Builder $query): self
    {
        return new self($query);
    }

    /** Kolom untuk LIKE; "relasi.kolom" mencari di tabel relasi. */
    public function search(array $columns): self
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * Kolom enum: dicari lewat label() dan diurut menurut urutan case pada enum
     * (urutan alur kerja), bukan menurut abjad nilai yang tersimpan.
     *
     * @param  class-string<BackedEnum>  $enum
     */
    public function status(string $column, string $enum): self
    {
        $this->statuses[$column] = $enum;
        $this->sortable[$column] = fn (Builder $query, string $direction) => $query->orderByRaw(
            $this->enumOrderSql($query->qualifyColumn($column), $enum).' '.$direction,
            array_column($enum::cases(), 'value'),
        );

        return $this;
    }

    /**
     * Kolom yang boleh diurutkan. Entri tanpa kunci = nama kolom; entri berkunci = alias =>
     * kolom, subquery, atau Closure(Builder $query, string $direction).
     */
    public function sort(array $columns): self
    {
        foreach ($columns as $key => $target) {
            $this->sortable[is_int($key) ? $target : $key] = $target;
        }

        return $this;
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(array_keys($this->sortable))],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', Rule::in(config('asrama.per_page_options'))],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $this->applySearch($input['q'] ?? '');
        $this->applySort($input['sort'] ?? 'id', $input['direction'] ?? 'desc');

        return $this->query->paginate(
            (int) ($input['per_page'] ?? config('asrama.per_page_options')[0])
        );
    }

    /** Semua baris sesuai filter dan urutan (tanpa paginasi), dibatasi untuk ekspor. */
    public function export(Request $request, int $limit = 5000): Collection
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(array_keys($this->sortable))],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        $this->applySearch($input['q'] ?? '');
        $this->applySort($input['sort'] ?? 'id', $input['direction'] ?? 'desc');

        return $this->query->limit($limit)->get();
    }

    private function applySearch(string $term): void
    {
        if ($term === '') {
            return;
        }

        // % dan _ yang diketik pengguna dicari apa adanya, bukan sebagai wildcard.
        $like = '%'.addcslashes($term, '%_\\').'%';

        $this->query->where(function (Builder $query) use ($like, $term) {
            foreach ($this->searchable as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $query->orWhereRelation($relation, $field, 'like', $like);
                } else {
                    $query->orWhere($query->qualifyColumn($column), 'like', $like);
                }
            }

            foreach ($this->statuses as $column => $enum) {
                $query->orWhereIn($query->qualifyColumn($column), $this->valuesMatchingLabel($enum, $term));
            }
        });
    }

    private function applySort(string $sort, string $direction): void
    {
        $target = $this->sortable[$sort];

        if ($target instanceof Closure) {
            $target($this->query, $direction);
        } else {
            $this->query->orderBy($target, $direction);
        }

        // Pengurut kedua menjaga urutan tetap stabil antar halaman.
        if ($sort !== 'id') {
            $this->query->orderBy($this->query->qualifyColumn('id'), $direction);
        }
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @return list<string>
     */
    private function valuesMatchingLabel(string $enum, string $term): array
    {
        $matches = array_filter(
            $enum::cases(),
            fn (BackedEnum $case) => mb_stripos($case->label(), $term) !== false
        );

        return array_values(array_map(fn (BackedEnum $case) => $case->value, $matches));
    }

    /** @param  class-string<BackedEnum>  $enum */
    private function enumOrderSql(string $column, string $enum): string
    {
        $branches = '';
        foreach (array_keys($enum::cases()) as $position) {
            $branches .= " WHEN ? THEN {$position}";
        }

        return "CASE {$column}{$branches} END";
    }
}
