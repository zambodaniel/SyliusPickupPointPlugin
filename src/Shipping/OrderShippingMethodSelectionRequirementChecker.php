<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Shipping;

use Setono\SyliusPickupPointPlugin\Model\ShippingMethodInterface;
use Sylius\Component\Core\Checker\OrderShippingMethodSelectionRequirementCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;

final class OrderShippingMethodSelectionRequirementChecker implements OrderShippingMethodSelectionRequirementCheckerInterface
{
    private OrderShippingMethodSelectionRequirementCheckerInterface $decorated;

    private ShippingMethodsResolverInterface $shippingMethodsResolver;

    public function __construct(OrderShippingMethodSelectionRequirementCheckerInterface $decorated, ShippingMethodsResolverInterface $shippingMethodsResolver)
    {
        $this->decorated = $decorated;
        $this->shippingMethodsResolver = $shippingMethodsResolver;
    }

    public function isShippingMethodSelectionRequired(OrderInterface $order): bool
    {
        $required = $this->decorated->isShippingMethodSelectionRequired($order);
        if (true === $required) {
            return true;
        }

        if (!$order->isShippingRequired()) {
            return false;
        }

        /** @var ShipmentInterface $shipment */
        foreach ($order->getShipments() as $shipment) {
            $supportedMethods = $this->shippingMethodsResolver->getSupportedMethods($shipment);
            if (count($supportedMethods) > 1) {
                return true;
            }

            /** @var ShippingMethodInterface $supportedMethod */
            foreach ($supportedMethods as $supportedMethod) {
                if ($supportedMethod->getPickupPointProvider() !== null) {
                    return true;
                }
            }
        }

        return false;
    }
}
