<?php
// get_item_data.php - AUTO CONVERT (KG->gr, L->ml)
require 'db.php';

$keyword = $_GET['code'] ?? '';

if ($keyword) {
    // Cari item (Code atau Name)
    $sql = "SELECT * FROM items 
            WHERE code = ? 
            OR name = ? 
            OR name LIKE ? 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $keyword,
        $keyword,
        "%$keyword%"
    ]);
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // --- LOGIKA KONVERSI SATUAN (THE CONVERTER) ---
        
        $masterUnit = strtoupper(trim($item['unit'])); // Ambil satuan asli, huruf besar
        
        $finalUOM = $masterUnit; // Default: Pakai satuan asli
        $finalConv = 1;          // Default: Konversi 1
        
        // 1. LOGIKA BENDA PADAT (KG -> gr)
        if (in_array($masterUnit, ['KG', 'KGM', 'KILO', 'KILOGRAM'])) {
            $finalUOM = 'gr';   // Ubah tampilan jadi gr
            $finalConv = 1000;  // Set pembagi otomatis 1000
        }
        // Jika aslinya sudah Gram, rapikan saja
        elseif (in_array($masterUnit, ['GR', 'GRAM', 'G'])) {
            $finalUOM = 'gr';
            $finalConv = 1;
        }
        
        // 2. LOGIKA CAIRAN (Liter -> ml)
        elseif (in_array($masterUnit, ['L', 'LTR', 'LITER', 'LI'])) {
            $finalUOM = 'ml';   // Ubah tampilan jadi ml
            $finalConv = 1000;  // Set pembagi otomatis 1000
        }
        // Jika aslinya sudah Mili, rapikan saja
        elseif (in_array($masterUnit, ['ML', 'MILI'])) {
            $finalUOM = 'ml';
            $finalConv = 1;
        }
        
        // 3. LOGIKA LAINNYA (PCS, DOZ, SHEET, dll)
        // Biarkan default (UOM Asli, Conv 1)
        // Kecuali Lusin mau dijadikan PCS
        elseif ($masterUnit == 'DOZ' || $masterUnit == 'LUSIN') {
            $finalUOM = 'pcs';
            $finalConv = 12;
        }

        // --- END LOGIKA ---

        // Ambil Harga (Prioritas Last Direct Cost, kalau 0 ambil Unit Cost)
        $price = $item['last_direct_cost'] > 0 ? $item['last_direct_cost'] : $item['unit_cost'];

        echo json_encode([
            'status' => 'found',
            'code' => $item['code'],
            'name' => $item['name'],
            
            // Kirim Satuan & Konversi yang sudah diolah
            'uom' => $finalUOM,    
            'conv' => $finalConv,
            
            'price' => $price
        ]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
}
?>