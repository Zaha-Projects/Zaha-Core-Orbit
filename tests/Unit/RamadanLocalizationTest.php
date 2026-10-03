<?php

namespace Tests\Unit;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class RamadanLocalizationTest extends TestCase
{
    public function test_arabic_and_english_translation_keys_have_exact_parity(): void
    {
        $english = require resource_path('lang/en/ramadan_iftars.php');
        $arabic = require resource_path('lang/ar/ramadan_iftars.php');

        $this->assertSame($this->keys($english), $this->keys($arabic));
    }

    public function test_literal_ramadan_translation_references_exist_in_both_locales(): void
    {
        $english = require resource_path('lang/en/ramadan_iftars.php');
        $arabic = require resource_path('lang/ar/ramadan_iftars.php');
        $files = array_merge(
            $this->phpFiles(resource_path('views/pages/events/ramadan')),
            glob(app_path('Modules/Events/Http/Controllers/Ramadan/*.php')),
            glob(app_path('Modules/Events/Http/Requests/Ramadan/*.php')),
            glob(app_path('Modules/Events/Services/*.php'))
        );

        foreach (array_unique($files) as $file) {
            preg_match_all("/__\\(['\"](ramadan_iftars\\.[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*)['\"]/", file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                $relative = substr($key, strlen('ramadan_iftars.'));
                $this->assertNotNull(data_get($english, $relative), $file.' references missing English key '.$key);
                $this->assertNotNull(data_get($arabic, $relative), $file.' references missing Arabic key '.$key);
            }
        }
    }

    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && substr($file->getFilename(), -4) === '.php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function keys(array $translations): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($translations));
        $keys = [];
        foreach ($iterator as $value) {
            $parts = [];
            for ($depth = 0; $depth <= $iterator->getDepth(); $depth++) {
                $parts[] = $iterator->getSubIterator($depth)->key();
            }
            $keys[] = implode('.', $parts);
        }
        sort($keys);

        return $keys;
    }
}
