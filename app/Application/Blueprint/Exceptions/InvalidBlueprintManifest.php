<?php

namespace App\Application\Blueprint\Exceptions;

use RuntimeException;

final class InvalidBlueprintManifest extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Invalid blueprint manifest.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
