<?php

declare(strict_types=1);

namespace App\Enums;

/** Jenis objek yang disentuh sebuah entri log (PRD §M7). */
enum TargetAksi: string
{
    case User = 'USER';
    case Ebook = 'EBOOK';
    case Langganan = 'LANGGANAN';
    case Pembayaran = 'PEMBAYARAN';
    case Sistem = 'SISTEM';
}
