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
            
            // 1. Check for Text (Model Number / Product Name)
            if (!empty($resp['textAnnotations'])) {
                $fullText = $resp['textAnnotations'][0]['description'];
                // Simple heuristic: Take first line or first 30 chars
                $lines = explode("\n", $fullText);
                $query = trim($lines[0]); 
                $ocrMsg = "🔍 OCR Detected Text: " . htmlspecialchars($query);
            } 
            // 2. Fallback to Labels (e.g. "Shoe", "Headphones")
            elseif (!empty($resp['labelAnnotations'])) {
                $labels = array_column($resp['labelAnnotations'], 'description');
                $query = $labels[0]; // Top label
                $ocrMsg = "🖼️ AI Recognized: " . htmlspecialchars(implode(", ", $labels));
            } else {
                $ocrMsg = "⚠️ No text or objects identified.";
            }
        }
    }

    // Perform Search (Mock or Real)
    if ($query) {
        // Mock Results for Demo
        $results = [
            ['name' => $query . ' - Pro Model', 'price' => 24999, 'image' => 'https://via.placeholder.com/150'],
            ['name' => $query . ' - Lite Edition', 'price' => 12999, 'image' => 'https://via.placeholder.com/150'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search - PriceScope Pro</title>
    <link href="style.css" rel="stylesheet">
    <script>
        function toggleSearchMode(mode) {
            const textInput = document.getElementById('text-input');
            const imgInput = document.getElementById('img-input');
            const opts = document.querySelectorAll('.toggle-opt');
            
            opts.forEach(o => o.classList.remove('active'));
            
            if (mode === 'text') {
                textInput.style.display = 'block';
                imgInput.style.display = 'none';
                opts[0].classList.add('active');
            } else {
                textInput.style.display = 'none';
                imgInput.style.display = 'block';
                opts[1].classList.add('active');
            }
        }
    </script>
    <style>
        .active { background: rgba(255,255,255,0.1); color: var(--neon-cyan); }
        .toggle-opt { padding: 8px 20px; border-radius: 15px; cursor: pointer; font-size: 0.9em; transition: 0.3s; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="search-wrapper">
        <div class="search-container">
            <h1 style="font-weight: 200; letter-spacing: 2px;">FIND <span style="color: var(--neon-cyan);">PRODUCT</span></h1>
            
            <div style="margin-bottom: 20px; display: inline-flex; background: rgba(0,0,0,0.3); padding: 5px; border-radius: 20px;">
                <span class="toggle-opt active" onclick="toggleSearchMode('text')">Text Search</span>
                <span class="toggle-opt" onclick="toggleSearchMode('image')">Image OCR</span>
            </div>

            <?php if ($ocrMsg): ?>
                <div style="margin-bottom: 20px; color: var(--neon-green);"><?= $ocrMsg ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="search-bar-group">
                    <div id="text-input" style="flex: 1;">
                        <input type="text" name="query" placeholder="Paste URL or type product name..." value="<?= htmlspecialchars($query ?? '') ?>">
                    </div>
                    <div id="img-input" style="flex: 1; display: none; padding: 10px;">
                        <input type="file" name="image" accept="image/*" style="border: none;">
                    </div>
                    <button type="submit" class="search-btn">SEARCH</button>
                </div>
            </form>

            <?php if (!empty($results)): ?>
                <div style="margin-top: 50px; text-align: left;">
                    <h3 style="color: var(--text-muted);">RESULTS</h3>
                    <div class="dashboard-grid">
                        <?php foreach ($results as $r): ?>
                        <div class="glass-card" style="padding: 20px;">
                            <div style="height: 150px; background: white; border-radius: 10px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center;">
                                <img src="<?= $r['image'] ?>" style="max-height: 100%;">
                            </div>
                            <h4 style="margin: 0 0 10px 0;"><?= htmlspecialchars($r['name']) ?></h4>
                            <div style="color: var(--neon-cyan); font-weight: bold; font-size: 1.2em;">₹<?= number_format($r['price']) ?></div>
                            <button class="btn btn-outline" style="width: 100%; margin-top: 15px;">Track This</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
