<?php
require 'config.php';
require 'functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
$stmt->execute([$userId]);
$totalItems = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND alert_triggered = 1");
$stmt->execute([$userId]);
$activeAlerts = $stmt->fetchColumn();

$savings = 3500; // Mock

// Fetch Products with Vendor Prices
$query = "
    SELECT p.*, 
           MAX(CASE WHEN v.name = 'Amazon' THEN vp.price END) as amazon_price,
           MAX(CASE WHEN v.name = 'Flipkart' THEN vp.price END) as flipkart_price,
           MAX(CASE WHEN v.name = 'Croma' THEN vp.price END) as croma_price
    FROM products p
    LEFT JOIN vendor_prices vp ON p.id = vp.product_id
    LEFT JOIN vendors v ON vp.vendor_id = v.id
    GROUP BY p.id
    LIMIT 5
";
$stmt = $pdo->query($query);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PriceScope Pro</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="glass-card" style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px; padding: 20px; border-left: 4px solid var(--neon-cyan);">
            <div style="font-size: 40px;">🐧</div>
            <span><strong>Blu's Insight:</strong> Tech stocks are dipping. It's a buyer's market for headphones today.</span>
        </div>

        <div class="dashboard-grid">
            <div class="hud-card positive">
                <div class="label" style="color: #94a3b8; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px;">Total Value</div>
                <div class="hud-value">₹<?= number_format($savings * 12) ?></div>
                <div style="color: var(--neon-green);">▲ 2.4%</div>
            </div>
            <div class="hud-card negative">
                <div class="label" style="color: #94a3b8; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px;">Top Loser</div>
                <div class="hud-value">iPhone 15</div>
                <div style="color: var(--neon-red);">▼ 2.1%</div>
            </div>
            <div class="hud-card">
                <div class="label" style="color: #94a3b8; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px;">Items Tracked</div>
                <div class="hud-value"><?= $totalItems ?></div>
            </div>
        </div>

        <div class="glass-card" style="padding: 0;">
            <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Live Market Watch</h3>
                <a href="add_product.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.8em;">+ Track Product</a>
            </div>
            <table class="data-table">
                <tr>
                    <th>PRODUCT</th>
                    <th>AMAZON</th>
                    <th>FLIPKART</th>
                    <th>CROMA</th>
                    <th>BEST PRICE</th>
                    <th>ACTION</th>
                </tr>
                <?php foreach ($products as $p): 
                    $prices = array_filter([
                        'Amazon' => $p['amazon_price'], 
                        'Flipkart' => $p['flipkart_price'], 
                        'Croma' => $p['croma_price']
                    ]);
                    $minPrice = !empty($prices) ? min($prices) : 0;
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width: 40px; height: 40px; object-fit: contain; background: white; padding: 2px; border-radius: 4px;">
                            <div>
                                <div style="font-weight: bold;"><?= htmlspecialchars($p['name']) ?></div>
                                <div style="font-size: 0.8em; color: var(--text-muted);"><?= htmlspecialchars($p['asin']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $p['amazon_price'] ? '₹'.number_format($p['amazon_price']) : '-' ?></td>
                    <td><?= $p['flipkart_price'] ? '₹'.number_format($p['flipkart_price']) : '-' ?></td>
                    <td><?= $p['croma_price'] ? '₹'.number_format($p['croma_price']) : '-' ?></td>
                    <td style="color: var(--neon-cyan); font-weight: bold;">₹<?= number_format($minPrice) ?></td>
                    <td>
                        <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.8em;">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        No products tracked yet. <a href="add_product.php" style="color: var(--neon-cyan);">Add your first product</a>.
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
