<?php

declare(strict_types=1);

use App\Contracts\GerbangPembayaran;
use App\Enums\SaluranBayar;
use App\Enums\StatusPembayaran;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Services\MidtransGerbang;
use App\Support\HasilCharge;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Gerbang tiruan.
 *
 * Tidak ada satu pun tes di berkas ini yang menembak jaringan Midtrans —
 * itulah gunanya GerbangPembayaran jadi antarmuka. Verifikasi tanda tangan
 * diuji terhadap implementasi aslinya, karena di situlah keamanannya berada.
 */
final class GerbangTiruan implements GerbangPembayaran
{
    /** @var array<string, mixed> */
    public array $status = [];

    public bool $gagal = false;

    public function buatTransaksi(Pembayaran $pembayaran, SaluranBayar $saluran): HasilCharge
    {
        if ($this->gagal) {
            throw new KesalahanDomain('Gerbang menolak.');
        }

        return new HasilCharge(
            transactionId: 'trx-uji-1',
            orderId: (string) $pembayaran->midtrans_order_id,
            payload: ['status_code' => '201'],
            batasBayar: CarbonImmutable::now()->addDay(),
            kodeBayar: $saluran->pakaiKode() ? '8808081234567890' : null,
            qrUrl: $saluran->pakaiKode() ? null : 'https://contoh/qr.png',
        );
    }

    /** @return array<string, mixed> */
    public function periksaStatus(Pembayaran $pembayaran): array
    {
        return $this->status;
    }

    /** @param  array<string, mixed>  $payload */
    public function notifikasiSah(array $payload): bool
    {
        return ($payload['signature_key'] ?? null) === 'sah';
    }
}

function gerbangTiruan(): GerbangTiruan
{
    $tiruan = new GerbangTiruan;
    app()->instance(GerbangPembayaran::class, $tiruan);

    return $tiruan;
}

function tokoBerlangganan(int $sisaHari = 20): PosUser
{
    return PosUser::factory()->bisaMasuk()->berlangganan($sisaHari)->create();
}

// ---------------------------------------------------------------------------
// Membuat tagihan
// ---------------------------------------------------------------------------

it('membuat tagihan menunggu beserta instruksi bayarnya', function (): void {
    gerbangTiruan();
    $toko = tokoBerlangganan();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN',
            'saluran' => 'VA_BCA',
        ])->assertCreated()
        ->assertJsonPath('status', 'MENUNGGU')
        ->assertJsonPath('nominal', 99_000)
        ->assertJsonPath('saluran', 'VA_BCA')
        ->assertJsonPath('pakaiKode', true)
        ->assertJsonPath('kodeBayar', '8808081234567890');

    // Tagihan dari aplikasi POS muncul di tabel pembayaran panel admin —
    // satu tabel, bukan dua sistem yang harus direkonsiliasi.
    expect(Pembayaran::query()->count())->toBe(1)
        ->and(Pembayaran::query()->first()?->metode->value)->toBe('VIRTUAL_ACCOUNT');
});

it('menjanjikan tanggal berlaku yang menyambung sisa hari', function (): void {
    gerbangTiruan();
    $toko = tokoBerlangganan(sisaHari: 20);

    $respons = $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN', 'saluran' => 'QRIS',
        ])->assertCreated();

    // Sisa hari TIDAK hangus (PRD §F4.4): satu bulan ditambahkan dari tanggal
    // berakhir yang sekarang, bukan dari hari ini.
    $berlaku = CarbonImmutable::parse($respons->json('berlakuSampai'));
    expect($berlaku->startOfDay()->toDateString())
        ->toBe($toko->langganan_berakhir_pada->addMonth()->startOfDay()->toDateString());
});

it('menandai tagihan gagal kalau gerbang menolak', function (): void {
    gerbangTiruan()->gagal = true;
    $toko = tokoBerlangganan();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN', 'saluran' => 'QRIS',
        ])->assertStatus(422);

    // Barisnya tetap ada sebagai jejak, tapi tidak boleh tertinggal
    // "menunggu" — tagihan tanpa instruksi bayar yang terus muncul sebagai
    // sesuatu yang bisa dibayar.
    expect(Pembayaran::query()->first()?->status)->toBe(StatusPembayaran::Gagal);
});

it('menolak membeli paket uji coba', function (): void {
    gerbangTiruan();

    $this->actingAs(tokoBerlangganan(), 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'TRIAL', 'saluran' => 'QRIS',
        ])->assertStatus(422)->assertJsonValidationErrors('durasi');
});

// ---------------------------------------------------------------------------
// Webhook — jalur utama pelunasan
// ---------------------------------------------------------------------------

/** @return array{0: PosUser, 1: Pembayaran} */
function tagihanMenunggu(): array
{
    gerbangTiruan();
    $toko = tokoBerlangganan();

    test()->actingAs($toko, 'pos')->postJson(route('api.mobile.tagihan.store'), [
        'durasi' => 'BULANAN', 'saluran' => 'QRIS',
    ])->assertCreated();

    return [$toko->refresh(), Pembayaran::query()->firstOrFail()];
}

it('memperpanjang langganan saat webhook melaporkan settlement', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();
    $berakhirLama = $toko->langganan_berakhir_pada;

    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'transaction_id' => 'trx-midtrans-1',
        'signature_key' => 'sah',
    ])->assertOk();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Lunas)
        ->and($tagihan->dibayar_pada)->not->toBeNull()
        // Satu jalur pelunasan: perpanjangan langganan ikut terjadi otomatis.
        ->and($toko->refresh()->langganan_berakhir_pada->greaterThan($berakhirLama))->toBeTrue()
        ->and($tagihan->langganan_id)->not->toBeNull();
});

it('menolak notifikasi dengan tanda tangan palsu', function (): void {
    [, $tagihan] = tagihanMenunggu();

    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'palsu',
    ])->assertForbidden();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Menunggu);
});

it('menolak notifikasi yang nominalnya tidak cocok', function (): void {
    [, $tagihan] = tagihanMenunggu();

    // Tanda tangan membuktikan pesannya dari Midtrans, tapi ia dihitung dari
    // gross_amount yang dikirim — kecocokan nominalnya belum terbukti.
    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '200',
        'gross_amount' => '1000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'sah',
    ])->assertStatus(409);

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Menunggu);
});

it('tidak memperpanjang dua kali saat notifikasi dikirim ulang', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();

    $notifikasi = [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'sah',
    ];

    $this->postJson(route('api.midtrans.notifikasi'), $notifikasi)->assertOk();
    $berakhirSetelahSekali = $toko->refresh()->langganan_berakhir_pada;

    // Midtrans mengirim ulang apa pun yang tidak dijawab 200.
    $this->postJson(route('api.midtrans.notifikasi'), $notifikasi)->assertOk();

    expect($toko->refresh()->langganan_berakhir_pada->equalTo($berakhirSetelahSekali))->toBeTrue()
        ->and($toko->langganan()->count())->toBe(1);
});

it('tidak menurunkan status invoice yang sudah lunas', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();

    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'sah',
    ])->assertOk();

    // Notifikasi `expire` yang menyusul setelah settlement benar-benar terjadi;
    // menurutinya berarti mencabut langganan yang sudah dibayar.
    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '407',
        'gross_amount' => '99000.00',
        'transaction_status' => 'expire',
        'signature_key' => 'sah',
    ])->assertOk();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Lunas);
});

it('menandai kedaluwarsa, bukan gagal, saat batas waktunya lewat', function (): void {
    [, $tagihan] = tagihanMenunggu();

    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => $tagihan->midtrans_order_id,
        'status_code' => '407',
        'gross_amount' => '99000.00',
        'transaction_status' => 'expire',
        'signature_key' => 'sah',
    ])->assertOk();

    // Gagal berarti ada yang mencoba membayar dan ditolak; kedaluwarsa berarti
    // tidak pernah ada percobaan sama sekali.
    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Kedaluwarsa);
});

it('menjawab 200 untuk invoice yang tidak dikenali', function (): void {
    gerbangTiruan();

    // Midtrans mengirim ulang apa pun yang tidak dijawab 200, berhari-hari.
    // Invoice yang memang tidak ada di sini tidak akan jadi ada karenanya.
    $this->postJson(route('api.midtrans.notifikasi'), [
        'order_id' => 'INV/2026/9999-xxxx',
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'sah',
    ])->assertOk();
});

// ---------------------------------------------------------------------------
// Tombol "Saya sudah bayar"
// ---------------------------------------------------------------------------

it('melunasi lewat pemeriksaan manual kalau gerbang bilang settlement', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'settlement', 'fraud_status' => 'accept'];

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertOk()
        ->assertJsonPath('status', 'LUNAS');
});

it('membiarkan tagihan menunggu kalau dananya memang belum masuk', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'pending'];

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertOk()
        ->assertJsonPath('status', 'MENUNGGU');
});

it('menjawab 404 saat memeriksa tagihan milik toko lain', function (): void {
    [, $tagihan] = tagihanMenunggu();

    $this->actingAs(tokoBerlangganan(), 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Implementasi Midtrans yang sebenarnya
// ---------------------------------------------------------------------------

it('memverifikasi tanda tangan notifikasi dengan sha512', function (): void {
    $gerbang = new MidtransGerbang(serverKey: 'kunci-rahasia', produksi: false);

    $sah = hash('sha512', 'ORDER-1'.'200'.'99000.00'.'kunci-rahasia');

    expect($gerbang->notifikasiSah([
        'order_id' => 'ORDER-1',
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'signature_key' => $sah,
    ]))->toBeTrue();

    expect($gerbang->notifikasiSah([
        'order_id' => 'ORDER-1',
        'status_code' => '200',
        'gross_amount' => '99000.00',
        'signature_key' => str_repeat('a', 128),
    ]))->toBeFalse();
});

it('menolak notifikasi tanpa tanda tangan', function (): void {
    $gerbang = new MidtransGerbang(serverKey: 'kunci-rahasia', produksi: false);

    expect($gerbang->notifikasiSah(['order_id' => 'ORDER-1']))->toBeFalse();
});

it('mengirim charge QRIS ke sandbox dan membaca url kode qr-nya', function (): void {
    Http::fake([
        'api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_id' => 'abc-123',
            'order_id' => 'INV-2026-0001-aaa',
            'transaction_status' => 'pending',
            'actions' => [[
                'name' => 'generate-qr-code',
                'method' => 'GET',
                'url' => 'https://api.sandbox.midtrans.com/v2/qris/abc-123/qr-code',
            ]],
        ]),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'nominal' => 99_000,
        'midtrans_order_id' => 'INV-2026-0001-aaa',
    ]);

    $hasil = (new MidtransGerbang(serverKey: 'kunci', produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris);

    expect($hasil->transactionId)->toBe('abc-123')
        ->and($hasil->qrUrl)->toContain('/qr-code')
        ->and($hasil->kodeBayar)->toBeNull();

    Http::assertSent(fn ($request): bool => $request['payment_type'] === 'qris'
        && $request['transaction_details']['gross_amount'] === 99_000);
});

it('membaca nomor virtual account BCA dari jawaban charge', function (): void {
    Http::fake([
        'api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'transaction_id' => 'def-456',
            'order_id' => 'INV-2026-0002-bbb',
            'va_numbers' => [['bank' => 'bca', 'va_number' => '12345678911']],
        ]),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'nominal' => 499_000,
        'midtrans_order_id' => 'INV-2026-0002-bbb',
    ]);

    $hasil = (new MidtransGerbang(serverKey: 'kunci', produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::VaBca);

    expect($hasil->kodeBayar)->toBe('12345678911');

    Http::assertSent(fn ($request): bool => $request['payment_type'] === 'bank_transfer'
        && $request['bank_transfer']['bank'] === 'bca');
});

it('menjelaskan bahwa pembayaran otomatis mati saat server key kosong', function (): void {
    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create(['pos_user_id' => $toko->id]);

    // Pesannya harus bisa dibaca pemilik toko, bukan galat mentah — dan harus
    // menyebutkan jalan keluarnya.
    expect(fn () => (new MidtransGerbang(serverKey: null, produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris))
        ->toThrow(KesalahanDomain::class, 'Pembayaran otomatis belum aktif');
});
