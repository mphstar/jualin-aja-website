<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Pengaturan;

/**
 * Konfigurasi pembayaran Midtrans — nilai berlapis.
 *
 * Satu tempat seperti `HargaPaket`, tapi dengan dua lapis sumber. `.env`
 * (via `config/services.php`) adalah bawaan pabrik yang dipakai sebelum admin
 * pernah menyimpan apa pun; tabel `pengaturan` adalah nilai yang tersimpan dan
 * MENANG begitu ada. Ini membuat server key sandbox bisa dipasang saat
 * scaffolding dan ditimpa lewat panel tanpa menyentuh berkas `.env`.
 */
final class KonfigurasiMidtrans
{
    /** @return array<string, mixed> */
    public static function semua(): array
    {
        $tersimpan = Pengaturan::ambil(Pengaturan::KUNCI_MIDTRANS, []);

        if (! is_array($tersimpan)) {
            $tersimpan = [];
        }

        return [...self::bawaan(), ...$tersimpan];
    }

    public static function serverKey(): string
    {
        return (string) (self::semua()['server_key'] ?? '');
    }

    public static function clientKey(): string
    {
        return (string) (self::semua()['client_key'] ?? '');
    }

    public static function produksi(): bool
    {
        return (bool) (self::semua()['is_production'] ?? false);
    }

    public static function timeout(): int
    {
        return (int) (self::semua()['timeout'] ?? 15);
    }

    /**
     * Bawaan pabrik dari `.env` — dipakai sebelum ada nilai tersimpan.
     *
     * @return array<string, mixed>
     */
    public static function bawaan(): array
    {
        return [
            'server_key' => (string) config('services.midtrans.server_key', ''),
            'client_key' => (string) config('services.midtrans.client_key', ''),
            'is_production' => (bool) config('services.midtrans.is_production', false),
            'timeout' => (int) config('services.midtrans.timeout', 15),
        ];
    }
}
