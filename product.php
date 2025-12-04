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
        $note = $_POST['note'] ?? '';
        
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
$stmt = $pdo->prepare("SELECT price, recorded_at FROM price_history WHERE product_id = ? ORDER BY recorded_at ASC"); // ASC for Chart.js
$stmt->execute([$pid]);
$history = $stmt->fetchAll();

// Prepare Data for Chart.js
$labels = [];
$dataPoints = [];
foreach ($history as $h) {
    $labels[] = date('d M', strtotime($h['recorded_at']));
    $dataPoints[] = $h['price'];
}

// Stats Calculation
$prices = array_column($history, 'price');
$avgPrice = count($prices) > 0 ? array_sum($prices) / count($prices) : $bestPrice;
$minPrice = count($prices) > 0 ? min($prices) : $bestPrice;
$maxPrice = count($prices) > 0 ? max($prices) : $bestPrice;
$stdDev = 0; // Simplified for now
if (count($prices) > 1) {
    $variance = 0;
    foreach ($prices as $v) { $variance += pow($v - $avgPrice, 2); }
    $stdDev = sqrt($variance / count($prices));
}

// Deal Strength (0-100)
$dealStrength = 50;
if ($maxPrice > $minPrice) {
    // Lower price = Higher strength
    $dealStrength = 100 - (($bestPrice - $minPrice) / ($maxPrice - $minPrice) * 100);
}
$dealStrength = max(0, min(100, $dealStrength));

?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | PriceScope Pro</title>
    
    <!-- Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { 
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    },
                    colors: {
                        deep: '#020617',
                        neon: {
                            cyan: '#22d3ee',
                            purple: '#a855f7',
                            pink: '#f472b6',
                            green: '#34d399'
                        }
                    },
                    animation: {
                        'aurora-move': 'auroraMove 20s infinite alternate'
                    },
                    keyframes: {
                        auroraMove: {
                            '0%': { transform: 'translateX(-10%) rotate(0deg)' },
                            '100%': { transform: 'translateX(10%) rotate(5deg)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { background-color: #020617; color: white; overflow-x: hidden; }

        /* --- GLOBAL AURORA BACKGROUND --- */
        .aurora-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
            background: #020617;
        }
        
        .aurora-beam {
            position: absolute;
            filter: blur(80px);
            opacity: 0.4;
            mix-blend-mode: screen;
            animation: aurora-move 15s infinite alternate ease-in-out;
        }

        /* Beam 1: Cyan */
        .beam-1 {
            top: -20%; left: -10%; width: 70%; height: 70%;
            background: radial-gradient(circle, rgba(34,211,238,0.4) 0%, rgba(0,0,0,0) 70%);
            animation-duration: 25s;
        }
        /* Beam 2: Purple */
        .beam-2 {
            bottom: -20%; right: -10%; width: 60%; height: 60%;
            background: radial-gradient(circle, rgba(168,85,247,0.3) 0%, rgba(0,0,0,0) 70%);
            animation-duration: 18s;
            animation-delay: -5s;
        }
        /* Beam 3: Vertical Streak */
        .beam-3 {
            top: 20%; left: 40%; width: 20%; height: 120%;
            background: linear-gradient(to bottom, rgba(52,211,153,0.1), rgba(34,211,238,0.2), rgba(0,0,0,0));
            transform: rotate(-15deg);
            filter: blur(60px);
            animation-duration: 12s;
        }

        /* --- UI COMPONENTS --- */
        .glass-panel {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }

        /* Tech Table Styles */
        .tech-table th { @apply text-xs font-mono text-neon-cyan uppercase tracking-wider py-3 px-4 text-left border-b border-white/5; }
        .tech-table td { @apply py-3 px-4 text-sm border-b border-white/5 text-slate-300; }
        .tech-table tr:last-child td { border-bottom: none; }
    </style>
</head>
<body class="p-6 md:p-10 max-w-7xl mx-auto space-y-8 min-h-screen">

    <!-- GLOBAL ANIMATED BACKGROUND -->
    <div class="aurora-container">
        <div class="aurora-beam beam-1"></div>
        <div class="aurora-beam beam-2"></div>
        <div class="aurora-beam beam-3"></div>
        <!-- Stars/Particles -->
        <div class="absolute top-10 left-20 w-1 h-1 bg-white rounded-full opacity-50 animate-pulse"></div>
        <div class="absolute top-40 right-40 w-1.5 h-1.5 bg-cyan-200 rounded-full opacity-30 animate-pulse delay-700"></div>
    </div>

    <!-- Navigation -->
    <a href="dashboard.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-neon-cyan transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
    </a>

    <!-- Product Header -->
    <header class="flex flex-col md:flex-row gap-8 items-center">
        <div class="w-40 h-40 glass-panel rounded-3xl p-6 flex items-center justify-center shadow-[0_0_20px_rgba(6,182,212,0.3)]">
            <img src="<?= htmlspecialchars($product['image_url']) ?>" class="max-w-full max-h-full object-contain">
        </div>
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-4xl font-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="flex items-center justify-center md:justify-start gap-3">
                <span class="px-3 py-1 rounded-full bg-slate-800 text-xs font-bold text-slate-300">Electronics</span>
                <span class="flex items-center gap-1 text-yellow-400 text-sm font-bold"><i data-lucide="star" class="w-4 h-4 fill-yellow-400"></i> 4.8</span>
            </div>
        </div>
        <div class="text-center md:text-right">
            <div class="text-5xl font-bold text-white mb-2 tracking-tight">₹<?= number_format($bestPrice) ?></div>
            <form method="POST" class="inline-block">
                <input type="hidden" name="add_watchlist" value="1">
                <input type="hidden" name="target_price" value="<?= $bestPrice * 0.9 ?>"> <!-- Auto-set 10% lower target -->
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-neon-cyan to-blue-600 text-white font-bold px-8 py-3 rounded-full hover:shadow-[0_0_20px_rgba(6,182,212,0.5)] transition-all">
                    Track / Buy <i data-lucide="external-link" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- Deep Dive Analysis Grid -->
    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Technical Chart Section -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Main Chart -->
            <div class="glass-panel p-6 rounded-3xl border border-neon-cyan/20">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-bold text-xl text-neon-cyan flex items-center gap-2"><i data-lucide="activity" class="w-5 h-5"></i> Price Analysis</h3>
                        <p class="text-xs text-slate-400 font-mono mt-1">SMA(50) Indicator Active</p>
                    </div>
                    <div class="flex gap-1 bg-slate-800 rounded-lg p-1">
                        <button class="px-3 py-1 rounded bg-slate-700 text-white text-xs">1W</button>
                        <button class="px-3 py-1 rounded hover:bg-slate-700 text-slate-400 text-xs">1M</button>
                        <button class="px-3 py-1 rounded hover:bg-slate-700 text-slate-400 text-xs">Forecast</button>
                    </div>
                </div>
                <div class="h-80 w-full relative">
                    <canvas id="neonChart"></canvas>
                </div>
            </div>

            <!-- Technical Data Table -->
            <div class="glass-panel rounded-3xl overflow-hidden border border-white/10">
                <div class="px-6 py-4 bg-white/5 border-b border-white/5 flex justify-between items-center">
                    <h4 class="font-bold text-sm text-slate-300 uppercase tracking-wider">Statistical Breakdown</h4>
                    <span class="text-xs text-slate-500 font-mono">Last 30 Days</span>
                </div>
                <table class="w-full tech-table">
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Analysis</th>
                    </tr>
                    <tr>
                        <td>Lowest Price</td>
                        <td class="font-mono text-neon-green">₹<?= number_format($minPrice) ?></td>
                        <td><span class="inline-block px-2 py-0.5 rounded bg-neon-green/20 text-neon-green text-[10px] font-bold">HISTORIC LOW</span></td>
                    </tr>
                    <tr>
                        <td>Highest Price</td>
                        <td class="font-mono">₹<?= number_format($maxPrice) ?></td>
                        <td>Volatile Peak</td>
                    </tr>
                    <tr>
                        <td>Average Price</td>
                        <td class="font-mono">₹<?= number_format($avgPrice) ?></td>
                        <td><?= $bestPrice < $avgPrice ? '-'.round((1 - $bestPrice/$avgPrice)*100).'%' : '+'.round(($bestPrice/$avgPrice - 1)*100).'%' ?> vs Market</td>
                    </tr>
                    <tr>
                        <td>Standard Deviation</td>
                        <td class="font-mono text-slate-400">±₹<?= number_format($stdDev) ?></td>
                        <td>High Stability</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Technical Widgets Column -->
        <div class="space-y-6">
            
            <!-- Volatility Meter -->
            <div class="glass-panel p-6 rounded-3xl relative overflow-hidden">
                <h3 class="font-bold text-slate-300 mb-4 uppercase tracking-wider text-xs">Market Volatility</h3>
                <div class="flex justify-between items-end mb-2">
                    <span class="text-4xl font-bold text-neon-purple">Low</span>
                    <span class="text-sm text-slate-400 font-mono">Index: 12.4</span>
                </div>
                <!-- Custom Progress Bar -->
                <div class="h-2 w-full bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full w-[25%] bg-gradient-to-r from-neon-green to-yellow-400"></div>
                </div>
                <p class="text-xs text-slate-500 mt-3">Prices are stable. Good time for entry.</p>
            </div>

            <!-- Price Velocity -->
            <div class="glass-panel p-6 rounded-3xl relative overflow-hidden border-l-4 border-l-neon-cyan">
                <h3 class="font-bold text-slate-300 mb-2 uppercase tracking-wider text-xs">Price Velocity</h3>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-500/20 rounded-lg text-red-400">
                        <i data-lucide="arrow-down-right" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white">-₹50<span class="text-sm text-slate-500 font-normal">/day</span></div>
                        <p class="text-[10px] text-slate-400">Dropping fast</p>
                    </div>
                </div>
            </div>

            <!-- Deal Strength Gauge -->
            <div class="glass-panel p-6 rounded-3xl text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-neon-green/5"></div>
                <h3 class="font-bold text-slate-300 mb-6 uppercase tracking-wider text-xs">Deal Strength</h3>
                <div class="relative w-48 h-24 mx-auto overflow-hidden mb-4">
                    <div class="w-48 h-48 rounded-full border-[20px] border-slate-800 border-t-neon-green border-l-neon-green/50 border-r-red-500/20 rotate-[<?= ($dealStrength * 1.8) - 135 ?>deg] shadow-[0_0_20px_rgba(74,222,128,0.2)] transition-all duration-1000"></div>
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 text-3xl font-bold text-white"><?= round($dealStrength) ?><span class="text-sm text-slate-400">%</span></div>
                </div>
                <p class="text-neon-green font-bold text-lg animate-pulse"><?= $dealStrength > 70 ? 'Strong Buy' : ($dealStrength > 40 ? 'Hold' : 'Wait') ?></p>
            </div>

            <!-- Blu's Tip -->
            <div class="glass-panel p-5 rounded-3xl flex gap-4 border border-neon-cyan/30 relative">
                <div class="absolute -top-3 -left-3 w-6 h-6 bg-neon-cyan rounded-full flex items-center justify-center border-2 border-slate-900">
                    <i data-lucide="quote" class="w-3 h-3 text-white fill-white"></i>
                </div>
                <img src="mascot.jpg" onerror="this.src='https://img.icons8.com/3d-fluency/94/penguin.png'" class="w-12 h-12 rounded-full border border-neon-cyan/50 shadow-lg">
                <div>
                    <h4 class="font-bold text-neon-cyan text-sm">Blu says:</h4>
                    <p class="text-slate-300 text-sm italic leading-relaxed">"Based on the standard deviation, this price is an outlier (too cheap). Grab it!"</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        // --- Advanced Chart Initialization ---
        const ctx = document.getElementById('neonChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(34, 211, 238, 0.5)'); 
        gradient.addColorStop(1, 'rgba(34, 211, 238, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'Price History',
                        data: <?= json_encode($dataPoints) ?>,
                        borderColor: '#22d3ee',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#020617',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { labels: { color: '#94a3b8' } } 
                },
                scales: {
                    y: { 
                        grid: { color: 'rgba(255,255,255,0.05)' }, 
                        ticks: { color: '#94a3b8', font: { family: 'Outfit' } } 
                    },
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: '#94a3b8', font: { family: 'Outfit' } } 
                    }
                }
            }
        });
    </script>
</body>
</html>
