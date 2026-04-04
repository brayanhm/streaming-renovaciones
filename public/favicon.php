<?php
declare(strict_types=1);

$src = __DIR__ . '/img/logo.png';

if (!file_exists($src)) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');

if (function_exists('imagecreatefromstring') && function_exists('imagecopyresampled')) {
    $orig = imagecreatefromstring((string) file_get_contents($src));
    if ($orig !== false) {
        $dest = imagecreatetruecolor(32, 32);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
        imagecopyresampled($dest, $orig, 0, 0, 0, 0, 32, 32, imagesx($orig), imagesy($orig));
        imagepng($dest, null, 9);
        imagedestroy($dest);
        imagedestroy($orig);
        exit;
    }
    imagedestroy($orig);
}

readfile($src);
