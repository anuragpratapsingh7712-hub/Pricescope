<?php
require 'config.php';
require 'functions.php';

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
        $desc = "Imported from Amazon via RapidAPI";

        try {
            // Insert Product
            $stmt = $pdo->prepare("INSERT INTO products (name, description, base_price, image_url, asin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $price, $image, $asin]);
            $pid = $pdo->lastInsertId();

            // Insert Initial Price
            $stmt = $pdo->prepare("INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES (?, 1, ?)");
            $stmt->execute([$pid, $price]);

            // Generate Fake History (so chart works)
            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $histPrice = $price * (1 + (rand(-5, 5)/100));
                $stmt = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
                $stmt->execute([$pid, $histPrice, $date]);
            }

            $message = "✅ Imported: " . h($name);
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
    <title>PriceScope - Import Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0">📦 Import from Amazon (RapidAPI)</h3>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?= h($message) ?></div>
                <?php endif; ?>

                <form method="POST" class="d-flex gap-2 mb-4">
                    <input type="text" name="query" class="form-control" placeholder="Search Amazon (e.g. 'Gaming Mouse')" required>
                    <button type="submit" name="search" class="btn btn-primary">Search API</button>
                </form>

                <?php if (!empty($results)): ?>
                    <h5>Search Results</h5>
                    <div class="row g-3">
                        <?php foreach ($results as $item): ?>
                            <div class="col-md-3">
                                <div class="card h-100">
                                    <img src="<?= h($item['product_photo']) ?>" class="card-img-top p-3" style="height: 200px; object-fit: contain;">
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate"><?= h($item['product_title']) ?></h6>
                                        <p class="fw-bold text-success"><?= h($item['product_price']) ?></p>
                                        <form method="POST">
                                            <input type="hidden" name="import" value="1">
                                            <input type="hidden" name="name" value="<?= h($item['product_title']) ?>">
                                            <input type="hidden" name="price" value="<?= h($item['product_price']) ?>">
                                            <input type="hidden" name="image" value="<?= h($item['product_photo']) ?>">
                                            <input type="hidden" name="asin" value="<?= h($item['asin']) ?>">
                                            <button class="btn btn-sm btn-outline-success w-100">Import to DB</button>
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
</body>
</html>
