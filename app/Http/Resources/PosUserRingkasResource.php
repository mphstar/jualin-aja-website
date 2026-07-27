<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PosUser;
use Illuminate\Http\Request;

/**
 * Padanan tipe `PosUserRingkas` — PosUser + kondisi langganannya.
 *
 * Status dan sisa hari DITURUNKAN di sini lewat KondisiLangganan, tidak dibaca
 * dari kolom, supaya jawabannya tidak pernah basi (PRD §4.2).
 *
 * Butuh relasi `langgananBerlaku` sudah di-eager-load.
 *
 * @mixin PosUser
 */
class PosUserRingkasResource extends PosUserResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $berlaku = $this->relationLoaded('langgananBerlaku') ? $this->langgananBerlaku : null;

        return [
            ...parent::toArray($request),
            'langgananAktif' => $berlaku !== null ? new LanggananResource($berlaku) : null,
            'status' => $this->status()->value,
            'sisaHari' => $this->sisaHari(),
            'durasi' => $this->langganan_durasi?->value,
        ];
    }
}
