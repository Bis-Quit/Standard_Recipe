<?php
// index.php - CLEAN CORPORATE LOOK
require 'db.php';

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
            <h1>Dashboard Resep</h1>
            <span>Kitchen Management System</span>
        </div>
    </div>
</div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
        <div style="display:flex; gap:10px;">
            <a href="menu_actions.php?action=create" class="btn btn-primary">
                <svg class="icon" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Buat Resep Baru
            </a>
            <a href="import_master.php" class="btn btn-secondary">
                <svg class="icon" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                Master Item
            </a>
            <a href="import_recipe.php" class="btn btn-secondary">
                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Import Excel
            </a>
        </div>

        <form method="get" style="display:flex; gap:5px;">
            <input type="text" name="q" placeholder="Cari Nama Menu..." value="<?php echo htmlspecialchars($search); ?>" style="width:250px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if($search): ?>
                <a href="index.php" class="btn btn-danger" title="Reset">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;" class="text-center">No</th>
                    <th>Nama Menu (Dish Name)</th>
                    <th style="width:150px;" class="text-center">Porsi</th>
                    <th style="width:150px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recipes as $index => $r): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td>
                        <a href="detail.php?id=<?php echo $r['id']; ?>" style="font-weight:bold; color:var(--text-main); text-decoration:none;">
                            <?php echo htmlspecialchars($r['name_of_dish']); ?>
                        </a>
                    </td>
                    <td class="text-center">
                        <?php echo $r['portion_size'] . ' pax'; ?>
                    </td>
                    <td class="text-center">
                        <div style="display:flex; justify-content:center; gap:5px;">
                            <a href="detail.php?id=<?php echo $r['id']; ?>" class="btn btn-secondary" style="padding:5px 10px;" title="Edit">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </a>
                            <a href="menu_actions.php?action=duplicate&id=<?php echo $r['id']; ?>" class="btn btn-secondary" style="padding:5px 10px;" title="Duplicate" onclick="return confirm('Copy menu ini?');">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                            </a>
                            <a href="menu_actions.php?action=delete&id=<?php echo $r['id']; ?>" class="btn btn-danger" style="padding:5px 10px;" title="Hapus" onclick="return confirm('Yakin hapus menu ini?');">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($recipes)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="padding:40px; color:var(--text-muted);">
                        Belum ada data resep. Klik "Buat Resep Baru" untuk memulai.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>