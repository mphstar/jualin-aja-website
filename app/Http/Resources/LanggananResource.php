<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Langganan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `Langganan`.
 *
 * `userId` — bukan `posUserId` — karena begitulah frontend menamainya sejak
 * awal; menyelaraskan nama di sini lebih murah daripada menyisir puluhan
 * komponen.
 *
 * @mixin Langganan
 */
class LanggananResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->pos_user_id,
            'durasi' => $this->durasi->value,
            'sumber' => $this->sumber->value,
            'tanggalMulai' => $this->tanggal_mulai->toISOString(),
            'tanggalBerakhir' => $this->tanggal_berakhir->toISOString(),
            'dibuatOleh' => $this->dibuat_oleh,
            'catatan' => $this->catatan,
        ];
    }
}
