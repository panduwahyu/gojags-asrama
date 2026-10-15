<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Mengunduh data tabel sebagai Excel (xlsx) atau PDF. */
final class TableExport
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<scalar|null>>  $rows
     */
    public static function download(string $format, string $title, array $headings, array $rows): StreamedResponse
    {
        $filename = str($title)->slug().'-'.now()->format('Ymd-His').'.'.$format;

        return response()->streamDownload(
            function () use ($format, $title, $headings, $rows) {
                echo $format === 'pdf'
                    ? self::pdf($title, $headings, $rows)
                    : self::xlsx($title, $headings, $rows);
            },
            $filename,
            ['Content-Type' => $format === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** Waktu UTC -> teks WIB untuk dokumen. */
    public static function wib(mixed $time): string
    {
        return $time === null
            ? ''
            : $time->copy()->setTimezone((string) config('asrama.display_timezone'))->format('d-m-Y H:i').' WIB';
    }

    private static function xlsx(string $title, array $headings, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        foreach ([$headings, ...$rows] as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                // Selalu teks: sel yang diawali "=" tidak dievaluasi sebagai rumus.
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (range(1, count($headings)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');

        return (string) ob_get_clean();
    }

    private static function pdf(string $title, array $headings, array $rows): string
    {
        $cell = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $head = implode('', array_map(fn ($h) => '<th>'.$cell($h).'</th>', $headings));
        $body = implode('', array_map(
            fn ($row) => '<tr>'.implode('', array_map(fn ($v) => '<td>'.$cell($v).'</td>', $row)).'</tr>',
            $rows
        ));

        $html = '<html><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,sans-serif;font-size:9px}h1{font-size:14px}'
            .'table{width:100%;border-collapse:collapse}th,td{border:1px solid #999;padding:3px;text-align:left}'
            .'th{background:#eee}</style></head><body><h1>'.$cell($title).'</h1><p>Dibuat '
            .$cell(self::wib(now())).'</p><table><thead><tr>'.$head.'</tr></thead><tbody>'.$body
            .'</tbody></table></body></html>';

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return (string) $pdf->output();
    }
}
