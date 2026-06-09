<?php

namespace App\Support;

class GoogleMaps
{
    public static function embedUrl(?string $url, ?string $placeName = null, ?string $address = null): ?string
    {
        $query = self::locationQuery($url, $placeName, $address);

        if ($query === null) {
            return null;
        }

        return 'https://maps.google.com/maps?q='.rawurlencode($query).'&output=embed';
    }

    public static function navigationUrl(?string $url, ?string $placeName = null, ?string $address = null): ?string
    {
        $query = self::locationQuery($url, $placeName, $address);

        if ($query !== null) {
            return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($query);
        }

        $url = self::filledString($url);

        return $url ?: null;
    }

    public static function locationQuery(?string $url, ?string $placeName = null, ?string $address = null): ?string
    {
        return self::queryFromUrl($url) ?: self::fallbackQuery($placeName, $address);
    }

    protected static function queryFromUrl(?string $url): ?string
    {
        $url = self::filledString($url);

        if (! $url) {
            return null;
        }

        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $url, $matches) === 1) {
            return $matches[1].','.$matches[2];
        }

        if (preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', $url, $matches) === 1) {
            return $matches[1].','.$matches[2];
        }

        $parts = parse_url($url);
        $queryParams = [];
        parse_str((string) ($parts['query'] ?? ''), $queryParams);

        foreach (['q', 'query', 'destination', 'daddr', 'll'] as $key) {
            $value = self::filledString($queryParams[$key] ?? null);

            if ($value) {
                return $value;
            }
        }

        $path = (string) ($parts['path'] ?? '');
        if (preg_match('~/maps/place/([^/?]+)~', $path, $matches) === 1) {
            return str_replace('+', ' ', rawurldecode($matches[1]));
        }

        return null;
    }

    protected static function fallbackQuery(?string $placeName, ?string $address): ?string
    {
        $parts = array_filter([
            self::filledString($placeName),
            self::filledString($address),
        ]);

        return $parts ? implode('، ', $parts) : null;
    }

    protected static function filledString(mixed $value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
