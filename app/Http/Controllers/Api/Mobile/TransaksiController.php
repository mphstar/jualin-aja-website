<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Pos\LunasiTransaksiPos;
use App\Actions\Pos\NomorStrukBerikutnya;
use App\Actions\Pos\SimpanTransaksiPos;
use App\Actions\Pos\UbahIsiTransaksiPos;
use App\Enums\MetodeBayarPos;
use App\Enums\StatusTransaksi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\LunasiTransaksiRequest;
use App\Http\Requests\Pos\SimpanTransaksiRequest;
use App\Http\Requests\Pos\UbahIsiTransaksiRequest;
use App\Http\Resources\Pos\TransaksiResource;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransaksiController extends Controller
{
    use MilikToko;

    /** Riwayat, terbaru di depan. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaksi::query()->milik($this->toko($request))->with('baris');

        if (($cari = trim((string) $request->string('cari'))) !== '') {
            $query->where(function (Builder $q) use ($cari): void {
                $q->where('nomor_struk', 'like', '%'.$cari.'%')
                    ->orWhere('pelanggan', 'like', '%'.$cari.'%')
                    ->orWhereHas('baris', fn (Builder $b) => $b->where('nama', 'like', '%'.$cari.'%'));
            });
        }

        if (($status = StatusTransaksi::tryFrom((string) $request->string('status'))) !== null) {
            $query->where('status', $status->value);
        }

        if (($metode = MetodeBayarPos::tryFrom((string) $request->string('metode'))) !== null) {
            $query->where('metode', $metode->value);
        }

        if (($sesiId = trim((string) $request->string('sesi_id'))) !== '') {
            // The mobile app sends kode_sesi (e.g. "SHIFT-xxx") as the session
            // identifier, but transactions store the numeric primary key in
            // sesi_kasir_id.  Resolve kode_sesi → id before filtering.
            $sesi = \App\Models\SesiKasir::query()
                ->where('kode_sesi', $sesiId)
                ->first();

            $query->where('sesi_kasir_id', $sesi?->id ?? 0);
        }

        if (($namaKasir = trim((string) $request->string('nama_kasir'))) !== '') {
            $query->where('nama_kasir', 'like', '%'.$namaKasir.'%');
        }

        return TransaksiResource::collection(
            $query->orderByDesc('waktu')
                ->orderByDesc('id')
                ->limit($request->integer('batas', 100) ?: 100)
                ->get(),
        );
    }

    /** Piutang yang belum ditagih, TERTUA di depan — utang ditagih menurut umurnya. */
    public function piutang(Request $request): AnonymousResourceCollection
    {
        return TransaksiResource::collection(
            Transaksi::query()
                ->milik($this->toko($request))
                ->where('status', StatusTransaksi::Ditahan->value)
                ->with('baris')
                ->orderBy('waktu')
                ->get(),
        );
    }

    /** @return array{nomorStruk: string} */
    public function nomorBerikutnya(Request $request, NomorStrukBerikutnya $nomor): array
    {
        return ['nomorStruk' => $nomor->intip($this->toko($request))];
    }

    public function show(Request $request, Transaksi $transaksi): TransaksiResource
    {
        $this->pastikanMilikToko($request, $transaksi->pos_user_id);

        return new TransaksiResource($transaksi->load('baris'));
    }

    public function store(SimpanTransaksiRequest $request, SimpanTransaksiPos $simpan): TransaksiResource
    {
        return new TransaksiResource($simpan(
            toko: $this->toko($request),
            item: $request->item(),
            metode: $request->metode(),
            status: $request->status(),
            pelanggan: $request->pelanggan(),
            uangDiterima: $request->uangDiterima(),
            diskonTipe: $request->diskonTipe(),
            diskonNilai: $request->diskonNilai(),
        ));
    }

    public function lunasi(
        LunasiTransaksiRequest $request,
        Transaksi $transaksi,
        LunasiTransaksiPos $lunasi,
    ): TransaksiResource {
        $this->pastikanMilikToko($request, $transaksi->pos_user_id);

        return new TransaksiResource($lunasi(
            transaksi: $transaksi,
            metode: $request->metode(),
            uangDiterima: $request->uangDiterima(),
        ));
    }

    public function ubahIsi(
        UbahIsiTransaksiRequest $request,
        Transaksi $transaksi,
        UbahIsiTransaksiPos $ubah,
    ): TransaksiResource {
        $this->pastikanMilikToko($request, $transaksi->pos_user_id);

        return new TransaksiResource($ubah($this->toko($request), $transaksi, $request->item()));
    }
}
