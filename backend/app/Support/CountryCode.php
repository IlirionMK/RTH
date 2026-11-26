<?php

namespace App\Support;

use League\ISO3166\Exception\OutOfBoundsException;
use League\ISO3166\ISO3166;

final class CountryCode
{
    public static function fromAlpha3(mixed $alpha3): ?string
    {
        if (! is_string($alpha3)) {
            return null;
        }

        try {
            return (new ISO3166)->alpha3($alpha3)['alpha2'];
        } catch (OutOfBoundsException) {
            return null;
        }
    }
}
