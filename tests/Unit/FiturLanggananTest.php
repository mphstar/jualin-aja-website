<?php

declare(strict_types=1);

use App\Enums\StatusLangganan;
use App\Enums\VersiLangganan;
use App\Support\FiturLangganan;

test('pemetaan status ke versi langganan', function (): void {
    expect(FiturLangganan::versiDariStatus(StatusLangganan::Trial))->toBe(VersiLangganan::Trial);
    expect(FiturLangganan::versiDariStatus(StatusLangganan::Aktif))->toBe(VersiLangganan::Langganan);
    expect(FiturLangganan::versiDariStatus(StatusLangganan::AkanBerakhir))->toBe(VersiLangganan::Langganan);
    expect(FiturLangganan::versiDariStatus(StatusLangganan::Kedaluwarsa))->toBe(VersiLangganan::Gratis);
    expect(FiturLangganan::versiDariStatus(StatusLangganan::Nonaktif))->toBe(VersiLangganan::Gratis);
    expect(FiturLangganan::versiDariStatus(null))->toBe(VersiLangganan::Gratis);
});

test('akses resep hanya untuk paket langganan', function (): void {
    expect(FiturLangganan::bolehAksesResep(VersiLangganan::Gratis))->toBeFalse();
    expect(FiturLangganan::bolehAksesResep(VersiLangganan::Trial))->toBeFalse();
    expect(FiturLangganan::bolehAksesResep(VersiLangganan::Langganan))->toBeTrue();
});

test('akses voucher untuk trial dan langganan', function (): void {
    expect(FiturLangganan::bolehAksesVoucher(VersiLangganan::Gratis))->toBeFalse();
    expect(FiturLangganan::bolehAksesVoucher(VersiLangganan::Trial))->toBeTrue();
    expect(FiturLangganan::bolehAksesVoucher(VersiLangganan::Langganan))->toBeTrue();
});

test('batas produk gratis 20 dan unlimited untuk trial/langganan', function (): void {
    expect(FiturLangganan::batasMaksimalProduk(VersiLangganan::Gratis))->toBe(20);
    expect(FiturLangganan::batasMaksimalProduk(VersiLangganan::Trial))->toBeNull();
    expect(FiturLangganan::batasMaksimalProduk(VersiLangganan::Langganan))->toBeNull();
});

test('pemeriksaan penambahan produk', function (): void {
    // Versi Gratis: batas 20
    expect(FiturLangganan::bolehTambahProduk(VersiLangganan::Gratis, 19, 1))->toBeTrue();
    expect(FiturLangganan::bolehTambahProduk(VersiLangganan::Gratis, 20, 1))->toBeFalse();

    // Versi Trial & Langganan: tanpa batas
    expect(FiturLangganan::bolehTambahProduk(VersiLangganan::Trial, 100, 10))->toBeTrue();
    expect(FiturLangganan::bolehTambahProduk(VersiLangganan::Langganan, 1000, 50))->toBeTrue();
});
