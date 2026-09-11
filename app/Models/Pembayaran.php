<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DurasiPaket;
use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nomor_invoice
 * @property int $pos_user_id
 * @property int|null $langganan_id
 * @property int $nominal
 * @property DurasiPaket $durasi
 * @property MetodePembayaran $metode
 * @property string|null $saluran
 * @property StatusPembayaran $status
 * @property Carbon $tanggal
 * @property Carbon|null $batas_bayar
 * @property Carbon|null $kedaluwarsa_saluran
 * @property Carbon|null $berlaku_sampai
 * @property string|null $kode_bayar
 * @property string|null $kode_perusahaan
 * @property string|null $qr_url
 * @property string|null $tautan_bayar
 * @property array<string, mixed>|null $instruksi_bayar
 * @property string|null $catatan
 * @property string|null $mayar_order_id
 * @property string|null $mayar_transaction_id
 * @property array<string, mixed>|null $mayar_payload
 * @property string $tipe
 * @property int|null $ebook_id
 * @property-read PosUser $posUser
 * @property-read Ebook|null $ebook
 */
#[Fillable([
    'nomor_invoice', 'pos_user_id', 'langganan_id', 'nominal', 'durasi', 'tipe', 'ebook_id',
    'metode', 'saluran', 'status', 'tanggal', 'batas_bayar',
    'kedaluwarsa_saluran', 'berlaku_sampai', 'kode_bayar', 'kode_perusahaan',
    'qr_url', 'tautan_bayar', 'instruksi_bayar', 'catatan',
    'mayar_order_id', 'mayar_transaction_id', 'mayar_payload', 'dibayar_pada',
])]
class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
    use HasFactory;

    public const string TIPE_LANGGANAN = 'LANGGANAN';

    public const string TIPE_PUSTAKA_SATUAN = 'PUSTAKA_SATUAN';

    protected $table = 'pembayaran';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'durasi' => DurasiPaket::class,
            'metode' => MetodePembayaran::class,
            'status' => StatusPembayaran::class,
            'tanggal' => 'datetime',
            'batas_bayar' => 'datetime',
            'kedaluwarsa_saluran' => 'datetime',
            'berlaku_sampai' => 'datetime',
            'instruksi_bayar' => 'array',
            'mayar_payload' => 'array',
            'dibayar_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<PosUser, $this> */
    public function posUser(): BelongsTo
    {
        return $this->belongsTo(PosUser::class);
    }

    /** @return BelongsTo<Ebook, $this> */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class);
    }

    public function lewatBatas(): bool
    {
        return $this->batas_bayar !== null && $this->batas_bayar->isPast();
    }

    /**
     * Status yang sudah memperhitungkan batas waktu.
     *
     * Mayar mengirim webhook pengingat, tapi yang menutup tagihan yang belum
     * dibayar adalah batas waktunya sendiri. Tagihan yang sudah lewat batas
     * tapi masih tercatat "menunggu" akan terus menampilkan tautan pembayaran
     * yang sudah tidak berlaku. Diturunkan di sini, bukan ditulis ke kolom,
     * supaya tidak bisa basi.
     */
    public function statusKini(): StatusPembayaran
    {
        return $this->status === StatusPembayaran::Menunggu && $this->lewatBatas()
            ? StatusPembayaran::Kedaluwarsa
            : $this->status;
    }

    /** @return BelongsTo<Langganan, $this> */
    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }
}
