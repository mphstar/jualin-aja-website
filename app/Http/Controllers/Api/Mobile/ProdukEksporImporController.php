<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProdukEksporImporController extends Controller
{
    use MilikToko;

    /**
     * Download format templat Excel (.xlsx) untuk impor produk.
     */
    public function formatImpor(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Templat Impor Produk');

        $headers = [
            'Nama Produk',
            'Kategori',
            'Harga Jual',
            'Satuan',
            'Lacak Stok (Ya/Tidak)',
            'Stok',
        ];

        // Tulis header
        foreach ($headers as $colIndex => $header) {
            $colLetter = chr(65 + $colIndex);
            $cell = $colLetter . '1';
            $sheet->setCellValue($cell, $header);
        }

        // Contoh Data Lengkap Produk Siap Impor
        $contoh = [
            ['Kopi Susu Gula Aren', 'Kopi', 18000, 'cangkir', 'Ya', 50],
            ['Kopi Hitam Espresso', 'Kopi', 15000, 'cangkir', 'Ya', 40],
            ['Matcha Latte Ice', 'Non-Kopi', 22000, 'cangkir', 'Ya', 30],
            ['Cokelat Hangat Spesial', 'Non-Kopi', 20000, 'cangkir', 'Ya', 35],
            ['Teh Tarik Original', 'Minuman', 12000, 'gelas', 'Ya', 60],
            ['Air Mineral 600ml', 'Minuman', 5000, 'botol', 'Ya', 100],
            ['Roti Bakar Cokelat Keju', 'Makanan', 18000, 'porsi', 'Ya', 25],
            ['Nasi Goreng Spesial Toko', 'Makanan', 25000, 'porsi', 'Tidak', 0],
            ['Mie Goreng Telur', 'Makanan', 20000, 'porsi', 'Tidak', 0],
            ['Kentang Goreng Crinkle', 'Camilan', 15000, 'porsi', 'Ya', 30],
            ['Singkong Goreng Keju', 'Camilan', 14000, 'porsi', 'Ya', 20],
            ['Donat Gula Halus', 'Camilan', 8000, 'pcs', 'Ya', 45],
        ];

        foreach ($contoh as $rowIndex => $row) {
            $rowNum = $rowIndex + 2;
            $sheet->setCellValue('A' . $rowNum, $row[0]);
            $sheet->setCellValue('B' . $rowNum, $row[1]);
            $sheet->setCellValueExplicit('C' . $rowNum, (string) $row[2], DataType::TYPE_NUMERIC);
            $sheet->setCellValue('D' . $rowNum, $row[3]);
            $sheet->setCellValue('E' . $rowNum, $row[4]);
            $sheet->setCellValueExplicit('F' . $rowNum, (string) $row[5], DataType::TYPE_NUMERIC);
        }

        // Styling Header
        $headerRange = 'A1:F1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-fit kolom
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(24);

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="format_impor_produk.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Ekspor seluruh produk toko ke berkas Excel (.xlsx).
     */
    public function ekspor(Request $request): StreamedResponse
    {
        $toko = $this->toko($request);
        $produkList = Produk::query()
            ->milik($toko)
            ->with('kategori')
            ->orderBy('nama')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Produk');

        $headers = [
            'ID',
            'Nama Produk',
            'Kategori',
            'Harga Jual',
            'Satuan',
            'Lacak Stok',
            'Stok',
        ];

        foreach ($headers as $colIndex => $header) {
            $colLetter = chr(65 + $colIndex);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        foreach ($produkList as $index => $p) {
            $rowNum = $index + 2;
            $sheet->setCellValue('A' . $rowNum, $p->id);
            $sheet->setCellValue('B' . $rowNum, $p->nama);
            $sheet->setCellValue('C' . $rowNum, $p->kategori->nama ?? '-');
            $sheet->setCellValueExplicit('D' . $rowNum, (string) $p->harga_jual, DataType::TYPE_NUMERIC);
            $sheet->setCellValue('E' . $rowNum, $p->satuan);
            $sheet->setCellValue('F' . $rowNum, $p->lacak_stok ? 'Ya' : 'Tidak');
            $sheet->setCellValueExplicit('G' . $rowNum, (string) $p->stok, DataType::TYPE_NUMERIC);
        }

        // Styling
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
        ]);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $namaFile = 'data_produk_' . preg_replace('/[^a-z0-9]/i', '_', strtolower($toko->nama_toko)) . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $namaFile));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Impor produk dari berkas Excel (.xlsx / .csv).
     */
    public function impor(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Berkas Excel wajib diunggah.',
            'file.mimes' => 'Format berkas harus berupa .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran berkas maksimal 5MB.',
        ]);

        $toko = $this->toko($request);
        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return response()->json([
                'pesan' => 'Gagal membaca berkas Excel: ' . $e->getMessage(),
            ], 422);
        }

        if (count($rows) <= 1) {
            return response()->json([
                'pesan' => 'Berkas Excel kosong atau hanya berisi header.',
            ], 422);
        }

        // Skip header (baris 1)
        $headerBaris = array_shift($rows);

        $totalBaris = count($rows);
        $berhasilCount = 0;
        $rincianGagal = [];

        DB::transaction(function () use ($rows, $toko, &$berhasilCount, &$rincianGagal): void {
            foreach ($rows as $index => $row) {
                $nomorBaris = $index + 2;

                $nama = trim((string) ($row['A'] ?? ''));
                $namaKategori = trim((string) ($row['B'] ?? ''));
                $hargaJualRaw = trim((string) ($row['C'] ?? '0'));
                $satuan = trim((string) ($row['D'] ?? 'pcs'));
                $lacakStokRaw = trim((string) ($row['E'] ?? 'Tidak'));
                $stokRaw = trim((string) ($row['F'] ?? '0'));

                if ($nama === '') {
                    // Skip baris kosong sepenuhnya
                    if ($namaKategori === '' && $hargaJualRaw === '' && $satuan === '') {
                        continue;
                    }

                    $rincianGagal[] = [
                        'baris' => $nomorBaris,
                        'alasan' => 'Nama produk wajib diisi.',
                    ];
                    continue;
                }

                if ($namaKategori === '') {
                    $namaKategori = 'Umum';
                }

                // Bersihkan harga jual dari format seperti "Rp 15.000" atau "15,000"
                $hargaJualClean = (int) preg_replace('/[^0-9]/', '', $hargaJualRaw);
                if ($hargaJualClean < 0) {
                    $hargaJualClean = 0;
                }

                $satuanFinal = $satuan !== '' ? $satuan : 'pcs';

                $lacakStok = in_array(strtolower($lacakStokRaw), ['ya', '1', 'true', 'yes', 'y'], true);
                $stokClean = (int) preg_replace('/[^0-9]/', '', $stokRaw);
                if ($stokClean < 0) {
                    $stokClean = 0;
                }

                // Cari atau buat Kategori untuk toko ini
                $kategori = Kategori::query()->firstOrCreate(
                    [
                        'pos_user_id' => $toko->id,
                        'nama' => $namaKategori,
                    ],
                    [
                        'urutan' => 0,
                    ],
                );

                // Buat atau perbarui produk
                Produk::query()->updateOrCreate(
                    [
                        'pos_user_id' => $toko->id,
                        'nama' => $nama,
                    ],
                    [
                        'kategori_id' => $kategori->id,
                        'harga_jual' => $hargaJualClean,
                        'satuan' => $satuanFinal,
                        'lacak_stok' => $lacakStok,
                        'stok' => $stokClean,
                    ],
                );

                $berhasilCount++;
            }
        });

        return response()->json([
            'pesan' => sprintf('Impor berhasil diproses: %d produk ditambahkan/diperbarui.', $berhasilCount),
            'totalBaris' => $totalBaris,
            'berhasil' => $berhasilCount,
            'gagal' => count($rincianGagal),
            'rincianGagal' => $rincianGagal,
        ]);
    }
}
