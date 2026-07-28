<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SimpanPengaturanStrukRequest;
use App\Http\Requests\Pos\SimpanTokoRequest;
use App\Http\Resources\Pos\PengaturanStrukResource;
use App\Http\Resources\Pos\TokoResource;
use App\Models\PengaturanStruk;
use Illuminate\Http\Request;

class TokoController extends Controller
{
    use MilikToko;

    public function show(Request $request): TokoResource
    {
        return new TokoResource($this->toko($request));
    }

    public function update(SimpanTokoRequest $request): TokoResource
    {
        $toko = $this->toko($request);
        $toko->fill($request->nilai())->save();

        return new TokoResource($toko);
    }

    public function struk(Request $request): PengaturanStrukResource
    {
        return new PengaturanStrukResource($this->pengaturan($request));
    }

    public function simpanStruk(SimpanPengaturanStrukRequest $request): PengaturanStrukResource
    {
        $pengaturan = $this->pengaturan($request);
        $pengaturan->fill($request->nilai())->save();

        return new PengaturanStrukResource($pengaturan);
    }

    /**
     * Baris pengaturan dibuat saat pertama kali dibutuhkan, bukan saat toko
     * mendaftar. Toko yang dibuat lewat jalur mana pun — seeder, impor,
     * pendaftaran — tetap punya pengaturan yang benar tanpa satu pun jalur
     * perlu mengingatnya.
     */
    private function pengaturan(Request $request): PengaturanStruk
    {
        $toko = $this->toko($request);

        return PengaturanStruk::query()->firstOrCreate(
            ['pos_user_id' => $toko->id],
            PengaturanStruk::bawaan(),
        );
    }
}
