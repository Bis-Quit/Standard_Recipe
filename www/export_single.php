<?php
// export_single.php - FINAL MATCHING WEB LAYOUT (Financial Panel Right, Name Left)
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

// 1. AMBIL DATA
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM recipe_headers WHERE id = ?");
$stmt->execute([$id]);
$header = $stmt->fetch();

if (!$header) die("Data tidak ditemukan.");

$stmt_ing = $pdo->prepare("SELECT * FROM recipe_ingredients WHERE recipe_header_id = ? ORDER BY sequence_no ASC");
$stmt_ing->execute([$id]);
$ingredients = $stmt_ing->fetchAll();

// 2. SETUP EXCEL
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// Nama Sheet (Bersihkan karakter aneh)
$sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9]/', ' ', $header['name_of_dish']), 0, 30));

// --- KALKULASI ULANG DI PHP (Agar Data Excel Sinkron dengan Web) ---
// 1. Hitung Subtotal dari bahan
$subtotal_batch = 0;
foreach ($ingredients as $ing) {
    $qty = floatval($ing['unit_use']);
    $conv = floatval($ing['purchase_unit_conversion']);
    $price = floatval($ing['purchase_price']);
    if($conv <= 0) $conv = 1;
    
    $row_cost = ($qty / $conv) * $price;
    $subtotal_batch += $row_cost;
}

// 2. Hitung Variable & Total
$var_percent = floatval($header['variable_percent']);
$variable_cost = $subtotal_batch * ($var_percent / 100);
$total_cost_batch = $subtotal_batch + $variable_cost;

// 3. Hitung Financials
$portion = floatval($header['portion_size']) > 0 ? floatval($header['portion_size']) : 1;
$selling_plus = floatval($header['selling_price_plus_plus']);
$selling_nett = floatval($header['selling_price_nett']);

$cost_per_portion = $total_cost_batch / $portion;
$nett_profit = $selling_plus - $cost_per_portion;
$cost_percent = ($selling_plus > 0) ? ($cost_per_portion / $selling_plus) : 0;


// --- LAYOUTING EXCEL ---

// 1. SETTING LEBAR KOLOM (Biar Rapi)
$sheet->getColumnDimension('A')->setWidth(5);   // No
$sheet->getColumnDimension('B')->setWidth(15);  // Code
$sheet->getColumnDimension('C')->setWidth(35);  // Name (Lebar)
$sheet->getColumnDimension('D')->setWidth(8);   // UOM
$sheet->getColumnDimension('E')->setWidth(12);  // Unit Use
$sheet->getColumnDimension('F')->setWidth(12);  // Purch Unit
$sheet->getColumnDimension('G')->setWidth(15);  // Purch Price
$sheet->getColumnDimension('H')->setWidth(18);  // Cost

// 2. BAGIAN HEADER (JUDUL KIRI, FINANCIAL KANAN)

// A. Judul (Kiri) - Mulai baris 2
$sheet->setCellValue('A2', 'NAME OF DISH :');
$sheet->setCellValue('A3', $header['name_of_dish']);
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14)->setUnderline(true);

// B. Financial Panel (Kanan) - Mulai baris 2, Posisi Kolom G-H
$fin_row = 2; // Baris awal panel kanan

// Styles Helper
$styleLabelRight = ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
$styleBorderBottom = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]];
$bgGrey = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']]]; // Abu tipis

// Row 1: Selling Price (Nett)
$sheet->setCellValue('G'.$fin_row, 'Selling Price (nett) :');
$sheet->setCellValue('H'.$fin_row, $selling_nett);
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->applyFromArray($styleBorderBottom)->getNumberFormat()->setFormatCode('#,##0.00');

// Row 2: Selling Price (++)
$fin_row++;
$sheet->setCellValue('G'.$fin_row, 'Selling Price (++) :');
$sheet->setCellValue('H'.$fin_row, $selling_plus);
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->applyFromArray($styleBorderBottom)->getNumberFormat()->setFormatCode('#,##0.00');

// Row 3: Cost per Portion (Grey)
$fin_row++;
$sheet->setCellValue('G'.$fin_row, 'Cost per Portion :');
$sheet->setCellValue('H'.$fin_row, $cost_per_portion);
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->applyFromArray($styleBorderBottom)->applyFromArray($bgGrey)->getNumberFormat()->setFormatCode('#,##0.00');

// Row 4: Cost % (Grey)
$fin_row++;
$sheet->setCellValue('G'.$fin_row, 'Cost % :');
$sheet->setCellValue('H'.$fin_row, $cost_percent);
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->applyFromArray($styleBorderBottom)->applyFromArray($bgGrey)->getNumberFormat()->setFormatCode('0.00%');

// Row 5: Nett Profit (Colored Text)
$fin_row++;
$sheet->setCellValue('G'.$fin_row, 'Nett Profit :');
$sheet->setCellValue('H'.$fin_row, $nett_profit);
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->applyFromArray($styleBorderBottom)->applyFromArray($bgGrey)->getNumberFormat()->setFormatCode('#,##0.00');

// Warna Merah/Hijau Profit
if($nett_profit < 0) {
    $sheet->getStyle('H'.$fin_row)->getFont()->getColor()->setARGB(Color::COLOR_RED);
} else {
    $sheet->getStyle('H'.$fin_row)->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
}

// Row 6: Portion
$fin_row++;
$sheet->setCellValue('G'.$fin_row, 'Portion :');
$sheet->setCellValue('H'.$fin_row, $portion . ' pax');
$sheet->getStyle('G'.$fin_row)->applyFromArray($styleLabelRight);
$sheet->getStyle('H'.$fin_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


// 3. TABEL BAHAN BAKU (INGREDIENTS)
$row_head = $fin_row + 3; // Kasih jarak 3 baris dari panel financial

// --- SETUP HEADER TABLE ---
// Baris Atas Header
$sheet->setCellValue('A'.$row_head, 'NO');
$sheet->setCellValue('B'.$row_head, 'CODE');
$sheet->setCellValue('C'.$row_head, 'NAME (SYSTEM)');
$sheet->setCellValue('D'.$row_head, 'UOM');
$sheet->setCellValue('E'.$row_head, 'UNIT USE');

// Merge Purchase
$sheet->setCellValue('F'.$row_head, 'PURCHASE');
$sheet->mergeCells('F'.$row_head.':G'.$row_head); 

$sheet->setCellValue('H'.$row_head, 'COST');

// Baris Bawah Header (Sub-kolom)
$row_head_sub = $row_head + 1;
$sheet->setCellValue('F'.$row_head_sub, 'Unit');
$sheet->setCellValue('G'.$row_head_sub, 'Price');

// Merge Vertikal Kolom Lain
$mergeCols = ['A', 'B', 'C', 'D', 'E', 'H'];
foreach($mergeCols as $col) {
    $sheet->mergeCells($col.$row_head.':'.$col.$row_head_sub);
}

// Styling Header (Bold, Center, Grey Background, Border)
$styleHead = [
    'font' => ['bold' => true, 'size' => 10],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFEFEF']]
];
$sheet->getStyle('A'.$row_head.':H'.$row_head_sub)->applyFromArray($styleHead);


// --- ISI DATA (LOOPING) ---
$row = $row_head + 2; // Mulai isi data
$no = 1;

foreach ($ingredients as $ing) {
    // Hitung ulang per baris
    $qty = floatval($ing['unit_use']);
    $conv = floatval($ing['purchase_unit_conversion']);
    $price = floatval($ing['purchase_price']);
    if($conv <= 0) $conv = 1;
    $cost = ($qty / $conv) * $price;

    $sheet->setCellValue('A'.$row, $no);
    $sheet->setCellValue('B'.$row, $ing['item_code']);
    $sheet->setCellValue('C'.$row, $ing['item_name_system']);
    $sheet->setCellValue('D'.$row, $ing['uom_system']);
    $sheet->setCellValue('E'.$row, $qty);
    $sheet->setCellValue('F'.$row, $conv);
    $sheet->setCellValue('G'.$row, $price);
    $sheet->setCellValue('H'.$row, $cost);

    $no++;
    $row++;
}

// Styling Data Tabel
$last_data_row = $row - 1;
$styleDataBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
$sheet->getStyle('A'.($row_head+2).':H'.$last_data_row)->applyFromArray($styleDataBorder);

// Alignment Data
$sheet->getStyle('A'.($row_head+2).':A'.$last_data_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
$sheet->getStyle('B'.($row_head+2).':B'.$last_data_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Code
$sheet->getStyle('D'.($row_head+2).':F'.$last_data_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // UOM, Use, Conv

// Format Angka Data (Desimal)
$sheet->getStyle('G'.($row_head+2).':H'.$last_data_row)->getNumberFormat()->setFormatCode('#,##0.00'); // Price & Cost
$sheet->getStyle('E'.($row_head+2).':F'.$last_data_row)->getNumberFormat()->setFormatCode('#,##0.00'); // Qty & Conv


// 4. FOOTER (TOTALS)
$row_footer = $row; // Lanjut di bawah data

// SubTotal
$sheet->setCellValue('G'.$row_footer, 'SubTotal :');
$sheet->setCellValue('H'.$row_footer, $subtotal_batch);
$sheet->getStyle('G'.$row_footer)->getFont()->setBold(true);
$sheet->getStyle('G'.$row_footer)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('H'.$row_footer)->getNumberFormat()->setFormatCode('#,##0.00');

// Variable Cost
$row_footer++;
$sheet->setCellValue('G'.$row_footer, "Variable Cost :");
$sheet->setCellValue('H'.$row_footer, $variable_cost);
$sheet->getStyle('G'.$row_footer)->getFont()->setBold(true);
$sheet->getStyle('G'.$row_footer)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('H'.$row_footer)->getNumberFormat()->setFormatCode('#,##0.00');

// Total Cost
$row_footer++;
$sheet->setCellValue('G'.$row_footer, 'TOTAL COST :');
$sheet->setCellValue('H'.$row_footer, $total_cost_batch);
$sheet->getStyle('G'.$row_footer)->getFont()->setBold(true);
$sheet->getStyle('G'.$row_footer)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('H'.$row_footer)->getNumberFormat()->setFormatCode('#,##0.00');
// Garis Bawah Double untuk Total
$sheet->getStyle('H'.$row_footer)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);


// --- OUTPUT FILE ---
$filename = "Recipe_" . preg_replace('/[^A-Za-z0-9]/', '_', $header['name_of_dish']) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;