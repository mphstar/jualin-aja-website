<?php

declare(strict_types=1);

use App\Contracts\GerbangPembayaran;
use App\Enums\JenisKonten;
use App\Enums\StatusPembayaran;
use App\Models\AksesPustaka;
use App\Models\Ebook;
use App\Models\Pembayaran;
use App\Models\PosUser;
use App\Services\MayarGerbang;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Beli satuan konten Pustaka — tagihan Mayar per konten.
 *
 * Jalur ini sebelumnya tidak punya tes sama sekali, dan dua kesalahannya
 * bersembunyi di situ: deklarasi kembalian `beli()` yang tidak cocok dengan
 * cabang 409-nya, dan batas bayar yang tersimpan tujuh jam lebih awal karena
 * Carbon UTC dari Mayar ditulis mentah ke kolom `datetime`.
 */

/** Toko berlangganan aktif — syarat untuk bisa membeli konten Pustaka. */
function tokoPustaka(int $sisaHari = 20): PosUser
{
    return PosUser::factory()->bisaMasuk()->berlangganan($sisaHari)->create();
}

/**
 * Pasang gerbang Mayar yang sebenarnya dengan jaringan dipalsukan.
 *
 * `Http::fake` wajib: gerbang aslinya akan menembak api.mayar.io sungguhan.
 *
 * @param  array<string, mixed>  $data  Badan `data` jawaban create invoice.
 */
function pasangGerbangMayar(array $data): void
{
    Http::fake([
        'api.mayar.io/hl/v2/invoices/create' => Http::response([
            'statusCode' => 200,
            'messages' => 'success',
            'data' => $data,
        ]),
    ]);

    app()->instance(GerbangPembayaran::class, new MayarGerbang(apiKey: 'kunci', produksi: false));
}

/** Jawaban create invoice QRIS untuk satu konten. */
function jawabanBeliPustaka(CarbonImmutable $berakhir): array
{
    return [
        'id' => 'inv-pustaka-1',
        'transactionId' => 'trx-pustaka-1',
        'link' => 'https://pay.contoh.myr.id/inv/pustaka',
        'expiredAt' => $berakhir->getTimestampMs(),
        'paymentDetail' => [
            'type' => 'QR_CODE',
            'qr_code' => ['channel_properties' => [
                'qr_string' => '00020101021226650013CO.XENDIT.WWW',
                'expires_at' => $berakhir->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'),
            ]],
        ],
    ];
}

it('menerbitkan tagihan satuan dan menandainya sebagai pembelian Pustaka', function (): void {
    $beku = CarbonImmutable::parse('2026-09-14 20:00:00', 'Asia/Jakarta');
    $this->travelTo($beku);

    pasangGerbangMayar(jawabanBeliPustaka($beku->addHour()));

    $ebook = Ebook::factory()->create(['harga' => 35_000]);

    $this->actingAs(tokoPustaka(), 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'qris'])
        ->assertSuccessful()
        ->assertJsonPath('tipe', Pembayaran::TIPE_PUSTAKA_SATUAN)
        ->assertJsonPath('status', 'MENUNGGU')
        ->assertJsonPath('nominal', 35_000)
        ->assertJsonPath('instruksi.tipe', 'qr_code');

    $pembayaran = Pembayaran::query()->firstOrFail();

    expect($pembayaran->ebook_id)->toBe($ebook->id)
        ->and($pembayaran->tipe)->toBe(Pembayaran::TIPE_PUSTAKA_SATUAN)
        // Instant, bukan jam dinding: kolom `datetime` menyimpan jam dinding
        // apa adanya, jadi Carbon UTC dari Mayar akan tersimpan tujuh jam
        // lebih awal dan tagihan berumur satu jam langsung terbaca kedaluwarsa.
        ->and($pembayaran->batas_bayar?->getTimestamp())->toBe($beku->addHour()->getTimestamp())
        ->and($pembayaran->statusKini())->toBe(StatusPembayaran::Menunggu);

    // Nama konten ikut ke Mayar supaya pembeli tahu sedang membayar apa.
    Http::assertSent(fn ($request): bool => str_contains((string) $request['items'][0]['description'], (string) $ebook->judul));
});

it('menjawab 409 saat kontennya sudah terbuka, bukan galat 500', function (): void {
    // Deklarasi `: TagihanResource` sementara cabang ini mengembalikan
    // JsonResponse — di bawah strict_types itu TypeError, dan yang sampai ke
    // pengguna adalah galat 500 alih-alih pesan ramahnya.
    pasangGerbangMayar(jawabanBeliPustaka(CarbonImmutable::now()->addHour()));

    $toko = tokoPustaka();
    $ebook = Ebook::factory()->create();

    AksesPustaka::query()->create([
        'pos_user_id' => $toko->id,
        'ebook_id' => $ebook->id,
        'jenis' => JenisKonten::Resep->value,
        'tipe_akses' => AksesPustaka::TIPE_BELI_SATUAN,
    ]);

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'qris'])
        ->assertStatus(409)
        ->assertJsonPath('kode', 'SUDAH_PUNYA_AKSES');

    expect(Pembayaran::query()->count())->toBe(0);
});

it('memakai ulang tagihan menunggu yang belum lewat batas', function (): void {
    // Mencegah Mayar menolak 429 "Duplicate request" saat pembeli menekan
    // beli dua kali. Penjaga ini bergantung pada `batas_bayar` yang benar —
    // dengan batas yang tersimpan tujuh jam lebih awal, ia tidak pernah
    // bekerja karena tagihannya selalu terbaca sudah kedaluwarsa.
    $beku = CarbonImmutable::parse('2026-09-14 20:00:00', 'Asia/Jakarta');
    $this->travelTo($beku);

    pasangGerbangMayar(jawabanBeliPustaka($beku->addHour()));

    $toko = tokoPustaka();
    $ebook = Ebook::factory()->create(['harga' => 35_000]);

    $pertama = $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'qris'])
        ->assertSuccessful();

    $kedua = $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'qris'])
        ->assertSuccessful();

    expect($kedua->json('id'))->toBe($pertama->json('id'))
        ->and(Pembayaran::query()->count())->toBe(1);
});

it('menolak membeli konten yang belum terbit', function (): void {
    pasangGerbangMayar(jawabanBeliPustaka(CarbonImmutable::now()->addHour()));

    $ebook = Ebook::factory()->draf()->create();

    $this->actingAs(tokoPustaka(), 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'qris'])
        ->assertNotFound();

    expect(Pembayaran::query()->count())->toBe(0);
});

it('menolak saluran di luar daftar server', function (): void {
    pasangGerbangMayar(jawabanBeliPustaka(CarbonImmutable::now()->addHour()));

    $ebook = Ebook::factory()->create();

    $this->actingAs(tokoPustaka(), 'pos')
        ->postJson(route('api.mobile.resep.beli', $ebook), ['saluran' => 'va/bca'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('saluran');
});
