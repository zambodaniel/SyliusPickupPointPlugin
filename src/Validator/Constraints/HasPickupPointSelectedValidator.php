<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Validator\Constraints;

use Setono\SyliusPickupPointPlugin\Model\PickupPointProviderAwareInterface;
use Setono\SyliusPickupPointPlugin\Model\ShipmentInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class HasPickupPointSelectedValidator extends ConstraintValidator
{
    /**
     * @param ShipmentInterface|mixed $value
     * @param HasPickupPointSelected|Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, HasPickupPointSelected::class);

        Assert::isInstanceOf($value, ShipmentInterface::class);

        /** @var PickupPointProviderAwareInterface $method */
        $method = $value->getMethod();

        if (!$method->hasPickupPointProvider()) {
            return;
        }

        if (!$value->hasPickupPointId()) {
            $this->context
                ->buildViolation($constraint->pickupPointNotBlank)
                ->addViolation()
            ;

            return;
        }
    }
}
