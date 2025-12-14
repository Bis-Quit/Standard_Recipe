<?php
// menu_actions.php - Controller untuk Add, Duplicate, Delete
require 'db.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

try {
    // --- 1. ACTION: CREATE NEW (TAMBAH BARU) ---
    if ($action == 'create') {
        // Buat nama default unik biar gak bentrok
        $defaultName = "New Recipe - " . date('Ymd-His');
        
        $stmt = $pdo->prepare("INSERT INTO recipe_headers (name_of_dish, portion_size) VALUES (?, 1)");
        $stmt->execute([$defaultName]);
        
        $newId = $pdo->lastInsertId();
        
        // Langsung lempar ke halaman Edit agar user bisa ganti nama & isi bahan
        header("Location: detail.php?id=$newId");
        exit;
    }

    // --- 2. ACTION: DELETE (HAPUS) ---
    if ($action == 'delete' && $id) {
        // Hapus Header (Ingredients akan terhapus otomatis karena ON DELETE CASCADE di db.php)
        // Tapi untuk keamanan ganda, kita hapus manual ingredients-nya dulu
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_header_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM recipe_headers WHERE id = ?")->execute([$id]);
        
        header("Location: index.php");
        exit;
    }

    // --- 3. ACTION: DUPLICATE (COPY MENU) ---
    if ($action == 'duplicate' && $id) {
        $pdo->beginTransaction();

        // A. Ambil Data Lama (Header)
        $stmt = $pdo->prepare("SELECT * FROM recipe_headers WHERE id = ?");
        $stmt->execute([$id]);
        $oldHeader = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oldHeader) {
            // B. Insert Header Baru (Nama + Copy)
            $newName = $oldHeader['name_of_dish'] . " (Copy)";
            
            $sqlHead = "INSERT INTO recipe_headers (
                name_of_dish, portion_size, portion_unit, 
                selling_price_nett, selling_price_plus_plus, 
                cost_per_portion, nett_profit, cost_percentage, variable_percent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtHead = $pdo->prepare($sqlHead);
            $stmtHead->execute([
                $newName, $oldHeader['portion_size'], $oldHeader['portion_unit'],
                $oldHeader['selling_price_nett'], $oldHeader['selling_price_plus_plus'],
                $oldHeader['cost_per_portion'], $oldHeader['nett_profit'], 
                $oldHeader['cost_percentage'], $oldHeader['variable_percent']
            ]);
            
            $newId = $pdo->lastInsertId();

            // C. Ambil Bahan Lama & Insert ke ID Baru
            $stmtIng = $pdo->prepare("SELECT * FROM recipe_ingredients WHERE recipe_header_id = ?");
            $stmtIng->execute([$id]);
            $oldIngredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

            $sqlIng = "INSERT INTO recipe_ingredients (
                recipe_header_id, sequence_no, ingredient_name_manual, quantity, unit_manual,
                item_code, item_name_system, uom_system, unit_use, purchase_unit_conversion, purchase_price, total_cost
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtInsIng = $pdo->prepare($sqlIng);

            foreach ($oldIngredients as $ing) {
                $stmtInsIng->execute([
                    $newId, $ing['sequence_no'], $ing['ingredient_name_manual'], $ing['quantity'], $ing['unit_manual'],
                    $ing['item_code'], $ing['item_name_system'], $ing['uom_system'], $ing['unit_use'], 
                    $ing['purchase_unit_conversion'], $ing['purchase_price'], $ing['total_cost']
                ]);
            }
        }

        $pdo->commit();
        header("Location: index.php"); // Balik ke dashboard
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Error Action: " . $e->getMessage());
}
?>