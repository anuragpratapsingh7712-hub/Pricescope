<?php
require 'config.php';

$message = "";
$msgType = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    try {
        if ($action === 'seed_core') {
            // 1. Core Product Data (Manual/Seed)
            // We use the db.sql file which contains the "Manual" INSERT statements
            $sql = file_get_contents('db.sql');
            $pdo->exec($sql);
            $message = "✅ Core Data (Products, Vendors) reset and seeded from db.sql.";
        }
        elseif ($action === 'simulate_history') {
            // 2. Price History Data (Simulated/Programmatic)
            // Clear existing history to avoid duplicates
            $pdo->exec("DELETE FROM price_history");
            $pdo->exec("DELETE FROM vendor_prices");

            $stmt = $pdo->query("SELECT id, base_price FROM products");
            $products = $stmt->fetchAll();

            $vendors = [1, 2, 3]; // IDs of Amazon, Flipkart, Croma

            foreach ($products as $p) {
                $pid = $p['id'];
                $base = $p['base_price'];
                
                // Generate 30 days of history
                // Generate 180 days (6 months) of history
                $trendType = rand(0, 2); // 0=Stable, 1=Downward, 2=Seasonal
                
                for ($i = 180; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    
                    // Base Trend Logic
                    if ($trendType === 1) {
                        // Downward trend (e.g. tech getting cheaper)
                        // Price drops by ~0.1% per day on average
                        $trendFactor = 1 - ($i * 0.001); 
                    } elseif ($trendType === 2) {
                        // Seasonal (Sine wave)
                        // Fluctuates up and down over 60 day periods
                        $trendFactor = 1 + (sin($i / 20) * 0.1);
                    } else {
                        // Stable
                        $trendFactor = 1;
                    }

                    // Random daily noise +/- 2%
                    $noise = rand(-20, 20) / 1000; 
                    
                    $price = $base * $trendFactor * (1 + $noise);
                    
                    // Ensure price doesn't go below 50% of base
                    if ($price < $base * 0.5) $price = $base * 0.5;
                    
                    $price = round($price / 10) * 10; // Round to nearest 10

                    // Insert into price_history
                    $stmtHist = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
                    $stmtHist->execute([$pid, $price, $date]);

                    // If it's today (i=0), update current vendor prices too
                    if ($i === 0) {
                        foreach ($vendors as $vid) {
                            $vPrice = $price * (1 + (rand(-1, 1)/100));
                            $stmtV = $pdo->prepare("INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES (?, ?, ?)");
                            $stmtV->execute([$pid, $vid, round($vPrice/10)*10]);
                        }
                    }
                }
            }
            $message = "✅ Price History simulated for the last 30 days.";
        }
        elseif ($action === 'seed_users') {
            // 3. User-Generated Data (Simulated)
            // Create a demo user if not exists
            $email = "demo@example.com";
            $pass = password_hash("password", PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)");
                $stmt->execute(["Demo User", $email, $pass]);
                $message = "✅ Created Admin User: demo@example.com / password";
            } else {
                $message = "ℹ️ Demo user already exists.";
                $msgType = "info";
            }
        }
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
        $msgType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - Data Seeding</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">🛠️ Admin: Data Seeding Tool</h3>
            </div>
            <div class="card-body">
                <p class="lead">Use this tool to populate your database as per the project requirements.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?= $msgType ?>"><?= $message ?></div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Source 1 -->
                    <div class="col-md-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary">1. Core Data</h5>
                                <p class="small text-muted">Resets database and loads Products & Vendors from <code>db.sql</code>.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="seed_core">
                                    <button class="btn btn-primary w-100">Reset & Seed Core</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Source 2 -->
                    <div class="col-md-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">2. Price History</h5>
                                <p class="small text-muted">Generates <strong>180 days</strong> of realistic trending data (Seasonal, Dropping, Stable).</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="simulate_history">
                                    <button class="btn btn-warning w-100">Simulate History</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Source 3 -->
                    <div class="col-md-4">
                        <div class="card h-100 border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">3. User Data</h5>
                                <p class="small text-muted">Creates a demo user account to test Login and Watchlist features.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="seed_users">
                                    <button class="btn btn-success w-100">Create Demo User</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-center">
                    <a href="index.php" class="btn btn-outline-dark">Go to Login Page</a>
                    <a href="dashboard.php" class="btn btn-outline-dark">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
