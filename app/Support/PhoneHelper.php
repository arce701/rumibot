<?php

namespace App\Support;

class PhoneHelper
{
    /** @var array<string, array{iso: string, name: string, flag: string, mask: ?string}> */
    public const COUNTRIES = [
        // NANP Caribbean 4-digit codes (must come first for matching priority)
        '1809' => ['iso' => 'DO', 'name' => 'República Dominicana', 'flag' => "\u{1F1E9}\u{1F1F4}", 'mask' => '999 9999'],
        '1829' => ['iso' => 'DO', 'name' => 'República Dominicana', 'flag' => "\u{1F1E9}\u{1F1F4}", 'mask' => '999 9999'],
        '1849' => ['iso' => 'DO', 'name' => 'República Dominicana', 'flag' => "\u{1F1E9}\u{1F1F4}", 'mask' => '999 9999'],
        '1787' => ['iso' => 'PR', 'name' => 'Puerto Rico', 'flag' => "\u{1F1F5}\u{1F1F7}", 'mask' => '999 9999'],
        '1939' => ['iso' => 'PR', 'name' => 'Puerto Rico', 'flag' => "\u{1F1F5}\u{1F1F7}", 'mask' => '999 9999'],

        // LATAM 3-digit codes
        '240' => ['iso' => 'GQ', 'name' => 'Guinea Ecuatorial', 'flag' => "\u{1F1EC}\u{1F1F6}", 'mask' => null],
        '502' => ['iso' => 'GT', 'name' => 'Guatemala', 'flag' => "\u{1F1EC}\u{1F1F9}", 'mask' => '9999 9999'],
        '503' => ['iso' => 'SV', 'name' => 'El Salvador', 'flag' => "\u{1F1F8}\u{1F1FB}", 'mask' => '9999 9999'],
        '504' => ['iso' => 'HN', 'name' => 'Honduras', 'flag' => "\u{1F1ED}\u{1F1F3}", 'mask' => '9999 9999'],
        '505' => ['iso' => 'NI', 'name' => 'Nicaragua', 'flag' => "\u{1F1F3}\u{1F1EE}", 'mask' => '9999 9999'],
        '506' => ['iso' => 'CR', 'name' => 'Costa Rica', 'flag' => "\u{1F1E8}\u{1F1F7}", 'mask' => '9999 9999'],
        '507' => ['iso' => 'PA', 'name' => 'Panamá', 'flag' => "\u{1F1F5}\u{1F1E6}", 'mask' => '9999 9999'],
        '509' => ['iso' => 'HT', 'name' => 'Haití', 'flag' => "\u{1F1ED}\u{1F1F9}", 'mask' => '9999 9999'],
        '591' => ['iso' => 'BO', 'name' => 'Bolivia', 'flag' => "\u{1F1E7}\u{1F1F4}", 'mask' => '9999 9999'],
        '592' => ['iso' => 'GY', 'name' => 'Guyana', 'flag' => "\u{1F1EC}\u{1F1FE}", 'mask' => null],
        '593' => ['iso' => 'EC', 'name' => 'Ecuador', 'flag' => "\u{1F1EA}\u{1F1E8}", 'mask' => '9 9999 9999'],
        '594' => ['iso' => 'GF', 'name' => 'Guayana Francesa', 'flag' => "\u{1F1EC}\u{1F1EB}", 'mask' => null],
        '595' => ['iso' => 'PY', 'name' => 'Paraguay', 'flag' => "\u{1F1F5}\u{1F1FE}", 'mask' => '999 999 999'],
        '596' => ['iso' => 'MQ', 'name' => 'Martinique', 'flag' => "\u{1F1F2}\u{1F1F6}", 'mask' => null],
        '597' => ['iso' => 'SR', 'name' => 'Suriname', 'flag' => "\u{1F1F8}\u{1F1F7}", 'mask' => null],
        '598' => ['iso' => 'UY', 'name' => 'Uruguay', 'flag' => "\u{1F1FA}\u{1F1FE}", 'mask' => '9999 9999'],

        // 2-digit codes
        '51' => ['iso' => 'PE', 'name' => 'Perú', 'flag' => "\u{1F1F5}\u{1F1EA}", 'mask' => '999 999 999'],
        '52' => ['iso' => 'MX', 'name' => 'México', 'flag' => "\u{1F1F2}\u{1F1FD}", 'mask' => '1 999 999 9999'],
        '53' => ['iso' => 'CU', 'name' => 'Cuba', 'flag' => "\u{1F1E8}\u{1F1FA}", 'mask' => '9999 9999'],
        '54' => ['iso' => 'AR', 'name' => 'Argentina', 'flag' => "\u{1F1E6}\u{1F1F7}", 'mask' => '9 99 9999 9999'],
        '55' => ['iso' => 'BR', 'name' => 'Brasil', 'flag' => "\u{1F1E7}\u{1F1F7}", 'mask' => '99 99999 9999'],
        '56' => ['iso' => 'CL', 'name' => 'Chile', 'flag' => "\u{1F1E8}\u{1F1F1}", 'mask' => '9 9999 9999'],
        '57' => ['iso' => 'CO', 'name' => 'Colombia', 'flag' => "\u{1F1E8}\u{1F1F4}", 'mask' => '999 999 9999'],
        '58' => ['iso' => 'VE', 'name' => 'Venezuela', 'flag' => "\u{1F1FB}\u{1F1EA}", 'mask' => '999 999 9999'],
        '34' => ['iso' => 'ES', 'name' => 'España', 'flag' => "\u{1F1EA}\u{1F1F8}", 'mask' => null],
        '33' => ['iso' => 'FR', 'name' => 'Francia', 'flag' => "\u{1F1EB}\u{1F1F7}", 'mask' => null],
        '39' => ['iso' => 'IT', 'name' => 'Italia', 'flag' => "\u{1F1EE}\u{1F1F9}", 'mask' => null],
        '44' => ['iso' => 'GB', 'name' => 'Reino Unido', 'flag' => "\u{1F1EC}\u{1F1E7}", 'mask' => null],
        '49' => ['iso' => 'DE', 'name' => 'Alemania', 'flag' => "\u{1F1E9}\u{1F1EA}", 'mask' => null],
        '61' => ['iso' => 'AU', 'name' => 'Australia', 'flag' => "\u{1F1E6}\u{1F1FA}", 'mask' => null],
        '62' => ['iso' => 'ID', 'name' => 'Indonesia', 'flag' => "\u{1F1EE}\u{1F1E9}", 'mask' => null],
        '63' => ['iso' => 'PH', 'name' => 'Filipinas', 'flag' => "\u{1F1F5}\u{1F1ED}", 'mask' => null],
        '81' => ['iso' => 'JP', 'name' => 'Japón', 'flag' => "\u{1F1EF}\u{1F1F5}", 'mask' => null],
        '82' => ['iso' => 'KR', 'name' => 'Corea del Sur', 'flag' => "\u{1F1F0}\u{1F1F7}", 'mask' => null],
        '86' => ['iso' => 'CN', 'name' => 'China', 'flag' => "\u{1F1E8}\u{1F1F3}", 'mask' => null],
        '91' => ['iso' => 'IN', 'name' => 'India', 'flag' => "\u{1F1EE}\u{1F1F3}", 'mask' => null],

        // 1-digit code
        '1' => ['iso' => 'US', 'name' => 'Estados Unidos', 'flag' => "\u{1F1FA}\u{1F1F8}", 'mask' => '999 999 9999'],
    ];

    public static function format(string $phone): string
    {
        $digits = ltrim($phone, '+');

        if ($digits === '') {
            return $phone;
        }

        $match = static::matchCountryPrefix($digits);

        if (! $match) {
            return '+'.$digits;
        }

        [$prefix, $rest] = $match;
        $country = static::COUNTRIES[$prefix];

        // NANP 4-digit prefix (e.g., 1809 DR, 1787 PR): format as +1 XXX YYY ZZZZ
        if (strlen($prefix) === 4 && str_starts_with($prefix, '1')) {
            $areaCode = substr($prefix, 1);
            $mask = $country['mask'] ?? '999 9999';

            return '+1 '.$areaCode.' '.static::applyMask($rest, $mask);
        }

        $mask = $country['mask'] ?? null;

        if ($mask && static::maskDigitCount($mask) === strlen($rest)) {
            return '+'.$prefix.' '.static::applyMask($rest, $mask);
        }

        return '+'.$prefix.' '.static::groupDigits($rest);
    }

    public static function detectCountryIso(string $phone): ?string
    {
        $digits = ltrim($phone, '+');
        $match = static::matchCountryPrefix($digits);

        if (! $match) {
            return null;
        }

        return static::COUNTRIES[$match[0]]['iso'] ?? null;
    }

    public static function detectCountryName(string $phone): ?string
    {
        $digits = ltrim($phone, '+');
        $match = static::matchCountryPrefix($digits);

        if (! $match) {
            return null;
        }

        return static::COUNTRIES[$match[0]]['name'] ?? null;
    }

    public static function countryNameFromIso(string $iso): ?string
    {
        $iso = strtoupper($iso);

        foreach (static::COUNTRIES as $entry) {
            if ($entry['iso'] === $iso) {
                return $entry['name'];
            }
        }

        return null;
    }

    public static function flagForPhone(string $phone): ?string
    {
        $digits = ltrim($phone, '+');
        $match = static::matchCountryPrefix($digits);

        if (! $match) {
            return null;
        }

        return static::COUNTRIES[$match[0]]['flag'] ?? null;
    }

    public static function flagFromIso(string $iso): ?string
    {
        $iso = strtoupper($iso);

        foreach (static::COUNTRIES as $entry) {
            if ($entry['iso'] === $iso) {
                return $entry['flag'] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}|null [prefix, rest]
     */
    private static function matchCountryPrefix(string $digits): ?array
    {
        // Try 4-digit (NANP Caribbean), then 3-digit, 2-digit, 1-digit prefix
        for ($len = 4; $len >= 1; $len--) {
            $prefix = substr($digits, 0, $len);

            if (isset(static::COUNTRIES[$prefix])) {
                return [$prefix, substr($digits, $len)];
            }
        }

        return null;
    }

    private static function applyMask(string $digits, string $mask): string
    {
        $result = '';
        $digitIndex = 0;

        for ($i = 0; $i < strlen($mask); $i++) {
            if ($mask[$i] === ' ') {
                $result .= ' ';
            } elseif ($digitIndex < strlen($digits)) {
                $result .= $digits[$digitIndex];
                $digitIndex++;
            }
        }

        // Append remaining digits if number is longer than mask
        if ($digitIndex < strlen($digits)) {
            $result .= ' '.substr($digits, $digitIndex);
        }

        return $result;
    }

    private static function maskDigitCount(string $mask): int
    {
        return strlen(str_replace(' ', '', $mask));
    }

    private static function groupDigits(string $digits): string
    {
        return rtrim(chunk_split($digits, 4, ' '));
    }
}
