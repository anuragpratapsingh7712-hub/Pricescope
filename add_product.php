<?php
require 'config.php';
require 'functions.php';
requireLogin(); // Ensure user is logged in

$message = "";
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $query = urlencode($_POST['query']);
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
            // Fix MAMP SSL
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
        $desc = "Imported by " . $_SESSION['user_name'];

        try {
            // Insert Product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, base_price, image_url, asin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $price, $image, $asin]);
            $pid = $pdo->lastInsertId();

            // Insert Initial Price
            $stmt = $pdo->prepare("INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES (?, 1, ?)");
            $stmt->execute([$pid, $price]);

            // Generate Fake History (so chart works immediately for demo)
            // In a real app, we would start with 0 history
            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $histPrice = $price * (1 + (rand(-5, 5)/100));
                $stmt = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
                $stmt->execute([$pid, $histPrice, $date]);
            }

            // Auto-add to user's watchlist
            $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $pid]);

            $message = "✅ Added to Catalog & Watchlist: " . htmlspecialchars($name);
        } catch (PDOException $e) {
            $message = "Error: Product might already exist.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - PriceScope</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div style="display: flex; justify-content: center; padding-top: 50px;">
        <div class="glass-card" style="width: 600px; padding: 0; overflow: hidden;">
            <div style="background: linear-gradient(45deg, var(--neon-cyan), var(--primary-color)); padding: 30px; text-align: center; color: #020617;">
                <h2 style="margin: 0; font-weight: 900;">ADD NEW ASSET</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.8; font-weight: 600;">Start tracking price signals</p>
            </div>
            
            <div style="padding: 40px;">
                <?php if ($message): ?>
                    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--neon-cyan); color: var(--neon-cyan);">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label>SEARCH AMAZON</label>
                        <input type="text" name="query" placeholder="e.g. 'Sony WH-1000XM5'" required>
                    </div>
                    <button type="submit" name="search" class="btn btn-primary" style="width: 100%;">SEARCH MARKETPLACE</button>
                </form>

                <?php if (!empty($results)): ?>
                    <div style="margin-top: 40px;">
                        <h4 style="color: var(--text-muted); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">RESULTS</h4>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($results as $item): ?>
                                <div style="display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px;">
                                    <img src="<?= htmlspecialchars($item['product_photo']) ?>" style="width: 50px; height: 50px; object-fit: contain; background: white; border-radius: 4px;">
                                    <div style="flex: 1;">
                                        <div style="font-size: 0.9em; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px;"><?= htmlspecialchars($item['product_title']) ?></div>
                                        <div style="color: var(--neon-green);"><?= htmlspecialchars($item['product_price']) ?></div>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="import" value="1">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($item['product_title']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($item['product_price']) ?>">
                                        <input type="hidden" name="image" value="<?= htmlspecialchars($item['product_photo']) ?>">
                                        <input type="hidden" name="asin" value="<?= htmlspecialchars($item['asin']) ?>">
                                        <button class="btn btn-outline" style="padding: 5px 15px; font-size: 0.8em;">ADD</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
