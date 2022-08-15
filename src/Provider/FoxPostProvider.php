<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

use Gaufrette\File;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use Setono\SyliusPickupPointPlugin\Exception\TimeoutException;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use League\Flysystem\FilesystemInterface;
use Webmozart\Assert\Assert;

final class FoxPostProvider extends FileProvider
{

    public const BASE_DIR = 'pickup_points';

    public const FILENAME = 'apms.json';

    private ClientInterface $client;

    private FactoryInterface $pickupPointFactory;

    private array $countryCodes;

    private array $data = [];

    public function __construct(
        ClientInterface $client,
        FactoryInterface $pickupPointFactory,
        FilesystemInterface $filesystem,
        array $countryCodes = ['HU']
    ) {
        parent::__construct($filesystem);
        $this->client = $client;
        $this->pickupPointFactory = $pickupPointFactory;
        $this->countryCodes = $countryCodes;
    }

    /**
     * @throws FileNotFoundException
     * @throws GuzzleException
     * @throws FileExistsException
     */
    private function fetchData(): void
    {
        if (empty($this->data)) {
            try {
                $data = json_decode($this->getFile(), true);
            } catch (FileNotFoundException $exception) {
                $response = $this->client->request('GET', 'https://cdn.foxpost.hu/apms.json');
                $data = (string) $response->getBody();
                $this->storeFile($data);
            }

            $this->data = $data;
        }
    }

    protected function getFileName(): string
    {
        return self::FILENAME;
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
