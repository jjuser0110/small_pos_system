<?php

if (!function_exists('money')) {
    /** Money as shown everywhere in FsMart: "RM 1,234.50" (negative: "-RM 12.00"). */
    function money($amount, int $decimals = 2): string
    {
        $amount = (float) ($amount ?? 0);
        return ($amount < 0 ? '-' : '') . 'RM ' . number_format(abs($amount), $decimals);
    }
}
