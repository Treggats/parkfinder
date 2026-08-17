<?php

declare(strict_types=1);

namespace App\Tests;

final class HttpErrorResponse
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        /** @var HttpValidationError[] $violations */
        public array $violations,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public static function make(string $content): self
    {
        /** @var array{type: string, title: string, status: int, detail: string, violations: array<string, string>[]} $errors */
        $errors = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        $violations = array_map(function (array $violation) {
            /* @var array{propertyPath: string, title: string, template:string, parameters: array<string, string>, type: string} $violation */
            return new HttpValidationError(...$violation);
        }, $errors['violations'] ?? []);

        return new self(
            type: (string) $errors['type'],
            title: (string) $errors['title'],
            status: (int) $errors['status'],
            detail: (string) $errors['detail'],
            violations: $violations,
        );
    }
}
