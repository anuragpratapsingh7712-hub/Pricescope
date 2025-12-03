<?php
require 'config.php';

echo "<h1>Initializing Database...</h1>";

try {
    // Read SQL file
    $sql = file_get_contents('db.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    echo "<p class='text-success'>✅ Database tables created and seeded successfully.</p>";
    
    // Create Default User
    $name = "Demo User";
    $email = "user@test.com";
    $pass = "password";
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $hash]);
        echo "<p class='text-success'>✅ Created demo user: <strong>$email</strong> / <strong>$pass</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='text-warning'>⚠️ Demo user already exists.</p>";
    }

    // Create OR Update Admin User
    $adminEmail = "admin@pricescope.com";
    $adminPass = "admin123"; // Meets new criteria: 8 chars, letters + numbers
    $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetch()) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, is_admin = 1 WHERE email = ?");
        $stmt->execute([$adminHash, $adminEmail]);
        echo "<p class='text-success'>✅ Admin password RESET to: <strong>$adminPass</strong></p>";
    } else {
        // Create new
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute(["Admin User", $adminEmail, $adminHash]);
        echo "<p class='text-success'>✅ Created ADMIN user: <strong>$adminEmail</strong> / <strong>$adminPass</strong></p>";
    }
    
    echo "<hr>";
    echo "<a href='index.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "<p class='text-danger'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
<style>
    body { font-family: sans-serif; padding: 2rem; line-height: 1.5; }
    .text-success { color: green; }
    .text-danger { color: red; }
    .text-warning { color: orange; }
</style>
