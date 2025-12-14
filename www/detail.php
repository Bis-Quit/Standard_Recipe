<?php
// detail.php - FINAL FIX: EMPTY STATE HANDLER (Bisa Tambah Baris di Resep Kosong)
require 'db.php';

$id = $_GET['id'] ?? 0;

// Header
$stmt = $pdo->prepare("SELECT * FROM recipe_headers WHERE id = ?");
$stmt->execute([$id]);
$header = $stmt->fetch();
if (!$header) die("Resep tidak ditemukan.");

// Default Values
$variablePercent = $header['variable_percent'] ?? 10;
$portionSize = $header['portion_size'] > 0 ? $header['portion_size'] : 1;
$priceNett = $header['selling_price_nett'] ?? 0;
$pricePlus = $header['selling_price_plus_plus'] ?? 0;

// Bahan
$stmt_ing = $pdo->prepare("SELECT * FROM recipe_ingredients WHERE recipe_header_id = ? ORDER BY sequence_no ASC");
$stmt_ing->execute([$id]);
$ingredients = $stmt_ing->fetchAll();

// Master Data
$master_items = $pdo->query("SELECT code, name FROM items ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit: <?php echo htmlspecialchars($header['name_of_dish']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f4f4; padding: 20px; font-family: Arial, sans-serif; }
        .container { max-width: 1300px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        /* --- BUTTONS & ICONS --- */
        .btn { 
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 8px 15px; 
            border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; transition: 0.2s; border: 1px solid transparent;
        }
        .btn-back { background: #eee; color: #333; border-color: #ccc; }
        .btn-back:hover { background: #ddd; }
        .btn-export { background: #28a745; color: #fff; }
        .btn-export:hover { background: #218838; }
        .btn-save { background: #007bff; color: #fff; }
        .btn-save:hover { background: #0069d9; }
        .icon { width: 16px; height: 16px; fill: currentColor; }

        /* --- LAYOUT HEADER --- */
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header-left { flex: 1; padding-right: 30px; }
        .header-right { width: 420px; }

        input.headline-input { 
            font-size: 24px; font-weight: bold; text-transform: uppercase; color: #333;
            border: none; border-bottom: 2px solid #ccc; width: 100%; padding: 5px 0; background: transparent;
        }
        input.headline-input:focus { border-bottom: 2px solid #007bff; outline: none; }

        /* Financial Table */
        .fin-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .fin-table td { border: 1px solid #ddd; padding: 6px 10px; vertical-align: middle; }
        .fin-label { background: #f8f9fa; font-weight: bold; text-align: left; width: 140px; color: #555; }
        .fin-input { width: 100%; text-align: right; border: none; font-weight: bold; background: transparent; outline: none; font-family: monospace; font-size: 13px; }
        .fin-input:focus { background: #e6f7ff; }

        /* --- MAIN TABLE STYLE --- */
        .excel-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 10px; }
        .excel-table th, .excel-table td { border: 1px solid #ccc; height: 32px; padding: 0; }
        .excel-table th { background: #e9ecef; text-align: center; font-weight: bold; vertical-align: middle; color: #495057; text-transform: uppercase; font-size: 11px; }

        input.inp-cell { width: 100%; height: 100%; border: none; background: transparent; font-family: Arial, sans-serif; font-size: 12px; padding: 0 8px; box-sizing: border-box; outline: none; }
        input.inp-cell:focus { background: #e6f7ff; border-bottom: 2px solid #007bff; position:relative; z-index:10; }
        
        input.readonly { background: #f8f9fa; color: #6c757d; pointer-events: none; text-align: right; }
        input.num-cell { text-align: right; font-family: monospace; } 
        input.center-cell { text-align: center; }
        input.qty-input { background-color: #fff3cd; font-weight: bold; text-align: center; }
        input.qty-input:focus { background-color: #ffeeba; }

        .btn-del { 
            background: transparent; border: none; cursor: pointer; color: #dc3545; 
            display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;
        }
        .btn-del:hover { background: #ffe6e6; }
        .btn-del .icon { width: 18px; height: 18px; }

        .btn-add-row { 
            display: flex; align-items: center; justify-content: center; gap: 5px;
            width: 100%; padding: 10px; background: #f8f9fa; border: 2px dashed #ccc; 
            color: #6c757d; cursor: pointer; font-size: 12px; font-weight: bold; margin-bottom: 20px; transition: 0.2s;
        }
        .btn-add-row:hover { background: #e2e6ea; border-color: #adb5bd; color: #333; }

        .footer-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .footer-row td { padding: 5px; border: none; }
        .val-box { text-align: right; font-family: monospace; font-size: 13px; font-weight: bold; width: 150px; }
        .total-line { border-bottom: 3px double #333; font-size: 15px; }
    </style>
</head>
<body>

<datalist id="masterItemsList">
    <?php foreach($master_items as $m): ?>
        <option value="<?php echo htmlspecialchars($m['name']); ?>">Kode: <?php echo $m['code']; ?></option>
        <option value="<?php echo htmlspecialchars($m['code']); ?>">Nama: <?php echo $m['name']; ?></option>
    <?php endforeach; ?>
</datalist>

<div class="container">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <a href="index.php" class="btn btn-back">
            <svg class="icon" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            KEMBALI
        </a>
        <div style="display:flex; gap:10px;">
            <a href="export_single.php?id=<?php echo $id; ?>" class="btn btn-export" target="_blank">
                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                EXPORT EXCEL
            </a>
            <button onclick="saveRecipe()" class="btn btn-save">
                <svg class="icon" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                SIMPAN DATA
            </button>
        </div>
    </div>

    <div class="header-section">
        <div class="header-left">
            <div style="font-size:11px; color:#888; margin-bottom:5px; font-weight:bold;">NAME OF DISH</div>
            <input type="text" id="dishName" class="headline-input" value="<?php echo htmlspecialchars($header['name_of_dish']); ?>">
        </div>
        <div class="header-right">
            <table class="fin-table">
                <tr>
                    <td class="fin-label">Selling Price (Nett)</td>
                    <td><input type="text" id="priceNett" class="fin-input" value="<?php echo number_format($priceNett, 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcTotal();"></td>
                </tr>
                <tr>
                    <td class="fin-label">Selling Price (++)</td>
                    <td><input type="text" id="pricePlus" class="fin-input" value="<?php echo number_format($pricePlus, 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcTotal();"></td>
                </tr>
                <tr>
                    <td class="fin-label">Cost per Portion</td>
                    <td style="background:#f8f9fa;"><input type="text" id="displayCostPortion" class="fin-input" readonly value="0,00"></td>
                </tr>
                <tr>
                    <td class="fin-label">Cost %</td>
                    <td style="background:#f8f9fa;"><input type="text" id="displayCostPercent" class="fin-input" readonly value="0%"></td>
                </tr>
                <tr>
                    <td class="fin-label">Nett Profit</td>
                    <td style="background:#f8f9fa;"><input type="text" id="displayNettProfit" class="fin-input" readonly value="0,00" style="font-weight:bold;"></td>
                </tr>
                <tr>
                    <td class="fin-label">Portion</td>
                    <td>
                        <div style="display:flex; justify-content:flex-end; align-items:center;">
                            <input type="number" id="portionSize" class="fin-input" style="width:50px; text-align:center;" value="<?php echo $portionSize; ?>" oninput="calcTotal()">
                            <span style="margin-left:5px; font-size:10px; color:#666;">pax</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="excel-table" id="recipeTable">
        <thead>
            <tr>
                <th rowspan="2" style="width:40px;">NO</th>
                <th rowspan="2" style="width:100px;">CODE</th>
                <th rowspan="2">NAME (SYSTEM)</th>
                <th rowspan="2" style="width:50px;">UOM</th>
                <th rowspan="2" style="width:80px; background:#fff3cd;">UNIT USE</th>
                <th colspan="2">PURCHASE</th>
                <th rowspan="2" style="width:110px;">COST</th>
                <th rowspan="2" style="width:40px;"></th>
            </tr>
            <tr>
                <th style="width:90px;">Unit</th>
                <th style="width:130px;">Price</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php foreach($ingredients as $index => $ing): ?>
            <tr class="item-row">
                <td style="text-align:center; background:#f9f9f9;"><span class="row-no"><?php echo $index + 1; ?></span></td>
                <td><input type="text" list="masterItemsList" class="inp-cell code center-cell" value="<?php echo $ing['item_code']; ?>" onchange="fetchMasterItem(this)" placeholder="Code"></td>
                <td><input type="text" list="masterItemsList" class="inp-cell name-sys" value="<?php echo htmlspecialchars($ing['item_name_system']); ?>" onchange="fetchMasterItem(this)" placeholder="Cari Item..."></td>
                <td><input type="text" class="inp-cell uom center-cell" value="<?php echo $ing['uom_system']; ?>" readonly tabindex="-1"></td>
                <td><input type="text" class="inp-cell qty-input unit-use" value="<?php echo number_format($ing['unit_use'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                <td><input type="text" class="inp-cell num-cell conv" value="<?php echo number_format($ing['purchase_unit_conversion'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);" style="text-align:center;"></td>
                <td><input type="text" class="inp-cell num-cell price" value="<?php echo number_format($ing['purchase_price'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                <td><input type="text" class="inp-cell readonly cost" value="0,00" readonly style="font-weight:bold;"></td>
                <td style="text-align:center;">
                    <button type="button" class="btn-del" onclick="deleteRow(this)" title="Hapus Baris">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div onclick="addRow()" class="btn-add-row">
        <svg class="icon" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        TAMBAH BARIS BAHAN
    </div>

    <table class="footer-table">
         <tr class="footer-row">
            <td></td>
            <td style="width:200px; text-align:right; color:#555;">SubTotal :</td>
            <td class="val-box"><span id="valSubTotal">0,00</span></td>
            <td style="width:40px;"></td>
        </tr>
         <tr class="footer-row">
            <td></td>
            <td style="text-align:right; color:#555;">
                Variable Cost 
                (<input type="number" id="varPercent" value="<?php echo $variablePercent; ?>" 
                        style="width:35px; text-align:center; font-weight:bold; border:1px solid #ccc; padding:2px;" 
                        oninput="calcTotal()">%) :
            </td>
            <td class="val-box"><span id="valVariable">0,00</span></td>
            <td></td>
        </tr>
         <tr class="footer-row">
            <td></td>
            <td style="text-align:right; font-size:14px; font-weight:bold;">TOTAL COST (BATCH) :</td>
            <td class="val-box total-line"><span id="valTotalCost">0,00</span></td>
            <td></td>
        </tr>
    </table>
</div>

<script>
    // PARSER
    function parseForceIndo(val) {
        if (!val) return 0;
        let str = val.toString().split('.').join('').replace(',', '.');
        return parseFloat(str) || 0;
    }
    function formatIndo(num) {
        return num.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function unformatMe(input) {
        let val = parseForceIndo(input.value);
        if (val !== 0) input.value = val; else input.value = ""; 
        input.select();
    }
    function formatMe(input) {
        let val = parseForceIndo(input.value);
        input.value = formatIndo(val);
    }

    // CALCULATIONS
    function calcRow(element) {
        let row = element.closest('tr');
        let unitUse = parseForceIndo(row.querySelector('.unit-use').value);
        let conv = parseForceIndo(row.querySelector('.conv').value);
        let price = parseForceIndo(row.querySelector('.price').value);
        if (!conv || conv === 0) { conv = 1; }
        let cost = (unitUse / conv) * price;
        row.querySelector('.cost').value = formatIndo(cost);
        calcTotal();
    }

    function calcTotal() {
        let rows = document.querySelectorAll('.item-row');
        let subtotal = 0;
        rows.forEach(row => { subtotal += parseForceIndo(row.querySelector('.cost').value); });

        let percent = parseFloat(document.getElementById('varPercent').value) || 0;
        let variableCost = subtotal * (percent / 100);
        let totalCostBatch = subtotal + variableCost;

        let portion = parseFloat(document.getElementById('portionSize').value) || 1;
        let sellingPlus = parseForceIndo(document.getElementById('pricePlus').value);
        
        let costPerPortion = totalCostBatch / portion;
        let nettProfit = sellingPlus - costPerPortion;
        let costPercent = (sellingPlus > 0) ? (costPerPortion / sellingPlus) * 100 : 0;

        document.getElementById('valSubTotal').innerText = formatIndo(subtotal);
        document.getElementById('valVariable').innerText = formatIndo(variableCost);
        document.getElementById('valTotalCost').innerText = formatIndo(totalCostBatch);
        document.getElementById('displayCostPortion').value = formatIndo(costPerPortion);
        document.getElementById('displayNettProfit').value = formatIndo(nettProfit);
        document.getElementById('displayCostPercent').value = costPercent.toFixed(2) + '%';
        
        let profitField = document.getElementById('displayNettProfit');
        if (nettProfit < 0) { profitField.style.color = "#dc3545"; } else { profitField.style.color = "#28a745"; }
    }

    // FETCH & ACTIONS
    function fetchMasterItem(inputElement) {
        let keyword = inputElement.value; 
        let row = inputElement.closest('tr');
        if(keyword.length < 2) return; 

        fetch('get_item_data.php?code=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                if (data.status === 'found') {
                    row.querySelector('.code').value = data.code; 
                    row.querySelector('.name-sys').value = data.name; 
                    row.querySelector('.uom').value = data.uom; 
                    row.querySelector('.conv').value = formatIndo(parseFloat(data.conv));
                    row.querySelector('.price').value = ""; 
                    row.querySelector('.unit-use').focus();
                    calcRow(inputElement); 
                }
            });
    }

    // --- FIX UTAMA: ADD ROW PADA EMPTY STATE ---
    function addRow() {
        let table = document.getElementById('tableBody');
        let template = table.querySelector('.item-row');

        if (template) {
            // Jika sudah ada baris, clone aja (lebih cepat)
            let newRow = template.cloneNode(true);
            let inputs = newRow.querySelectorAll('input');
            inputs.forEach(input => input.value = '');
            newRow.querySelector('.conv').value = '1.000,00'; 
            newRow.querySelector('.cost').value = '0,00';
            table.appendChild(newRow);
        } else {
            // Jika tabel KOSONG, buat HTML manual
            let newRow = document.createElement('tr');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <td style="text-align:center; background:#f9f9f9;"><span class="row-no">1</span></td>
                <td><input type="text" list="masterItemsList" class="inp-cell code center-cell" onchange="fetchMasterItem(this)" placeholder="Code"></td>
                <td><input type="text" list="masterItemsList" class="inp-cell name-sys" onchange="fetchMasterItem(this)" placeholder="Cari Item..."></td>
                <td><input type="text" class="inp-cell uom center-cell" readonly tabindex="-1"></td>
                <td><input type="text" class="inp-cell qty-input unit-use" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                <td><input type="text" class="inp-cell num-cell conv" value="1.000,00" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);" style="text-align:center;"></td>
                <td><input type="text" class="inp-cell num-cell price" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                <td><input type="text" class="inp-cell readonly cost" value="0,00" readonly style="font-weight:bold;"></td>
                <td style="text-align:center;">
                    <button type="button" class="btn-del" onclick="deleteRow(this)" title="Hapus Baris">
                        <svg class="icon" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </td>
            `;
            table.appendChild(newRow);
        }
        renumberRows();
    }

    function deleteRow(btn) {
        // Hapus safety check "minimal 1 baris" agar user bisa menghapus semua jika mau
        if(confirm("Hapus baris bahan ini?")) {
            btn.closest('tr').remove();
            calcTotal();
            renumberRows();
        }
    }

    function renumberRows() {
        let rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => { row.querySelector('.row-no').innerText = index + 1; });
    }

    function saveRecipe() {
        let recipeId = <?php echo $id; ?>;
        
        let payload = {
            id: recipeId,
            name_of_dish: document.getElementById('dishName').value,
            portion: document.getElementById('portionSize').value,
            price_nett: parseForceIndo(document.getElementById('priceNett').value),
            price_plus: parseForceIndo(document.getElementById('pricePlus').value),
            total_cost: parseForceIndo(document.getElementById('valTotalCost').innerText),
            cost_per_portion: parseForceIndo(document.getElementById('displayCostPortion').value),
            nett_profit: parseForceIndo(document.getElementById('displayNettProfit').value),
            cost_percent: parseFloat(document.getElementById('displayCostPercent').value.replace('%','')),
            var_percent: document.getElementById('varPercent').value,
            ingredients: []
        };

        let rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            let sysName = row.querySelector('.name-sys').value;
            if(sysName) { 
                payload.ingredients.push({
                    no: row.querySelector('.row-no').innerText,
                    name_sys: sysName,
                    uom: row.querySelector('.uom').value,
                    unit_use: parseForceIndo(row.querySelector('.unit-use').value),
                    code: row.querySelector('.code').value,
                    conv: parseForceIndo(row.querySelector('.conv').value),
                    price: parseForceIndo(row.querySelector('.price').value),
                    cost: parseForceIndo(row.querySelector('.cost').value)
                });
            }
        });

        fetch('save_recipe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Data Resep Berhasil Disimpan!");
                document.title = "Edit: " + payload.name_of_dish;
            } else { alert("Gagal Simpan: " + data.message); }
        });
    }

    window.onload = function() {
        let rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            let inputs = [row.querySelector('.unit-use'), row.querySelector('.conv'), row.querySelector('.price')];
            inputs.forEach(inp => { if(inp.value) formatMe(inp); });
        });
        calcTotal();
    };
</script>

</body>
</html>