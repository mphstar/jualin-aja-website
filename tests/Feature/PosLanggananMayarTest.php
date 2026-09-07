<?php

declare(strict_types=1);

use App\Contracts\GerbangPembayaran;
use App\Enums\DurasiPaket;
use App\Enums\SaluranBayar;
use App\Enums\StatusPembayaran;
use App\Exceptions\KesalahanDomain;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Services\MayarGerbang;
use App\Support\HasilCharge;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gerbang tiruan.
 *
 * Tidak ada satu pun tes di berkas ini yang menembak jaringan Mayar â€”
 * itulah gunanya GerbangPembayaran jadi antarmuka. Verifikasi bentuk notifikasi
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
            transactionId: 'trx-mayar-1',
            orderId: 'inv-mayar-1',
            payload: [
                'statusCode' => 200,
                'data' => ['id' => 'inv-mayar-1', 'transactionId' => 'trx-mayar-1', 'link' => 'https://pay.contoh.myr.id/inv/1'],
            ],
            batasBayar: CarbonImmutable::now()->addMinutes(30),
            kedaluwarsaSaluran: CarbonImmutable::now()->addMinutes(30),
            tautanBayar: 'https://pay.contoh.myr.id/inv/1',
            instruksi: [
                'tipe' => 'qr_code',
                'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=contoh',
                'kodeBayar' => null,
                'kodePerusahaan' => null,
                'aksi' => [],
            ],
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
        $data = $payload['data'] ?? [];

        return is_array($data)
            && ($data['transactionId'] ?? null) !== null;
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

/** Rahasia path webhook â€” alfanumerik, panjang, dan sama di config. */
function rahasiaWebhook(): string
{
    return 'RahasiaUjiPanjangAbcdef9876543210xYz';
}

/** Rahasia yang salah â€” tetap memenuhi bentuk path, tapi bukan nilainya. */
function rahasiaSalah(): string
{
    return 'KunciSalahPanjangAbcdefghij9876543210';
}

/** @return array<string, mixed> */
function statusLunas(Pembayaran $pembayaran): array
{
    return [
        'transaction_status' => 'paid',
        'transaction_id' => 'trx-mayar-1',
        'gross_amount' => $pembayaran->nominal,
        'extraData' => ['orderId' => $pembayaran->nomor_invoice],
        'data' => ['id' => 'trx-mayar-1', 'status' => 'paid'],
    ];
}

// ---------------------------------------------------------------------------
// Membuat tagihan
// ---------------------------------------------------------------------------

it('membuat tagihan menunggu beserta instrumen QR native', function (): void {
    gerbangTiruan();
    $toko = tokoBerlangganan();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN',
            'saluran' => 'qris',
        ])->assertCreated()
        ->assertJsonPath('status', 'MENUNGGU')
        ->assertJsonPath('nominal', 99_000)
        ->assertJsonPath('saluran', 'qris')
        ->assertJsonPath('saluranLabel', 'QRIS')
        ->assertJsonPath('instruksi.tipe', 'qr_code')
        ->assertJsonPath('instruksi.qrUrl', 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=contoh')
        ->assertJsonPath('tautanBayar', 'https://pay.contoh.myr.id/inv/1');

    $pembayaran = Pembayaran::query()->firstOrFail();
    expect($pembayaran->metode->value)->toBe('QRIS')
        ->and($pembayaran->kedaluwarsa_saluran)->not->toBeNull()
        // Kedaluwarsa saluran menang — batas pembayaran ikut batas kode QR.
        ->and($pembayaran->batas_bayar?->getTimestamp())->toBe($pembayaran->kedaluwarsa_saluran?->getTimestamp());
});

it('menolak saluran yang tidak ada di daftar server', function (): void {
    gerbangTiruan();

    $this->actingAs(tokoBerlangganan(), 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN',
            'saluran' => 'va/bca',
        ])->assertStatus(422)->assertJsonValidationErrors('saluran');
});

it('menjanjikan tanggal berlaku yang menyambung sisa hari', function (): void {
    gerbangTiruan();
    $toko = tokoBerlangganan(sisaHari: 20);

    $respons = $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'BULANAN', 'saluran' => 'qris',
        ])->assertCreated();

    // Sisa hari TIDAK hangus (PRD Â§F4.4): satu bulan ditambahkan dari tanggal
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
            'durasi' => 'BULANAN', 'saluran' => 'qris',
        ])->assertStatus(422);

    // Barisnya tetap ada sebagai jejak, tapi tidak boleh tertinggal
    // "menunggu" â€” tagihan tanpa instrumen bayar yang terus muncul sebagai
    // sesuatu yang bisa dibayar.
    expect(Pembayaran::query()->first()?->status)->toBe(StatusPembayaran::Gagal);
});

it('menolak membeli paket uji coba', function (): void {
    gerbangTiruan();

    $this->actingAs(tokoBerlangganan(), 'pos')
        ->postJson(route('api.mobile.tagihan.store'), [
            'durasi' => 'TRIAL', 'saluran' => 'qris',
        ])->assertStatus(422)->assertJsonValidationErrors('durasi');
});

// ---------------------------------------------------------------------------
// Webhook â€” jalur utama pelunasan
// ---------------------------------------------------------------------------

/** @return array{0: PosUser, 1: Pembayaran} */
function tagihanMenunggu(): array
{
    $tiruan = gerbangTiruan();
    $toko = tokoBerlangganan();

    test()->actingAs($toko, 'pos')->postJson(route('api.mobile.tagihan.store'), [
        'durasi' => 'BULANAN', 'saluran' => 'qris',
    ])->assertCreated();

    $pembayaran = Pembayaran::query()->firstOrFail();

    // Status yang dibaca ulang dari transaksi detail.
    $tiruan->status = statusLunas($pembayaran);

    return [$toko->refresh(), $pembayaran];
}

it('memperpanjang langganan saat webhook lalu status terbaca paid', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [$toko, $tagihan] = tagihanMenunggu();
    $berakhirLama = $toko->langganan_berakhir_pada;

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), [
        'event' => 'payment.received',
        'data' => [
            'transactionId' => $tagihan->mayar_transaction_id,
            'transactionStatus' => 'paid',
            'amount' => 99_000,
            'paymentMethod' => 'QRIS',
        ],
    ])->assertOk();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Lunas)
        ->and($tagihan->dibayar_pada)->not->toBeNull()
        // Satu jalur pelunasan: perpanjangan langganan ikut terjadi otomatis.
        ->and($toko->refresh()->langganan_berakhir_pada->greaterThan($berakhirLama))->toBeTrue()
        ->and($tagihan->langganan_id)->not->toBeNull()
        // Metode sungguhannya ikut tercatat untuk laporan admin.
        ->and($tagihan->metode->value)->toBe('QRIS');
});

it('menolak notifikasi dengan rahasia yang salah', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaSalah()]), [
        'event' => 'payment.received',
        'data' => ['transactionId' => 'trx-apa-saja', 'amount' => 99_000],
    ])->assertNotFound();
});

it('menolak notifikasi dengan bentuk tidak sah', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [, $tagihan] = tagihanMenunggu();

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), [
        'event' => 'payment.received',
        'data' => ['transactionStatus' => 'paid', 'amount' => 99_000],
    ])->assertForbidden();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Menunggu);
});

it('tidak melunasi selagi status terbaca belum paid', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [$toko, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'created'];

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), [
        'event' => 'payment.received',
        'data' => [
            'transactionId' => $tagihan->mayar_transaction_id,
            'amount' => 99_000,
        ],
    ])->assertOk();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Menunggu)
        ->and($toko->refresh()->langganan()->count())->toBe(0);
});

it('menolak notifikasi yang nominalnya tidak cocok', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = [
        'transaction_status' => 'paid',
        'transaction_id' => 'trx-mayar-1',
        'gross_amount' => 1_000,
        'data' => ['id' => 'trx-mayar-1', 'status' => 'paid'],
    ];

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), [
        'event' => 'payment.received',
        'data' => [
            'transactionId' => $tagihan->mayar_transaction_id,
            'transactionStatus' => 'paid',
            'amount' => 99_000,
        ],
    ])->assertStatus(409);

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Menunggu);
});

it('tidak memperpanjang dua kali saat notifikasi dikirim ulang', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [$toko, $tagihan] = tagihanMenunggu();

    $notifikasi = [
        'event' => 'payment.received',
        'data' => [
            'transactionId' => $tagihan->mayar_transaction_id,
            'transactionStatus' => 'paid',
            'amount' => 99_000,
        ],
    ];

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), $notifikasi)->assertOk();
    $berakhirSetelahSekali = $toko->refresh()->langganan_berakhir_pada;

    // Mayar mengirim ulang apa pun yang tidak dijawab 200.
    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), $notifikasi)->assertOk();

    expect($toko->refresh()->langganan_berakhir_pada->equalTo($berakhirSetelahSekali))->toBeTrue()
        ->and($toko->langganan()->count())->toBe(1);
});

it('tidak menurunkan status invoice yang sudah lunas', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    [$toko, $tagihan] = tagihanMenunggu();

    $bagus = [
        'event' => 'payment.received',
        'data' => ['transactionId' => $tagihan->mayar_transaction_id, 'amount' => 99_000],
    ];

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), $bagus)->assertOk();

    // Status yang menurun setelah settlement benar-benar terjadi; menurutinya
    // berarti mencabut langganan yang sudah dibayar.
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'expired'];

    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), $bagus)->assertOk();

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Lunas);
});

it('menjawab 200 untuk invoice yang tidak dikenali', function (): void {
    config()->set('services.mayar.webhook_secret', rahasiaWebhook());
    gerbangTiruan();

    // Mayar mengirim ulang apa pun yang tidak dijawab 200, berhari-hari.
    // Invoice yang memang tidak ada di sini tidak akan jadi ada karenanya.
    $this->postJson(route('api.mayar.notifikasi', ['rahasia' => rahasiaWebhook()]), [
        'event' => 'payment.received',
        'data' => [
            'transactionId' => 'transaksi-asing-9999',
            'transactionStatus' => 'paid',
            'amount' => 99_000,
        ],
    ])->assertOk();
});

// ---------------------------------------------------------------------------
// Tombol "Saya sudah bayar"
// ---------------------------------------------------------------------------

it('melunasi lewat pemeriksaan manual kalau gerbang bilang paid', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = statusLunas($tagihan);

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertOk()
        ->assertJsonPath('status', 'LUNAS');
});

it('membiarkan tagihan menunggu kalau dananya memang belum masuk', function (): void {
    [$toko, $tagihan] = tagihanMenunggu();
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'created'];

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertOk()
        ->assertJsonPath('status', 'MENUNGGU');
});

it('menandai kedaluwarsa saat transaksi terbaca expired', function (): void {
    [, $tagihan] = tagihanMenunggu();
    $toko = $tagihan->posUser;
    /** @var GerbangTiruan $gerbang */
    $gerbang = app(GerbangPembayaran::class);
    $gerbang->status = ['transaction_status' => 'expired'];

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertOk()
        ->assertJsonPath('status', 'KEDALUWARSA');

    expect($tagihan->refresh()->status)->toBe(StatusPembayaran::Kedaluwarsa);
});

it('menjawab 404 saat memeriksa tagihan milik toko lain', function (): void {
    [, $tagihan] = tagihanMenunggu();

    $this->actingAs(tokoBerlangganan(), 'pos')
        ->postJson(route('api.mobile.tagihan.periksa', $tagihan))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Implementasi Mayar yang sebenarnya
// ---------------------------------------------------------------------------

it('menerima bentuk notifikasi webhook yang sah', function (): void {
    $gerbang = new MayarGerbang(apiKey: 'kunci-rahasia', produksi: false);

    expect($gerbang->notifikasiSah([
        'event' => 'payment.received',
        'data' => ['transactionId' => 'TRX-1', 'amount' => 99_000],
    ]))->toBeTrue();

    expect($gerbang->notifikasiSah([
        'event' => 'payment.received',
        'data' => ['amount' => 99_000],
    ]))->toBeFalse();

    expect($gerbang->notifikasiSah(['data' => ['transactionId' => 'TRX-1']]))->toBeFalse();
});

it('mengirim invoice QRIS dan membaca instrumen kode QR-nya', function (): void {
    Http::fake([
        'api.mayar.io/hl/v2/invoices/create' => Http::response([
            'statusCode' => 200,
            'messages' => 'success',
            'data' => [
                'id' => 'inv-mayar-1',
                'transactionId' => 'trx-mayar-1',
                'link' => 'https://pay.contoh.myr.id/inv/1',
                'expiredAt' => now()->addHour()->getTimestampMs(),
                'paymentDetail' => [
                    'type' => 'QR_CODE',
                    'qr_code' => ['channel_properties' => ['qr_string' => '000201010212...']],
                ],
            ],
        ]),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'durasi' => DurasiPaket::Bulanan,
        'nominal' => 99_000,
    ]);

    $hasil = (new MayarGerbang(apiKey: 'kunci', produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris);

    expect($hasil->transactionId)->toBe('trx-mayar-1')
        ->and($hasil->tautanBayar)->toBe('https://pay.contoh.myr.id/inv/1')
        ->and($hasil->instruksi['tipe'])->toBe('qr_code')
        ->and($hasil->instruksi['qrUrl'])->toContain('000201010212');

    Http::assertSent(
        fn ($request): bool => $request['paymentMethod'] === 'qris'
            && $request['items'][0]['rate'] === 99_000
            && ($request['extraData']['orderId'] ?? null) === $pembayaran->nomor_invoice,
    );
});

it('menyimpan tautan hosted sebagai cadangan saat paymentDetail tidak dikenal', function (): void {
    Http::fake([
        'api.mayar.io/hl/v2/invoices/create' => Http::response([
            'statusCode' => 200,
            'messages' => 'success',
            'data' => [
                'id' => 'inv-mayar-1',
                'transactionId' => 'trx-mayar-1',
                'link' => 'https://pay.contoh.myr.id/inv/1',
            ],
        ]),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'durasi' => DurasiPaket::Bulanan,
        'nominal' => 99_000,
    ]);

    $hasil = (new MayarGerbang(apiKey: 'kunci', produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris);

    expect($hasil->instruksi)->toBeNull()
        ->and($hasil->tautanBayar)->toBe('https://pay.contoh.myr.id/inv/1');
});

it('periksaStatus menormalkan transaksi ke bentuk yang dipahami penelaras', function (): void {
    Http::fake([
        'api.mayar.io/hl/v2/transactions/trx-mayar-1' => Http::response([
            'statusCode' => 200,
            'messages' => 'success',
            'data' => [
                'id' => 'trx-mayar-1',
                'extraData' => ['orderId' => 'INV/2026/0001'],
                'amount' => 99_000,
                'status' => 'paid',
                'paymentMethod' => 'QRIS',
            ],
        ]),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'mayar_transaction_id' => 'trx-mayar-1',
    ]);

    $status = (new MayarGerbang(apiKey: 'kunci', produksi: false))
        ->periksaStatus($pembayaran);

    expect($status['transaction_status'])->toBe('paid')
        ->and($status['gross_amount'])->toBe(99_000)
        ->and($status['extraData']['orderId'])->toBe('INV/2026/0001')
        ->and($status['payment_method'])->toBe('QRIS');
});

it('menjelaskan bahwa pembayaran otomatis mati saat API key kosong', function (): void {
    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'durasi' => DurasiPaket::Bulanan,
    ]);

    // Pesannya harus bisa dibaca pemilik toko, bukan galat mentah â€” dan harus
    // menyebutkan jalan keluarnya.
    expect(fn () => (new MayarGerbang(apiKey: null, produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris))
        ->toThrow(KesalahanDomain::class, 'Pembayaran otomatis belum aktif');
});

it('menjelaskan penolakan create termasuk kode dan pesan Mayar', function (): void {
    Log::spy();

    Http::fake([
        'api.mayar.io/hl/v2/invoices/create' => Http::response([
            'statusCode' => 429,
            'messages' => 'Duplicate request detected. Please wait 1 minute before trying again.',
        ], 429),
    ]);

    $toko = tokoBerlangganan();
    $pembayaran = Pembayaran::factory()->create([
        'pos_user_id' => $toko->id,
        'durasi' => DurasiPaket::Bulanan,
        'nominal' => 99_000,
    ]);

    expect(fn () => (new MayarGerbang(apiKey: 'kunci', produksi: false))
        ->buatTransaksi($pembayaran, SaluranBayar::Qris))
        ->toThrow(KesalahanDomain::class, 'Gagal membuat tagihan: Duplicate request detected. Please wait 1 minute before trying again. Coba lagi beberapa saat.');

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $pesan, array $konteks): bool => str_contains($pesan, 'Mayar menolak')
            && ($konteks['status_code'] ?? null) === 429);
});
