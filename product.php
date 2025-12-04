<?php
require 'config.php';
require 'functions.php';
requireLogin();

$pid = $_GET['id'] ?? 1;
$userId = $_SESSION['user_id'];

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$pid]);
$product = $stmt->fetch();

if (!$product) die("Product not found");

// Handle Watchlist Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_watchlist'])) {
        $target = !empty($_POST['target_price']) ? $_POST['target_price'] : null;
        $note = $_POST['note'];
        
        // Check exist
        $stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id=? AND product_id=?");
        $stmt->execute([$userId, $pid]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE watchlist SET target_price=?, note=?, alert_triggered=0 WHERE user_id=? AND product_id=?");
            $stmt->execute([$target, $note, $userId, $pid]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, product_id, target_price, note) VALUES (?,?,?,?)");
            $stmt->execute([$userId, $pid, $target, $note]);
        }
        $msg = "Watchlist updated!";
    }
}

// Fetch Vendors
$stmt = $pdo->prepare("SELECT v.name, vp.price FROM vendor_prices vp JOIN vendors v ON vp.vendor_id = v.id WHERE vp.product_id = ? ORDER BY vp.price ASC");
$stmt->execute([$pid]);
$vendors = $stmt->fetchAll();

$bestPrice = $vendors[0]['price'] ?? $product['base_price'];

// Fetch History for Chart
$stmt = $pdo->prepare("SELECT price, recorded_at FROM price_history WHERE product_id = ? ORDER BY recorded_at DESC LIMIT 7");
$stmt->execute([$pid]);
$history = array_reverse($stmt->fetchAll());

// Calculate Gauge Position (Mock Logic based on history)
$avgPrice = $bestPrice; // Default
if (count($history) > 0) {
    $prices = array_column($history, 'price');
    $avgPrice = array_sum($prices) / count($prices);
    $minPrice = min($prices);
    $maxPrice = max($prices);
}
// Position 0-100%
$gaugePos = 50;
if (isset($minPrice) && $maxPrice > $minPrice) {
    $gaugePos = (($bestPrice - $minPrice) / ($maxPrice - $minPrice)) * 100;
}
$gaugePos = max(0, min(100, $gaugePos)); // Clamp
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - PriceScope</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="details-layout">
        <div class="main-col">
            <h1 class="product-title-large"><?= htmlspecialchars($product['name']) ?></h1>
            <div style="margin: 15px 0 30px; display: flex; gap: 10px;">
                <span style="padding: 5px 12px; border: 1px solid #334155; border-radius: 4px; font-size: 0.8em; color: #94a3b8;">Electronics</span>
                <?php if ($bestPrice <= $avgPrice): ?>
                    <span style="padding: 5px 12px; border: 1px solid var(--neon-green); border-radius: 4px; font-size: 0.8em; color: var(--neon-green);">Good Deal</span>
                <?php endif; ?>
            </div>

            <div class="glass-card">
                <h3 style="margin-top: 0; color: #94a3b8; font-weight: 400;">PRICE HISTORY (7D)</h3>
                <div class="chart-box">
                    <div class="grid-lines"><div class="line"></div><div class="line"></div><div class="line"></div><div class="line"></div></div>
                    <?php foreach ($history as $h): 
                        $height = ($h['price'] / ($maxPrice * 1.2)) * 200; // Scale height
                        $isRed = $h['price'] > $avgPrice;
                    ?>
                        <div class="candle <?= $isRed ? 'red' : '' ?>" style="height: <?= $height ?>px; margin-bottom: 20px;" title="₹<?= $h['price'] ?> on <?= $h['recorded_at'] ?>"></div>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                        <div style="position: absolute; width: 100%; text-align: center; color: var(--text-muted);">No history data available yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card" style="margin-top: 20px;">
                <h3 style="margin: 0 0 20px 0; font-size: 1em; color: #94a3b8;">MARKET METER</h3>
                <div class="gauge-container">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8em; color: #94a3b8;">
                        <span style="color: var(--neon-green);">GREAT DEAL</span>
                        <span>FAIR</span>
                        <span style="color: var(--neon-red);">OVERPRICED</span>
                    </div>
                    <div class="gauge-track">
                        <div class="gauge-marker" style="left: <?= $gaugePos ?>%;"></div>
                    </div>
                    <p style="font-size: 0.9em; opacity: 0.8;">Currently trading at <strong>₹<?= number_format($bestPrice) ?></strong>.</p>
                </div>
            </div>
        </div>

        <div class="side-col">
            <div class="glass-card" style="text-align: center;">
                <div style="font-size: 0.9em; color: #94a3b8; letter-spacing: 1px;">BEST PRICE</div>
                <div class="price-tag-large">₹<?= number_format($bestPrice) ?></div>
                <button class="btn btn-primary" style="width: 100%; background: var(--neon-green); color: black; box-shadow: 0 0 20px rgba(0, 255, 157, 0.4);">BUY NOW</button>
                
                <form method="POST" style="margin-top: 20px; text-align: left; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <label>Set Alert Price</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="target_price" placeholder="20000" style="padding: 10px;">
                        <button name="add_watchlist" class="btn btn-outline" style="padding: 10px;">🔔</button>
                    </div>
                    <input type="hidden" name="note" value="Added from product page">
                </form>
            </div>

            <div class="glass-card" style="margin-top: 20px;">
                <h4 style="margin-top: 0; color: #94a3b8;">VENDOR COMPARISON</h4>
                <?php foreach ($vendors as $v): ?>
                <div class="vendor-row">
                    <span><?= htmlspecialchars($v['name']) ?></span>
                    <span style="font-weight: bold; <?= $v['price'] == $bestPrice ? 'color: var(--neon-green);' : '' ?>">₹<?= number_format($v['price']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; align-items: center; gap: 15px; margin-top: 30px; background: rgba(0, 242, 255, 0.1); padding: 15px; border-radius: 10px; border: 1px solid var(--neon-cyan);">
                <div style="font-size: 30px;">🐧</div>
                <div style="font-size: 0.9em; font-style: italic;">"Signal Strength is strong! This price usually only lasts 24 hours."</div>
            </div>
        </div>
    </div>
</body>
</html>
