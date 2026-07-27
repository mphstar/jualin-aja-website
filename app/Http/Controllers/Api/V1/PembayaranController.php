<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\TandaiPembayaranGagal;
use App\Actions\TandaiPembayaranLunas;
use App\Enums\StatusPembayaran;
use App\Http\Concerns\MengirimHalaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarPembayaranRequest;
use App\Http\Resources\PembayaranResource;
use App\Http\Resources\PembayaranRingkasResource;
use App\Http\Resources\PosUserResource;
use App\Models\Pembayaran;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class PembayaranController extends Controller
{
    use MengirimHalaman;

    /** @return array<string, mixed> */
    public function index(DaftarPembayaranRequest $request): array
    {
        $query = Pembayaran::query()
            ->select('pembayaran.*')
            ->join('pos_users', 'pos_users.id', '=', 'pembayaran.pos_user_id');

        if (($cari = $request->cari()) !== null) {
            $query->where(function (EloquentBuilder $q) use ($cari): void {
                $q->where('pembayaran.nomor_invoice', 'like', '%'.$cari.'%')
                    ->orWhere('pos_users.nama', 'like', '%'.$cari.'%')
                    ->orWhere('pos_users.nama_toko', 'like', '%'.$cari.'%');
            });
        }

        if (($status = $request->status()) !== null) {
            $query->where('pembayaran.status', $status->value);
        }

        if (($metode = $request->metode()) !== null) {
            $query->where('pembayaran.metode', $metode->value);
        }

        if (($dari = $request->dari()) !== null) {
            $query->where('pembayaran.tanggal', '>=', $dari);
        }

        if (($sampai = $request->sampai()) !== null) {
            $query->where('pembayaran.tanggal', '<=', $sampai);
        }

        $kolom = match ($request->urutKolom('tanggal')) {
            'nominal' => 'pembayaran.nominal',
            'namaToko' => 'pos_users.nama_toko',
            default => 'pembayaran.tanggal',
        };

        $halaman = $query
            ->with('posUser')
            ->orderBy($kolom, $request->urutArah('desc'))
            ->orderBy('pembayaran.id')
            ->paginate(perPage: $request->perHalaman(10), page: $request->halaman());

        return $this->halaman($halaman, PembayaranRingkasResource::class);
    }

    /**
     * Ringkasan di atas tabel (PRD §F6.5).
     *
     * @return array{totalLunasBulanIni: int, jumlahMenunggu: int, jumlahGagal: int}
     */
    public function ringkasan(): array
    {
        $awalBulan = CarbonImmutable::now()->startOfMonth();

        return [
            'totalLunasBulanIni' => (int) Pembayaran::query()
                ->where('status', StatusPembayaran::Lunas->value)
                ->where('tanggal', '>=', $awalBulan)
                ->sum('nominal'),
            'jumlahMenunggu' => Pembayaran::query()
                ->where('status', StatusPembayaran::Menunggu->value)->count(),
            'jumlahGagal' => Pembayaran::query()
                ->where('status', StatusPembayaran::Gagal->value)->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function show(Pembayaran $pembayaran): array
    {
        $pembayaran->load('posUser');

        return [
            'pembayaran' => new PembayaranRingkasResource($pembayaran),
            'user' => new PosUserResource($pembayaran->posUser),
        ];
    }

    public function tandaiLunas(Pembayaran $pembayaran, TandaiPembayaranLunas $tandai): PembayaranResource
    {
        return new PembayaranResource($tandai($pembayaran));
    }

    public function tandaiGagal(Pembayaran $pembayaran, TandaiPembayaranGagal $tandai): PembayaranResource
    {
        return new PembayaranResource($tandai($pembayaran));
    }
}
