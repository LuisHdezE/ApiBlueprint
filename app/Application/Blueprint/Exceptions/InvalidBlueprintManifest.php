<?php

namespace App\Application\Blueprint\Exceptions;

use RuntimeException;

final class InvalidBlueprintManifest extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Invalid blueprint manifest.');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
