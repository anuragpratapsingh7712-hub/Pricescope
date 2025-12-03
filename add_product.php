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

            $message = "✅ Added to Catalog & Watchlist: " . h($name);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-primary text-white p-4 text-center">
                        <div style="font-size: 3rem;">🛍️</div>
                        <h2 class="mb-0 fw-bold">Add New Product</h2>
                        <p class="mb-0 opacity-75">Search Amazon and add items to PriceScope</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-info rounded-3"><?= h($message) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="d-flex gap-2 mb-5">
                            <input type="text" name="query" class="form-control form-control-lg rounded-pill" placeholder="Search Amazon (e.g. 'Sony WH-1000XM5')" required>
                            <button type="submit" name="search" class="btn btn-primary btn-lg rounded-pill px-4">Search</button>
                        </form>

                        <?php if (!empty($results)): ?>
                            <h5 class="mb-3 fw-bold">Search Results</h5>
                            <div class="row g-4">
                                <?php foreach ($results as $item): ?>
                                    <div class="col-md-3">
                                        <div class="card h-100 border-0 shadow-sm hover-scale">
                                            <div class="position-relative" style="height: 200px; overflow: hidden;">
                                                <img src="<?= h($item['product_photo']) ?>" class="w-100 h-100 p-3" style="object-fit: contain;">
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <h6 class="card-title text-truncate mb-2" title="<?= h($item['product_title']) ?>"><?= h($item['product_title']) ?></h6>
                                                <p class="fw-bold text-success fs-5 mb-3"><?= h($item['product_price']) ?></p>
                                                <form method="POST" class="mt-auto">
                                                    <input type="hidden" name="import" value="1">
                                                    <input type="hidden" name="name" value="<?= h($item['product_title']) ?>">
                                                    <input type="hidden" name="price" value="<?= h($item['product_price']) ?>">
                                                    <input type="hidden" name="image" value="<?= h($item['product_photo']) ?>">
                                                    <input type="hidden" name="asin" value="<?= h($item['asin']) ?>">
                                                    <button class="btn btn-outline-primary w-100 rounded-pill">Add to PriceScope</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
