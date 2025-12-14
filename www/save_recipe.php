<?php
// save_recipe.php - FINAL UPDATE (Financial Data Support)
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $id = $input['id'];
    $newName = $input['name_of_dish'];
    $ingredients = $input['ingredients'];
    
    // Data Financial Baru
    $portion = $input['portion'];
    $price_nett = $input['price_nett'];
    $price_plus = $input['price_plus'];
    $total_cost = $input['total_cost']; // Ini Total Cost Batch (Semua bahan)
    $cost_per_portion = $input['cost_per_portion'];
    $var_percent = $input['var_percent'];
    $nett_profit = $input['nett_profit'];
    $cost_percent = $input['cost_percent'];

    try {
        $pdo->beginTransaction();

        // Update Header Lengkap
        $sql = "UPDATE recipe_headers 
                SET name_of_dish = ?,
                    portion_size = ?,
                    selling_price_nett = ?,
                    selling_price_plus_plus = ?,
                    variable_percent = ?,
                    cost_per_portion = ?,
                    nett_profit = ?,
                    cost_percentage = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $newName, 
            $portion, 
            $price_nett, 
            $price_plus, 
            $var_percent,
            $cost_per_portion,
            $nett_profit,
            $cost_percent,
            $id
        ]);

        // Reset & Insert Bahan
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_header_id = ?")->execute([$id]);

        $sql_ins = "INSERT INTO recipe_ingredients (
            recipe_header_id, sequence_no, 
            ingredient_name_manual, quantity, unit_manual,
            item_code, item_name_system, uom_system, unit_use, purchase_unit_conversion, purchase_price, total_cost
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_ins = $pdo->prepare($sql_ins);

        foreach ($ingredients as $ing) {
            $stmt_ins->execute([
                $id,
                $ing['no'],
                $ing['name_sys'], 
                $ing['unit_use'], 
                $ing['uom'],      
                $ing['code'],
                $ing['name_sys'],
                $ing['uom'],
                $ing['unit_use'],
                $ing['conv'],
                $ing['price'],
                $ing['cost']
            ]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>