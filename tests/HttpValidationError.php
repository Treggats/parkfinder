<?php

declare(strict_types=1);

namespace App\Tests;

final class HttpValidationError
{
    public function __construct(
        public string $propertyPath,
        public string $title,
        public string $template,
        /** @var array<string, string> $parameters */
        public array $parameters,
        public string $type = '',
    ) {
    }
}
