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
// Filter & Sort Logic
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
    case 'price_asc':
        $sql .= " ORDER BY current_price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY current_price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY w.created_at ASC";
        break;
    default: // date_desc
        $sql .= " ORDER BY w.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - Watchlist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="d-flex align-items-center mb-4">
            <div class="me-3 blu-mascot" style="font-size: 2rem;">📋</div>
            <h3 class="mb-0">Your Watchlist</h3>
        </div>

        <!-- Filter & Sort Bar -->
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">Sort By</label>
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="date_desc" <?= $sort == 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                    <option value="date_asc" <?= $sort == 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name: A-Z</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter</label>
                <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Items</option>
                    <option value="alerts" <?= $filter == 'alerts' ? 'selected' : '' ?>>🔔 Alerts Only</option>
                </select>
            </div>
        </form>

        <div class="row g-4">
            <?php if(empty($items)): ?>
                <div class="col-12 text-center p-5">Nothing here yet!</div>
            <?php endif; ?>

            <?php foreach($items as $item): ?>
                <?php 
                    $isAlert = $item['alert_triggered'];
                    $borderClass = $isAlert ? 'border-success border-2' : '';
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 <?= $borderClass ?> shadow-sm">
                        <div class="card-body">
                            <?php if($isAlert): ?>
                                <span class="badge bg-success mb-2">🎉 Price reached target!</span>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-start mb-3">
                                <img src="<?= h($item['image_url']) ?>" class="rounded me-3" width="60" height="60" style="object-fit:cover;">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <a href="product.php?id=<?= $item['product_id'] ?>" class="text-decoration-none text-dark">
                                            <?= h($item['product_name']) ?>
                                        </a>
                                    </h5>
                                    <span class="badge bg-success">In Stock</span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Current Price:</span>
                                <span class="fw-bold fs-5">₹<?= number_format($item['current_price']) ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Target Price:</span>
                                <span><?= $item['target_price'] ? '₹'.number_format($item['target_price']) : 'Not set' ?></span>
                            </div>

                            <div class="bg-light p-2 rounded mb-3 small text-muted fst-italic">
                                📝 <?= h($item['note'] ?: 'No note added.') ?>
                            </div>

                            <form method="POST" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm w-100">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
