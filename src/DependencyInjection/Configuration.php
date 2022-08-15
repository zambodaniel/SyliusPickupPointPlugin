<?php

declare(strict_types=1);

namespace Setono\SyliusPickupPointPlugin\DependencyInjection;

use Setono\DAOBundle\SetonoDAOBundle;
use Setono\GlsWebserviceBundle\SetonoGlsWebserviceBundle;
use Setono\PostNordBundle\SetonoPostNordBundle;
use Setono\SyliusPickupPointPlugin\Doctrine\ORM\PickupPointRepository;
use Setono\SyliusPickupPointPlugin\Model\PickupPoint;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Form\Type\DefaultResourceType;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_pickup_point');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('driver')->defaultValue(SyliusResourceBundle::DRIVER_DOCTRINE_ORM)->end()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('local')
                            ->info('A service id for the feed filesystem')
                            ->cannotBeEmpty()
                            ->defaultValue('setono_sylius_pickup_point.storage.local')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('pool')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('local')
                    ->defaultValue(true)
                    ->info('Whether to use the local database when timeouts occur in third party HTTP calls. Remember to run the setono-sylius-pickup-point:load-pickup-points command periodically to populate the local database with pickup points')
                    ->example(true)
                ->end()
                ->arrayNode('providers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('faker')
                            ->info('Whether to enable the Faker provider')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('dao')
                            ->example(true)
                            ->info('Whether to enable the DAO provider')
                            ->defaultValue(class_exists(SetonoDAOBundle::class))
                        ->end()
                        ->booleanNode('gls')
                            ->example(true)
                            ->info('Whether to enable the GLS provider')
                            ->defaultValue(class_exists(SetonoGlsWebserviceBundle::class))
                        ->end()
                        ->booleanNode('post_nord')
                            ->example(true)
                            ->info('Whether to enable the PostNord provider')
                            ->defaultValue(class_exists(SetonoPostNordBundle::class))
                        ->end()
                        ->booleanNode('foxpost')
                            ->example(true)
                            ->info('Whether to enable the FoxPost provider')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('pickup_point')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(PickupPoint::class)->cannotBeEmpty()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(PickupPointRepository::class)->cannotBeEmpty()->end()
                                        ->scalarNode('form')->defaultValue(DefaultResourceType::class)->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
