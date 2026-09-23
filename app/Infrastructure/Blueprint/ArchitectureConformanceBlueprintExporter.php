<?php

namespace App\Infrastructure\Blueprint;

use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Application\Blueprint\Data\ExportedBlueprint;
use RuntimeException;
use ZipArchive;

final readonly class ArchitectureConformanceBlueprintExporter implements BlueprintExporter
{
    public function __construct(private BlueprintExporter $inner) {}

    public function export(array $manifest): ExportedBlueprint
    {
        $exported = $this->inner->export($manifest);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-architecture-');

        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create temporary architecture-conformance archive.');
        }

        try {
            if (file_put_contents($temporaryFile, $exported->content) === false) {
                throw new RuntimeException('Unable to stage generated archive for architecture conformance.');
            }

            $zip = new ZipArchive;
            if ($zip->open($temporaryFile) !== true) {
                throw new RuntimeException('Unable to open generated archive for architecture conformance.');
            }

            $projectRoot = pathinfo($exported->filename, PATHINFO_FILENAME);
            $testPath = $projectRoot.'/tests/Unit/ArchitectureBoundaryTest.php';

            if (! $zip->addFromString($testPath, $this->architectureBoundaryTestFile())) {
                $zip->close();
                throw new RuntimeException('Unable to add generated architecture boundary test.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize generated architecture-conformance archive.');
            }

            $content = file_get_contents($temporaryFile);
            if ($content === false) {
                throw new RuntimeException('Unable to read generated architecture-conformance archive.');
            }

            return new ExportedBlueprint(
                $exported->filename,
                $content,
                $exported->mimeType,
            );
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function architectureBoundaryTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureBoundaryTest extends TestCase
{
    public function test_generated_layers_preserve_clean_architecture_dependency_direction(): void
    {
        $root = dirname(__DIR__, 2);
        $rules = [
            'Domain' => [
                'Illuminate\\',
                'Laravel\\',
                'Symfony\\',
                'App\\Application\\',
                'App\\Infrastructure\\',
                'App\\Presentation\\',
                'App\\Providers\\',
            ],
            'Application' => [
                'Illuminate\\',
                'Laravel\\',
                'Symfony\\',
                'App\\Infrastructure\\',
                'App\\Presentation\\',
                'App\\Providers\\',
            ],
            'Infrastructure' => [
                'App\\Presentation\\',
            ],
            'Presentation' => [
                'App\\Infrastructure\\',
            ],
        ];

        foreach ($rules as $layer => $forbiddenDependencies) {
            foreach ($this->phpFiles($root.'/app/'.$layer) as $file) {
                $contents = file_get_contents($file->getPathname());
                $this->assertIsString($contents);
                $this->assertStringContainsString(
                    'namespace App\\'.$layer,
                    $contents,
                    sprintf('%s must declare the namespace that matches its architecture layer.', $file->getPathname()),
                );

                foreach ($forbiddenDependencies as $forbiddenDependency) {
                    $this->assertStringNotContainsString(
                        $forbiddenDependency,
                        $contents,
                        sprintf(
                            '%s violates Clean Architecture: %s must not depend on %s.',
                            $file->getPathname(),
                            $layer,
                            $forbiddenDependency,
                        ),
                    );
                }
            }
        }
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

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
PHP;
    }
}
