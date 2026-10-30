<?php

// Generate local responsive variants from the existing licensed demo photographs.
$root = dirname(__DIR__).'/public/images';
$sources = json_decode(file_get_contents($root.'/SOURCES.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($sources as $source) {
    $name = $source['name'];
    $image = imagecreatefromwebp($root.'/'.$name.'.webp');
    foreach ([480, 960] as $width) {
        if (imagesx($image) <= $width) {
            continue;
        }
        $height = (int) round(imagesy($image) * $width / imagesx($image));
        $variant = imagecreatetruecolor($width, $height);
        imagecopyresampled($variant, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        imagewebp($variant, $root.'/'.$name.'-'.$width.'.webp', 78);
    }
}
echo "Responsive demo image variants generated.\n";
