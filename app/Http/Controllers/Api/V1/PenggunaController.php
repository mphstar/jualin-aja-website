<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\PulihkanPosUser;
use App\Actions\TangguhkanPosUser;
use App\Enums\StatusLangganan;
use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPenggunaRequest;
use App\Http\Requests\TangguhkanPenggunaRequest;
use App\Http\Resources\LanggananResource;
use App\Http\Resources\PembayaranResource;
use App\Http\Resources\PosUserResource;
use App\Http\Resources\PosUserRingkasResource;
use App\Http\Resources\UnduhanRingkasResource;
use App\Models\PosUser;
use App\Support\FilterStatusLangganan;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    use MengirimHalaman;

    /** @return array<string, mixed> */
    public function index(DaftarPenggunaRequest $request): array
    {
        $query = $this->dasar($request->cari(), $request->status(), $request);

        $kolom = match ($request->urutKolom('tanggalDaftar')) {
            'nama' => 'nama',
            'namaToko' => 'nama_toko',
            // Sisa hari monoton terhadap tanggal berakhir, jadi mengurutkan
            // salah satunya memberi urutan yang sama persis.
            'sisaHari' => 'langganan_berakhir_pada',
            default => 'tanggal_daftar',
        };

        $halaman = $query
            ->with('langgananBerlaku')
            ->orderBy($kolom, $request->urutArah('desc'))
            ->orderBy('id')
            ->paginate(perPage: $request->perHalaman(10), page: $request->halaman());

        return $this->halaman($halaman, PosUserRingkasResource::class);
    }

    /** @return array<string, mixed> */
    public function show(PosUser $pengguna): array
    {
        $pengguna->load('langgananBerlaku');

        return [
            'user' => new PosUserRingkasResource($pengguna),
            'riwayatLangganan' => LanggananResource::collection(
                $pengguna->langganan()->orderByDesc('tanggal_mulai')->orderByDesc('id')->get(),
            ),
            'riwayatPembayaran' => PembayaranResource::collection(
                $pengguna->pembayaran()->orderByDesc('tanggal')->orderByDesc('id')->get(),
            ),
            'riwayatUnduhan' => UnduhanRingkasResource::collection(
                $pengguna->unduhan()->with(['posUser', 'ebook'])->orderByDesc('tanggal')->get(),
            ),
        ];
    }

    public function tangguhkan(
        TangguhkanPenggunaRequest $request,
        PosUser $pengguna,
        TangguhkanPosUser $tangguhkan,
    ): PosUserResource {
        return new PosUserResource($tangguhkan($pengguna, (string) $request->string('alasan')));
    }

    public function pulihkan(PosUser $pengguna, PulihkanPosUser $pulihkan): PosUserResource
    {
        return new PosUserResource($pulihkan($pengguna));
    }

    /**
     * Daftar "akan berakhir" untuk dasbor (PRD §F2.5) — yang paling dekat habis
     * lebih dulu, supaya yang butuh tindakan ada di atas.
     *
     * @return array<int, mixed>
     */
    public function akanBerakhir(Request $request): array
    {
        $batas = min(max($request->integer('batas', 10), 1), 50);

        $query = PosUser::query()->with('langgananBerlaku');
        FilterStatusLangganan::terapkan(
            $query, StatusLangganan::AkanBerakhir,
            'langganan_berakhir_pada', 'langganan_sumber', 'ditangguhkan',
        );

        return PosUserRingkasResource::collection(
            $query->orderBy('langganan_berakhir_pada')->limit($batas)->get(),
        )->resolve();
    }

    /** @return EloquentBuilder<PosUser> */
    private function dasar(?string $cari, ?StatusLangganan $status, DaftarPenggunaRequest $request): EloquentBuilder
    {
        $query = PosUser::query();

        if ($cari !== null) {
            $query->where(function (EloquentBuilder $q) use ($cari): void {
                foreach (['nama', 'email', 'nama_toko', 'telepon', 'kota'] as $kolom) {
                    $q->orWhere($kolom, 'like', '%'.$cari.'%');
                }
            });
        }

        if ($status !== null) {
            FilterStatusLangganan::terapkan(
                $query, $status,
                'langganan_berakhir_pada', 'langganan_sumber', 'ditangguhkan',
            );
        }

        if (($durasi = $request->durasi()) !== null) {
            $query->where('langganan_durasi', $durasi->value);
        }

        if (($jenisUsaha = $request->jenisUsaha()) !== null) {
            $query->where('jenis_usaha', $jenisUsaha->value);
        }

        return $query;
    }
}
