<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\BuatTagihanPustaka;
use App\Enums\JenisKonten;
use App\Enums\SaluranBayar;
use App\Enums\StatusEbook;
use App\Enums\VersiLangganan;
use App\Http\Controllers\Controller;
use App\Http\Resources\Pos\EbookPosResource;
use App\Http\Resources\Pos\TagihanResource;
use App\Models\AksesPustaka;
use App\Models\Ebook;
use App\Models\UnduhanEbook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Katalog Pustaka — resep dan prompt (PRD §4.3, konsep baru).
 *
 * Katalog terbuka untuk SEMUA akun (Trial, Gratis, Langganan): semua orang
 * bisa melihat daftar, tapi hanya yang membuka yang bisa membaca isinya.
 * Akses per-konten berasal dari dua sumber:
 *
 * 1. Klaim jatah langganan — 1 Resep + 1 Prompt gratis untuk pelanggan aktif.
 * 2. Beli satuan — tagihan Mayar per konten, terbuka permanen setelah lunas.
 */
class EbookController extends Controller
{
    use MilikToko;

    public function index(Request $request): AnonymousResourceCollection
    {
        $toko = $this->toko($request);
        $toko->load('aksesPustaka');

        $punyaLanggananAktif = $toko->versiLangganan() === VersiLangganan::Langganan;

        $sudahKlaim = collect([
            JenisKonten::Resep->value => false,
            JenisKonten::Prompt->value => false,
        ]);
        foreach ($toko->aksesPustaka as $akses) {
            if ($akses->tipe_akses === AksesPustaka::TIPE_KLAIM_LANGGANAN) {
                $sudahKlaim[$akses->jenis] = true;
            }
        }

        $daftar = Ebook::query()
            ->where('status', StatusEbook::Terbit->value)
            ->orderByDesc('tanggal_terbit')
            ->orderByDesc('id')
            ->get();

        return EbookPosResource::collection(
            $daftar->map(fn (Ebook $e): EbookPosResource => new EbookPosResource(
                $e,
                bolehUnduh: $toko->punyaAksesEbook($e->id),
                bisaKlaim: $punyaLanggananAktif && ! $sudahKlaim[$e->jenis->value],
            )),
        );
    }

    /**
     * Klaim 1 Resep / 1 Prompt gratis dengan jatah langganan.
     *
     * Jatahnya 1 per JENIS konten, bukan 1 total: pelanggan boleh membawa
     * pulang satu resep dan satu prompt. Yang sudah diklaim tetap terbuka
     * walau langganannya nanti berhenti — klaimnya tidak ditarik kembali.
     */
    public function klaim(Request $request, Ebook $ebook): JsonResponse
    {
        $toko = $this->toko($request);

        if ($ebook->status !== StatusEbook::Terbit) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($toko->punyaAksesEbook($ebook->id)) {
            return response()->json([
                'message' => 'Konten ini sudah terbuka untuk tokomu.',
                'kode' => 'SUDAH_PUNYA_AKSES',
            ], Response::HTTP_CONFLICT);
        }

        if ($toko->versiLangganan() !== VersiLangganan::Langganan) {
            return response()->json([
                'message' => 'Klaim gratis hanya untuk pelanggan berlangganan aktif.',
                'kode' => 'LANGGANAN_DIBUTUHKAN',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        $jenis = $ebook->jenis;
        if ($toko->sudahKlaimJenis($jenis)) {
            return response()->json([
                'message' => sprintf(
                    'Jatah klaim %s gratis sudah terpakai. Konten lain bisa dibeli satuan.',
                    $jenis->label(),
                ),
                'kode' => 'JATAH_KLAIM_HABIS',
            ], Response::HTTP_CONFLICT);
        }

        AksesPustaka::query()->create([
            'pos_user_id' => $toko->id,
            'ebook_id' => $ebook->id,
            'jenis' => $jenis->value,
            'tipe_akses' => AksesPustaka::TIPE_KLAIM_LANGGANAN,
        ]);

        return response()->json([
            'message' => sprintf('"%s" berhasil diklaim gratis.', $ebook->judul),
            'terbuka' => true,
        ]);
    }

    /**
     * Beli satuan — terbitkan tagihan Mayar untuk membuka satu konten.
     *
     * Terbuka permanen setelah lunas, terlepas dari status langganan.
     */
    public function beli(Request $request, Ebook $ebook, BuatTagihanPustaka $buat): TagihanResource
    {
        $toko = $this->toko($request);

        $data = $request->validate([
            // Hanya saluran yang diputuskan server yang sah.
            'saluran' => ['required', Rule::in(array_map(
                static fn (SaluranBayar $s): string => $s->value,
                SaluranBayar::daftar(),
            ))],
        ]);

        if ($ebook->status !== StatusEbook::Terbit) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($toko->punyaAksesEbook($ebook->id)) {
            return response()->json([
                'message' => 'Konten ini sudah terbuka untuk tokomu.',
                'kode' => 'SUDAH_PUNYA_AKSES',
            ], Response::HTTP_CONFLICT);
        }

        $pembayaran = $buat(
            toko: $toko,
            ebook: $ebook,
            saluran: SaluranBayar::from($data['saluran']),
        );

        return new TagihanResource($pembayaran);
    }

    /**
     * Buka konten — catat unduhan lalu kembalikan tautan berkasnya.
     *
     * Pemeriksaan akses ada DI SINI, bukan hanya di daftar. Tautan yang pernah
     * terlihat akan tetap tersimpan di perangkat, dan tanpa gerbang di titik
     * buka ia bisa dipakai siapa pun yang menyalinnya.
     */
    public function unduh(Request $request, Ebook $ebook): JsonResponse
    {
        $toko = $this->toko($request);

        if (! $toko->punyaAksesEbook($ebook->id)) {
            return response()->json([
                'message' => 'Konten ini belum terbuka. Klaim dengan jatah langganan atau beli satuan.',
                'kode' => 'KONTEN_TERKUNCI',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        if ($ebook->status !== StatusEbook::Terbit || $ebook->berkas_path === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        UnduhanEbook::query()->create([
            'ebook_id' => $ebook->id,
            'pos_user_id' => $toko->id,
            'tanggal' => now(),
        ]);

        // Penghitung didenormalisasi untuk tabel admin; dinaikkan lewat
        // `increment` supaya dua unduhan bersamaan tidak saling menimpa.
        $ebook->increment('jumlah_unduhan');

        return response()->json(['fileUrl' => $this->urlBerkas($ebook->berkas_path, $request)]);
    }

    /**
     * URL berkas relatif terhadap host yang mengirim permintaan.
     *
     * `Storage::disk('public')->url()` memakai `APP_URL` (= localhost:8000),
     * yang benar untuk panel admin tapi salah bagi aplikasi POS yang datang
     * lewat ngrok: `localhost` di perangkat itu menunjuk ke perangkatnya
     * sendiri. Memakai `$request->root()` membuat tautan selalu cocok dengan
     * host yang dipakai aplikasi memanggil.
     */
    private function urlBerkas(mixed $path, Request $request): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) $request->root(), '/').'/uploads/'.ltrim($path, '/');
    }
}
