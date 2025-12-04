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
    <title>PriceScope Pro - Watchlist</title>
    <style>
        /* Base Variables & Overrides for this Page */
        :root { --bg: #020617; --neon-cyan: #00f2ff; --neon-green: #10b981; --neon-red: #ef4444; }

        /* Global & Body Styles */
        body { background: var(--bg); color: white; font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        
        /* Header */
        h1 { border-bottom: 1px solid #334155; padding-bottom: 20px; color: var(--neon-cyan); font-weight: 200; letter-spacing: 2px; }
        
        /* Empty State Styles */
        .empty-state {
            text-align: center; margin-top: 100px; padding: 50px;
            background: rgba(255,255,255,0.02); border-radius: 20px;
            border: 1px dashed #334155;
        }
        .empty-state img { 
            width: 120px; opacity: 0.8; margin-bottom: 20px; border-radius: 50%; 
            box-shadow: 0 0 20px rgba(0,242,255,0.2); 
        }
        h3 { font-size: 1.5em; margin-bottom: 10px; }
        p { color: #94a3b8; }
        
        /* Grid Layout */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px; }
        
        /* Card Styles */
        .card {
            background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px;
            overflow: hidden; transition: 0.3s; position: relative;
        }
        .card:hover { transform: translateY(-5px); border-color: var(--neon-cyan); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .card-img { height: 200px; background: white; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card-img img { max-height: 100%; max-width: 100%; object-fit: contain; }
        
        .card-body { padding: 20px; }
        .card-title { font-weight: bold; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-price { font-size: 1.5em; font-weight: bold; color: var(--neon-cyan); margin-bottom: 5px; }
        .card-target { color: #94a3b8; font-size: 0.9em; margin-bottom: 15px; }
        
        .actions { display: flex; gap: 10px; }
        .btn { flex: 1; padding: 10px; border: 1px solid #334155; background: transparent; color: white; border-radius: 5px; cursor: pointer; text-align: center; text-decoration: none; font-size: 0.9em; }
        .btn:hover { background: rgba(255,255,255,0.1); }
        .btn-del { border-color: var(--neon-red); color: var(--neon-red); flex: 0; padding: 10px 15px; }
        .btn-del:hover { background: var(--neon-red); color: white; }
        
        .alert-badge {
            position: absolute; top: 10px; right: 10px; background: var(--neon-green); color: black;
            padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 0.8em;
            box-shadow: 0 0 10px var(--neon-green);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <h1>WATCHLIST</h1>
    
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <img src="mascot.jpg" alt="Sleeping Penguin">
            <h3>The Vault is Empty</h3>
            <p>Blu is waiting for your first signal command.</p>
            <a href="add_product.php" style="display: inline-block; margin-top: 20px; color: var(--neon-cyan); text-decoration: none; border: 1px solid var(--neon-cyan); padding: 10px 20px; border-radius: 5px;">Add Asset</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($items as $item): ?>
                <div class="card">
                    <?php if ($item['alert_triggered']): ?>
                        <div class="alert-badge">TARGET HIT</div>
                    <?php endif; ?>
                    
                    <div class="card-img">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="Product">
                    </div>
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div class="card-price">₹<?= number_format($item['current_price']) ?></div>
                        <div class="card-target">
                            <?php if ($item['target_price']): ?>
                                Target: ₹<?= number_format($item['target_price']) ?>
                            <?php else: ?>
                                No Target Set
                            <?php endif; ?>
                        </div>
                        
                        <div class="actions">
                            <a href="product.php?id=<?= $item['product_id'] ?>" class="btn">Analytics</a>
                            <form method="POST" onsubmit="return confirm('Remove from watchlist?');" style="margin:0;">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button class="btn btn-del">✕</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
