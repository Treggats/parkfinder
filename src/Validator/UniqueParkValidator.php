<?php

declare(strict_types=1);

namespace App\Validator;

use App\Action\GenerateSlug;
use App\Repository\ParkRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueParkValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ParkRepository $parkRepository,
        private readonly GenerateSlug $generateSlug,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniquePark) {
            throw new UnexpectedTypeException($constraint, UniquePark::class);
        }

        if ($value === null) {
            return;
        }

        if (! \is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $slug = $this->generateSlug->make($value);

        if ($this->parkRepository->count(['slug' => $slug]) >= 1) {
            $this->context
                ->buildViolation($constraint->message)
                ->setCode(UniquePark::PARK_NOT_UNIQUE_ERROR)
                ->addViolation();
        }
    }
}
