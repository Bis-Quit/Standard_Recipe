<?php
// import_master.php - FINAL "ALL-IN-ONE" (Upload, Search, Sort, & DELETE Works!)
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";
$messageType = "";

// ==========================================
// 1. LOGIKA HAPUS & RESET (Ditaruh Paling Atas)
// ==========================================

// A. Hapus Satu Item
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['code'])) {
    $codeToDelete = $_GET['code'];
    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE code = ?");
        $stmt->execute([$codeToDelete]);
        
        // Redirect agar URL bersih kembali
        header("Location: import_master.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $message = "Gagal menghapus: " . $e->getMessage();
        $messageType = "error";
    }
}

// B. Reset Database (Hapus Semua)
if (isset($_GET['action']) && $_GET['action'] == 'reset_all') {
    try {
        $pdo->exec("DELETE FROM items");
        // Reset Auto Increment / Vacuum (Optional for SQLite)
        $pdo->exec("VACUUM");
        
        header("Location: import_master.php?msg=reset_success");
        exit;
    } catch (Exception $e) {
        $message = "Gagal reset: " . $e->getMessage();
        $messageType = "error";
    }
}

// C. Tangkap Pesan Sukses setelah Redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $message = "Item berhasil dihapus permanen.";
        $messageType = "success";
    } elseif ($_GET['msg'] == 'reset_success') {
        $message = "Database Master Item BERHASIL DIKOSONGKAN.";
        $messageType = "success";
    }
}

// ==========================================
// 2. LOGIKA UPLOAD / IMPORT
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
                $sql = "INSERT OR REPLACE INTO items (code, name, unit, unit_cost, last_direct_cost, last_updated) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $count = 0; $is_header = true;
                foreach ($rows as $row) {
                    if ($is_header) {
                        if (strtolower(trim($row[0])) == 'code' || strtolower(trim($row[0])) == 'no') { $is_header = false; continue; }
                        $is_header = false; 
                    }
                    if (empty($row[0])) continue;

                    $code = trim($row[0]);
                    $name = trim($row[1]);
                    $unit = strtoupper(trim($row[3])); 
                    $cost_avg = floatval(str_replace(',', '', $row[6] ?? 0)); 
                    $cost_last = floatval(str_replace(',', '', $row[7] ?? 0)); 

                    $stmt->execute([$code, $name, $unit, $cost_avg, $cost_last, date('Y-m-d H:i:s')]);
                    $count++;
                }
                $pdo->commit();
                $message = "Sukses! $count data berhasil di-upload/update.";
                $messageType = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Format file salah. Gunakan Excel (.xlsx) atau CSV.";
            $messageType = "error";
        }
    }
}

// ==========================================
// 3. LOGIKA TAMPILAN & SORTING
// ==========================================
$search = $_GET['q'] ?? '';
$show_all = isset($_GET['show_all']);
$limit_default = 200;

$sort_col = $_GET['sort'] ?? 'last_updated';
$sort_order = $_GET['order'] ?? 'DESC';

$allowed_cols = ['code', 'name', 'unit', 'last_direct_cost', 'unit_cost', 'last_updated'];
if (!in_array($sort_col, $allowed_cols)) { $sort_col = 'last_updated'; }
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') { $sort_order = 'DESC'; }

function getSortLink($col, $label, $currentCol, $currentOrder, $search, $show_all) {
    $newOrder = ($currentCol == $col && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $arrow = ($currentCol == $col) ? ($currentOrder == 'ASC' ? ' ▲' : ' ▼') : '';
    $params = ['sort' => $col, 'order' => $newOrder];
    if($search) $params['q'] = $search;
    if($show_all) $params['show_all'] = 1;
    $url = "?" . http_build_query($params);
    $style = ($currentCol == $col) ? "color:black; text-decoration:underline;" : "color:gray; text-decoration:none;";
    return "<a href='$url' style='$style'>$label$arrow</a>";
}

if ($search) {
    $sql = "SELECT * FROM items WHERE code LIKE ? OR name LIKE ? ORDER BY $sort_col $sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", "%$search%"]);
    $items = $stmt->fetchAll();
    $view_label = "Hasil: " . number_format(count($items)) . " item";
} elseif ($show_all) {
    $items = $pdo->query("SELECT * FROM items ORDER BY $sort_col $sort_order")->fetchAll();
    $view_label = "Menampilkan SEMUA DATA (" . number_format(count($items)) . ")";
} else {
    $items = $pdo->query("SELECT * FROM items ORDER BY $sort_col $sort_order LIMIT $limit_default")->fetchAll();
    $view_label = "Menampilkan $limit_default Item Terupdate";
}

$total_db = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Item Database</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f4f4; padding: 20px; font-family: Arial, sans-serif; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .page-title h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .page-title span { color: #777; font-size: 14px; }

        .upload-section { 
            background: #f9f9f9; border: 1px solid #ddd; padding: 20px; 
            border-radius: 5px; margin-bottom: 30px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .file-input { border: 1px solid #ccc; padding: 5px; background: #fff; }
        .btn-upload { background: #007bff; color: #fff; border: none; padding: 8px 15px; cursor: pointer; font-weight: bold; border-radius: 3px; }
        .btn-upload:hover { background: #0056b3; }
        
        .btn-reset { 
            background: #dc3545; color: #fff; border: none; padding: 8px 15px; 
            cursor: pointer; font-weight: bold; border-radius: 3px; text-decoration: none; font-size: 12px;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-reset:hover { background: #c82333; }

        .search-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #eee; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
        .search-box { display: flex; gap: 5px; flex: 1; }
        .search-input { padding: 8px; border: 1px solid #999; width: 100%; max-width: 400px; font-size: 14px; }
        .btn-search { background: #333; color: white; border: 1px solid #000; padding: 8px 15px; cursor: pointer; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #f0f0f0; border-bottom: 2px solid #999; padding: 10px; text-align: left; text-transform: uppercase; position: sticky; top: 0; z-index: 10; }
        th a { color: #555; text-decoration: none; display: block; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:hover { background-color: #f0f8ff; }
        
        .table-wrapper { max-height: 600px; overflow-y: auto; border: 1px solid #ccc; }
        .btn-back { text-decoration: none; color: #333; font-weight: bold; display: flex; align-items: center; gap: 5px; background: #eee; padding: 8px 15px; border-radius: 4px; border: 1px solid #ccc; }
        
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }

        .btn-del { color: #ccc; text-decoration: none; font-size: 16px; font-weight: bold; padding: 0 5px; }
        .btn-del:hover { color: #dc3545; transform: scale(1.2); }
        .show-all-link { color: #007bff; text-decoration: none; font-size: 12px; border-bottom: 1px dashed #007bff; }
        
        .icon { width: 18px; height: 18px; fill: currentColor; }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Master Item Database</h1>
            <span>Total: <strong><?php echo number_format($total_db); ?></strong> Items</span>
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
            <div><strong>Upload Data:</strong><br><small style="color:#666;">.xlsx / .csv</small></div>
            <input type="file" name="file" class="file-input" accept=".csv, .xlsx, .xls" required>
            <button type="submit" name="upload" class="btn-upload">UPLOAD</button>
        </form>

        <a href="import_master.php?action=reset_all" class="btn-reset" 
           onclick="return confirm('PERINGATAN!\n\nAnda akan MENGHAPUS SEMUA DATA Master Item.\nTindakan ini tidak bisa dibatalkan.\n\nYakin?');">
           <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
           RESET DATABASE
        </a>
    </div>

    <div class="search-container">
        <form method="get" class="search-box">
            <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
            <input type="text" name="q" class="search-input" placeholder="Cari Kode atau Nama Barang..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">CARI</button>
            <?php if($search || $show_all): ?>
                <a href="import_master.php" style="padding: 8px; color: red; text-decoration: none; font-weight: bold;">[Reset]</a>
            <?php endif; ?>
        </form>
        <div style="text-align:right;">
            <span style="font-size:12px; font-weight:bold; color:#333;"><?php echo $view_label; ?></span><br>
            <?php if(!$search && !$show_all): ?>
                <a href="import_master.php?show_all=1&sort=<?php echo $sort_col; ?>&order=<?php echo $sort_order; ?>" class="show-all-link">Lihat Semua Data</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrapper">
        <table border="0">
            <thead>
                <tr>
                    <th style="width:120px;"><?php echo getSortLink('code', 'CODE', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th><?php echo getSortLink('name', 'ITEM NAME', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th style="width:60px; text-align:center;"><?php echo getSortLink('unit', 'UNIT', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th style="width:130px; text-align:right;"><?php echo getSortLink('last_direct_cost', 'LAST COST', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th style="width:130px; text-align:right;"><?php echo getSortLink('unit_cost', 'AVG COST', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th style="width:120px; text-align:center;"><?php echo getSortLink('last_updated', 'UPDATED', $sort_col, $sort_order, $search, $show_all); ?></th>
                    <th style="width:50px; text-align:center;">ACT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td style="font-family:monospace; font-weight:bold; color:#007bff;"><?php echo htmlspecialchars($item['code']); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td style="text-align:center; font-weight:bold;"><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td style="text-align:right; font-family:monospace; font-weight:bold; <?php echo ($item['last_direct_cost'] == 0) ? 'color:red;' : ''; ?>">
                        <?php echo number_format($item['last_direct_cost'], 2); ?>
                    </td>
                    <td style="text-align:right; font-family:monospace; color:#666;">
                        <?php echo number_format($item['unit_cost'], 2); ?>
                    </td>
                    <td style="text-align:center; font-size:11px; color:#888;">
                        <?php echo date('d-M-Y', strtotime($item['last_updated'])); ?>
                    </td>
                    <td style="text-align:center;">
                        <a href="import_master.php?action=delete&code=<?php echo urlencode($item['code']); ?>" 
                           class="btn-del" 
                           onclick="return confirm('Hapus Item: <?php echo $item['name']; ?>?');"
                           title="Hapus">
                           &times;
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($items)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">Tidak ada data.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>