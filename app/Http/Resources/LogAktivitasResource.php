<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padanan tipe `LogAktivitas`.
 *
 * @mixin LogAktivitas
 */
class LogAktivitasResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'waktu' => $this->waktu->toISOString(),
            'aktorId' => (string) ($this->aktor_id ?? ''),
            'aktorNama' => $this->aktor_nama,
            'aksi' => $this->aksi->value,
            'targetTipe' => $this->target_tipe->value,
            'targetId' => $this->target_id,
            'targetLabel' => $this->target_label,
            'deskripsi' => $this->deskripsi,
        ];
    }
}
