<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\HapusEbook;
use App\Actions\SimpanEbook;
use App\Actions\UbahStatusEbook;
use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarEbookRequest;
use App\Http\Requests\SimpanEbookRequest;
use App\Http\Requests\UbahStatusEbookRequest;
use App\Http\Resources\EbookResource;
use App\Http\Resources\UnduhanRingkasResource;
use App\Models\Ebook;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Response;

class EbookController extends Controller
{
    use MengirimHalaman;

    /** Panjang grafik unduhan di halaman detail (PRD §F5.6). */
    private const int HARI_DERET = 30;

    /** @return array<string, mixed> */
    public function index(DaftarEbookRequest $request): array
    {
        $query = Ebook::query();

        if (($cari = $request->cari()) !== null) {
            $query->where(function (EloquentBuilder $q) use ($cari): void {
                $q->where('judul', 'like', '%'.$cari.'%')
                    ->orWhere('deskripsi', 'like', '%'.$cari.'%');
            });
        }

        if (($jenis = $request->jenis()) !== null) {
            $query->where('jenis', $jenis->value);
        }

        if (($kategori = $request->kategori()) !== null) {
            $query->where('kategori', $kategori->value);
        }

        if (($kategoriPrompt = $request->kategoriPrompt()) !== null) {
            $query->where('kategori_prompt', $kategoriPrompt->value);
        }

        if (($status = $request->status()) !== null) {
            $query->where('status', $status->value);
        }

        $kolom = match ($request->urutKolom('tanggalDibuat')) {
            'judul' => 'judul',
            'jumlahUnduhan' => 'jumlah_unduhan',
            default => 'created_at',
        };

        $halaman = $query
            ->orderBy($kolom, $request->urutArah('desc'))
            ->orderBy('id')
            ->paginate(perPage: $request->perHalaman(12), page: $request->halaman());

        return $this->halaman($halaman, EbookResource::class);
    }

    /** @return array<string, mixed> */
    public function show(Ebook $ebook): array
    {
        $unduhan = $ebook->unduhan()
            ->with(['posUser', 'ebook'])
            ->orderByDesc('tanggal')
            ->get();

        return [
            'ebook' => new EbookResource($ebook),
            'unduhan' => UnduhanRingkasResource::collection($unduhan),
            'deret30Hari' => $this->deretUnduhan($ebook),
        ];
    }

    public function store(SimpanEbookRequest $request, SimpanEbook $simpan): EbookResource
    {
        return new EbookResource($simpan(
            ebook: null,
            jenis: $request->jenis(),
            judul: (string) $request->string('judul'),
            kategori: $request->kategori(),
            kategoriPrompt: $request->kategoriPrompt(),
            deskripsi: (string) $request->string('deskripsi'),
            status: $request->status(),
            cover: $request->cover(),
            berkas: $request->berkas(),
            jumlahHalaman: $request->jumlahHalaman(),
        ));
    }

    public function update(SimpanEbookRequest $request, Ebook $ebook, SimpanEbook $simpan): EbookResource
    {
        return new EbookResource($simpan(
            ebook: $ebook,
            jenis: $request->jenis(),
            judul: (string) $request->string('judul'),
            kategori: $request->kategori(),
            kategoriPrompt: $request->kategoriPrompt(),
            deskripsi: (string) $request->string('deskripsi'),
            status: $request->status(),
            cover: $request->cover(),
            berkas: $request->berkas(),
            jumlahHalaman: $request->jumlahHalaman(),
        ));
    }

    public function ubahStatus(
        UbahStatusEbookRequest $request,
        Ebook $ebook,
        UbahStatusEbook $ubahStatus,
    ): EbookResource {
        return new EbookResource($ubahStatus($ebook, $request->status()));
    }

    public function destroy(Ebook $ebook, HapusEbook $hapus): Response
    {
        $hapus($ebook);

        return response()->noContent();
    }

    /**
     * Unduhan per hari selama 30 hari terakhir.
     *
     * Dihitung dari satu kueri agregat, bukan dengan menyaring koleksi unduhan
     * di PHP: jumlah unduhan sebuah ebook populer bisa jauh lebih besar dari
     * yang perlu ditampilkan grafiknya.
     *
     * @return list<array{label: string, nilai: int}>
     */
    private function deretUnduhan(Ebook $ebook): array
    {
        $mulai = CarbonImmutable::now()->startOfDay()->subDays(self::HARI_DERET - 1);

        $perHari = $ebook->unduhan()
            ->where('tanggal', '>=', $mulai)
            ->get(['tanggal'])
            ->countBy(fn ($unduhan): string => $unduhan->tanggal->format('Y-m-d'));

        $deret = [];
        for ($i = 0; $i < self::HARI_DERET; $i++) {
            $hari = $mulai->addDays($i);
            $deret[] = [
                'label' => $hari->format('j/n'),
                'nilai' => (int) $perHari->get($hari->format('Y-m-d'), 0),
            ];
        }

        return $deret;
    }
}
