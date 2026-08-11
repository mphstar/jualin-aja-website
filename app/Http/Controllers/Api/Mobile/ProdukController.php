<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Pos\HapusProdukPos;
use App\Actions\Pos\SimpanProdukPos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SimpanProdukRequest;
use App\Http\Resources\Pos\ProdukResource;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProdukController extends Controller
{
    use MilikToko;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Produk::query()->milik($this->toko($request));

        if (($cari = trim((string) $request->string('cari'))) !== '') {
            $query->where('nama', 'like', '%'.$cari.'%');
        }

        if (($kategoriId = $request->integer('kategoriId')) > 0) {
            $query->where('kategori_id', $kategoriId);
        }

        /*
         * Diurutkan mengikuti urutan kategori, lalu nama. Layar Produk
         * mengelompokkan per kategori dan mengandalkan urutan ini apa adanya —
         * mengurutkannya lagi di klien berarti dua aturan urutan yang bisa
         * berbeda.
         */
        return ProdukResource::collection(
            $query->join('kategori', 'kategori.id', '=', 'produk.kategori_id')
                ->orderBy('kategori.urutan')
                ->orderBy('produk.nama')
                ->select('produk.*')
                ->get(),
        );
    }

    public function store(SimpanProdukRequest $request, SimpanProdukPos $simpan): ProdukResource
    {
        $toko = $this->toko($request);
        $jumlahSaatIni = $toko->produk()->count();

        if (! $toko->bolehTambahProduk($jumlahSaatIni, 1)) {
            $batas = $toko->batasMaksimalProduk();
            abort(response()->json([
                'message' => 'Batas maksimal produk untuk versi Gratis telah tercapai (maksimal '.$batas.' produk). Tingkatkan paket untuk menambah produk tanpa batas.',
                'errors' => [
                    'produk' => ['Batas maksimal produk untuk versi Gratis telah tercapai (maksimal '.$batas.' produk).'],
                ],
            ], 422));
        }

        return new ProdukResource($simpan($toko, $request->nilai()));
    }

    public function update(
        SimpanProdukRequest $request,
        Produk $produk,
        SimpanProdukPos $simpan,
    ): ProdukResource {
        $this->pastikanMilikToko($request, $produk->pos_user_id);

        return new ProdukResource($simpan($this->toko($request), $request->nilai(), $produk));
    }

    public function destroy(Request $request, Produk $produk, HapusProdukPos $hapus): JsonResponse
    {
        $this->pastikanMilikToko($request, $produk->pos_user_id);

        $hapus($this->toko($request), $produk);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
