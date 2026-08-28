<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pembuat PDF contoh minimal (satu halaman, satu baris teks).
 *
 * Seeder ebook biasanya tidak pernah mengunggah berkas sungguhan, sehingga
 * `berkas_path` kosong dan endpoint `/resep/{id}/unduh` mengembalikan 404.
 * Agar konten contoh tetap bisa dibuka/dipratinjau di aplikasi, seeder membuat
 * PDF valid secara terprogram — bukan foto copy-paste yang menumpuk di storage.
 */
final class PdfContoh
{
    /** @return string byte PDF valid (satu halaman). */
    public static function buat(string $teks): string
    {
        // Karakter khusus di dalam string PDF harus di-escape.
        $teks = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $teks);
        $teks = mb_substr($teks, 0, 80);

        $isi = 'BT /F1 24 Tf 72 720 Td ('.$teks.') Tj ET';

        $objek = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] '
                .'/Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length '.strlen($isi)." >>\nstream\n".$isi."\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];

        foreach ($objek as $nomor => $badan) {
            $offset[$nomor] = strlen($pdf);
            $pdf .= $nomor." 0 obj\n".$badan."\nendobj\n";
        }

        $posisiXref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objek) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offset as $mulai) {
            $pdf .= sprintf("%010d 00000 n \n", $mulai);
        }

        $pdf .= "trailer\n<< /Size ".(count($objek) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$posisiXref."\n%%EOF";

        return $pdf;
    }
}
