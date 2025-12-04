<?php
require 'config.php';
require 'functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Handle Delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM watchlist WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['delete_id'], $userId]);
}

// Check Alerts
$stmt = $pdo->prepare("
    UPDATE watchlist w
    JOIN products p ON w.product_id = p.id
    JOIN vendor_prices vp ON p.id = vp.product_id
    SET w.alert_triggered = 1
    WHERE w.user_id = ? 
    AND w.target_price IS NOT NULL 
    AND vp.price <= w.target_price
");
$stmt->execute([$userId]);

// Fetch Watchlist
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'date_desc';

$sql = "
    SELECT w.*, p.name as product_name, p.image_url,
    (SELECT price FROM vendor_prices vp WHERE vp.product_id = p.id ORDER BY timestamp DESC LIMIT 1) as current_price
    FROM watchlist w JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ?
";

$params = [$userId];

if ($filter === 'alerts') {
    $sql .= " AND w.alert_triggered = 1";
}

switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY current_price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY current_price DESC"; break;
    default: $sql .= " ORDER BY w.created_at DESC"; break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watchlist - PriceScope Pro</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div style="padding: 40px; max-width: 1000px; margin: 0 auto;">
        <h1 style="border-bottom: 1px solid #334155; padding-bottom: 20px; color: var(--neon-cyan); font-weight: 200;">WATCHLIST</h1>
        
        <?php if (empty($items)): ?>
            <div style="text-align: center; margin-top: 100px; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px dashed #334155;">
                <div style="font-size: 80px; margin-bottom: 20px;">🐧</div>
                <h3 style="font-size: 1.5em; margin-bottom: 10px;">The Vault is Empty</h3>
                <p style="color: #94a3b8;">Blu is waiting for your first signal command.</p>
                <a href="add_product.php" class="btn btn-primary" style="margin-top: 20px;">Add Asset</a>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($items as $item): ?>
                    <div class="glass-card" style="padding: 0; display: flex; flex-direction: column;">
                        <div style="height: 200px; background: white; padding: 20px; display: flex; align-items: center; justify-content: center;">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" style="max-height: 100%; max-width: 100%;">
                        </div>
                        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                            <h4 style="margin: 0 0 10px 0; font-size: 1.1em;"><?= htmlspecialchars($item['product_name']) ?></h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span style="font-size: 1.5em; font-weight: bold; color: var(--neon-cyan);">₹<?= number_format($item['current_price']) ?></span>
                                <?php if ($item['alert_triggered']): ?>
                                    <span style="background: var(--neon-green); color: black; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">ALERT</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['target_price']): ?>
                                <div style="font-size: 0.9em; color: var(--text-muted); margin-bottom: 15px;">Target: ₹<?= number_format($item['target_price']) ?></div>
                            <?php endif; ?>

                            <div style="margin-top: auto; display: flex; gap: 10px;">
                                <a href="product.php?id=<?= $item['product_id'] ?>" class="btn btn-outline" style="flex: 1; text-align: center; padding: 10px;">View</a>
                                <form method="POST" onsubmit="return confirm('Remove from watchlist?');">
                                    <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                    <button class="btn btn-outline" style="border-color: var(--neon-red); color: var(--neon-red); padding: 10px;">✕</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
