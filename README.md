# Perceptual Hash

This library is a rewrite of the perceptual hash implementation by Anatoly Pashin (@b1rdex) for the jenssegers/imagehash library made by Jens Segers (@jenssegers). It provides a way to generate perceptual hashes for images using PHP.

## Installation

To install the library, use Composer:

```sh
composer require sidus/perceptual-hash
```

## Usage

Here is a simple example of how to generate perceptual hashes for images:

```php
$manager = new \Intervention\Image\ImageManager($driver);
$image = $manager->make($imagePath);
$hash = PerceptualHash::hash($image);
```

And to compare two hashes:

```php
$distance = PerceptualHash::distance($hash1, $hash2);
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
