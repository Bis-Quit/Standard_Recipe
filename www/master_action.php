<?php
// master_actions.php - Controller Hapus & Reset
require 'db.php';

$action = $_GET['action'] ?? '';
$code = $_GET['code'] ?? '';

try {
    // 1. HAPUS SATU ITEM
    if ($action == 'delete' && $code) {
        // Hapus berdasarkan kode
        $stmt = $pdo->prepare("DELETE FROM items WHERE code = ?");
        $stmt->execute([$code]);
        
        // Cek apakah ada baris yang terhapus
        if ($stmt->rowCount() > 0) {
            header("Location: import_master.php?msg=deleted");
        } else {
            // Kalau gagal (misal kode beda spasi/huruf), coba trim
            $stmt = $pdo->prepare("DELETE FROM items WHERE TRIM(code) = ?");
            $stmt->execute([trim($code)]);
            header("Location: import_master.php?msg=deleted");
        }
        exit;
    }

    // 2. RESET TOTAL (HAPUS SEMUA)
    if ($action == 'reset_all') {
        // Kosongkan tabel items
        $pdo->exec("DELETE FROM items");
        
        // Reset Resep juga? Jangan dong, nanti resepnya error.
        // Cukup hapus master itemnya saja.
        
        header("Location: import_master.php?msg=reset_success");
        exit;
    }

} catch (Exception $e) {
    die("Error Database: " . $e->getMessage());
}
?>