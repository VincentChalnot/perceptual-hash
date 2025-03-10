#!/usr/bin/env php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers;
use Sidus\PerceptualHash\PerceptualHash;

if ($argc < 2) {
    echo "Usage: php get_hash.php <image_path>\n";
    exit(1);
}

$imagePath = $argv[1];
$size = $argv[2] ?? 32;

$drivers = [
    'gd' => Drivers\Gd\Driver::class,
    'imagick' => Drivers\Imagick\Driver::class,
];
foreach ($drivers as $driver) {
    try {
        $manager = new ImageManager(new $driver);
    } catch (Exception $exception) {}
}
if (!isset($manager)) {
    echo "No suitable image driver found, please install GD or Imagick PHP extension\n";
    exit(1);
}

try {
    $image = $manager->read($imagePath);
} catch (Exception $e) {
    echo "Error loading image: {$e->getMessage()}\n";
    exit(1);
}

$hash = new PerceptualHash();
$bits = $hash->hash($image);

foreach ($bits as $bit) {
    // Convert to binary string
    echo str_pad(decbin($bit), 8, '0', STR_PAD_LEFT);
}
echo "\n";
