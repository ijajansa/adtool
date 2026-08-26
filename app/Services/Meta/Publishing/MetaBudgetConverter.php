<?php

namespace App\Services\Meta\Publishing;

use InvalidArgumentException;

class MetaBudgetConverter
{
    public function toMinorUnits(string $amount, string $currency): int
    {
        $currency = strtoupper($currency);
        $precision = config('meta_publishing.currency_precision.'.$currency);
        if ($precision === null) {
            throw new InvalidArgumentException("The currency {$currency} is not supported for Meta publication.");
        }
        if (! preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]+))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('The budget amount is invalid.');
        }

        $whole = ltrim($matches[1], '0') ?: '0';
        $fraction = $matches[2] ?? '';
        if (strlen($fraction) > $precision && trim(substr($fraction, $precision), '0') !== '') {
            throw new InvalidArgumentException("{$currency} supports {$precision} decimal places.");
        }
        $fraction = str_pad(substr($fraction, 0, $precision), $precision, '0');
        $minor = ltrim($whole.$fraction, '0') ?: '0';
        if (strlen($minor) > 18 || (strlen($minor) === 18 && strcmp($minor, (string) PHP_INT_MAX) > 0)) {
            throw new InvalidArgumentException('The budget amount is too large.');
        }

        return (int) $minor;
    }
}
