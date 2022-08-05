<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Setono\SyliusPickupPointPlugin\Exception\TimeoutException;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class FoxPostProvider extends Provider
{
    private ClientInterface $client;

    private FactoryInterface $pickupPointFactory;

    private array $countryCodes;

    private array $data = [];

    public function __construct(
        FactoryInterface $pickupPointFactory,
        array $countryCodes = ['HU']
    ) {
        $this->client = new Client();
        $this->pickupPointFactory = $pickupPointFactory;
        $this->countryCodes = $countryCodes;
    }

    private function fetchData(): void
    {
        if (empty($this->data)) {
            $response = $this->client->request('GET', 'https://cdn.foxpost.hu/apms.json');
            $data = json_decode((string) $response->getBody(), true);
            if (false === $data) {
                throw new TimeoutException();
            }
            $this->data = $data;
        }
    }

    public function findPickupPoints(OrderInterface $order): iterable
    {
        $shippingAddress = $order->getShippingAddress();
        if (null === $shippingAddress) {
            return [];
        }

        $street = $shippingAddress->getStreet();
        $postCode = $shippingAddress->getPostcode();
        $countryCode = $shippingAddress->getCountryCode();
        if (!in_array($countryCode, $this->countryCodes) || null === $street || null === $postCode || null === $countryCode) {
            return [];
        }

        try {
            $this->fetchData();
            $parcelShops = [];
            foreach ($this->data as $point) {
                if ($point['zip'] === $postCode) {
                    $parcelShops[] = $point;
                }
            }
        } catch (ConnectException $e) {
            throw new TimeoutException($e);
        }

        $pickupPoints = [];
        foreach ($parcelShops as $item) {
            $pickupPoints[] = $this->transform($item);
        }

        return $pickupPoints;
    }

    public function findPickupPoint(PickupPointCode $code): ?PickupPointInterface
    {
        try {
            $this->fetchData();
            foreach ($this->data as $point) {
                if ($point['place_id'] == $code->getIdPart()) {
                    return $this->transform($point);
                }
            }
            return null;
        } catch (ConnectException $e) {
            throw new TimeoutException($e);
        }
    }

    public function findAllPickupPoints(): iterable
    {
        try {
            foreach ($this->data as $point) {
                yield $this->transform($point);
            }
        } catch (ConnectException $e) {
            throw new TimeoutException($e);
        }
    }

    public function getCode(): string
    {
        return 'foxpost';
    }

    public function getName(): string
    {
        return 'Foxpost';
    }

    private function transform(array $parcelShop): PickupPointInterface
    {
        /** @var PickupPointInterface|object $pickupPoint */
        $pickupPoint = $this->pickupPointFactory->createNew();

        Assert::isInstanceOf($pickupPoint, PickupPointInterface::class);

        $pickupPoint->setCode(new PickupPointCode($parcelShop['place_id'], $this->getCode(), 'HU'));
        $pickupPoint->setName($parcelShop['name']);
        $pickupPoint->setAddress($parcelShop['street']);
        $pickupPoint->setZipCode($parcelShop['zip']);
        $pickupPoint->setCity($parcelShop['city']);
        $pickupPoint->setCountry('HU');
        $pickupPoint->setLatitude((float) $parcelShop['geolat']);
        $pickupPoint->setLongitude((float) $parcelShop['geolng']);

        return $pickupPoint;
    }
}
