<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\PulihkanPosUser;
use App\Actions\TangguhkanPosUser;
use App\Enums\DurasiPaket;
use App\Enums\JenisAksi;
use App\Enums\StatusLangganan;
use App\Enums\SumberLangganan;
use App\Enums\TargetAksi;
use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPenggunaRequest;
use App\Http\Requests\SimpanPosUserRequest;
use App\Http\Requests\TangguhkanPenggunaRequest;
use App\Http\Resources\LanggananResource;
use App\Http\Resources\PembayaranResource;
use App\Http\Resources\PosUserResource;
use App\Http\Resources\PosUserRingkasResource;
use App\Http\Resources\UnduhanRingkasResource;
use App\Models\Langganan;
use App\Models\PosUser;
use App\Services\PencatatAktivitas;
use App\Support\FilterStatusLangganan;
use App\Support\NamaAktor;
use App\Support\RingkasanPos;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function store(SimpanPosUserRequest $request, PencatatAktivitas $pencatat): PosUserResource
    {
        $posUser = DB::transaction(function () use ($request, $pencatat): PosUser {
            $posUser = PosUser::query()->create([
                'nama' => (string) $request->string('nama'),
                'email' => $request->string('email')->lower()->trim()->value(),
                'telepon' => (string) $request->string('telepon'),
                'nama_toko' => (string) $request->string('namaToko'),
                'jenis_usaha' => (string) $request->string('jenisUsaha'),
                'kota' => (string) $request->string('kota'),
                'alamat' => $request->input('alamat'),
                'password' => (string) $request->string('password'),
                'tanggal_daftar' => now(),
            ]);

            if ($request->has('langganan')) {
                $lamaHari = $request->integer('langganan.lamaHari');
                $durasi = DurasiPaket::from((string) $request->string('langganan.durasi'));
                $sumber = SumberLangganan::from((string) $request->string('langganan.sumber'));

                Langganan::query()->create([
                    'pos_user_id' => $posUser->id,
                    'durasi' => $durasi,
                    'sumber' => $sumber,
                    'tanggal_mulai' => now(),
                    'tanggal_berakhir' => now()->addDays($lamaHari),
                    'dibuat_oleh' => NamaAktor::dari(Auth::user()),
                    'catatan' => $request->input('langganan.catatan'),
                ]);

                $posUser->refresh();
            }

            $pencatat->catat(
                aksi: JenisAksi::PenggunaDitambahkan,
                targetTipe: TargetAksi::User,
                deskripsi: sprintf('Menambahkan pengguna baru: %s (%s).', $posUser->nama_toko, $posUser->email),
                targetId: (string) $posUser->id,
                targetLabel: $posUser->nama_toko,
            );

            return $posUser;
        });

        return new PosUserResource($posUser);
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
            // Seberapa hidup kasirnya. Ditaruh di halaman yang sama dengan
            // tanggal berakhir langganan supaya "aktif tapi tidak dipakai"
            // terlihat sebagai satu gambaran, bukan dua laporan terpisah.
            'ringkasanPos' => RingkasanPos::untuk($pengguna),
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
