<?php
// import_master.php - CLEAN CORPORATE UI
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";
$messageType = "";

// --- 1. LOGIKA HAPUS & RESET ---
if (isset($_GET['action'])) {
    try {
        if ($_GET['action'] == 'delete' && isset($_GET['code'])) {
            $stmt = $pdo->prepare("DELETE FROM items WHERE code = ?");
            $stmt->execute([$_GET['code']]);
            header("Location: import_master.php?msg=deleted");
            exit;
        }
        if ($_GET['action'] == 'reset_all') {
            $pdo->exec("DELETE FROM items");
            $pdo->exec("VACUUM");
            header("Location: import_master.php?msg=reset_success");
            exit;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Pesan Redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') { $message = "Item berhasil dihapus."; $messageType = "success"; }
    if ($_GET['msg'] == 'reset_success') { $message = "Database Master BERHASIL DIKOSONGKAN."; $messageType = "success"; }
}

// --- 2. LOGIKA UPLOAD ---
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
                $message = "Sukses mengimport <strong>$count</strong> data!";
                $messageType = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Gagal: " . $e->getMessage();
                $messageType = "error";
            }
        } else { $message = "Format file salah."; $messageType = "error"; }
    }
}

// --- 3. VIEW DATA ---
$search = $_GET['q'] ?? '';
$show_all = isset($_GET['show_all']);
$limit_default = 200;

$sort_col = $_GET['sort'] ?? 'last_updated';
$sort_order = $_GET['order'] ?? 'DESC';
// Whitelist sort col
if(!in_array($sort_col, ['code','name','unit','last_direct_cost','unit_cost','last_updated'])) $sort_col='last_updated';

function getSortLink($col, $label, $curCol, $curOrd) {
    global $search, $show_all;
    $newOrd = ($curCol == $col && $curOrd == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($curCol == $col) ? ($curOrd == 'ASC' ? ' ▲' : ' ▼') : '';
    $url = "?sort=$col&order=$newOrd" . ($search ? "&q=$search" : "") . ($show_all ? "&show_all=1" : "");
    return "<a href='$url'>$label$icon</a>";
}

if ($search) {
    $sql = "SELECT * FROM items WHERE code LIKE ? OR name LIKE ? ORDER BY $sort_col $sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", "%$search%"]);
    $items = $stmt->fetchAll();
    $view_label = "Hasil Pencarian: " . count($items) . " item";
} elseif ($show_all) {
    $items = $pdo->query("SELECT * FROM items ORDER BY $sort_col $sort_order")->fetchAll();
    $view_label = "Menampilkan SEMUA DATA";
} else {
    $items = $pdo->query("SELECT * FROM items ORDER BY $sort_col $sort_order LIMIT $limit_default")->fetchAll();
    $view_label = "Menampilkan $limit_default Data Terupdate";
}
$total_db = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Item Database</title>
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
                    <h1>Master Data</h1>
                    <span>Database Bahan Baku • Total: <strong><?php echo number_format($total_db); ?></strong> Item</span>
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
                    <strong style="color:var(--primary);">Upload Update:</strong><br>
                    <small style="color:var(--text-muted);">File Excel (.xlsx)</small>
                </div>
                <input type="file" name="file" accept=".csv, .xlsx, .xls" required style="background:white; max-width:300px;">
                <button type="submit" name="upload" class="btn btn-primary">
                    <svg class="icon" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                    Upload Data
                </button>
            </form>

            <a href="import_master.php?action=reset_all" class="btn btn-danger" 
               onclick="return confirm('PERINGATAN KERAS!\n\nAnda akan MENGHAPUS SEMUA DATA Master Item.\n\nYakin?');">
               <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
               Reset Database
            </a>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <form method="get" style="display:flex; gap:5px;">
            <input type="hidden" name="sort" value="<?php echo $sort_col; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
            <input type="text" name="q" placeholder="Cari Kode / Nama..." value="<?php echo htmlspecialchars($search); ?>" style="width:250px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if($search || $show_all): ?>
                <a href="import_master.php" class="btn btn-secondary">Reset Filter</a>
            <?php endif; ?>
        </form>
        
        <div class="text-right">
            <span style="font-size:12px; color:var(--text-muted); font-weight:bold;"><?php echo $view_label; ?></span><br>
            <?php if(!$search && !$show_all): ?>
                <a href="import_master.php?show_all=1" style="font-size:12px; color:var(--accent);">Lihat Semua Data</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:120px;"><?php echo getSortLink('code', 'Code', $sort_col, $sort_order); ?></th>
                    <th><?php echo getSortLink('name', 'Item Name', $sort_col, $sort_order); ?></th>
                    <th style="width:60px;" class="text-center"><?php echo getSortLink('unit', 'Unit', $sort_col, $sort_order); ?></th>
                    <th style="width:130px;" class="text-right"><?php echo getSortLink('last_direct_cost', 'Last Cost', $sort_col, $sort_order); ?></th>
                    <th style="width:130px;" class="text-right"><?php echo getSortLink('unit_cost', 'Avg Cost', $sort_col, $sort_order); ?></th>
                    <th style="width:120px;" class="text-center"><?php echo getSortLink('last_updated', 'Updated', $sort_col, $sort_order); ?></th>
                    <th style="width:50px;" class="text-center">Act</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td class="font-mono text-bold" style="color:var(--accent);"><?php echo htmlspecialchars($item['code']); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-center text-bold"><?php echo htmlspecialchars($item['unit']); ?></td>
                    
                    <td class="text-right font-mono" style="<?php echo ($item['last_direct_cost'] == 0) ? 'color:var(--danger);' : ''; ?>">
                        <?php echo number_format($item['last_direct_cost'], 2); ?>
                    </td>
                    
                    <td class="text-right font-mono" style="color:var(--text-muted);">
                        <?php echo number_format($item['unit_cost'], 2); ?>
                    </td>
                    <td class="text-center" style="font-size:11px; color:var(--text-muted);">
                        <?php echo date('d-M-y', strtotime($item['last_updated'])); ?>
                    </td>
                    <td class="text-center">
                        <a href="import_master.php?action=delete&code=<?php echo urlencode($item['code']); ?>" 
                           onclick="return confirm('Hapus Item?');" title="Hapus" 
                           style="color:var(--danger); text-decoration:none; font-weight:bold; font-size:16px;">
                           ✕
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($items)): ?>
                <tr><td colspan="7" class="text-center" style="padding:30px; color:var(--text-muted);">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>