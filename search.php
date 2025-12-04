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

    // Perform Search (Redirect to add_product.php or show mock results)
    // For this standalone page, we'll show mock results as per previous logic, 
    // but ideally this should link to the tracking system.
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
    <title>PriceScope Pro - Search Terminal</title>
    <style>
        /* Base Variables & Overrides for this Page */
        :root { --bg: #020617; --neon-cyan: #00f2ff; }

        /* Global & Body Styles */
        body { 
            background: var(--bg); 
            color: white; 
            font-family: 'Segoe UI', sans-serif; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            margin: 0;
        }
        
        /* Search Container (Glass Card variant) */
        .search-container {
            width: 600px; padding: 40px; border-radius: 30px;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 40px rgba(0, 242, 255, 0.1);
            backdrop-filter: blur(20px);
            text-align: center;
        }
        
        /* Search Bar Group */
        .search-bar {
            display: flex; margin-top: 20px; border-radius: 50px; overflow: hidden; 
            border: 1px solid var(--neon-cyan);
            box-shadow: 0 0 15px rgba(0, 242, 255, 0.2);
        }
        input { 
            flex: 1; padding: 20px; background: transparent; border: none; 
            color: white; font-size: 1.1em; outline: none; 
        }
        button { 
            padding: 0 30px; background: var(--neon-cyan); border: none; font-weight: bold; 
            cursor: pointer; transition: 0.3s; 
        }
        button:hover { background: white; box-shadow: 0 0 20px white; }
        h1 { font-weight: 200; letter-spacing: 2px; }
        
        /* OCR Toggle */
        .ocr-toggle { 
            margin-bottom: 20px; display: inline-flex; background: rgba(0,0,0,0.3); 
            padding: 5px; border-radius: 20px; 
        }
        .toggle-opt { padding: 8px 20px; border-radius: 15px; cursor: pointer; font-size: 0.9em; transition: 0.3s; }
        .active { background: rgba(255,255,255,0.1); color: var(--neon-cyan); }

        /* Results Grid */
        .results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
        .result-card { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; text-align: left; }
        .result-card img { width: 100%; border-radius: 10px; margin-bottom: 10px; }
    </style>
    <script>
        function toggleSearchMode(mode) {
            const textInput = document.getElementById('text-input-container');
            const imgInput = document.getElementById('img-input-container');
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
</head>
<body>
    <div class="search-container">
        <h1>FIND <span style="color: #00f2ff;">PRODUCT</span></h1>
        
        <div class="ocr-toggle">
            <span class="toggle-opt active" onclick="toggleSearchMode('text')">Text Search</span>
            <span class="toggle-opt" onclick="toggleSearchMode('image')">Image OCR</span>
        </div>

        <?php if ($ocrMsg): ?>
            <div style="margin-bottom: 15px; color: var(--neon-cyan); font-size: 0.9em;"><?= $ocrMsg ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="search-bar">
                <div id="text-input-container" style="flex: 1;">
                    <input type="text" name="query" placeholder="Paste URL or type product name..." value="<?= htmlspecialchars($query ?? '') ?>">
                </div>
                <div id="img-input-container" style="flex: 1; display: none;">
                    <input type="file" name="image" accept="image/*" style="padding: 15px; color: #94a3b8;">
                </div>
                <button type="submit">SEARCH</button>
            </div>
        </form>

        <?php if (!empty($results)): ?>
            <div class="results-grid">
                <?php foreach ($results as $r): ?>
                <div class="result-card">
                    <img src="<?= $r['image'] ?>" alt="Product">
                    <h4 style="margin: 0 0 5px 0; font-size: 0.9em;"><?= htmlspecialchars($r['name']) ?></h4>
                    <div style="color: var(--neon-cyan); font-weight: bold;">₹<?= number_format($r['price']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
