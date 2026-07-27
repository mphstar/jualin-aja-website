<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Langganan;

/**
 * Menjaga kolom ringkasan langganan di `pos_users` tetap benar.
 *
 * Ini satu-satunya tempat kolom cache itu ditulis. Menaruhnya di observer
 * (bukan memanggilnya dari tiap Action) berarti jalur apa pun yang membuat,
 * mengubah, atau menghapus siklus langganan — termasuk seeder dan factory —
 * ikut tersegarkan tanpa harus diingat.
 */
class LanggananObserver
{
    public function saved(Langganan $langganan): void
    {
        $this->segarkan($langganan);
    }

    public function deleted(Langganan $langganan): void
    {
        $this->segarkan($langganan);
    }

    private function segarkan(Langganan $langganan): void
    {
        $langganan->posUser()->first()?->segarkanRingkasanLangganan();
    }
}
