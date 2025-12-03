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

        <!-- Main Data Table -->
        <div class="card overflow-hidden mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">Live Market Data</h5>
                <div class="d-flex gap-2">

                    <a href="add_product.php" class="btn btn-primary btn-sm" title="Search Amazon and add new products to track">+ Track Product</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-dark-surface">
                        <tr>
                            <th class="ps-4 text-secondary text-uppercase small">Ticker / Name</th>
                            <th class="text-end text-secondary text-uppercase small">Price</th>
                            <th class="text-end text-secondary text-uppercase small">24h %</th>
                            <th class="text-end text-secondary text-uppercase small">7d High</th>
                            <th class="text-end text-secondary text-uppercase small">7d Low</th>
                            <th class="text-end text-secondary text-uppercase small">Vol (Est)</th>
                            <th class="text-end text-secondary text-uppercase small">Signal</th>
                            <th class="text-end pe-4 text-secondary text-uppercase small">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p):
                            $change = rand(-500, 500) / 100;
                            $color = $change >= 0 ? 'text-success' : 'text-danger';
                            $sign = $change >= 0 ? '+' : '';
                            $vol = rand(100, 5000);
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="rounded bg-white p-1 me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <img src="<?= h($p['image_url']) ?>" style="max-width: 100%; max-height: 100%;">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white"><?= h($p['name']) ?></div>
                                        <div class="small text-muted font-monospace"><?= h($p['asin']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end fw-bold font-monospace text-white">₹<?= number_format($p['current_price']) ?></td>
                            <td class="text-end font-monospace <?= $color ?>"><?= $sign ?><?= $change ?>%</td>
                            <td class="text-end font-monospace text-muted">₹<?= number_format($p['current_price'] * 1.05) ?></td>
                            <td class="text-end font-monospace text-muted">₹<?= number_format($p['current_price'] * 0.95) ?></td>
                            <td class="text-end font-monospace text-muted"><?= number_format($vol) ?></td>
                            <td class="text-end">
                                <?php if($change > 2): ?>
                                    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 rounded-1">STRONG BUY</span>
                                <?php elseif($change > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-1">BUY</span>
                                <?php elseif($change > -2): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-1">HOLD</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-1">SELL</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary border-dark-subtle text-muted hover-white">
                                    Analyze
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
