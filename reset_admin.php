<?php
require 'config.php';

$email = "admin@pricescope.com";
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Delete if exists to force reset
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
    
    // Create fresh
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->execute(["Admin User", $email, $hash]);
    
    echo "<h1>✅ Admin Reset Successful</h1>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p>Database Host: " . getenv('DB_HOST') . " (Should be trolley...)</p>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
