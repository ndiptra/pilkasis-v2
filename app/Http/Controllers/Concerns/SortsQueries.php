<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait SortsQueries
{
    /**
     * @param  Builder<*>  $query
     * @param  array<int, string>  $columns
     * @return array{sort: string, direction: string}
     */
    protected function applySort(Builder $query, Request $request, array $columns, string $defaultColumn, string $defaultDirection = 'desc'): array
    {
        $column = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString());

        if (! in_array($column, $columns, true)) {
            $column = $defaultColumn;
            $direction = $defaultDirection;
        } else {
            $direction = $direction === 'desc' ? 'desc' : 'asc';
        }

        $query->orderBy($column, $direction)->orderBy('id');

        return ['sort' => $column, 'direction' => $direction];
    }

    /**
     * @param  Builder<*>  $query
     */
    protected function paginateQuery(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = $request->string('per_page')->toString();

        if ($perPage === 'all') {
            $perPage = max((clone $query)->count(), 1);
        } else {
            $perPage = in_array((int) $perPage, [10, 50, 100], true) ? (int) $perPage : 10;
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
