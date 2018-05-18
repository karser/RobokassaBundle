<?php

namespace Karser\RobokassaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('karser_robokassa');

        $rootNode->children()
            ->scalarNode('login')->isRequired()->end()
            ->scalarNode('password1')->isRequired()->end()
            ->scalarNode('password2')->isRequired()->end()
            ->scalarNode('test')->defaultTrue()->end()
            ->scalarNode('debug')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
