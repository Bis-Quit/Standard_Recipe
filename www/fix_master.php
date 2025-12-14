<?php
// fix_master.php - Versi Anti-Error SQLite
require 'db.php';

try {
    // 1. Tambah kolom tanpa default value aneh-aneh (biar SQLite gak marah)
    // Kita biarkan NULL dulu
    $pdo->exec("ALTER TABLE items ADD COLUMN last_updated DATETIME");
    
    // 2. Isi kolom yang baru dibuat dengan tanggal hari ini (biar gak kosong melompong)
    $now = date('Y-m-d H:i:s');
    $pdo->exec("UPDATE items SET last_updated = '$now' WHERE last_updated IS NULL");

    echo "<h1>SUKSES!</h1> Kolom 'last_updated' berhasil ditambahkan.";
    echo "<br><a href='import_master.php'>Klik di sini untuk kembali ke Import Master</a>";

} catch (PDOException $e) {
    // Kalau errornya "duplicate column name", berarti sebenarnya sudah ada.
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "<h1>Sudah Beres!</h1> Kolom sudah ada sebelumnya. Aman.";
        echo "<br><a href='import_master.php'>Lanjut ke Import Master</a>";
    } else {
        echo "<h1>Info:</h1> " . $e->getMessage();
        echo "<br>Coba refresh halaman import_master.php, kemungkinan sudah berhasil.";
    }
}
?>