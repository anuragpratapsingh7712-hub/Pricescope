<?php
// Suppress all errors to prevent image corruption
error_reporting(0);
ini_set('display_errors', 0);

// Start Output Buffering
ob_start();

require 'config.php'; // Optional, but good for consistency if needed later

// Clean buffer
ob_clean();

// Set Headers
header("Content-Type: image/png");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check for GD Library
if (!function_exists('imagecreatetruecolor')) {
    // Fallback: Create a 1x1 error pixel if GD is missing (or just die)
    // But ideally we want to see if this runs.
    die("GD Library Missing");
}

// Generate Random Code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$length = 5;
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

// Stateless Verification: Store Hash in Cookie
$secret = "PriceScope_Secret_Key_99";
$hash = hash_hmac('sha256', strtoupper($code), $secret);

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
setcookie('captcha_hash', $hash, time() + 300, '/', '', $isSecure, true);

// Create Image
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg = imagecolorallocate($image, 2, 6, 23); // Deep dark blue (#020617)
$text_color = imagecolorallocate($image, 0, 242, 255); // Neon Cyan (#00f2ff)
$line_color = imagecolorallocate($image, 188, 19, 254); // Neon Purple
$noise_color = imagecolorallocate($image, 30, 41, 59); // Slate

imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Add Noise (Dots)
for($i=0; $i<50; $i++) {
    imagesetpixel($image, rand(0,$width), rand(0,$height), $noise_color);
}

// Add Noise (Lines)
for($i=0; $i<5; $i++) {
    imageline($image, 0, rand(0,$height), $width, rand(0,$height), $line_color);
}

// Add Text
$font = 5;
$font_width = imagefontwidth($font);
$font_height = imagefontheight($font);
$text_width = $font_width * strlen($code);
$x = ($width - $text_width) / 2;
$y = ($height - $font_height) / 2;

imagestring($image, $font, $x, $y, $code, $text_color);

// Output
imagepng($image);
imagedestroy($image);

// Flush buffer
ob_end_flush();
?>
