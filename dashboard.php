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
";
$stmt = $pdo->query($query);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4">
        <!-- Top Summary Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Total Market Value</div>
                        <div class="h3 mb-0 fw-bold">₹<?= number_format($savings * 12) ?></div>
                    </div>
                    <div class="text-success bg-success bg-opacity-10 p-2 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Top Gainer</div>
                        <div class="h4 mb-0 fw-bold text-success">Sony WH-1000XM5</div>
                        <div class="small text-success">+4.2%</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Top Loser</div>
                        <div class="h4 mb-0 fw-bold text-danger">iPhone 15</div>
                        <div class="small text-danger">-2.1%</div>
                    </div>
                </div>
            </div>
            <!-- Mascot Insight Card -->
            <div class="col-md-3">
                <div class="card p-3 h-100 border-primary bg-primary bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="font-size: 2.5rem;">🐧</div>
                        <div>
                            <div class="text-primary small text-uppercase fw-bold">Blu's Insight</div>
                            <div class="small fw-bold">"Market is volatile today. Watch for dips in Tech sector."</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Price Comparison Table -->
        <div class="card overflow-hidden mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">Product Price Comparison</h5>
                <div class="d-flex gap-2">
                    <a href="add_product.php" class="btn btn-primary btn-sm" title="Search Amazon and add new products to track">+ Track Product</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-dark-surface">
                        <tr>
                            <th class="ps-4 text-secondary text-uppercase small">Product</th>
                            <th class="text-end text-secondary text-uppercase small">Amazon</th>
                            <th class="text-end text-secondary text-uppercase small">Flipkart</th>
                            <th class="text-end text-secondary text-uppercase small">Croma</th>
                            <th class="text-end text-secondary text-uppercase small">Best Price</th>
                            <th class="text-end pe-4 text-secondary text-uppercase small">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): 
                            $prices = array_filter([
                                'Amazon' => $p['amazon_price'], 
                                'Flipkart' => $p['flipkart_price'], 
                                'Croma' => $p['croma_price']
                            ]);
                            $minPrice = !empty($prices) ? min($prices) : 0;
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="rounded bg-white p-1 me-3 d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                                        <img src="<?= h($p['image_url']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= h($p['name']) ?></div>
                                        <div class="small text-muted font-monospace"><?= h($p['asin']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end font-monospace">
                                <?php if ($p['amazon_price']): ?>
                                    <span class="<?= $p['amazon_price'] == $minPrice ? 'text-success fw-bold' : 'text-muted' ?>">
                                        ₹<?= number_format($p['amazon_price']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end font-monospace">
                                <?php if ($p['flipkart_price']): ?>
                                    <span class="<?= $p['flipkart_price'] == $minPrice ? 'text-success fw-bold' : 'text-muted' ?>">
                                        ₹<?= number_format($p['flipkart_price']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end font-monospace">
                                <?php if ($p['croma_price']): ?>
                                    <span class="<?= $p['croma_price'] == $minPrice ? 'text-success fw-bold' : 'text-muted' ?>">
                                        ₹<?= number_format($p['croma_price']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end font-monospace fw-bold text-dark">
                                ₹<?= number_format($minPrice) ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No products tracked yet. <a href="add_product.php">Add your first product</a>.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
