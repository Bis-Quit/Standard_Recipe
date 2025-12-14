<?php
// update_db.php - Script sekali jalan untuk update struktur tabel
require 'db.php';

try {
    // Tambah kolom 'variable_percent' ke tabel header
    // Default 10%
    $pdo->exec("ALTER TABLE recipe_headers ADD COLUMN variable_percent REAL DEFAULT 10");
    echo "<h1>SUKSES!</h1> Database berhasil di-update. Kolom persen sudah ditambahkan.";
} catch (PDOException $e) {
    echo "<h1>Info:</h1> " . $e->getMessage();
    echo "<br>Kemungkinan kolom sudah ada. Aman untuk lanjut.";
}
?>