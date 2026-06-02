<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class NoMergeConflictMarkersTest extends TestCase
{
    public function test_source_files_do_not_contain_merge_conflict_markers(): void
    {
        $paths = [
            app_path(),
            config_path(),
            resource_path('views'),
            base_path('routes'),
        ];

        $conflictedFiles = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($files as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                if (! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if (preg_match('/^(<<<<<<< |=======\r?$|>>>>>>> )/m', (string) $contents)) {
                    $conflictedFiles[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $conflictedFiles, 'Resolve merge conflict markers before committing PHP/Blade source files.');
    }
}
