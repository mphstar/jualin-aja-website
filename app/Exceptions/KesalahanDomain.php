<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pelanggaran aturan domain yang tidak bisa ditangkap validasi bentuk —
 * misalnya "invoice ini sudah lunas" atau "uji coba tidak bisa diperpanjang".
 *
 * Merender dirinya sendiri supaya Action tetap bebas dari urusan HTTP dan
 * tiap controller tidak perlu menerjemahkan ulang kesalahan yang sama.
 */
class KesalahanDomain extends RuntimeException
{
    public function __construct(string $pesan, private readonly int $status = 422)
    {
        parent::__construct($pesan);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
