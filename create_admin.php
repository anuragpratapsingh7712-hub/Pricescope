<?php
// Railway Credentials
$host = 'trolley.proxy.rlwy.net';
$db   = 'railway';
$user = 'root';
$pass = 'LSlOBYdceoJjGLaympdbyxSWFyuwwYsV';
$port = 40837;

echo "Connecting to Railway Database...\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully!\n";

    // Create Admin User
    $name = "Admin User";
    $email = "admin@pricescope.com";
    $password = "admin123";
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "⚠️ Admin user already exists.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $email, $hash]);
        echo "✅ Admin user created successfully!\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
