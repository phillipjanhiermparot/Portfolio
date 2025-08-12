<?php
session_start();

// Function to generate a random string
function generateRandomString($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Generate the random word for the captcha
$captcha_text = generateRandomString();
$_SESSION['captcha_text'] = $captcha_text;

// Image dimensions
$width = 120;
$height = 40;

// Create the image
$image = imagecreatetruecolor($width, $height);

// Background color (light gray)
$bg_color = imagecolorallocate($image, 220, 220, 220);
imagefill($image, 0, 0, $bg_color);

// Text color (dark blue)
$text_color = imagecolorallocate($image, 0, 0, 128);

// Font settings (adjust path if needed)
$font = './Arimo-VariableFont_wght.ttf'; // You might need to adjust this path to your font file

// Add some noise (optional)
for ($i = 0; $i < 30; $i++) {
    $noise_color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imagesetpixel($image, rand(0, $width - 1), rand(0, $height - 1), $noise_color);
}

// Add the text with some distortion
$angle = rand(-15, 15);
$x = 15;
$y = 25;
imagettftext($image, 20, $angle, $x, $y, $text_color, $font, $captcha_text);

// Output the image as a PNG
header('Content-type: image/png');
imagepng($image);

// Destroy the image to free up memory
imagedestroy($image);
?>