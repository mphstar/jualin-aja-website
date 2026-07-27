<?php

declare(strict_types=1);

namespace Database\Seeders;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Pengacak deterministik untuk data contoh.
 *
 * Seed tetap ⇒ `migrate:fresh --seed` menghasilkan data yang sama persis tiap
 * kali. Itu penting: tanpa ini, tangkapan layar dan angka dasbor berubah setiap
 * kali database dibangun ulang, dan tidak ada yang bisa dibandingkan.
 *
 * Memakai Randomizer ber-engine sendiri, bukan `mt_srand()` global — supaya
 * seeder tidak diam-diam mengubah keluaran acak bagian lain aplikasi.
 */
final class Acak
{
    private readonly Randomizer $mesin;

    public function __construct(int $seed)
    {
        $this->mesin = new Randomizer(new Mt19937($seed));
    }

    public function bulat(int $min, int $maks): int
    {
        return $this->mesin->getInt($min, $maks);
    }

    /**
     * @template T
     *
     * @param  list<T>  $daftar
     * @return T
     */
    public function pilih(array $daftar): mixed
    {
        return $daftar[$this->mesin->getInt(0, count($daftar) - 1)];
    }

    /**
     * @template T
     *
     * @param  list<T>  $daftar
     * @return list<T>
     */
    public function kocok(array $daftar): array
    {
        return array_values($this->mesin->shuffleArray($daftar));
    }

    public function peluang(float $peluang): bool
    {
        return $this->mesin->getFloat(0.0, 1.0) < $peluang;
    }

    /**
     * Pilih satu kunci menurut bobotnya, mis. ['LUNAS' => 85, 'GAGAL' => 5].
     *
     * @param  array<string, int>  $bobot
     */
    public function pilihBerbobot(array $bobot): string
    {
        $total = array_sum($bobot);
        $undian = $this->bulat(1, $total);

        foreach ($bobot as $kunci => $nilai) {
            $undian -= $nilai;
            if ($undian <= 0) {
                return (string) $kunci;
            }
        }

        return (string) array_key_first($bobot);
    }
}
