<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Amplop daftar `Halaman<T>` = { data, total, halaman, perHalaman }.
 *
 * Bentuk bawaan Laravel ({ data, links, meta }) sengaja tidak dipakai: seluruh
 * tabel di frontend sudah menerima amplop ini sejak masih memakai data mock,
 * jadi menyamakan sisi server lebih murah daripada menyisir kembali komponen
 * tabel, store, dan tipenya.
 */
trait MengirimHalaman
{
    /**
     * @template TModel of Model
     *
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  class-string<JsonResource>  $resource
     * @return array{data: mixed, total: int, halaman: int, perHalaman: int}
     */
    protected function halaman(LengthAwarePaginator $paginator, string $resource): array
    {
        return [
            'data' => $resource::collection($paginator->getCollection()),
            'total' => $paginator->total(),
            'halaman' => $paginator->currentPage(),
            'perHalaman' => $paginator->perPage(),
        ];
    }
}
