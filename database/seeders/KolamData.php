<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisUsaha;
use App\Enums\KategoriEbook;

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
     * 12 ebook: 9 pertama terbit, 3 terakhir draf.
     *
     * @return list<array{judul: string, kategori: KategoriEbook, deskripsi: string}>
     */
    public static function ebook(): array
    {
        return [
            [
                'judul' => '50 Resep Minuman Kekinian',
                'kategori' => KategoriEbook::Minuman,
                'deskripsi' => 'Kumpulan resep minuman yang sedang digemari: kopi susu gula aren, matcha latte, thai tea, hingga mocktail buah. Lengkap dengan takaran dan tips penyajian.',
            ],
            [
                'judul' => 'Racikan Kopi Manual Brew untuk Kafe',
                'kategori' => KategoriEbook::Minuman,
                'deskripsi' => 'Panduan V60, Aeropress, dan tubruk dengan rasio air-kopi yang konsisten, plus cara menjaga standar rasa antar barista.',
            ],
            [
                'judul' => 'Menu Andalan Warung Makan Laris',
                'kategori' => KategoriEbook::MakananBerat,
                'deskripsi' => 'Tiga puluh menu rumahan yang paling sering dipesan: ayam geprek, soto, rawon, nasi goreng, dan lauk pendamping.',
            ],
            [
                'judul' => 'Aneka Nasi Goreng & Mie Nusantara',
                'kategori' => KategoriEbook::MakananBerat,
                'deskripsi' => 'Variasi nasi goreng dan mie dari berbagai daerah, dengan bumbu dasar yang bisa disiapkan sekaligus untuk seminggu.',
            ],
            [
                'judul' => 'Snack Gorengan Modal Kecil',
                'kategori' => KategoriEbook::Snack,
                'deskripsi' => 'Resep gorengan dan camilan bermodal di bawah lima ribu rupiah per porsi, cocok untuk pendamping menu utama.',
            ],
            [
                'judul' => 'Camilan Kekinian untuk Anak Muda',
                'kategori' => KategoriEbook::Snack,
                'deskripsi' => 'Corn dog, cireng crispy, seblak, dan camilan viral lain yang mudah dibuat ulang dalam jumlah banyak.',
            ],
            [
                'judul' => 'Dessert Box & Pudding Praktis',
                'kategori' => KategoriEbook::Dessert,
                'deskripsi' => 'Dessert box, pudding, dan panna cotta yang tahan disimpan, cocok untuk pesanan pre-order.',
            ],
            [
                'judul' => 'Es Krim & Minuman Dingin Rumahan',
                'kategori' => KategoriEbook::Dessert,
                'deskripsi' => 'Es krim, es campur, dan minuman dingin tanpa mesin mahal — fokus pada tekstur dan daya tahan.',
            ],
            [
                'judul' => 'Roti Manis & Pastry Dasar',
                'kategori' => KategoriEbook::Bakery,
                'deskripsi' => 'Adonan roti manis, donat, dan croissant dasar dengan panduan proofing yang jelas untuk pemula.',
            ],
            [
                'judul' => 'Kue Kering Musim Lebaran',
                'kategori' => KategoriEbook::Bakery,
                'deskripsi' => 'Nastar, kastengel, putri salju, dan sagu keju — termasuk cara menghitung kebutuhan bahan untuk produksi massal.',
            ],
            [
                'judul' => 'Bumbu Dasar Serbaguna',
                'kategori' => KategoriEbook::BumbuSaus,
                'deskripsi' => 'Bumbu dasar merah, putih, dan kuning yang bisa dipakai untuk puluhan menu — hemat waktu prep harian.',
            ],
            [
                'judul' => 'Sambal & Saus Andalan',
                'kategori' => KategoriEbook::BumbuSaus,
                'deskripsi' => 'Sambal bawang, matah, ijo, dan saus pendamping yang tahan lama serta konsisten rasanya.',
            ],
        ];
    }
}
