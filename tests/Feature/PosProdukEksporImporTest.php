<?php

declare(strict_types=1);

use App\Models\Kategori;
use App\Models\PosUser;
use App\Models\Produk;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function (): void {
    $this->toko = PosUser::factory()->berlangganan()->bisaMasuk()->create();
    $this->kategori = Kategori::factory()->create([
        'pos_user_id' => $this->toko->id,
        'nama' => 'Makanan',
    ]);
});

it('dapat mendownload format templat impor (.xlsx)', function (): void {
    $this->actingAs($this->toko, 'pos')
        ->getJson('/api/mobile/v1/produk/format-impor')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('dapat mengekspor daftar produk ke Excel (.xlsx)', function (): void {
    Produk::factory()->create([
        'pos_user_id' => $this->toko->id,
        'kategori_id' => $this->kategori->id,
        'nama' => 'Kopi Latte',
    ]);

    $this->actingAs($this->toko, 'pos')
        ->getJson('/api/mobile/v1/produk/ekspor')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('dapat mengimpor produk dari berkas Excel (.xlsx)', function (): void {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Nama Produk');
    $sheet->setCellValue('B1', 'Kategori');
    $sheet->setCellValue('C1', 'Harga Jual');
    $sheet->setCellValue('D1', 'Satuan');
    $sheet->setCellValue('E1', 'Lacak Stok');
    $sheet->setCellValue('F1', 'Stok');

    $sheet->setCellValue('A2', 'Teh Tarik Subur');
    $sheet->setCellValue('B2', 'Minuman Segar');
    $sheet->setCellValue('C2', 12000);
    $sheet->setCellValue('D2', 'gelas');
    $sheet->setCellValue('E2', 'Ya');
    $sheet->setCellValue('F2', 25);

    $filePath = tempnam(sys_get_temp_dir(), 'test_impor_') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    $file = new UploadedFile(
        $filePath,
        'impor_test.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );

    $this->actingAs($this->toko, 'pos')
        ->postJson('/api/mobile/v1/produk/impor', [
            'file' => $file,
        ])
        ->assertOk()
        ->assertJsonPath('berhasil', 1);

    expect(Produk::where('pos_user_id', $this->toko->id)->where('nama', 'Teh Tarik Subur')->exists())->toBeTrue();
    expect(Kategori::where('pos_user_id', $this->toko->id)->where('nama', 'Minuman Segar')->exists())->toBeTrue();

    @unlink($filePath);
});
