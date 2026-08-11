<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class UniquePark extends Constraint
{
    public const string PARK_NOT_UNIQUE_ERROR = '4cad94ae-8a5e-449b-9a26-13c4f8c39262';
    protected const array ERROR_NAMES = [
        self::PARK_NOT_UNIQUE_ERROR => 'PARK_NOT_UNIQUE',
    ];

    public string $message = 'This park already exists.';

    /** @var callable|null */
    public $callback;

    #[HasNamedArguments]
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        $this->message = $message ?? $this->message;

        parent::__construct(groups: $groups, payload: $payload);
    }
}
