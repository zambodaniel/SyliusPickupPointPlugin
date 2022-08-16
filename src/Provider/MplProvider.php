<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\Provider;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Setono\SyliusPickupPointPlugin\Exception\TimeoutException;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

abstract class MplProvider extends FileProvider
{

    const CODE_POINT = 'mpl_point';
    const CODE_POST = 'mpl_post';
    const XML_URL = 'https://httpmegosztas.posta.hu/PartnerExtra/Out/PostInfo2.xml';

    private ClientInterface $client;
    private FactoryInterface $pickupPointFactory;
    /**
     * @var array|string[]
     */
    private array $countryCodes;

    private \SimpleXMLElement $data;

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

    protected function getFileName(): string
    {
        return basename(self::XML_URL);
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
                $data = simplexml_load_string($this->getFile());
            } catch (FileNotFoundException $exception) {
                $response = $this->client->request('GET', self::XML_URL);
                $data = (string) $response->getBody();
                $this->storeFile($data);
                $data = simplexml_load_string($data);
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
        $postCodeShort = substr($postCode, 0, 3);
        $isBp = substr($postCode, 0, 1) === '1';
        $city = $shippingAddress->getCity();
        $countryCode = $shippingAddress->getCountryCode();
        if (!in_array($countryCode, $this->countryCodes) || null === $street || null === $postCode || null === $countryCode) {
            return [];
        }

        try {
            $this->fetchData();
            $parcelShops = [];
            foreach ($this->data->postInfo->post as $point) {
                if ($isBp) {
                    if (substr($point['zipCode'], 0, 3) === $postCodeShort) {
                        $parcelShops[] = $point;
                    }
                } elseif ($point['zipCode'] === $postCode) {
                    $parcelShops[] = $point;
                } elseif (strtolower($point->city) === strtolower($city)) {
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

    private function transform(\SimpleXMLElement $parcelShop): PickupPointInterface
    {
        /** @var PickupPointInterface|object $pickupPoint */
        $pickupPoint = $this->pickupPointFactory->createNew();

        Assert::isInstanceOf($pickupPoint, PickupPointInterface::class);

        $pickupPoint->setCode(new PickupPointCode($parcelShop->ID, $this->getCode(), 'HU'));
        $pickupPoint->setName($parcelShop->name);
        $pickupPoint->setAddress(sprintf(
            '%s %s %s',
            $parcelShop->street->name,
            $parcelShop->street->type,
            $parcelShop->street->houseNumber
        ));
        $pickupPoint->setZipCode($parcelShop['zipCode']);
        $pickupPoint->setCity($parcelShop->city);
        $pickupPoint->setCountry('HU');
        $pickupPoint->setLatitude((float) $parcelShop->gpsData->WGSLat);
        $pickupPoint->setLongitude((float) $parcelShop->gpsData->WGSLon);

        return $pickupPoint;
    }

}
