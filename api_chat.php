<?php
require 'config.php';
require 'functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userQuery = $input['message'] ?? '';

    if (!$userQuery) {
        echo json_encode(['error' => 'No message provided']);
        exit;
    }

    // 1. Fetch Context (Products & Price Stats)
    // We calculate Avg, Min, Max from history to give AI "Deal Intelligence"
    $stmt = $pdo->query("
        SELECT 
            p.name, 
            p.description,
            (SELECT price FROM vendor_prices vp WHERE vp.product_id = p.id ORDER BY timestamp DESC LIMIT 1) as current_price,
            (SELECT name FROM vendors v JOIN vendor_prices vp ON v.id = vp.vendor_id WHERE vp.product_id = p.id ORDER BY vp.timestamp DESC LIMIT 1) as vendor,
            AVG(ph.price) as avg_price,
            MIN(ph.price) as min_price,
            MAX(ph.price) as max_price
        FROM products p
        LEFT JOIN price_history ph ON p.id = ph.product_id
        GROUP BY p.id
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $context = "CURRENT DATABASE STATUS (Real-time Data):\n";
    foreach ($products as $p) {
        $curr = $p['current_price'];
        $avg = round($p['avg_price'], 2);
        $min = $p['min_price'];
        $max = $p['max_price'];
        
        // Determine Deal Status for AI Context
        $status = "Fair Price";
        if ($curr <= $min) $status = "HISTORIC LOW (Great Deal!)";
        elseif ($curr < $avg) $status = "Good Deal (Below Average)";
        elseif ($curr > $avg * 1.1) $status = "Overpriced (Wait)";
        
        $context .= "- Product: {$p['name']}\n";
        $context .= "  Price: ₹{$curr} (at {$p['vendor']})\n";
        $context .= "  Stats: Avg: ₹{$avg} | Low: ₹{$min} | High: ₹{$max}\n";
        $context .= "  Analysis: $status\n";
        $context .= "  Desc: {$p['description']}\n\n";
    }

    // 2. Construct Prompt
    $systemPrompt = "You are 'Blu', the AI Analyst for PriceScope Pro.
    
    YOUR CAPABILITIES:
    1. DATABASE ACCESS: You have access to the user's tracked products (listed below). Use this data to answer specific questions about price, history, and deal quality.
    2. GENERAL KNOWLEDGE: For products NOT in the database, use your own internal knowledge (internet data) to provide general advice, specs, and estimated market prices.
    
    RULES:
    - If asked 'Is it a good time to buy?', compare the Current Price to the Stats (Avg/Low).
    - If Current Price <= Low, explicitly recommend buying immediately.
    - If the user asks about a product you don't track, say: 'I'm not tracking that yet, but here is what I know...' and provide general info.
    - Be professional, concise, and data-driven. Use emojis sparingly (📉, 🚀, ✅).";

    $fullPrompt = $systemPrompt . "\n\n" . $context . "\n\nUser Query: " . $userQuery . "\nBlu's Analysis:";

    // 3. Call Gemini API
    $apiKey = GEMINI_API_KEY;
    // Switched to gemini-pro which is widely available
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

    $requestJson = json_encode([
        "contents" => [
            ["parts" => [["text" => $fullPrompt]]]
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
        echo json_encode(['error' => 'Curl error: ' . curl_error($ch)]);
        exit;
    }
    
    curl_close($ch);

    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $aiReply = $data['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['reply' => $aiReply]);
    } else {
        // Return the actual error from Google
        $errorMsg = $data['error']['message'] ?? 'Unknown Gemini API Error';
        $errorCode = $data['error']['code'] ?? 'N/A';
        echo json_encode(['error' => "API Error ($errorCode): $errorMsg", 'raw' => $data]);
    }
}
?>
