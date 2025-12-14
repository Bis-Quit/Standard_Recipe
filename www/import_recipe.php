<?php
// import_recipe.php - CLEAN CORPORATE UI
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";
$messageType = "";

// --- LOGIKA HAPUS & RESET ---
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $idDel = $_GET['id'];
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_header_id = ?")->execute([$idDel]);
        $pdo->prepare("DELETE FROM recipe_headers WHERE id = ?")->execute([$idDel]);
        header("Location: import_recipe.php?msg=deleted");
        exit;
    }
    if ($_GET['action'] == 'reset_all') {
        $pdo->exec("DELETE FROM recipe_ingredients");
        $pdo->exec("DELETE FROM recipe_headers");
        $pdo->exec("VACUUM"); 
        header("Location: import_recipe.php?msg=reset_success");
        exit;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') { $message = "Resep berhasil dihapus."; $messageType = "success"; }
    if ($_GET['msg'] == 'reset_success') { $message = "SEMUA DATA RESEP BERHASIL DIKOSONGKAN."; $messageType = "success"; }
}

// --- LOGIKA IMPORT ---
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
                    // (Logika Parse Excel - SAMA SEPERTI SEBELUMNYA)
                    // ... [Disingkat agar muat, Logika ini sudah OK sebelumnya] ...
                    // GUNAKAN KODE LOGIKA IMPORT DARI FILE SEBELUMNYA
                    // HANYA BAGIAN UI HTML DI BAWAH INI YANG PENTING
                    
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
                    
                    // Financials
                    if ($current_recipe_id) {
                        if ($col_label == 'selling price (nett)') $pdo->prepare("UPDATE recipe_headers SET selling_price_nett = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        if ($col_label == 'cost per portion') $pdo->prepare("UPDATE recipe_headers SET cost_per_portion = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        if ($col_label == 'nett profit') $pdo->prepare("UPDATE recipe_headers SET nett_profit = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                        if ($col_label == 'portion') $pdo->prepare("UPDATE recipe_headers SET portion_size = ? WHERE id = ?")->execute([floatval($col_value), $current_recipe_id]);
                    }

                    // Ingredients
                    $col_0 = isset($row[0]) ? strtolower(trim($row[0])) : '';
                    $col_1 = isset($row[1]) ? strtolower(trim($row[1])) : '';
                    if ($col_0 == 'no' && $col_1 == 'ingredients') { $reading_ingredients = true; continue; }

                    if ($reading_ingredients && $current_recipe_id) {
                        if (empty($row[0]) || !is_numeric($row[0])) { if (empty($row[1])) $reading_ingredients = false; continue; }
                        $sql_ing = "INSERT INTO recipe_ingredients (recipe_header_id, sequence_no, ingredient_name_manual, quantity, unit_manual, item_code, item_name_system, uom_system, unit_use, purchase_unit_conversion, purchase_price, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt_ing = $pdo->prepare($sql_ing);
                        $price = floatval(str_replace(',', '', $row[11] ?? 0));
                        $cost = floatval(str_replace(',', '', $row[12] ?? 0));
                        $stmt_ing->execute([$current_recipe_id, $row[0], $row[1], floatval($row[2] ?? 0), $row[3] ?? '', $row[6] ?? null, $row[7] ?? null, $row[8] ?? null, floatval($row[9] ?? 0), floatval($row[10] ?? 1), $price, $cost]);
                    }
                }

                $pdo->commit();
                $message = "Sukses! Berhasil mengimport <strong>$recipes_count</strong> Resep Menu.";
                $messageType = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Gagal: " . $e->getMessage();
                $messageType = "error";
            }
        } else { $message = "Format salah. Gunakan .xlsx"; $messageType = "error"; }
    }
}

// 3. PREVIEW
$recipes = $pdo->query("SELECT * FROM recipe_headers ORDER BY id DESC LIMIT 50")->fetchAll();
$total_recipes = $pdo->query("SELECT COUNT(*) FROM recipe_headers")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Recipe</title>
    <link rel="icon" href="assets/app-logo.png" type="image/png">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    
    <div class="page-header">
        <div class="page-title">
            <div class="brand-wrapper">
                <img src="assets/app-logo.png" alt="Logo" class="app-logo">
                <div>
                    <h1>Import Recipe</h1>
                    <span>Upload File Excel • Total: <strong><?php echo number_format($total_recipes); ?></strong> Resep</span>
                </div>
            </div>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <svg class="icon" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Dashboard
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div style="background: #f8f9fa; padding: 20px; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            
            <form method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; flex:1;">
                <div style="margin-right:10px;">
                    <strong style="color:var(--primary);">Upload Resep:</strong><br>
                    <small style="color:var(--text-muted);">File .xlsx</small>
                </div>
                <input type="file" name="file" accept=".csv, .xlsx, .xls" required style="background:white; max-width:300px;">
                <button type="submit" name="upload" class="btn btn-primary">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                    Proses Import
                </button>
            </form>

            <a href="import_recipe.php?action=reset_all" class="btn btn-danger" 
               onclick="return confirm('PERINGATAN!\n\nAnda akan MENGHAPUS SEMUA DATA RESEP.\nMaster Item TIDAK akan terhapus.\n\nYakin?');">
               <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
               Hapus Semua Resep
            </a>
        </div>
    </div>

    <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:end; border-bottom:1px solid var(--border); padding-bottom:10px;">
        <h3 style="margin:0; font-size:16px; color:var(--primary);">Preview (50 Resep Terakhir)</h3>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;" class="text-center">No</th>
                    <th>Nama Menu (Dish Name)</th>
                    <th style="width:120px;" class="text-right">Cost</th>
                    <th style="width:120px;" class="text-right">Profit</th>
                    <th style="width:150px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($recipes as $r): ?>
                <tr>
                    <td class="text-center text-muted"><?php echo $no++; ?></td>
                    <td>
                        <strong style="color:var(--text-main); font-size:14px;"><?php echo htmlspecialchars($r['name_of_dish']); ?></strong>
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($r['cost_per_portion'], 2); ?>
                    </td>
                    <td class="text-right font-mono text-bold" style="color: <?php echo ($r['nett_profit'] < 0) ? 'var(--danger)' : 'var(--success)'; ?>;">
                        <?php echo number_format($r['nett_profit'], 2); ?>
                    </td>
                    <td class="text-center">
                        <div style="display:flex; justify-content:center; gap:10px;">
                            <a href="detail.php?id=<?php echo $r['id']; ?>" class="btn btn-secondary" style="padding:4px 10px; font-size:11px;" target="_blank">View</a>
                            <a href="import_recipe.php?action=delete&id=<?php echo $r['id']; ?>" 
                               onclick="return confirm('Hapus Resep ini?');" 
                               style="color:var(--danger); text-decoration:none; font-weight:bold; font-size:16px; padding-top:2px;">
                               ✕
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if(empty($recipes)): ?>
                <tr><td colspan="5" class="text-center" style="padding:40px; color:var(--text-muted);">Belum ada data resep.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>