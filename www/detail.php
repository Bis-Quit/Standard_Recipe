<?php
// detail.php - GLASSMORPHISM FINAL FIX (Variable Input Visible)
require 'db.php';

$id = $_GET['id'] ?? 0;

// Header & Logic
$stmt = $pdo->prepare("SELECT * FROM recipe_headers WHERE id = ?");
$stmt->execute([$id]);
$header = $stmt->fetch();
if (!$header) die("Resep tidak ditemukan.");

$variablePercent = $header['variable_percent'] ?? 10;
$portionSize = $header['portion_size'] > 0 ? $header['portion_size'] : 1;
$priceNett = $header['selling_price_nett'] ?? 0;
$pricePlus = $header['selling_price_plus_plus'] ?? 0;

$stmt_ing = $pdo->prepare("SELECT * FROM recipe_ingredients WHERE recipe_header_id = ? ORDER BY sequence_no ASC");
$stmt_ing->execute([$id]);
$ingredients = $stmt_ing->fetchAll();

$master_items = $pdo->query("SELECT code, name FROM items ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit: <?php echo htmlspecialchars($header['name_of_dish']); ?></title>
    <link rel="icon" href="assets/app-logo.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- GLASS STYLE OVERRIDES --- */
        
        /* 1. Header Panel */
        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex; gap: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        
        /* Input Judul Besar */
        .input-title { 
            font-size: 24px !important; font-weight: 800 !important; color: var(--text-main);
            background: transparent !important; border: none !important; border-bottom: 2px solid rgba(0,0,0,0.1) !important;
            border-radius: 0 !important; padding-left: 0 !important; margin-bottom: 15px;
        }
        .input-title:focus { border-bottom-color: var(--primary) !important; box-shadow: none !important; }

        /* Grid Financial di Kanan */
        .fin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .fin-item label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        
        /* Input Angka di Header */
        .input-glass { 
            background: #fff !important; /* Putih solid biar jelas */
            border: 1px solid rgba(0,0,0,0.1) !important;
            font-weight: bold; text-align: right; font-family: 'Consolas', monospace; 
        }
        
        /* 2. Tabel Style */
        .tbl-input { width: 100%; height: 100%; border: none; background: transparent; padding: 0 10px; font-size: 13px; font-family: inherit; }
        .tbl-input:focus { background: rgba(255,255,255,0.9); box-shadow: inset 0 0 0 2px var(--primary); border-radius: 4px; }
        
        /* Warna Kolom Spesial */
        .col-qty { background: rgba(255, 247, 237, 0.7) !important; color: #c2410c; font-weight: 700; text-align: center; } 
        .col-read { background: transparent !important; color: var(--text-muted); pointer-events: none; }
        
        /* 3. Footer Total (Masalahnya Disini Tadi) */
        .footer-glass {
            background: rgba(255,255,255,0.4); border-radius: 16px; padding: 20px;
            display: flex; justify-content: space-between; align-items: center; 
            border: 1px solid rgba(255,255,255,0.5);
            margin-top: 20px;
        }
        
        /* Fix Input Variable % */
        .var-input {
            width: 50px !important;
            background: #ffffff !important; /* Putih Solid */
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            text-align: center;
            font-weight: 800;
            color: var(--primary);
            padding: 5px !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .var-input:focus {
            border-color: var(--primary) !important;
            outline: none;
        }

        .btn-add-row {
            background: rgba(255,255,255,0.6); border: 2px dashed #cbd5e1; 
            color: var(--text-muted); width: 200px; justify-content: center;
        }
        .btn-add-row:hover { border-color: var(--primary); color: var(--primary); background: white; }
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
    
    <div class="page-header" style="border:none; margin-bottom:10px; padding-bottom:0;">
        <a href="index.php" class="btn btn-secondary">
            <svg class="icon" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg> 
            Dashboard
        </a>
        <div style="display:flex; gap:10px;">
            <a href="export_single.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank">
                <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg> 
                Export Excel
            </a>
            <button onclick="saveRecipe()" class="btn btn-primary">
                <svg class="icon" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                Simpan
            </button>
        </div>
    </div>

    <div class="glass-panel">
        <div style="flex:1;">
            <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">NAMA MENU</label>
            <input type="text" id="dishName" class="input-title" value="<?php echo htmlspecialchars($header['name_of_dish']); ?>">
            
            <div style="margin-top:20px;">
                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">PORSI (PAX)</label>
                <input type="number" id="portionSize" class="input-glass" style="width:100px; text-align:center;" value="<?php echo $portionSize; ?>" oninput="calcTotal()">
            </div>
        </div>

        <div style="width:400px;">
            <div class="fin-grid">
                <div class="fin-item">
                    <label>Selling Price (Nett)</label>
                    <input type="text" id="priceNett" class="input-glass" value="<?php echo number_format($priceNett, 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcTotal();">
                </div>
                <div class="fin-item">
                    <label>Selling Price (++)</label>
                    <input type="text" id="pricePlus" class="input-glass" value="<?php echo number_format($pricePlus, 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcTotal();">
                </div>
                <div class="fin-item">
                    <label>Cost / Portion</label>
                    <input type="text" id="displayCostPortion" class="input-glass col-read" readonly value="0,00">
                </div>
                <div class="fin-item">
                    <label>Cost %</label>
                    <input type="text" id="displayCostPercent" class="input-glass col-read" readonly value="0%">
                </div>
            </div>
            <div style="margin-top:15px;">
                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">NETT PROFIT</label>
                <input type="text" id="displayNettProfit" class="input-glass col-read" readonly value="0,00" style="font-size:18px;">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;" class="text-center">No</th>
                    <th style="width:120px;">Code</th>
                    <th>Item Name</th>
                    <th style="width:60px;" class="text-center">UOM</th>
                    <th style="width:100px;" class="text-center">Qty</th>
                    <th style="width:100px;" class="text-center">Conv</th>
                    <th style="width:130px;" class="text-right">Price</th>
                    <th style="width:130px;" class="text-right">Cost</th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach($ingredients as $index => $ing): ?>
                <tr class="item-row">
                    <td class="text-center"><span class="row-no"><?php echo $index + 1; ?></span></td>
                    <td><input type="text" list="masterItemsList" class="tbl-input text-center code" value="<?php echo $ing['item_code']; ?>" onchange="fetchMasterItem(this)" placeholder="Code"></td>
                    <td><input type="text" list="masterItemsList" class="tbl-input name-sys" value="<?php echo htmlspecialchars($ing['item_name_system']); ?>" onchange="fetchMasterItem(this)" placeholder="Search..."></td>
                    <td><input type="text" class="tbl-input text-center col-read uom" value="<?php echo $ing['uom_system']; ?>" readonly tabindex="-1"></td>
                    
                    <td style="padding:0;"><input type="text" class="tbl-input col-qty unit-use" value="<?php echo number_format($ing['unit_use'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                    
                    <td><input type="text" class="tbl-input text-center conv" value="<?php echo number_format($ing['purchase_unit_conversion'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                    <td><input type="text" class="tbl-input text-right price" value="<?php echo number_format($ing['purchase_price'], 2, ',', '.'); ?>" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
                    <td><input type="text" class="tbl-input text-right col-read cost" value="0,00" readonly></td>
                    <td class="text-center">
                        <button type="button" onclick="deleteRow(this)" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:bold;">✕</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer-glass">
        <button onclick="addRow()" class="btn btn-add-row">+ Add Ingredient</button>

        <table style="width:auto; text-align:right;">
            <tr>
                <td style="padding-right:15px; font-weight:600; color:var(--text-muted);">SubTotal :</td>
                <td style="font-family:'Consolas',monospace; font-weight:bold; font-size:16px;"><span id="valSubTotal">0,00</span></td>
            </tr>
            <tr>
                <td style="padding-right:15px; font-weight:600; color:var(--text-muted);">
                    Variable Cost (%):
                </td>
                <td style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                    <input type="number" id="varPercent" value="<?php echo $variablePercent; ?>" class="var-input" oninput="calcTotal()">
                    <span id="valVariable" style="font-family:'Consolas',monospace; font-weight:bold; font-size:16px;">0,00</span>
                </td>
            </tr>
            <tr>
                <td style="padding-right:15px; font-weight:800; color:var(--primary); font-size:16px; padding-top:15px;">TOTAL COST :</td>
                <td style="font-family:'Consolas',monospace; font-weight:800; font-size:24px; color:var(--primary); padding-top:15px;"><span id="valTotalCost">0,00</span></td>
            </tr>
        </table>
    </div>

</div>

<script>
    function parseForceIndo(val) { if (!val) return 0; return parseFloat(val.toString().split('.').join('').replace(',', '.')) || 0; }
    function formatIndo(num) { return num.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function unformatMe(input) { let val = parseForceIndo(input.value); input.value = (val !== 0) ? val : ""; input.select(); }
    function formatMe(input) { let val = parseForceIndo(input.value); input.value = formatIndo(val); }

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
        if (nettProfit < 0) { 
            profitField.style.color = "#dc2626"; profitField.style.backgroundColor = "#fee2e2";
        } else { 
            profitField.style.color = "#16a34a"; profitField.style.backgroundColor = "#dcfce7";
        }
    }

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

    function addRow() {
        let table = document.getElementById('tableBody');
        let newRow = document.createElement('tr');
        newRow.className = 'item-row';
        newRow.innerHTML = `
            <td class="text-center"><span class="row-no">1</span></td>
            <td><input type="text" list="masterItemsList" class="tbl-input text-center code" onchange="fetchMasterItem(this)" placeholder="Code"></td>
            <td><input type="text" list="masterItemsList" class="tbl-input name-sys" onchange="fetchMasterItem(this)" placeholder="Search..."></td>
            <td><input type="text" class="tbl-input text-center col-read uom" readonly tabindex="-1"></td>
            <td style="padding:0;"><input type="text" class="tbl-input col-qty unit-use" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
            <td><input type="text" class="tbl-input text-center conv" value="1.000,00" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
            <td><input type="text" class="tbl-input text-right price" onfocus="unformatMe(this)" onblur="formatMe(this); calcRow(this);"></td>
            <td><input type="text" class="tbl-input text-right col-read cost" value="0,00" readonly></td>
            <td class="text-center"><button type="button" onclick="deleteRow(this)" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:bold;">✕</button></td>
        `;
        table.appendChild(newRow);
        renumberRows();
    }

    function deleteRow(btn) { if(confirm("Hapus baris?")) { btn.closest('tr').remove(); calcTotal(); renumberRows(); } }
    function renumberRows() { document.querySelectorAll('.row-no').forEach((el, i) => el.innerText = i + 1); }

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
                alert("Berhasil Disimpan!");
                document.title = "Edit: " + payload.name_of_dish;
            } else { alert("Gagal: " + data.message); }
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