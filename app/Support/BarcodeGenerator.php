<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductUnit;

/**
 * In-store barcodes for things that have none (bundles, boxes, specials).
 * EAN-13 codes starting with "20" are reserved for in-store use, so they
 * never clash with a maker's printed barcode.
 */
class BarcodeGenerator
{
    public const PREFIX = '20';

    public static function unique(): string
    {
        do {
            $code = self::make();
        } while (Product::withTrashed()->where('barcode', $code)->exists() || ProductUnit::where('barcode', $code)->exists());

        return $code;
    }

    /** PREFIX + 10 random digits + EAN-13 check digit. */
    public static function make(): string
    {
        $body = self::PREFIX;
        while (strlen($body) < 12) {
            $body .= random_int(0, 9);
        }
        return $body . self::checkDigit($body);
    }

    public static function checkDigit(string $twelve): int
    {
        $sum = 0;
        foreach (str_split($twelve) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 ? 3 : 1);
        }
        return (10 - $sum % 10) % 10;
    }
}
