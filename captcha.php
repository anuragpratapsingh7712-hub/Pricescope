<?php
require 'config.php'; // Includes session setup

// Generate Random Code
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I, 1, O, 0 to avoid confusion
$length = 5;
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

$_SESSION['login_captcha'] = $code;

// Create Image
$width = 120;
$height = 40;
$image = imagecreate($width, $height);

// Colors
$bg = imagecolorallocate($image, 2, 6, 23); // Deep dark blue (matches theme)
$text_color = imagecolorallocate($image, 0, 242, 255); // Neon Cyan
$line_color = imagecolorallocate($image, 188, 19, 254); // Neon Purple

// Background Noise (Lines)
for($i=0; $i<5; $i++) {
    imageline($image, 0, rand()%$height, $width, rand()%$height, $line_color);
}

// Add Text
$font_size = 5; // Built-in font (1-5)
$x = 30;
$y = 12;
imagestring($image, $font_size, $x, $y, $code, $text_color);

// Output
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>
