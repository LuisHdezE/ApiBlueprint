<?php

namespace App\Application\Blueprint\Data;

final readonly class ExportedBlueprint
{
    public function __construct(
        public string $filename,
        public string $content,
        public string $mimeType = 'application/zip',
    ) {}
}
