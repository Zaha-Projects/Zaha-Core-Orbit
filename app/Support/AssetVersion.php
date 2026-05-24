<?php

namespace App\Support;

class AssetVersion
{
    public static function url(string $path): string
    {
        $resolvedPath = self::preferMinifiedPath($path);
        $absolutePath = public_path($resolvedPath);
        $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

        return asset($resolvedPath) . '?v=' . $version;
    }

    private static function preferMinifiedPath(string $path): string
    {
        if (! self::isMinifiableAsset($path)) {
            return $path;
        }

        if (str_ends_with($path, '.min.css') || str_ends_with($path, '.min.js')) {
            return $path;
        }

        $minifiedPath = preg_replace('/\.(css|js)$/', '.min.$1', $path);

        if (! is_string($minifiedPath)) {
            return $path;
        }

        return is_file(public_path($minifiedPath)) ? $minifiedPath : $path;
    }

    private static function isMinifiableAsset(string $path): bool
    {
        return str_ends_with($path, '.css') || str_ends_with($path, '.js');
    }
}
