<?php
// import_recipe.php - FINAL UI: With Numbering (No ID)
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";
$messageType = "";

// ==========================================
// 1. LOGIKA HAPUS & RESET
// ==========================================

// A. Hapus Satu Resep
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $idDel = $_GET['id'];
    try {
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_header_id = ?")->execute([$idDel]);
        $pdo->prepare("DELETE FROM recipe_headers WHERE id = ?")->execute([$idDel]);
        
        header("Location: import_recipe.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $message = "Gagal hapus: " . $e->getMessage();
        $messageType = "error";
    }
}

// B. Reset Semua Resep
if (isset($_GET['action']) && $_GET['action'] == 'reset_all') {
    try {
        $pdo->exec("DELETE FROM recipe_ingredients");
        $pdo->exec("DELETE FROM recipe_headers");
        $pdo->exec("VACUUM"); 
        
        header("Location: import_recipe.php?msg=reset_success");
        exit;
    } catch (Exception $e) {
        $message = "Gagal reset: " . $e->getMessage();
        $messageType = "error";
    }
}

// C. Tangkap Pesan Redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $message = "Resep berhasil dihapus.";
        $messageType = "success";
    } elseif ($_GET['msg'] == 'reset_success') {
        $message = "SEMUA DATA RESEP BERHASIL DIKOSONGKAN.";
        $messageType = "success";
    }
}

// ==========================================
// 2. LOGIKA IMPORT
// ==========================================
if (isset($_POST['upload'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, ['csv', 'xls', 'xlsx'])) {
            try {
                $spreadsheet = IOFactory::load($file_tmp);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                $pdo->beginTransaction();

                $current_recipe_id = null;
                $reading_ingredients = false;
                $recipes_count = 0;

                foreach ($rows as $index => $row) {
                    
                    // A. HEADER RESEP
                    $col_label = isset($row[7]) ? strtolower(trim($row[7])) : '';
                    $col_value = isset($row[8]) ? trim($row[8]) : '';

                    if ($col_label == 'name of dish' && !empty($col_value)) {
                        $dishName = $col_value;
                        $reading_ingredients = false; 

                        $del = $pdo->prepare("DELETE FROM recipe_headers WHERE name_of_dish = ?");
                        $del->execute([$dishName]);

                        $stmt = $pdo->prepare("INSERT INTO recipe_headers (name_of_dish, created_at) VALUES (?, ?)");
                        $stmt->execute([$dishName, date('Y-m-d H:i:s')]);
                        $current_recipe_id = $pdo->lastInsertId();
                        $recipes_count++;
                        continue; 
                    }

                    // B. FINANCIAL DATA
                    if ($current_recipe_id) {
                        if ($col_label == 'selling price (nett)') {
                            $pdo->prepare("UPDATE recipe_headers SET selling_price_nett = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        }
                        if ($col_label == 'cost per portion') {
                             $pdo->prepare("UPDATE recipe_headers SET cost_per_portion = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        }
                        if ($col_label == 'nett profit') {
                             $pdo->prepare("UPDATE recipe_headers SET nett_profit = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        }
                        if ($col_label == 'portion') {
                             $pdo->prepare("UPDATE recipe_headers SET portion_size = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        }
                    }

                    // C. DETEKSI TABEL BAHAN
                    $col_0 = isset($row[0]) ? strtolower(trim($row[0])) : '';
                    $col_1 = isset($row[1]) ? strtolower(trim($row[1])) : '';

                    if ($col_0 == 'no' && $col_1 == 'ingredients') {
                        $reading_ingredients = true;
                        continue; 
                    }

                    // D. BACA BAHAN
                    if ($reading_ingredients && $current_recipe_id) {
                        if (empty($row[0]) || !is_numeric($row[0])) {
                            if (empty($row[1])) $reading_ingredients = false;
                            continue;
                        }
                        
                        $sql_ing = "INSERT INTO recipe_ingredients (
                            recipe_header_id, sequence_no, 
                            ingredient_name_manual, quantity, unit_manual,
                            item_code, item_name_system, uom_system,
                            unit_use, purchase_unit_conversion, purchase_price, total_cost
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmt_ing = $pdo->prepare($sql_ing);
                        $price = floatval(str_replace(',', '', $row[11] ?? 0));
                        $cost = floatval(str_replace(',', '', $row[12] ?? 0));
                        
                        $stmt_ing->execute([
                            $current_recipe_id,
                            $row[0], 
                            $row[1], 
                            floatval($row[2] ?? 0), 
                            $row[3] ?? '', 
                            $row[6] ?? null, 
                            $row[7] ?? null, 
                            $row[8] ?? null, 
                            floatval($row[9] ?? 0), 
                            floatval($row[10] ?? 1), 
                            $price,
                            $cost
                        ]);
                    }
                }

                $pdo->commit();
                $message = "Sukses! Berhasil mengimport <strong>$recipes_count</strong> Resep Menu.";
                $messageType = "success";

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Gagal Import: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Format file salah. Harap upload .xlsx atau .csv";
            $messageType = "error";
        }
    }
}

// 3. DATA PREVIEW
$recipes = $pdo->query("SELECT * FROM recipe_headers ORDER BY id DESC LIMIT 50")->fetchAll();
$total_recipes = $pdo->query("SELECT COUNT(*) FROM recipe_headers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Recipe Data</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f4f4; padding: 20px; font-family: Arial, sans-serif; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .page-title h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .page-title span { color: #777; font-size: 14px; }

        .upload-section { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .file-input { border: 1px solid #ccc; padding: 5px; background: #fff; }
        .btn-upload { background: #007bff; color: #fff; border: none; padding: 8px 15px; cursor: pointer; font-weight: bold; border-radius: 3px; }
        .btn-upload:hover { background: #0056b3; }
        
        .btn-reset { 
            background: #dc3545; color: #fff; border: none; padding: 8px 15px; 
            cursor: pointer; font-weight: bold; border-radius: 3px; text-decoration: none; font-size: 12px;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-reset:hover { background: #c82333; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #333; color: #fff; padding: 10px; text-align: left; text-transform: uppercase; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:hover { background-color: #f0f8ff; }
        
        .btn-back { text-decoration: none; color: #333; font-weight: bold; display: flex; align-items: center; gap: 5px; background: #eee; padding: 8px 15px; border-radius: 4px; border: 1px solid #ccc; }
        .icon { width: 18px; height: 18px; fill: currentColor; }
        
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }

        .btn-del { color: #ccc; text-decoration: none; font-size: 16px; font-weight: bold; padding: 0 5px; }
        .btn-del:hover { color: #dc3545; transform: scale(1.2); }
        .btn-view { color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Import Recipe Excel</h1>
            <span>Upload File (Multi-block)</span>
        </div>
        <a href="index.php" class="btn-back">
            <svg class="icon" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Dashboard
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="upload-section">
        <form method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
            <div><strong>Upload File:</strong><br><small style="color:#666;">.xlsx (Recipe_Sandwich.xlsx)</small></div>
            <input type="file" name="file" class="file-input" accept=".csv, .xlsx, .xls" required>
            <button type="submit" name="upload" class="btn-upload">PROSES IMPORT</button>
        </form>

        <a href="import_recipe.php?action=reset_all" class="btn-reset" 
           onclick="return confirm('PERINGATAN!\n\nAnda akan MENGHAPUS SEMUA DATA RESEP.\nMaster Item TIDAK akan terhapus.\n\nYakin kosongkan resep?');">
           <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
           HAPUS SEMUA RESEP
        </a>
    </div>

    <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:end;">
        <h3 style="margin:0;">Preview Data Resep (<?php echo $total_recipes; ?> Item)</h3>
        <span style="font-size:11px; color:#666;">50 Data Terakhir</span>
    </div>

    <table border="0">
        <thead>
            <tr>
                <th style="width:50px; text-align:center;">NO</th> <th>MENU NAME</th>
                <th style="width:120px; text-align:right;">COST</th>
                <th style="width:120px; text-align:right;">PROFIT</th>
                <th style="width:100px; text-align:center;">ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Inisialisasi Nomor
            $no = 1; 
            foreach($recipes as $r): 
            ?>
            <tr>
                <td style="text-align:center; color:#888;"><?php echo $no++; ?></td>
                
                <td>
                    <strong style="font-size:13px;"><?php echo htmlspecialchars($r['name_of_dish']); ?></strong>
                </td>
                <td style="text-align:right; font-family:monospace;">
                    <?php echo number_format($r['cost_per_portion'], 2); ?>
                </td>
                <td style="text-align:right; font-family:monospace; font-weight:bold; color: <?php echo ($r['nett_profit'] < 0) ? 'red' : 'green'; ?>;">
                    <?php echo number_format($r['nett_profit'], 2); ?>
                </td>
                <td style="text-align:center;">
                    <a href="detail.php?id=<?php echo $r['id']; ?>" class="btn-view" target="_blank">View</a>
                    &nbsp;|&nbsp;
                    <a href="import_recipe.php?action=delete&id=<?php echo $r['id']; ?>" 
                       class="btn-del" 
                       onclick="return confirm('Hapus Resep: <?php echo $r['name_of_dish']; ?>?');">
                       &times;
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(empty($recipes)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding:40px; color:#999;">
                    Belum ada resep yang di-import.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>