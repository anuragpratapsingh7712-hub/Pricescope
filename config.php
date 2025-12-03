<?php
// Database Configuration
// Try to get credentials from Environment Variables (Cloud) or use Local Defaults (MAMP)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'pricescope';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';
$port = getenv('DB_PORT') ?: 8889; // MAMP default is 8889, Cloud usually 3306

// Gemini API Key (Set this in your Cloud Dashboard too!)
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'YOUR_LOCAL_API_KEY_HERE');
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
     error_log("DB Connection Error: " . $e->getMessage());
     // Show generic message to user
     die("<h3>Service Temporarily Unavailable</h3><p>We are experiencing technical difficulties. Please try again later.</p>");
}

session_start();

// Security: Hide errors from users, log them instead
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

define('GOOGLE_API_KEY', 'AIzaSyDNYKDf5OOlBUSK2I0y7WKiUx6nedtchIY');
define('GEMINI_API_KEY', 'AIzaSyCyZcpx5mQVIsj_4XURJ_TugqWAQa0w6ps');
define('RAPID_API_KEY', '818924e299msh6f9222b802b50cep111d79jsn0f574a5e5647');
?>
