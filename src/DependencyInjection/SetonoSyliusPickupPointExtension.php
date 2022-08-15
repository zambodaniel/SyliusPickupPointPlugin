<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\DependencyInjection;

use LogicException;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;

final class SetonoSyliusPickupPointExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $container->setParameter('setono_sylius_pickup_point.local', $config['local']);

        $this->registerResources('setono_sylius_pickup_point', $config['driver'], $config['resources'], $container);

        $loader->load('services.xml');

        $bundles = $container->hasParameter('kernel.bundles') ? $container->getParameter('kernel.bundles') : [];
        Assert::isArray($bundles);

        $cacheEnabled = $config['cache']['enabled'];
        if ($cacheEnabled) {
            if (!interface_exists(AdapterInterface::class)) {
                throw new LogicException('Using cache is only supported when symfony/cache is installed.');
            }

            if (null === $config['cache']['pool']) {
                throw new LogicException('You should specify pool in order to use cache for pickup point providers.');
            }

            $container->setAlias('setono_sylius_pickup_point.cache', $config['cache']['pool']);
        }

        $container->setParameter('setono_sylius_pickup_point.cache.enabled', $cacheEnabled);

        if ($config['providers']['faker']) {
            if ('prod' === $container->getParameter('kernel.environment')) {
                throw new LogicException("You can't use faker provider in production environment.");
            }

            $loader->load('services/providers/faker.xml');
        }

        if ($config['providers']['dao']) {
            if (!isset($bundles['SetonoDAOBundle'])) {
                throw new LogicException('You should use SetonoDAOBundle or disable dao provider.');
            }

            $loader->load('services/providers/dao.xml');
        }

        if ($config['providers']['gls']) {
            if (!isset($bundles['SetonoGlsWebserviceBundle'])) {
                throw new LogicException('You should use SetonoGlsWebserviceBundle or disable gls provider.');
            }

            $loader->load('services/providers/gls.xml');
        }

        if ($config['providers']['post_nord']) {
            if (!isset($bundles['SetonoPostNordBundle'])) {
                throw new LogicException('You should use SetonoPostNordBundle or disable post_nord provider.');
            }

            $loader->load('services/providers/post_nord.xml');
        }

        if ($config['providers']['foxpost']) {
            $loader->load('services/providers/foxpost.xml');
        }
    }
}
