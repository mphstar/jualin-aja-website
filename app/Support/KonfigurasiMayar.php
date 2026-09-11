<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Pengaturan;

/**
 * Konfigurasi pembayaran Mayar — nilai berlapis.
 *
 * Satu tempat seperti `HargaPaket`, tapi dengan dua lapis sumber. `.env`
 * (via `config/services.php`) adalah bawaan pabrik yang dipakai sebelum admin
 * pernah menyimpan apa pun; tabel `pengaturan` adalah nilai yang tersimpan dan
 * MENANG begitu ada. Ini membuat API key sandbox bisa dipasang saat scaffolding
 * dan ditimpa lewat panel tanpa menyentuh berkas `.env`.
 */
final class KonfigurasiMayar
{
    /** @return array<string, mixed> */
    public static function semua(): array
    {
        $tersimpan = Pengaturan::ambil(Pengaturan::KUNCI_MAYAR, []);

        if (! is_array($tersimpan)) {
            $tersimpan = [];
        }

        return [...self::bawaan(), ...$tersimpan];
    }

    public static function apiKey(): string
    {
        return (string) (self::semua()['api_key'] ?? '');
    }

    public static function webhookSecret(): string
    {
        return (string) (self::semua()['webhook_secret'] ?? '');
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
            'api_key' => (string) config('services.mayar.api_key', ''),
            'webhook_secret' => (string) config('services.mayar.webhook_secret', ''),
            'is_production' => (bool) config('services.mayar.is_production', false),
            'timeout' => (int) config('services.mayar.timeout', 15),
        ];
    }
}
