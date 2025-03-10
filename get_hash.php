#!/usr/bin/env php
<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers;
use Sidus\PerceptualHash\PerceptualHash;

if ($argc < 2) {
    echo "Usage: php get_hash.php <image_path> (...<image_path>)\n";
    exit(1);
}

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

$lastHash = null;
foreach (array_slice($argv, 1) as $imagePath) {
    if (!file_exists($imagePath)) {
        echo "File not found: $imagePath\n";
        continue;
    }
    try {
        $image = $manager->read($imagePath);
    } catch (Exception $e) {
        echo "Error loading image: {$e->getMessage()}\n";
        exit(1);
    }

    $hash = PerceptualHash::hash($image);
    if (null !== $lastHash) {
        // Perform a bitwise XOR operation with the last hash
        $diff = $hash ^ $lastHash;
        $distance = PerceptualHash::distance($hash, $lastHash);
        echo '                   - 0b'.str_pad(decbin($diff), 64, '0', STR_PAD_LEFT)." : (distance = {$distance})\n";
    }
    echo '0x'.dechex($hash).' - 0b'.str_pad(decbin($hash), 64, '0', STR_PAD_LEFT)." : {$imagePath}\n";

    $lastHash = $hash;
}
