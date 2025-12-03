<?php
require 'config.php';

try {
    echo "<h1>🧹 Cleaning Up Database...</h1>";

    // 1. Get IDs of products with NO vendor prices
    // We use a subquery to find products that are NOT in the vendor_prices table
    $sql = "DELETE FROM products WHERE id NOT IN (SELECT DISTINCT product_id FROM vendor_prices)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $count = $stmt->rowCount();

    echo "<p>✅ Deleted <strong>$count</strong> products that had no price data.</p>";
    
    // Optional: Also delete orphaned price history if any (though foreign keys usually handle this or restrict it)
    // But since we are cleaning up, let's be safe.
    
    echo "<p>Database is now clean!</p>";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
