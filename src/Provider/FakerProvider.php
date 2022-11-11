<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

use Faker\Factory;
use Faker\Generator;
use Setono\SyliusPickupPointPlugin\Model\PickupPoint;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class FakerProvider extends Provider
{
    private Generator $faker;

    private FactoryInterface $pickupPointFactory;

    public function __construct(FactoryInterface $pickupPointFactory)
    {
        $this->faker = Factory::create();
        $this->pickupPointFactory = $pickupPointFactory;
    }

    public function findPickupPoints(OrderInterface $order): iterable
    {
        $address = $order->getShippingAddress();
        Assert::notNull($address);

        $countryCode = $address->getCountryCode();
        Assert::notNull($countryCode);

        $pickupPoints = [];
        for ($i = 0; $i < 10; ++$i) {
            $pickupPoints[] = $this->createFakePickupPoint((string) $i, $countryCode);
        }

        return $pickupPoints;
    }

    public function findPickupPoint(PickupPointCode $code): ?PickupPointInterface
    {
        return $this->createFakePickupPoint($code->getIdPart(), $code->getCountryPart());
    }

    public function findAllPickupPoints(): iterable
    {
        for ($i = 0; $i < 10; ++$i) {
            yield $this->createFakePickupPoint((string) $i);
        }
    }

    public function getCode(): string
    {
        return 'faker';
    }

    public function getName(): string
    {
        return 'Faker';
    }

    private function createFakePickupPoint(string $index, ?string $countryCode = null): PickupPoint
    {
        if (null === $countryCode) {
            $countryCode = $this->faker->countryCode;
        }

        /** @var PickupPointInterface|object $pickupPoint */
        $pickupPoint = $this->pickupPointFactory->createNew();

        Assert::isInstanceOf($pickupPoint, PickupPointInterface::class);

        $pickupPoint->setCode(new PickupPointCode($index, $this->getCode(), $countryCode));
        $pickupPoint->setName("Post office #$index");
        $pickupPoint->setAddress($this->faker->streetAddress);
        $pickupPoint->setZipCode((string) $this->faker->numberBetween(11111, 99999));
        $pickupPoint->setCity($this->faker->city);
        $pickupPoint->setCountry($countryCode);
        $pickupPoint->setLatitude($this->faker->latitude);
        $pickupPoint->setLongitude($this->faker->longitude);
        $pickupPoint->setUpdatedAt(new \DateTime());

        return $pickupPoint;
    }
}
