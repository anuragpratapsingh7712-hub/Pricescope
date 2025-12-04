<?php
require 'config.php';
require 'functions.php';
requireLogin();

$message = "";
$results = [];
$searched_price = ""; // To persist target price across search

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $query = urlencode($_POST['query']);
        $searched_price = $_POST['target_price'] ?? ''; // Capture target price
        $apiKey = RAPID_API_KEY;
        
        // Using "Real-Time Amazon Data" API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://real-time-amazon-data.p.rapidapi.com/search?query=$query&page=1&country=IN",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: real-time-amazon-data.p.rapidapi.com",
                "x-rapidapi-key: $apiKey"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $message = "cURL Error: " . $err;
        } else {
            $data = json_decode($response, true);
            if (!empty($data['data']['products'])) {
                $results = $data['data']['products'];
            } else {
                $message = "No products found or API limit reached.";
            }
        }
    } 
    elseif (isset($_POST['import'])) {
        $name = $_POST['name'];
        $price = floatval(str_replace(['₹', ','], '', $_POST['price']));
        $image = $_POST['image'];
        $asin = $_POST['asin'];
        $target_price = !empty($_POST['target_price']) ? floatval($_POST['target_price']) : null;
        $desc = "Imported by " . $_SESSION['user_name'];

        try {
            // 1. Insert Product (Ignore if exists, but we need ID)
            $stmt = $pdo->prepare("SELECT id FROM products WHERE asin = ?");
            $stmt->execute([$asin]);
            $existing = $stmt->fetch();

            if ($existing) {
                $pid = $existing['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, base_price, image_url, asin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $image, $asin]);
                $pid = $pdo->lastInsertId();

                // Insert Initial Price
                $stmt = $pdo->prepare("INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES (?, 1, ?)");
                $stmt->execute([$pid, $price]);

                // Generate Fake History for Demo
                for ($i = 30; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $histPrice = $price * (1 + (rand(-5, 5)/100));
                    $stmt = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
                    $stmt->execute([$pid, $histPrice, $date]);
                }
            }

            // 2. Add to Watchlist (with Target Price)
            $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, product_id, target_price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE target_price = VALUES(target_price)");
            $stmt->execute([$_SESSION['user_id'], $pid, $target_price]);

            // REDIRECT TO PRODUCT PAGE
            header("Location: product.php?id=$pid");
            exit;

        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope Pro - Add New</title>
    <style>
        /* Base Variables & Overrides for this Page */
        :root { --bg: #020617; --neon-cyan: #00f2ff; }

        /* Global & Body Styles */
        body { 
            background: var(--bg); 
            font-family: 'Segoe UI', sans-serif; 
            color: white; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            padding-top: 50px; 
            min-height: 100vh;
            margin: 0;
        }
        
        /* Main Panel */
        .panel {
            width: 500px; border-radius: 20px; overflow: hidden;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }
        
        /* Gradient Header */
        .panel-head {
            background: linear-gradient(45deg, var(--neon-cyan), #0066ff); 
            padding: 30px; text-align: center; color: #020617;
        }
        .panel-body { padding: 40px; }
        
        /* Form Field Styles */
        .field { margin-bottom: 20px; }
        label { 
            display: block; margin-bottom: 10px; color: var(--neon-cyan); 
            font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; 
        }
        input { 
            width: 100%; padding: 15px; background: rgba(0,0,0,0.4); 
            border: 1px solid #334155; border-radius: 8px; color: white; 
            box-sizing: border-box; 
        }
        input:focus { border-color: var(--neon-cyan); outline: none; }
        
        /* Add Button */
        .btn-add {
            width: 100%; padding: 15px; background: var(--neon-cyan); border: none; 
            font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 10px; 
            font-size: 1em; color: #000;
            box-shadow: 0 0 15px rgba(0, 242, 255, 0.4);
        }
        .btn-add:hover { background: white; }

        /* Results Grid */
        .results-container { width: 600px; }
        .result-item {
            display: flex; align-items: center; gap: 15px; 
            background: rgba(255,255,255,0.05); padding: 15px; 
            border-radius: 12px; margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .result-item img { width: 60px; height: 60px; object-fit: contain; background: white; border-radius: 8px; }
        .result-info { flex: 1; }
        .result-title { font-weight: bold; font-size: 0.9em; margin-bottom: 5px; }
        .result-price { color: var(--neon-cyan); font-weight: bold; }
        .btn-small {
            padding: 8px 20px; background: transparent; border: 1px solid var(--neon-cyan);
            color: var(--neon-cyan); border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .btn-small:hover { background: var(--neon-cyan); color: black; }
        
        .message-box {
            background: rgba(0, 242, 255, 0.1); border: 1px solid var(--neon-cyan);
            color: var(--neon-cyan); padding: 15px; border-radius: 8px; margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="panel">
        <div class="panel-head">
            <h2 style="margin: 0;">ADD NEW ASSET</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.8;">Start tracking price signals</p>
        </div>
        <div class="panel-body">
            <?php if ($message): ?>
                <div class="message-box"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label>Product Name or URL</label>
                    <input type="text" name="query" placeholder="e.g. Sony Headphones or Amazon Link..." required value="<?= htmlspecialchars($_POST['query'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Target Price (₹)</label>
                    <input type="number" name="target_price" placeholder="Optional" value="<?= htmlspecialchars($searched_price) ?>">
                </div>
                <button type="submit" name="search" class="btn-add">INITIATE TRACKING</button>
            </form>
        </div>
    </div>

    <?php if (!empty($results)): ?>
        <div class="results-container">
            <h3 style="color: #94a3b8; margin-bottom: 20px;">SEARCH RESULTS</h3>
            <?php foreach ($results as $item): ?>
                <div class="result-item">
                    <img src="<?= htmlspecialchars($item['product_photo']) ?>" alt="Product">
                    <div class="result-info">
                        <div class="result-title"><?= htmlspecialchars($item['product_title']) ?></div>
                        <div class="result-price"><?= htmlspecialchars($item['product_price']) ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="import" value="1">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($item['product_title']) ?>">
                        <input type="hidden" name="price" value="<?= htmlspecialchars($item['product_price']) ?>">
                        <input type="hidden" name="image" value="<?= htmlspecialchars($item['product_photo']) ?>">
                        <input type="hidden" name="asin" value="<?= htmlspecialchars($item['asin']) ?>">
                        <!-- Pass the target price from the initial search -->
                        <input type="hidden" name="target_price" value="<?= htmlspecialchars($searched_price) ?>">
                        
                        <button class="btn-small">TRACK</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>
