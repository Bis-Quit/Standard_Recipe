<?php
$db_file = __DIR__ . '/hotel_system.db';

try {
    // Cek apakah ini pertama kali dijalankan?
    $is_first_run = !file_exists($db_file);

    // Koneksi ke SQLite
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Jika file baru dibuat, langsung bangun struktur tabel Hotel Bintang 5
    if ($is_first_run) {
        setup_database_schema($pdo);
    }

} catch (PDOException $e) {
    die("System Error (Database): " . $e->getMessage());
}

// FUNGSI MEMBUAT STRUKTUR TABEL (Hanya jalan sekali di awal)
function setup_database_schema($pdo) {
    
    // 1. TABEL MASTER ITEMS (Sesuai CSV Master Of Item)
    // Menggunakan tipe data REAL untuk angka desimal dan TEXT untuk string
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        code TEXT PRIMARY KEY,
        name TEXT,
        blocked INTEGER DEFAULT 0,
        unit TEXT,
        inventory REAL DEFAULT 0,
        costing_method TEXT DEFAULT 'Average',
        unit_cost REAL DEFAULT 0,
        last_direct_cost REAL DEFAULT 0,
        gen_prod_post_group TEXT,
        vat_prod_post_group TEXT,
        inv_post_group TEXT,
        reorder_quantity REAL DEFAULT 0,
        system_id TEXT,
        last_modified TEXT,
        last_modified_by TEXT
    )");

    // 2. TABEL RECIPE HEADER (Sesuai Header Recipe_sandwich)
    $pdo->exec("CREATE TABLE IF NOT EXISTS recipe_headers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name_of_dish TEXT UNIQUE,
        portion_size REAL DEFAULT 1,
        portion_unit TEXT DEFAULT 'pax',
        selling_price_nett REAL DEFAULT 0,
        selling_price_plus_plus REAL DEFAULT 0,
        cost_per_portion REAL DEFAULT 0,
        nett_profit REAL DEFAULT 0,
        cost_percentage REAL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. TABEL RECIPE INGREDIENTS (Detail Bahan)
    $pdo->exec("CREATE TABLE IF NOT EXISTS recipe_ingredients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        recipe_header_id INTEGER,
        sequence_no INTEGER,
        
        -- Bagian Kiri (Input Chef)
        ingredient_name_manual TEXT,
        quantity REAL,
        unit_manual TEXT,
        
        -- Bagian Kanan (Mapping System)
        item_code TEXT,
        item_name_system TEXT,
        uom_system TEXT,
        
        -- Konversi & Harga
        unit_use REAL,
        purchase_unit_conversion REAL,
        purchase_price REAL,
        total_cost REAL,

        FOREIGN KEY(recipe_header_id) REFERENCES recipe_headers(id) ON DELETE CASCADE,
        FOREIGN KEY(item_code) REFERENCES items(code)
    )");
}
?>