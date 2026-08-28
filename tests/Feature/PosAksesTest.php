<?php

declare(strict_types=1);

use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;

// ---------------------------------------------------------------------------
// Masuk
// ---------------------------------------------------------------------------

it('memberi token kepada pemilik toko yang kredensialnya benar', function (): void {
    PosUser::factory()->bisaMasuk('kopisenja')->berlangganan()->create([
        'email' => 'bintang@kopisenja.id',
    ]);

    $this->postJson(route('api.mobile.auth.masuk'), [
        'email' => 'BINTANG@kopisenja.id',
        'kataSandi' => 'kopisenja',
        'perangkat' => 'Redmi dapur',
    ])->assertOk()
        ->assertJsonStructure(['token', 'profil', 'toko', 'langganan'])
        ->assertJsonPath('langganan.bolehTransaksi', true);
});

it('menolak kata sandi yang salah dengan pesan yang sama seperti email tak dikenal', function (): void {
    PosUser::factory()->bisaMasuk('benar')->create(['email' => 'ada@toko.id']);

    $salahSandi = $this->postJson(route('api.mobile.auth.masuk'), [
        'email' => 'ada@toko.id', 'kataSandi' => 'salah',
    ])->assertStatus(422);

    $takAda = $this->postJson(route('api.mobile.auth.masuk'), [
        'email' => 'tidak@ada.id', 'kataSandi' => 'apa-pun',
    ])->assertStatus(422);

    // Pesan yang berbeda memberi tahu penyerang email mana yang terdaftar,
    // dan tidak menolong pengguna sah sedikit pun.
    expect($salahSandi->json('errors.kataSandi'))->toBe($takAda->json('errors.kataSandi'));
});

it('menolak toko yang ditangguhkan saat masuk', function (): void {
    PosUser::factory()->bisaMasuk()->ditangguhkan()->create(['email' => 'tangguh@toko.id']);

    $this->postJson(route('api.mobile.auth.masuk'), [
        'email' => 'tangguh@toko.id', 'kataSandi' => 'rahasia123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('mencabut token lama dari perangkat bernama sama', function (): void {
    $toko = PosUser::factory()->bisaMasuk()->berlangganan()->create(['email' => 'a@toko.id']);

    foreach ([1, 2] as $ignored) {
        $this->postJson(route('api.mobile.auth.masuk'), [
            'email' => 'a@toko.id', 'kataSandi' => 'rahasia123', 'perangkat' => 'HP Kasir',
        ])->assertOk();
    }

    // Tanpa ini, tiap pemasangan ulang aplikasi meninggalkan token hidup yang
    // tidak pernah bisa dicabut pemiliknya dari mana pun.
    expect($toko->tokens()->count())->toBe(1);
});

it('mencabut hanya token perangkat yang keluar', function (): void {
    $toko = PosUser::factory()->bisaMasuk()->berlangganan()->create(['email' => 'a@toko.id']);
    $tokenLain = $toko->createToken('Tablet meja')->plainTextToken;

    $token = $this->postJson(route('api.mobile.auth.masuk'), [
        'email' => 'a@toko.id', 'kataSandi' => 'rahasia123', 'perangkat' => 'HP Kasir',
    ])->json('token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.mobile.auth.keluar'))
        ->assertOk();

    /*
     * Guard di-reset di antara permintaan. Di HTTP sungguhan tiap permintaan
     * adalah proses baru, tapi di dalam satu tes `AuthManager` menyimpan
     * instance guard yang sudah memoisasi penggunanya — tanpa ini, permintaan
     * berikutnya lolos memakai hasil autentikasi permintaan sebelumnya, dan
     * tesnya akan hijau untuk alasan yang salah.
     */
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.mobile.auth.saya'))
        ->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    // Perangkat lain milik pemilik toko yang sama tidak boleh ikut keluar.
    $this->withHeader('Authorization', 'Bearer '.$tokenLain)
        ->getJson(route('api.mobile.auth.saya'))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Pemisahan guard admin ⇄ POS
// ---------------------------------------------------------------------------

it('menolak token POS di API panel admin', function (): void {
    $toko = PosUser::factory()->bisaMasuk()->berlangganan()->create();
    $token = $toko->createToken('uji')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.v1.pengguna.index'))
        ->assertUnauthorized();
});

it('menolak token admin di API aplikasi POS', function (): void {
    $admin = User::factory()->create();
    $token = $admin->createToken('uji')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.mobile.beranda'))
        ->assertUnauthorized();
});

it('menolak admin bersesi yang menembak API POS', function (): void {
    // Guard `pos` mencoba guard sesi lebih dulu (bawaan `sanctum.guard`), jadi
    // admin yang kebetulan punya cookie di peramban yang sama lolos
    // autentikasi. Middleware `pos` yang menghentikannya.
    $this->actingAs(User::factory()->create());

    $this->getJson(route('api.mobile.beranda'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Isolasi antar toko
// ---------------------------------------------------------------------------

it('tidak menampilkan produk toko lain', function (): void {
    $tetangga = PosUser::factory()->berlangganan()->create();
    Produk::factory()->untukToko($tetangga)->create(['nama' => 'Milik Tetangga']);

    $toko = PosUser::factory()->berlangganan()->create();
    Produk::factory()->untukToko($toko)->create(['nama' => 'Milik Sendiri']);

    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.produk.index'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.nama', 'Milik Sendiri');
});

it('menjawab 404 untuk transaksi milik toko lain', function (): void {
    $tetangga = PosUser::factory()->berlangganan()->create();
    $transaksi = Transaksi::factory()->create(['pos_user_id' => $tetangga->id]);

    // 404, bukan 403: menjawab "dilarang" berarti mengakui id itu ada, dan dari
    // situ isi basis data bisa dipetakan hanya dengan menaikkan angka.
    $this->actingAs(PosUser::factory()->berlangganan()->create(), 'pos')
        ->getJson(route('api.mobile.transaksi.show', $transaksi))
        ->assertNotFound();
});

it('menolak menjual produk milik toko lain', function (): void {
    $tetangga = PosUser::factory()->berlangganan()->create();
    $produkTetangga = Produk::factory()->untukToko($tetangga)->create();

    $toko = PosUser::factory()->berlangganan()->create();
    Kategori::factory()->create(['pos_user_id' => $toko->id]);

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.transaksi.store'), [
            'item' => [['produkId' => $produkTetangga->id, 'jumlah' => 1]],
            'metode' => 'QRIS',
            'status' => 'SELESAI',
        ])->assertStatus(422);

    expect(Transaksi::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Gerbang langganan
// ---------------------------------------------------------------------------

it('tetap mengizinkan transaksi baru saat langganan kedaluwarsa', function (): void {
    $toko = PosUser::factory()->kedaluwarsa()->create();
    $produk = Produk::factory()->untukToko($toko)->create();

    // Paket Gratis (kedaluwarsa) TIDAK dikunci dari kasir — yang dibatasi
    // hanya jumlah produk, voucher/diskon, dan katalog resep.
    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.transaksi.store'), [
            'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
            'metode' => 'QRIS',
            'status' => 'SELESAI',
        ])->assertCreated();

    expect(Transaksi::query()->count())->toBe(1);
});

it('mengizinkan transaksi baru untuk akun trial', function (): void {
    $toko = PosUser::factory()->trial()->create();
    $produk = Produk::factory()->untukToko($toko)->create();

    $this->actingAs($toko, 'pos')
        ->postJson(route('api.mobile.transaksi.store'), [
            'item' => [['produkId' => $produk->id, 'jumlah' => 1]],
            'metode' => 'QRIS',
            'status' => 'SELESAI',
        ])->assertCreated();

    expect(Transaksi::query()->count())->toBe(1);
});

it('tetap mengizinkan membaca katalog saat langganan kedaluwarsa', function (): void {
    $toko = PosUser::factory()->kedaluwarsa()->create();
    Produk::factory()->untukToko($toko)->create();

    // Kasir yang tidak bisa melihat daftar produknya sendiri akan mengira
    // datanya hilang — padahal datanya utuh, cuma langganannya habis.
    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.produk.index'))
        ->assertOk()
        ->assertJsonCount(1);
});

it('tetap membuka halaman langganan saat sudah kedaluwarsa', function (): void {
    $toko = PosUser::factory()->kedaluwarsa()->create();

    // Aplikasi yang mengunci pintu keluarnya sendiri tidak bisa diperpanjang.
    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.langganan'))
        ->assertOk()
        ->assertJsonPath('langganan.bolehTransaksi', true)
        ->assertJsonPath('langganan.status', 'KEDALUWARSA');
});

it('menutup seluruh aplikasi untuk toko yang ditangguhkan', function (): void {
    $toko = PosUser::factory()->berlangganan()->ditangguhkan()->create();

    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.langganan'))
        ->assertForbidden();
});

it('menutup Pustaka untuk akun trial', function (): void {
    // Trial masih "berjalan", tapi belum membeli — Pustaka hanya untuk paket
    // Berbayar (Langganan). `langgananBerjalan()` tidak boleh menjadi acuan.
    $toko = PosUser::factory()->trial()->create();

    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.langganan'))
        ->assertOk()
        ->assertJsonPath('langganan.bolehTransaksi', true)
        ->assertJsonPath('langganan.bolehUnduhResep', false);
});

it('membuka Pustaka untuk paket berbayar', function (): void {
    $toko = PosUser::factory()->berlangganan()->create();

    $this->actingAs($toko, 'pos')
        ->getJson(route('api.mobile.langganan'))
        ->assertOk()
        ->assertJsonPath('langganan.bolehTransaksi', true)
        ->assertJsonPath('langganan.bolehUnduhResep', true);
});
