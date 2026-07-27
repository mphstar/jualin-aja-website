<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\PerpanjangLangganan;
use App\Enums\StatusLangganan;
use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarLanggananRequest;
use App\Http\Requests\PerpanjangLanggananRequest;
use App\Http\Resources\LanggananResource;
use App\Http\Resources\LanggananRingkasResource;
use App\Models\Langganan;
use App\Models\PosUser;
use App\Support\FilterStatusLangganan;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class LanggananController extends Controller
{
    use MengirimHalaman;

    /** @return array<string, mixed> */
    public function index(DaftarLanggananRequest $request): array
    {
        $kolom = match ($request->urutKolom('tanggalBerakhir')) {
            'namaToko' => 'pos_users.nama_toko',
            default => 'langganan.tanggal_berakhir',
        };

        $halaman = $this->dasar($request)
            ->with('posUser')
            ->orderBy($kolom, $request->urutArah('asc'))
            ->orderBy('langganan.id')
            ->paginate(perPage: $request->perHalaman(10), page: $request->halaman());

        return $this->halaman($halaman, LanggananRingkasResource::class);
    }

    /**
     * Jumlah per status untuk tab filter (PRD §F4.2).
     *
     * Dihitung dari query dasar yang SAMA dengan yang mengisi tabel — kalau
     * dihitung terpisah, angka di tab tidak akan pernah cocok dengan isinya.
     *
     * @return array<string, int>
     */
    public function jumlahPerStatus(DaftarLanggananRequest $request): array
    {
        $hasil = ['SEMUA' => $this->dasar($request, abaikanStatus: true)->count()];

        foreach (StatusLangganan::cases() as $status) {
            $query = $this->dasar($request, abaikanStatus: true);
            FilterStatusLangganan::terapkan(
                $query, $status,
                'langganan.tanggal_berakhir', 'langganan.sumber', 'pos_users.ditangguhkan',
            );
            $hasil[$status->value] = $query->count();
        }

        return $hasil;
    }

    /** @return array<int, mixed> */
    public function riwayat(PosUser $pengguna): array
    {
        return LanggananRingkasResource::collection(
            $pengguna->langganan()
                ->with('posUser')
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->get(),
        )->resolve();
    }

    public function perpanjang(
        PerpanjangLanggananRequest $request,
        PerpanjangLangganan $perpanjang,
    ): LanggananResource {
        $posUser = PosUser::query()->findOrFail($request->integer('userId'));

        return new LanggananResource($perpanjang(
            posUser: $posUser,
            durasi: $request->durasi(),
            catatan: $request->input('catatan'),
        ));
    }

    /**
     * Query dasar tabel /langganan.
     *
     * `abaikanStatus` dipakai penghitung tab: ia perlu kumpulan baris yang sama
     * TANPA filter status, lalu menyaringnya sendiri per status.
     *
     * @return EloquentBuilder<Langganan>
     */
    private function dasar(DaftarLanggananRequest $request, bool $abaikanStatus = false): EloquentBuilder
    {
        $query = Langganan::query()
            ->select('langganan.*')
            ->join('pos_users', 'pos_users.id', '=', 'langganan.pos_user_id');

        if (! $request->termasukRiwayat()) {
            // Satu baris per toko: hanya siklus yang sedang berlaku.
            $query->whereColumn('langganan.id', 'pos_users.langganan_berlaku_id');
        }

        if (($cari = $request->cari()) !== null) {
            $query->where(function (EloquentBuilder $q) use ($cari): void {
                $q->where('pos_users.nama', 'like', '%'.$cari.'%')
                    ->orWhere('pos_users.nama_toko', 'like', '%'.$cari.'%');
            });
        }

        if (($durasi = $request->durasi()) !== null) {
            $query->where('langganan.durasi', $durasi->value);
        }

        if (! $abaikanStatus && ($status = $request->status()) !== null) {
            FilterStatusLangganan::terapkan(
                $query, $status,
                'langganan.tanggal_berakhir', 'langganan.sumber', 'pos_users.ditangguhkan',
            );
        }

        return $query;
    }
}
