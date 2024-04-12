<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Doctrine\ORM;

use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Setono\SyliusPickupPointPlugin\Repository\PickupPointRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\OrderInterface;

class PickupPointRepository extends EntityRepository implements PickupPointRepositoryInterface
{
    public function findOneByCode(PickupPointCode $code): ?PickupPointInterface
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.code.id = :codeId')
            ->andWhere('o.code.provider = :codeProvider')
            ->andWhere('o.code.country = :codeCountry')
            ->setParameters([
                'codeId' => $code->getIdPart(),
                'codeProvider' => $code->getProviderPart(),
                'codeCountry' => $code->getCountryPart(),
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByOrder(OrderInterface $order, string $provider): array
    {
        $shippingAddress = $order->getShippingAddress();
        if (null === $shippingAddress) {
            return [];
        }

        $countryCode = $shippingAddress->getCountryCode();
        if (null === $countryCode) {
            return [];
        }

        $postalCode = $shippingAddress->getPostcode();
        if (null === $postalCode) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->andWhere('o.code.provider = :provider')
            ->andWhere('o.code.country = :country')
            ->andWhere('o.zipCode = :postalCode')
            ->setParameters([
                'provider' => $provider,
                'country' => $countryCode,
                'postalCode' => $postalCode,
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    public function deleteOlderThan(\DateTimeInterface $dateTime, string $provider): void
    {
        $qb = $this->createQueryBuilder('o');
        $qb
            ->andWhere('o.code.provider = :provider')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->lt('o.updatedAt', ':dateTime'),
                $qb->expr()->isNull('o.updatedAt')
            ))
            ->setParameters([
                'dateTime' => $dateTime,
                'provider' => $provider,
            ])
            ->delete()
            ->getQuery()
            ->execute();
    }
}
