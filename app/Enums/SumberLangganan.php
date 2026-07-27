<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dari mana sebuah siklus langganan berasal. Dipakai kolom "Sumber" di
 * tabel /langganan dan ikut menentukan status TRIAL (PRD §4.2).
 */
enum SumberLangganan: string
{
    case Trial = 'TRIAL';
    case Pembelian = 'PEMBELIAN';
    case PerpanjanganManual = 'PERPANJANGAN_MANUAL';
    case Hadiah = 'HADIAH';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Uji Coba',
            self::Pembelian => 'Pembelian',
            self::PerpanjanganManual => 'Perpanjangan Manual',
            self::Hadiah => 'Hadiah',
        };
    }
}
