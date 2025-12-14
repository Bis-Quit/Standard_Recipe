<?php
// index.php - FINAL DASHBOARD: With Add, Duplicate, Delete Features
require 'db.php';

// Fitur Pencarian
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM recipe_headers";
if ($search) {
    $sql .= " WHERE name_of_dish LIKE :search";
}
$sql .= " ORDER BY name_of_dish ASC"; 

$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->execute([':search' => "%$search%"]);
} else {
    $stmt->execute();
}
$recipes = $stmt->fetchAll();

$total_menu = count($recipes);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Standard Recipe Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 20px; border: 1px solid #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .app-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #333; margin-bottom: 20px; }
        .app-title h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .app-title span { color: #777; font-size: 12px; }

        .toolbar { display: flex; gap: 10px; background: #eee; padding: 10px; border: 1px solid #ccc; margin-bottom: 20px; align-items: center; }
        
        .btn-icon {
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 8px 15px; 
            border: 1px solid #999; background: #fff; color: #333; font-size: 12px; font-weight: bold; cursor: pointer; transition: all 0.2s;
        }
        .btn-icon:hover { background: #e0e0e0; border-color: #666; }
        
        /* Highlight Add Button */
        .btn-new { background-color: #007bff; color: white; border-color: #0056b3; }
        .btn-new:hover { background-color: #0056b3; color: white; }
        .btn-new .icon { fill: white; }

        .icon { width: 16px; height: 16px; fill: currentColor; }

        .search-box { display: flex; gap: 5px; }
        .search-input { padding: 8px; border: 1px solid #999; font-size: 13px; width: 300px; }
        
        table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px; }
        th { background: #333; color: #fff; padding: 12px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background-color: #f9f9f9; }
        
        /* Action Buttons in Table */
        .action-group { display: flex; gap: 5px; justify-content: center; }
        
        .btn-action {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 4px; border: 1px solid #ccc;
            background: #fff; color: #555; text-decoration: none; transition: 0.2s;
        }
        .btn-action:hover { background: #eee; border-color: #999; color: #000; }
        
        /* Specific Colors */
        .btn-edit:hover { background: #e6f7ff; border-color: #007bff; color: #007bff; }
        .btn-copy:hover { background: #fff8e6; border-color: #ffc107; color: #d39e00; }
        .btn-del:hover { background: #ffe6e6; border-color: #dc3545; color: #dc3545; }

        .empty-state { text-align: center; padding: 50px; color: #999; }
    </style>
</head>
<body>

<div class="container">
    <div class="app-header">
        <div class="app-title">
            <h1>Recipe Database</h1>
            <span>Recipe Management System V.1.0</span>
        </div>
        <div style="font-size: 14px;">
            <strong>Total Menu:</strong> <?php echo $total_menu; ?> Items
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        
        <div class="toolbar" style="margin-bottom:0;">
            <a href="menu_actions.php?action=create" class="btn-icon btn-new">
                <svg class="icon" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                ADD NEW RECIPE
            </a>

            <a href="import_master.php" class="btn-icon">
                <svg class="icon" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                Master Item
            </a>
            <a href="import_recipe.php" class="btn-icon">
                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Import Recipe
            </a>
            <a href="index.php" class="btn-icon">
                <svg class="icon" viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                Refresh
            </a>
        </div>

        <form method="get" class="search-box">
            <input type="text" name="q" class="search-input" placeholder="Search Menu Name..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-icon" style="background:#333; color:#fff; border:1px solid #000;">
                <svg class="icon" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                Cari
            </button>
            <?php if($search): ?>
                <a href="index.php" style="margin-left:10px; color:#cc0000; text-decoration:none; display:flex; align-items:center;">&times; Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <table border="0">
        <thead>
            <tr>
                <th style="width:50px; text-align:center;">No</th>
                <th style="text-align:left;">Menu Name (Dish)</th>
                <th style="width:120px; text-align:center;">Portion</th>
                <th style="width:150px; text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($recipes as $index => $r): ?>
            <tr>
                <td style="text-align:center; color:#777;"><?php echo $index + 1; ?></td>
                <td>
                    <strong style="font-size:14px; color:#000;"><?php echo htmlspecialchars($r['name_of_dish']); ?></strong>
                </td>
                <td style="text-align:center; font-weight:bold; color:#555;">
                    <?php echo $r['portion_size'] . ' ' . $r['portion_unit']; ?>
                </td>
                <td style="text-align:center;">
                    <div class="action-group">
                        <a href="detail.php?id=<?php echo $r['id']; ?>" class="btn-action btn-edit" title="Edit Recipe">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </a>
                        
                        <a href="menu_actions.php?action=duplicate&id=<?php echo $r['id']; ?>" class="btn-action btn-copy" title="Duplicate Menu" onclick="return confirm('Duplicate this menu?');">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                        </a>

                        <a href="menu_actions.php?action=delete&id=<?php echo $r['id']; ?>" class="btn-action btn-del" title="Delete Menu" onclick="return confirm('Are you sure want to delete this menu permanently?');">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(empty($recipes)): ?>
            <tr>
                <td colspan="4" class="empty-state">
                    <svg class="icon" viewBox="0 0 24 24" style="width:48px; height:48px; fill:#ddd; margin-bottom:10px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    <br>
                    Data Recipe Kosong.<br>
                    Silakan klik "ADD NEW RECIPE" atau Upload Excel.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>