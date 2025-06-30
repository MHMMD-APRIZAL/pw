<?php
require 'supabase.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$data = supabase_request("GET", "/rest/v1/pendaftaran");
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray([['Nama', 'Kategori', 'Status', 'Jadwal']], null, 'A1');
$row = 2;
foreach ($data as $p) {
    $sheet->fromArray([
        $p['nama_lengkap'], $p['kategori'], $p['status'], $p['jadwal_seleksi']
    ], null, "A$row");
    $row++;
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="data_peserta.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>