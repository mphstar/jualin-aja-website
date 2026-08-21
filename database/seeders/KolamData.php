<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisKonten;
use App\Enums\JenisUsaha;
use App\Enums\KategoriEbook;
use App\Enums\KategoriPrompt;

/**
 * Kolam data Indonesia untuk seed.
 *
 * Nama toko, kota, dan judul ebook memakai contoh yang wajar supaya tampilan
 * terasa nyata saat ditinjau — data acak berbahasa Inggris membuat setiap
 * peninjauan desain terasa seperti membaca placeholder.
 */
final class KolamData
{
    /** @var list<string> */
    public const array NAMA_DEPAN = [
        'Budi', 'Siti', 'Agus', 'Dewi', 'Rizky', 'Putri', 'Andi', 'Rina',
        'Bayu', 'Maya', 'Fajar', 'Intan', 'Dimas', 'Sari', 'Hendra', 'Lestari',
        'Yusuf', 'Anisa', 'Reza', 'Novi', 'Galih', 'Ayu', 'Ilham', 'Wulan',
        'Bagus', 'Citra', 'Doni', 'Fitri', 'Eko', 'Ratna', 'Tono', 'Mega',
    ];

    /** @var list<string> */
    public const array NAMA_BELAKANG = [
        'Santoso', 'Wijaya', 'Pratama', 'Nugroho', 'Hidayat', 'Kusuma', 'Saputra',
        'Rahmawati', 'Setiawan', 'Maulana', 'Permata', 'Anggraini', 'Firmansyah',
        'Suryani', 'Hartono', 'Purnama', 'Ramadhan', 'Cahyani',
    ];

    /** @var list<string> */
    public const array KOTA = [
        'Jakarta Selatan', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang',
        'Medan', 'Makassar', 'Denpasar', 'Malang', 'Bogor', 'Bekasi', 'Solo',
        'Palembang', 'Balikpapan', 'Pekanbaru',
    ];

    /** @var list<string> */
    public const array AWALAN_TOKO = [
        'Kopi', 'Warung', 'Dapur', 'Kedai', 'Rumah Makan', 'Toko', 'Bakery',
        'Resto', 'Angkringan', 'Sate',
    ];

    /** @var list<string> */
    public const array AKHIRAN_TOKO = [
        'Senja', 'Bahagia', 'Sederhana', 'Nusantara', 'Barokah', 'Mekar',
        'Rindang', 'Pelangi', 'Harmoni', 'Kita', 'Bunda', 'Mama', 'Berkah',
        'Lestari', 'Damai',
    ];

    /** @var list<string> */
    public const array ALASAN_PENANGGUHAN = [
        'Pembayaran tertunggak lebih dari 30 hari.',
        'Permintaan pemilik toko — usaha sedang tutup sementara.',
        'Terindikasi berbagi akun dengan toko lain.',
    ];

    /** @return list<JenisUsaha> */
    public static function jenisUsaha(): array
    {
        return JenisUsaha::cases();
    }

    /**
     * 18 konten pustaka: 12 resep lalu 6 prompt. 3 terakhir jadi draf.
     *
     * @return list<array{
     *     judul: string,
     *     jenis: JenisKonten,
     *     kategori: KategoriEbook|null,
     *     kategoriPrompt: KategoriPrompt|null,
     *     deskripsi: string,
     * }>
     */
    public static function ebook(): array
    {
        return [
            [
                'judul' => '50 Resep Minuman Kekinian',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Minuman,
                'kategoriPrompt' => null,
                'deskripsi' => 'Kumpulan resep minuman yang sedang digemari: kopi susu gula aren, matcha latte, thai tea, hingga mocktail buah. Lengkap dengan takaran dan tips penyajian.',
            ],
            [
                'judul' => 'Racikan Kopi Manual Brew untuk Kafe',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Minuman,
                'kategoriPrompt' => null,
                'deskripsi' => 'Panduan V60, Aeropress, dan tubruk dengan rasio air-kopi yang konsisten, plus cara menjaga standar rasa antar barista.',
            ],
            [
                'judul' => 'Menu Andalan Warung Makan Laris',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::MakananBerat,
                'kategoriPrompt' => null,
                'deskripsi' => 'Tiga puluh menu rumahan yang paling sering dipesan: ayam geprek, soto, rawon, nasi goreng, dan lauk pendamping.',
            ],
            [
                'judul' => 'Aneka Nasi Goreng & Mie Nusantara',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::MakananBerat,
                'kategoriPrompt' => null,
                'deskripsi' => 'Variasi nasi goreng dan mie dari berbagai daerah, dengan bumbu dasar yang bisa disiapkan sekaligus untuk seminggu.',
            ],
            [
                'judul' => 'Snack Gorengan Modal Kecil',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Snack,
                'kategoriPrompt' => null,
                'deskripsi' => 'Resep gorengan dan camilan bermodal di bawah lima ribu rupiah per porsi, cocok untuk pendamping menu utama.',
            ],
            [
                'judul' => 'Camilan Kekinian untuk Anak Muda',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Snack,
                'kategoriPrompt' => null,
                'deskripsi' => 'Corn dog, cireng crispy, seblak, dan camilan viral lain yang mudah dibuat ulang dalam jumlah banyak.',
            ],
            [
                'judul' => 'Dessert Box & Pudding Praktis',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Dessert,
                'kategoriPrompt' => null,
                'deskripsi' => 'Dessert box, pudding, dan panna cotta yang tahan disimpan, cocok untuk pesanan pre-order.',
            ],
            [
                'judul' => 'Es Krim & Minuman Dingin Rumahan',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Dessert,
                'kategoriPrompt' => null,
                'deskripsi' => 'Es krim, es campur, dan minuman dingin tanpa mesin mahal — fokus pada tekstur dan daya tahan.',
            ],
            [
                'judul' => 'Roti Manis & Pastry Dasar',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Bakery,
                'kategoriPrompt' => null,
                'deskripsi' => 'Adonan roti manis, donat, dan croissant dasar dengan panduan proofing yang jelas untuk pemula.',
            ],
            [
                'judul' => 'Kue Kering Musim Lebaran',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::Bakery,
                'kategoriPrompt' => null,
                'deskripsi' => 'Nastar, kastengel, putri salju, dan sagu keju — termasuk cara menghitung kebutuhan bahan untuk produksi massal.',
            ],
            [
                'judul' => 'Bumbu Dasar Serbaguna',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::BumbuSaus,
                'kategoriPrompt' => null,
                'deskripsi' => 'Bumbu dasar merah, putih, dan kuning yang bisa dipakai untuk puluhan menu — hemat waktu prep harian.',
            ],
            [
                'judul' => 'Sambal & Saus Andalan',
                'jenis' => JenisKonten::Resep,
                'kategori' => KategoriEbook::BumbuSaus,
                'kategoriPrompt' => null,
                'deskripsi' => 'Sambal bawang, matah, ijo, dan saus pendamping yang tahan lama serta konsisten rasanya.',
            ],
            [
                'judul' => 'Prompt Logo Usaha Kekinian',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::Logo,
                'deskripsi' => 'Kumpulan prompt siap pakai untuk menghasilkan logo usaha dengan AI, lengkap dengan variasi gaya dan skema warna.',
            ],
            [
                'judul' => 'Prompt Desain Menu Digital',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::DesainMenu,
                'deskripsi' => 'Prompt untuk membuat desain menu makanan dan minuman yang rapi, menarik, dan mudah dibaca pelanggan.',
            ],
            [
                'judul' => 'Prompt Poster Promosi Jualan',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::PosterPromosi,
                'deskripsi' => 'Prompt poster promosi untuk diskon, menu baru, dan event — siap tempel di media sosial atau dicetak.',
            ],
            [
                'judul' => 'Prompt Konten Sosial Media',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::SosialMedia,
                'deskripsi' => 'Prompt feed dan story Instagram, caption, hingga desain konten harian yang konsisten dengan merek toko.',
            ],
            [
                'judul' => 'Prompt Foto Produk Makanan',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::FotoProduk,
                'deskripsi' => 'Prompt untuk menghasilkan foto produk makanan dan minuman yang menggugah selera, dengan tata cahaya dan latar yang pas.',
            ],
            [
                'judul' => 'Prompt Desain Kemasan Produk',
                'jenis' => JenisKonten::Prompt,
                'kategori' => null,
                'kategoriPrompt' => KategoriPrompt::KemasanProduk,
                'deskripsi' => 'Prompt desain kemasan, label, dan stiker produk agar tampil profesional dan mudah dikenali di rak.',
            ],
        ];
    }
}
