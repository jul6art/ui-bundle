<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\DependencyInjection;

use Jul6Art\UiBundle\Ui\IconSet;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The bundle's configuration tree.
 *
 * Write an `->info()` on every node: it is what `config:dump-reference` shows, and it is the only
 * documentation a reader gets before opening the code.
 *
 * > ⚠️ **A node that decides something at compile time cannot be an env var.** `%env(bool:X)%`
 * > reaches a `booleanNode()` as the placeholder *string* and the config layer rejects it. Use a
 * > plain value for anything that gates service registration, and keep env vars for values passed
 * > through to a service at runtime (a `scalarNode` argument).
 */
class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ui');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->info('Registers the bundle\'s services. false leaves it installed and inert.')
                    ->defaultTrue()
                ->end()
                ->arrayNode('icons')
                    ->info('Logical icon name → markup, merged over the bundled Font Awesome 6 set key by key: declaring one icon keeps the other defaults. An empty value removes an icon. The values are rendered as raw HTML — they are markup, and they come from this configuration, never from user input.')
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                    ->defaultValue(IconSet::FONT_AWESOME_ICONS)
                ->end()
                ->arrayNode('currency_icons')
                    ->info('ISO 4217 code → markup for a currency symbol, merged over the bundled set key by key. A currency absent from this map is rendered as its ISO code in text, which is the right answer for CHF, PLN or SEK: showing a wrong symbol next to an amount is a reporting error, not a cosmetic one.')
                    ->useAttributeAsKey('code')
                    ->scalarPrototype()->end()
                    ->defaultValue(IconSet::FONT_AWESOME_CURRENCIES)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
