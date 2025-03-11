<?php
declare(strict_types=1);

namespace Sidus\PerceptualHash;

use Intervention\Image\Image;

/**
 * Refactored version of a class from the https://github.com/jenssegers/imagehash library.
 * Original class authored by Anatoly Pashin @b1rdex (via pull request).
 * Original library maintained by Jens Segers @jenssegers.
 *
 * Refactored and maintained by Vincent Chalnot.
 *
 * This file is part of a library licensed under the MIT License.
 * 
 * MIT License
 * Copyright (c) 2025 Vincent Chalnot
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * @author Vincent Chalnot
 * @author Anatoly Pashin (Original author of the class)
 * @license MIT (https://opensource.org/licenses/MIT)
 */
class PerceptualHash
{
    private const SIZE = 32;
    private const SIZE_SQRT = 0.25;
    private const MATRIX_SIZE = 11;

    private const DCT_11_32 = [
        [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        [0.99879546, 0.98917651, 0.97003125, 0.94154407, 0.90398929, 0.85772861, 0.80320753, 0.74095113, 0.67155895, 0.5956993, 0.51410274, 0.42755509, 0.33688985, 0.24298018, 0.14673047, 0.04906767, -0.04906767, -0.14673047, -0.24298018, -0.33688985, -0.42755509, -0.51410274, -0.5956993, -0.67155895, -0.74095113, -0.80320753, -0.85772861, -0.90398929, -0.94154407, -0.97003125, -0.98917651, -0.99879546],
        [0.99518473, 0.95694034, 0.88192126, 0.77301045, 0.63439328, 0.47139674, 0.29028468, 0.09801714, -0.09801714, -0.29028468, -0.47139674, -0.63439328, -0.77301045, -0.88192126, -0.95694034, -0.99518473, -0.99518473, -0.95694034, -0.88192126, -0.77301045, -0.63439328, -0.47139674, -0.29028468, -0.09801714, 0.09801714, 0.29028468, 0.47139674, 0.63439328, 0.77301045, 0.88192126, 0.95694034, 0.99518473],
        [0.98917651, 0.90398929, 0.74095113, 0.51410274, 0.24298018, -0.04906767, -0.33688985, -0.5956993, -0.80320753, -0.94154407, -0.99879546, -0.97003125, -0.85772861, -0.67155895, -0.42755509, -0.14673047, 0.14673047, 0.42755509, 0.67155895, 0.85772861, 0.97003125, 0.99879546, 0.94154407, 0.80320753, 0.5956993, 0.33688985, 0.04906767, -0.24298018, -0.51410274, -0.74095113, -0.90398929, -0.98917651],
        [0.98078528, 0.83146961, 0.55557023, 0.19509032, -0.19509032, -0.55557023, -0.83146961, -0.98078528, -0.98078528, -0.83146961, -0.55557023, -0.19509032, 0.19509032, 0.55557023, 0.83146961, 0.98078528, 0.98078528, 0.83146961, 0.55557023, 0.19509032, -0.19509032, -0.55557023, -0.83146961, -0.98078528, -0.98078528, -0.83146961, -0.55557023, -0.19509032, 0.19509032, 0.55557023, 0.83146961, 0.98078528],
        [0.97003125, 0.74095113, 0.33688985, -0.14673047, -0.5956993, -0.90398929, -0.99879546, -0.85772861, -0.51410274, -0.04906767, 0.42755509, 0.80320753, 0.98917651, 0.94154407, 0.67155895, 0.24298018, -0.24298018, -0.67155895, -0.94154407, -0.98917651, -0.80320753, -0.42755509, 0.04906767, 0.51410274, 0.85772861, 0.99879546, 0.90398929, 0.5956993, 0.14673047, -0.33688985, -0.74095113, -0.97003125],
        [0.95694034, 0.63439328, 0.09801714, -0.47139674, -0.88192126, -0.99518473, -0.77301045, -0.29028468, 0.29028468, 0.77301045, 0.99518473, 0.88192126, 0.47139674, -0.09801714, -0.63439328, -0.95694034, -0.95694034, -0.63439328, -0.09801714, 0.47139674, 0.88192126, 0.99518473, 0.77301045, 0.29028468, -0.29028468, -0.77301045, -0.99518473, -0.88192126, -0.47139674, 0.09801714, 0.63439328, 0.95694034],
        [0.94154407, 0.51410274, -0.14673047, -0.74095113, -0.99879546, -0.80320753, -0.24298018, 0.42755509, 0.90398929, 0.97003125, 0.5956993, -0.04906767, -0.67155895, -0.98917651, -0.85772861, -0.33688985, 0.33688985, 0.85772861, 0.98917651, 0.67155895, 0.04906767, -0.5956993, -0.97003125, -0.90398929, -0.42755509, 0.24298018, 0.80320753, 0.99879546, 0.74095113, 0.14673047, -0.51410274, -0.94154407],
        [0.92387953, 0.38268343, -0.38268343, -0.92387953, -0.92387953, -0.38268343, 0.38268343, 0.92387953, 0.92387953, 0.38268343, -0.38268343, -0.92387953, -0.92387953, -0.38268343, 0.38268343, 0.92387953, 0.92387953, 0.38268343, -0.38268343, -0.92387953, -0.92387953, -0.38268343, 0.38268343, 0.92387953, 0.92387953, 0.38268343, -0.38268343, -0.92387953, -0.92387953, -0.38268343, 0.38268343, 0.92387953],
        [0.90398929, 0.24298018, -0.5956993, -0.99879546, -0.67155895, 0.14673047, 0.85772861, 0.94154407, 0.33688985, -0.51410274, -0.98917651, -0.74095113, 0.04906767, 0.80320753, 0.97003125, 0.42755509, -0.42755509, -0.97003125, -0.80320753, -0.04906767, 0.74095113, 0.98917651, 0.51410274, -0.33688985, -0.94154407, -0.85772861, -0.14673047, 0.67155895, 0.99879546, 0.5956993, -0.24298018, -0.90398929],
        [0.88192126, 0.09801714, -0.77301045, -0.95694034, -0.29028468, 0.63439328, 0.99518473, 0.47139674, -0.47139674, -0.99518473, -0.63439328, 0.29028468, 0.95694034, 0.77301045, -0.09801714, -0.88192126, -0.88192126, -0.09801714, 0.77301045, 0.95694034, 0.29028468, -0.63439328, -0.99518473, -0.47139674, 0.47139674, 0.99518473, 0.63439328, -0.29028468, -0.95694034, -0.77301045, 0.09801714, 0.88192126],
    ];

    public static function distance(int $hash1, int $hash2): int
    {
        $diff = $hash1 ^ $hash2;
        $distance = 0;

        for ($i = 0; $i < 64; $i++) {
            if (($diff >> $i) & 1) {
                $distance++;
            }
        }

        return $distance;
    }

    /**
     * Calculate the perceptual hash of an image, returns the hash as an integer to allow bitwise operations.
     */
    public static function hash(Image $image): int
    {
        $resized = $image->greyscale()->resize(self::SIZE, self::SIZE);

        $rows = [];
        for ($y = 0; $y < self::SIZE; $y++) {
            $row = [];
            for ($x = 0; $x < self::SIZE; $x++) {
                $rgba = $resized->pickColor($x, $y)->toArray();
                $row[$x] = $rgba[0]; // Pick any channel, image is already grayscale
            }
            $rows[$y] = self::calculateDCT($row, self::MATRIX_SIZE);
        }

        $rowMatrixSize = self::MATRIX_SIZE;

        $matrix = [];
        for ($x = 0; $x < self::MATRIX_SIZE; $x++) {
            $col = [];
            for ($y = 0; $y < self::SIZE; $y++) {
                $col[$y] = $rows[$y][$x];
            }
            $matrix[$x] = self::calculateDCT($col, $rowMatrixSize);
            $rowMatrixSize--;
        }

        $pixels = self::diagonalMatrix($matrix, self::MATRIX_SIZE);

        $pixels = array_slice($pixels, 1, 64); // discard first and cut to size

        $compare = self::average($pixels);

        $bits = [];
        foreach ($pixels as $pixel) {
            $bits[] = ($pixel > $compare);
        }

        return self::getInteger($bits);
    }

    /**
     * Perform a 1 dimension Discrete Cosine Transformation.
     *
     * @param array<int,int|float> $matrix
     *
     * @return array<int,float>
     */
    private static function calculateDCT(array $matrix, int $partialSize): array
    {
        $transformed = [];

        for ($i = 0; $i < $partialSize; $i++) {
            $sum = 0;
            for ($j = 0; $j < self::SIZE; $j++) {
                $sum += $matrix[$j] * self::DCT_11_32[$i][$j];
            }
            $sum *= self::SIZE_SQRT;
            if ($i === 0) {
                $sum *= 0.70710678118655;
            }
            $transformed[$i] = $sum;
        }

        return $transformed;
    }

    /**
     * Get the diagonal matrix of the DCT.
     * 
     * @param array<int,float> $mat
     */
    private static function diagonalMatrix(array $mat, int $size = 11): array
    {
        $mode = 0;
        $lower = 0;
        $result = [];
        $max = (ceil((($size * $size) / 2) + ($size * 0.5)));
        for ($t = 0; $t < (2 * $size - 1); $t++) {
            $t1 = $t;
            if ($t1 >= $size) {
                $mode++;
                $t1 = $size - 1;
                $lower++;
            } else {
                $lower = 0;
            }
            for ($i = $t1; $i >= $lower; $i--) {
                if (count($result) >= $max) {
                    return $result;
                }
                if (($t1 + $mode) % 2 === 0) {
                    $result[] = $mat[$i][$t1 + $lower - $i];
                } else {
                    $result[] = $mat[$t1 + $lower - $i][$i];
                }
            }
        }

        return $result;
    }

    /**
     * Get the average of the pixel values.
     * 
     * @param array<int,float> $pixels
     */
    private static function average(array $pixels): float
    {
        // Calculate the average value from top 8x8 pixels, except for the first one.
        $n = count($pixels) - 1;

        return array_sum(array_slice($pixels, 1, $n)) / $n;
    }

    /**
     * Pack an array of bits into an integer.
     * 
     * @param array<int,bool> $bits
     */
    public static function getInteger(array $bits): int
    {
        $maxIntSize = PHP_INT_SIZE * 8; // Total bits per integer
        $value = 0;
        for ($i = 0; $i < $maxIntSize; $i++) {
            $bit = $i < count($bits) && $bits[$i];
            if ($bit) {
                $value |= (1 << ($maxIntSize - 1 - $i));
            }
        }

        return $value;
    }
}