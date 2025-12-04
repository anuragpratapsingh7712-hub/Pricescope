<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Generate Random Code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$length = 5;
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

// Stateless Verification: Store Hash in Cookie
// Vercel/Serverless doesn't support persistent sessions well for this flow
$secret = "PriceScope_Secret_Key_99";
$hash = hash_hmac('sha256', strtoupper($code), $secret);

// Set Cookie (5 minutes expiry, Secure, HttpOnly)
// Note: In some local dev environments (non-https), 'Secure' might block the cookie. 
// We'll detect HTTPS.
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

// Add Text (Centered)
// Using built-in font 5 (largest built-in)
$font_width = imagefontwidth(5);
$font_height = imagefontheight(5);
$text_width = $font_width * strlen($code);
$x = ($width - $text_width) / 2;
$y = ($height - $font_height) / 2;

imagestring($image, 5, $x, $y, $code, $text_color);

// Output
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
