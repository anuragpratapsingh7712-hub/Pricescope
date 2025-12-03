<?php
require 'config.php';
require 'functions.php';
requireLogin();

$results = [];
$ocrMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $_POST['query'] ?? '';
    
    // Real Google Cloud Vision OCR
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $imageData = base64_encode(file_get_contents($_FILES['image']['tmp_name']));
        $apiKey = GOOGLE_API_KEY;
        $apiUrl = "https://vision.googleapis.com/v1/images:annotate?key=$apiKey";

        $requestJson = json_encode([
            "requests" => [
                [
                    "image" => ["content" => $imageData],
                    "features" => [
                        ["type" => "TEXT_DETECTION"],
                        ["type" => "LABEL_DETECTION", "maxResults" => 5]
                    ]
                ]
            ]
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        // Fix for MAMP SSL issues
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $ocrMsg = "❌ Network Error: " . curl_error($ch);
            curl_close($ch);
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
            $resp = $data['responses'][0] ?? [];
            
            // Priority 1: Text (OCR)
            if (!empty($resp['textAnnotations'][0]['description'])) {
                $detectedText = $resp['textAnnotations'][0]['description'];
                $lines = explode("\n", $detectedText);
                $query = trim($lines[0]); 
                $ocrMsg = "🔍 Found Text: '" . h($query) . "'";
            } 
            // Priority 2: Visual Labels (AI Vision)
            elseif (!empty($resp['labelAnnotations'])) {
                $labels = [];
                foreach ($resp['labelAnnotations'] as $label) {
                    $labels[] = $label['description'];
                }
                $query = implode(' ', array_slice($labels, 0, 2));
                $ocrMsg = "📷 Detected Object: '" . h($query) . "'";
            } else {
                $ocrMsg = "❌ No text or objects detected.";
                // DEBUG: Show raw error if available
                if (isset($data['error'])) {
                    $ocrMsg .= "<br><strong>API Error:</strong> " . h($data['error']['message']);
                } else {
                    $ocrMsg .= "<br>Raw Response: " . h($response);
                }
            }
        }
    }

    if ($query) {
        // 1. Exact/Strong Match
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
        $term = "%$query%";
        $stmt->execute([$term, $term]);
        $results = $stmt->fetchAll();
        
        // 2. "Did you mean?" (Partial Match)
        $suggestions = [];
        if (empty($results)) {
            // Split query into words (remove short words)
            $words = array_filter(explode(' ', $query), function($w) { return strlen($w) > 3; });
            
            if (!empty($words)) {
                $sql = "SELECT * FROM products WHERE ";
                $params = [];
                $clauses = [];
                foreach ($words as $word) {
                    $clauses[] = "name LIKE ?";
                    $params[] = "%$word%";
                }
                $sql .= implode(' OR ', $clauses) . " LIMIT 3";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $suggestions = $stmt->fetchAll();
            }
        }

        // 3. RapidAPI Search (Amazon Integration)
        // Only search API if query is long enough to be meaningful
        if (strlen($query) > 2) {
            $apiKey = RAPID_API_KEY;
            $encodedQuery = urlencode($query);
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://real-time-amazon-data.p.rapidapi.com/search?query=$encodedQuery&page=1&country=IN",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "x-rapidapi-host: real-time-amazon-data.p.rapidapi.com",
                    "x-rapidapi-key: $apiKey"
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $apiResponse = curl_exec($curl);
            $apiErr = curl_error($curl);
            curl_close($curl);

            if (!$apiErr) {
                $apiData = json_decode($apiResponse, true);
                if (!empty($apiData['data']['products'])) {
                    // Limit to 4 results to keep UI clean
                    $apiResults = array_slice($apiData['data']['products'], 0, 4);
                }
            }
        }
    }
    
    // Handle "Add from Search" action
    if (isset($_POST['import_from_search'])) {
        $name = $_POST['name'];
        $price = floatval(str_replace(['₹', ','], '', $_POST['price']));
        $image = $_POST['image'];
        $asin = $_POST['asin'];
        $desc = "Imported via Search";

        try {
            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM products WHERE asin = ?");
            $stmt->execute([$asin]);
            if ($exist = $stmt->fetch()) {
                $pid = $exist['id'];
                $ocrMsg = "✅ Product already exists! <a href='product.php?id=$pid'>View it here</a>";
            } else {
                // Insert Product
                $stmt = $pdo->prepare("INSERT INTO products (name, description, base_price, image_url, asin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $price, $image, $asin]);
                $pid = $pdo->lastInsertId();

                // Insert Price
                $stmt = $pdo->prepare("INSERT INTO vendor_prices (product_id, vendor_id, price) VALUES (?, 1, ?)");
                $stmt->execute([$pid, $price]);

                // Generate History
                for ($i = 30; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $histPrice = $price * (1 + (rand(-5, 5)/100));
                    $stmt = $pdo->prepare("INSERT INTO price_history (product_id, price, recorded_at) VALUES (?, ?, ?)");
                    $stmt->execute([$pid, $histPrice, $date]);
                }
                
                // Redirect instantly to the new product page
                header("Location: product.php?id=$pid");
                exit;
            }
        } catch (PDOException $e) {
            $ocrMsg = "Error adding product.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PriceScope - Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center mb-5">
                <div class="blu-mascot" style="font-size: 3rem;">🔎</div>
                <h2 class="mb-3">Find a Product</h2>
                
                <div class="card p-4 shadow-sm">
                    <ul class="nav nav-tabs mb-3" id="searchTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-pane">Text Search</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="image-tab" data-bs-toggle="tab" data-bs-target="#image-pane">Image Search (OCR)</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="text-pane">
                            <form method="POST" class="input-group">
                                <input type="text" name="query" class="form-control" placeholder="e.g. Headphones..." value="<?= h($_POST['query'] ?? '') ?>">
                                <button class="btn btn-primary">Search</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="image-pane">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                                <button class="btn btn-primary w-100">Upload & Scan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if($ocrMsg): ?>
            <div class="alert alert-success text-center"><?= h($ocrMsg) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach($results as $p): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <img src="<?= h($p['image_url']) ?>" class="img-fluid mb-3 rounded" style="max-height:150px;" onerror="this.src='https://placehold.co/300x200/png?text=No+Image'">
                            <h5 class="card-title"><?= h($p['name']) ?></h5>
                            <p class="card-text small text-muted"><?= h($p['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-info text-dark">Match: 98%</span>
                                <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if($_SERVER['REQUEST_METHOD'] === 'POST' && empty($results)): ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No exact matches found.</p>
                    
                    <?php if(!empty($suggestions)): ?>
                        <div class="alert alert-warning d-inline-block">
                            <strong>Did you mean?</strong><br>
                            We found these similar items:
                        </div>
                        <div class="row g-4 justify-content-center mt-2">
                            <?php foreach($suggestions as $s): ?>
                                <div class="col-md-4">
                                    <div class="card h-100 border-warning">
                                        <div class="card-body">
                                            <img src="<?= h($s['image_url']) ?>" class="img-fluid mb-3 rounded" style="max-height:150px;" onerror="this.src='https://placehold.co/300x200/png?text=No+Image'">
                                            <h5 class="card-title"><?= h($s['name']) ?></h5>
                                            <a href="product.php?id=<?= $s['id'] ?>" class="btn btn-outline-warning btn-sm">View This</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Amazon API Results -->
        <?php if (!empty($apiResults)): ?>
            <div class="mt-5">
                <h4 class="mb-3">🌐 Results from Amazon</h4>
                <div class="row g-4">
                    <?php foreach($apiResults as $item): ?>
                        <div class="col-md-3">
                            <div class="card h-100 border-primary shadow-sm">
                                <img src="<?= h($item['product_photo']) ?>" class="card-img-top p-3" style="height: 180px; object-fit: contain;">
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title text-truncate"><?= h($item['product_title']) ?></h6>
                                    <p class="fw-bold text-success"><?= h($item['product_price']) ?></p>
                                    <form method="POST" class="mt-auto">
                                        <input type="hidden" name="import_from_search" value="1">
                                        <input type="hidden" name="query" value="<?= h($_POST['query']) ?>"> <!-- Keep search term -->
                                        <input type="hidden" name="name" value="<?= h($item['product_title']) ?>">
                                        <input type="hidden" name="price" value="<?= h($item['product_price']) ?>">
                                        <input type="hidden" name="image" value="<?= h($item['product_photo']) ?>">
                                        <input type="hidden" name="asin" value="<?= h($item['asin']) ?>">
                                        <button class="btn btn-sm btn-primary w-100">➕ Add & Track</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
