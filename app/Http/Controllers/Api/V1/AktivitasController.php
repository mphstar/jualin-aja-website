<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarAktivitasRequest;
use App\Http\Resources\LogAktivitasResource;
use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

class AktivitasController extends Controller
{
    use MengirimHalaman;

    /** @return array<string, mixed> */
    public function index(DaftarAktivitasRequest $request): array
    {
        $query = LogAktivitas::query();

        if (($cari = $request->cari()) !== null) {
            $query->where(function (EloquentBuilder $q) use ($cari): void {
                foreach (['deskripsi', 'aktor_nama', 'target_label'] as $kolom) {
                    $q->orWhere($kolom, 'like', '%'.$cari.'%');
                }
            });
        }

        if (($aksi = $request->aksi()) !== null) {
            $query->where('aksi', $aksi->value);
        }

        if (($dari = $request->dari()) !== null) {
            $query->where('waktu', '>=', $dari);
        }

        if (($sampai = $request->sampai()) !== null) {
            $query->where('waktu', '<=', $sampai);
        }

        $halaman = $query
            ->orderByDesc('waktu')
            ->orderByDesc('id')
            ->paginate(perPage: $request->perHalaman(20), page: $request->halaman());

        return $this->halaman($halaman, LogAktivitasResource::class);
    }

    /**
     * Feed 8 aktivitas terbaru di dasbor (PRD §F2.6).
     *
     * @return array<int, mixed>
     */
    public function terbaru(Request $request): array
    {
        $batas = min(max($request->integer('batas', 8), 1), 50);

        return LogAktivitasResource::collection(
            LogAktivitas::query()
                ->orderByDesc('waktu')
                ->orderByDesc('id')
                ->limit($batas)
                ->get(),
        )->resolve();
    }
}
