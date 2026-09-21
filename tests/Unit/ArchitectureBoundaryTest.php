<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureBoundaryTest extends TestCase
{
    public function test_domain_and_application_do_not_depend_on_laravel(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([$root.'/app/Domain', $root.'/app/Application'] as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                $contents = file_get_contents($file->getPathname());

                $this->assertStringNotContainsString(
                    'Illuminate\\',
                    $contents,
                    sprintf('%s must remain framework independent.', $file->getPathname()),
                );
            }
        }
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
