<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Applies filtering, sorting and pagination to a listing endpoint from a
 * whitelist, so query parameters can never reach into columns the endpoint
 * didn't explicitly allow. Shared across every index endpoint instead of
 * reimplementing the same page/sort/filter parsing three times.
 */
final readonly class IndexQuery
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MAX_PER_PAGE = 100;

    /**
     * @param  list<string>  $filterable  columns matched with an exact WHERE
     * @param  list<string>  $searchable  columns matched with a partial, case-insensitive WHERE
     * @param  list<string>  $sortable  columns allowed in ?sort=
     */
    public function __construct(
        private array $filterable = [],
        private array $searchable = [],
        private array $sortable = [],
        private string $defaultSort = 'id',
    ) {}

    /**
     * @param  Builder<*>  $query
     * @return LengthAwarePaginator<int, *>
     */
    public function paginate(Builder $query, Request $request): LengthAwarePaginator
    {
        $this->applyFilters($query, $request)
            ->applySearch($query, $request)
            ->applySort($query, $request);

        $perPage = min((int) $request->integer('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);

        return $query->paginate(max($perPage, 1))->withQueryString();
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applyFilters(Builder $query, Request $request): self
    {
        foreach ($this->filterable as $column) {
            if ($request->filled("filter.{$column}")) {
                $query->where($column, $request->string("filter.{$column}"));
            }
        }

        return $this;
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applySearch(Builder $query, Request $request): self
    {
        foreach ($this->searchable as $column) {
            if ($request->filled("filter.{$column}")) {
                $query->where($column, 'ilike', '%'.$request->string("filter.{$column}").'%');
            }
        }

        return $this;
    }

    /**
     * @param  Builder<*>  $query
     */
    private function applySort(Builder $query, Request $request): self
    {
        $requested = $request->string('sort', $this->defaultSort)->toString();
        $direction = 'asc';

        if (str_starts_with($requested, '-')) {
            $direction = 'desc';
            $requested = substr($requested, 1);
        }

        $column = in_array($requested, $this->sortable, true) ? $requested : $this->defaultSort;

        $query->orderBy($column, $direction);

        return $this;
    }
}
