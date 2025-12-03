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
    } elseif (isset($_POST['vote'])) {
        $vote = ($_POST['vote'] === 'yes') ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, vote) VALUES (?,?,?)");
        $stmt->execute([$userId, $pid, $vote]);
        $msg = "Thanks for voting!";
    }
}

// Fetch Vendors
$stmt = $pdo->prepare("SELECT v.name, vp.price FROM vendor_prices vp JOIN vendors v ON vp.vendor_id = v.id WHERE vp.product_id = ?");
$stmt->execute([$pid]);
$vendors = $stmt->fetchAll();

// Fetch History
$stmt = $pdo->prepare("SELECT price, recorded_at FROM price_history WHERE product_id = ? ORDER BY recorded_at");
$stmt->execute([$pid]);
$history = $stmt->fetchAll();

// Calculate High/Low
$prices = array_column($history, 'price');
$highPrice = !empty($prices) ? max($prices) : 0;
$lowPrice = !empty($prices) ? min($prices) : 0;

// Forecast Logic (Linear Regression)
$forecast = [];
$trend = 'flat';
$buyRecommendation = 'neutral';
$forecastUpper = [];
$forecastLower = [];

if (count($history) >= 5) {
    $n = count($history);
    $x = []; // Time (days from start)
    $y = []; // Price
    
    $startDate = new DateTime($history[0]['recorded_at']);
    
    foreach ($history as $i => $h) {
        $date = new DateTime($h['recorded_at']);
        $diff = $startDate->diff($date)->days;
        $x[] = $diff;
        $y[] = $h['price'];
    }
    
    // Linear Regression Math: y = mx + b
    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumXY = 0;
    $sumXX = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumXY += ($x[$i] * $y[$i]);
        $sumXX += ($x[$i] * $x[$i]);
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Determine Trend
    if ($slope < -5) $trend = 'down';
    elseif ($slope > 5) $trend = 'up';
    
    // Generate 7-Day Forecast
    $lastRealDate = new DateTime($history[$n-1]['recorded_at']);
    $lastX = end($x);
    
    // Calculate Standard Error for Confidence Interval
    $errorSum = 0;
    for ($i = 0; $i < $n; $i++) {
        $predicted = $slope * $x[$i] + $intercept;
        $errorSum += pow($y[$i] - $predicted, 2);
    }
    $stdError = sqrt($errorSum / ($n - 2));
    
    for ($i = 1; $i <= 7; $i++) {
        $futureX = $lastX + $i;
        $futurePrice = $slope * $futureX + $intercept;
        $futureDate = clone $lastRealDate;
        $futureDate->modify("+$i days");
        
        // Confidence Interval (95% approx => 2 * stdError)
        $margin = 1.96 * $stdError; 
        
        $forecast[] = [
            'price' => round($futurePrice),
            'upper' => round($futurePrice + $margin),
            'lower' => round($futurePrice - $margin),
            'recorded_at' => $futureDate->format('Y-m-d')
        ];
    }
    
    // Buy Recommendation Logic
    $currentPrice = $history[$n-1]['price'];
    $nextDayPred = $forecast[0]['price'];
    
    if ($currentPrice <= $lowPrice) {
        $buyRecommendation = 'buy_now'; // At historic low
        $buyReason = "Price is at a historic low! Great time to buy.";
    } elseif ($trend === 'down') {
        $buyRecommendation = 'wait';
        $buyReason = "Price is trending down. Wait for it to drop further.";
    } elseif ($trend === 'up' && $currentPrice < $nextDayPred) {
        $buyRecommendation = 'buy_now';
        $buyReason = "Price is trending up. Buy before it gets more expensive.";
    } else {
        $buyRecommendation = 'neutral';
        $buyReason = "Price is stable. Buy if you need it now.";
    }

    // Deal Meter Logic
    $avgPrice = array_sum($prices) / count($prices);
    $diffPercent = (($currentPrice - $avgPrice) / $avgPrice) * 100;
    
    if ($diffPercent <= -10) {
        $dealScore = 100; // Great Deal
        $dealColor = 'success'; // Green
        $dealText = "🔥 Great Deal! (" . round(abs($diffPercent)) . "% below avg)";
    } elseif ($diffPercent <= 5) {
        $dealScore = 60; // Fair
        $dealColor = 'warning'; // Yellow
        $dealText = "⚖️ Fair Price";
    } else {
        $dealScore = 20; // Bad
        $dealColor = 'danger'; // Red
        $dealText = "💸 Overpriced (" . round($diffPercent) . "% above avg)";
    }
} else {
    // Not enough data
    $dealScore = 50;
    $dealColor = 'secondary';
    $dealText = "Not enough data yet";
}

// Watchlist Status
$stmt = $pdo->prepare("SELECT * FROM watchlist WHERE user_id=? AND product_id=?");
$stmt->execute([$userId, $pid]);
$watchlist = $stmt->fetch();

// Ratings
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(vote) as yes_votes FROM ratings WHERE product_id=?");
$stmt->execute([$pid]);
$rating = $stmt->fetch();
$yesPercent = ($rating['total'] > 0) ? round(($rating['yes_votes'] / $rating['total']) * 100) : 0;

// Prepare Chart Data
$labels = [];
$dataHist = [];
$dataFore = [];
$dataUpper = [];
$dataLower = [];

foreach ($history as $h) {
    $labels[] = $h['recorded_at'];
    $dataHist[] = $h['price'];
    $dataFore[] = null;
    $dataUpper[] = null;
    $dataLower[] = null;
}
// Connect lines
$lastIdx = count($dataFore)-1;
$dataFore[$lastIdx] = $history[$lastIdx]['price'];
$dataUpper[$lastIdx] = $history[$lastIdx]['price'];
$dataLower[$lastIdx] = $history[$lastIdx]['price'];

foreach ($forecast as $f) {
    $labels[] = $f['recorded_at'];
    $dataHist[] = null;
    $dataFore[] = $f['price'];
    $dataUpper[] = $f['upper'];
    $dataLower[] = $f['lower'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - <?= h($product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .gauge-wrapper {
            position: relative;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }
        .gauge-body {
            width: 100%;
            height: 0;
            padding-bottom: 50%;
            background: #e5e7eb;
            border-top-left-radius: 100% 200%;
            border-top-right-radius: 100% 200%;
            position: relative;
            overflow: hidden;
            background: conic-gradient(from 270deg at 50% 100%, #ef4444 0deg 60deg, #eab308 60deg 120deg, #22c55e 120deg 180deg);
        }
        .gauge-needle {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 4px;
            height: 90%;
            background: #1f2937;
            transform-origin: bottom center;
            transition: transform 0.5s ease-out;
            border-radius: 4px;
            z-index: 10;
        }
        .gauge-center {
            position: absolute;
            bottom: -10px;
            left: 50%;
            width: 20px;
            height: 20px;
            background: #1f2937;
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 11;
        }
        .gauge-label {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php if(isset($msg)): ?>
            <div class="alert alert-success"><?= h($msg) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="card p-4 mb-4">
                    <img src="<?= h($product['image_url']) ?>" class="img-fluid rounded mb-3">
                    <h3><?= h($product['name']) ?></h3>
                    
                    <!-- Old Deal Meter Removed -->

                    <!-- Blu's Recommendation -->
                    <div class="d-flex align-items-start bg-light p-3 rounded mb-3 border">
                        <div class="me-3" style="font-size: 2rem;">🐧</div>
                        <div>
                            <h6 class="fw-bold mb-1 text-primary">Blu says:</h6>
                            <p class="mb-0 small text-dark">"<?= h($buyReason) ?>"</p>
                        </div>
                    </div>

                    <p class="text-muted small"><?= h($product['description']) ?></p>

                    <form method="POST" class="mt-3">
                        <input type="hidden" name="add_watchlist" value="1">
                        <div class="mb-3">
                            <label class="form-label small">Target price (₹)</label>
                            <input type="number" name="target_price" class="form-control form-control-sm" value="<?= h($watchlist['target_price'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Note</label>
                            <textarea name="note" class="form-control form-control-sm"><?= h($watchlist['note'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-primary w-100">
                            <?= $watchlist ? 'Update Watchlist' : 'Add to Watchlist' ?>
                        </button>
                    </form>
                    
                    <?php if($watchlist && $watchlist['target_price']): ?>
                        <div class="mt-2 text-success small fw-bold">Alert Active: Notify below ₹<?= h($watchlist['target_price']) ?></div>
                    <?php endif; ?>

                    <hr>
                    
                    <!-- AI Recommendation Box -->
                    <?php if(isset($buyRecommendation)): ?>
                        <div class="alert <?= $buyRecommendation === 'buy_now' ? 'alert-success' : ($buyRecommendation === 'wait' ? 'alert-warning' : 'alert-secondary') ?> mb-3">
                            <strong>
                                <?= $buyRecommendation === 'buy_now' ? '🚀 BUY NOW' : ($buyRecommendation === 'wait' ? '✋ WAIT' : '⚖️ HOLD') ?>
                            </strong>
                            <p class="small mb-0"><?= $buyReason ?></p>
                        </div>
                    <?php endif; ?>

                    <h6>Community Vote</h6>
                    <form method="POST" class="d-flex gap-2">
                        <button name="vote" value="yes" class="btn btn-outline-success btn-sm flex-grow-1">👍 Yes</button>
                        <button name="vote" value="no" class="btn btn-outline-danger btn-sm flex-grow-1">👎 No</button>
                    </form>
                    <p class="small text-muted mt-2"><?= $yesPercent ?>% say "Buy Now" (<?= $rating['total'] ?> votes)</p>
                </div>
            </div>

            <div class="col-md-7">
                <!-- Speedometer Deal Meter -->
                <div class="card p-4 mb-4 text-center">
                    <h5 class="mb-3">Deal Quality</h5>
                    <div class="gauge-wrapper">
                        <div class="gauge-body">
                            <!-- Rotation: 0 to 180 deg. Score 0-100 map to -90 to 90 deg -->
                            <?php $rotation = ($dealScore / 100) * 180 - 90; ?>
                            <div class="gauge-needle" style="transform: rotate(<?= $rotation ?>deg);"></div>
                            <div class="gauge-center"></div>
                        </div>
                    </div>
                    <div class="gauge-label text-<?= $dealColor ?>">
                        <?= $dealText ?>
                    </div>
                </div>

                <div class="card p-4 mb-4">
                    <h5>Vendor Comparison</h5>
                    <div class="row">
                        <?php foreach($vendors as $v): ?>
                            <div class="col-md-6 mb-2">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between">
                                            <h6><?= h($v['name']) ?></h6>
                                            <span class="badge bg-success">In Stock</span>
                                        </div>
                                        <h4 class="text-primary">₹<?= number_format($v['price']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Price Forecast (7 Days)</h5>
                        <div class="small">
                            <span class="badge bg-danger">High: ₹<?= number_format($highPrice) ?></span>
                            <span class="badge bg-success">Low: ₹<?= number_format($lowPrice) ?></span>
                        </div>
                    </div>
                    <canvas id="historyChart"></canvas>
                    <div class="small text-muted mt-2 text-center">
                        Shaded area represents the 95% confidence interval.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('historyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'History',
                        data: <?= json_encode($dataHist) ?>,
                        borderColor: '#4DA8DA',
                        backgroundColor: '#4DA8DA',
                        tension: 0.1,
                        fill: false,
                        pointRadius: 4
                    },
                    {
                        label: 'Forecast',
                        data: <?= json_encode($dataFore) ?>,
                        borderColor: '#FF9F40',
                        borderDash: [5, 5],
                        tension: 0.1,
                        pointRadius: 0
                    },
                    {
                        label: 'Upper Bound',
                        data: <?= json_encode($dataUpper) ?>,
                        borderColor: 'transparent',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: '+1', // Fill to next dataset (Lower Bound)
                        pointRadius: 0
                    },
                    {
                        label: 'Lower Bound',
                        data: <?= json_encode($dataLower) ?>,
                        borderColor: 'transparent',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            filter: function(item, chart) {
                                // Hide Upper/Lower bound from legend
                                return !item.text.includes('Bound');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
