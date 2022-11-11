<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Repository;

use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface PickupPointRepositoryInterface extends RepositoryInterface
{
    public function findOneByCode(PickupPointCode $code): ?PickupPointInterface;

    /**
     * @psalm-return list<PickupPointInterface>
     */
    public function findByOrder(OrderInterface $order, string $provider): array;

    public function deleteOlderThan(\DateTimeInterface $dateTime, string $provider): void;
}
