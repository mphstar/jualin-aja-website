<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Models\PosUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dua hal yang dibutuhkan setiap controller POS.
 *
 * `pastikanMilikToko` sengaja menjawab **404, bukan 403**. Route model binding
 * menemukan barang milik toko lain lewat id-nya; menjawab "dilarang" berarti
 * mengakui id itu ada, dan dari situ isi seluruh basis data bisa dipetakan satu
 * per satu hanya dengan menaikkan angka.
 */
trait MilikToko
{
    protected function toko(Request $request): PosUser
    {
        /** @var PosUser $toko */
        $toko = $request->user();

        return $toko;
    }

    protected function pastikanMilikToko(Request $request, int $posUserId): void
    {
        if ($posUserId !== $this->toko($request)->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }
}
