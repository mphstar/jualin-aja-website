<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Pos\HapusKategoriPos;
use App\Actions\Pos\SimpanKategoriPos;
use App\Actions\Pos\UrutkanKategoriPos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SimpanKategoriRequest;
use App\Http\Requests\Pos\UrutkanKategoriRequest;
use App\Http\Resources\Pos\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class KategoriController extends Controller
{
    use MilikToko;

    public function index(Request $request): AnonymousResourceCollection
    {
        return KategoriResource::collection(
            Kategori::query()->milik($this->toko($request))->terurut()->get(),
        );
    }

    public function store(SimpanKategoriRequest $request, SimpanKategoriPos $simpan): KategoriResource
    {
        return new KategoriResource($simpan(
            toko: $this->toko($request),
            nama: $request->nama(),
            ikon: $request->ikon(),
        ));
    }

    public function update(
        SimpanKategoriRequest $request,
        Kategori $kategori,
        SimpanKategoriPos $simpan,
    ): KategoriResource {
        $this->pastikanMilikToko($request, $kategori->pos_user_id);

        return new KategoriResource($simpan(
            toko: $this->toko($request),
            nama: $request->nama(),
            ikon: $request->ikon(),
            kategori: $kategori,
        ));
    }

    public function destroy(Request $request, Kategori $kategori, HapusKategoriPos $hapus): JsonResponse
    {
        $this->pastikanMilikToko($request, $kategori->pos_user_id);

        $hapus(
            toko: $this->toko($request),
            kategori: $kategori,
            pindahkanKe: $request->integer('pindahkanKe') ?: null,
        );

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    public function urutkan(
        UrutkanKategoriRequest $request,
        UrutkanKategoriPos $urutkan,
    ): AnonymousResourceCollection {
        $toko = $this->toko($request);
        $urutkan($toko, $request->urutan());

        return KategoriResource::collection(
            Kategori::query()->milik($toko)->terurut()->get(),
        );
    }
}
