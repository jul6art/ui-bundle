<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\DependencyInjection;

use Jul6Art\UiBundle\DataTable\AdminDataTableConfig;
use Jul6Art\UiBundle\Ui\IconSet;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Wires the bundle's services and turns its configuration into something the services can read.
 *
 * Two rules this ecosystem arrived at the hard way:
 *
 * 1. **A brick whose dependency is optional is registered conditionally**, from here, guarded by
 *    `class_exists()` / `interface_exists()` — never by an attribute on the class. An
 *    `#[AsDecorator]` or `#[AsDoctrineListener]` on a vendor class is only honoured if the
 *    application autoconfigures `vendor/`, which it should not, and it makes the class
 *    unloadable when the package is absent.
 * 2. **A service that needs another *service* to exist is checked in a compiler pass**, not
 *    here: an extension runs before the other bundles have configured anything, so
 *    `$container->has('some.service')` is always false at this point.
 */
class UiExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        if (false === ($config['enabled'] ?? true)) {
            return;
        }

        // Exposed as a container parameter so an application can branch on it, and so
        // `debug:container --parameter` tells the truth about what is active.
        $container->setParameter('ui.enabled', true);

        // Les icônes atteignent les types de formulaire par le service `IconSet` et non par un
        // paramètre lu au vol : un type qui lirait `%ui.icons%` obligerait chaque projet à
        // déclarer le paramètre, y compris ceux qui gardent le jeu par défaut.
        // Fusion et non remplacement : un nœud prototype rend *uniquement* ce que le projet a
        // écrit, donc déclarer `ui.icons.email` effacerait les onze autres icônes — un formulaire
        // sur deux perdrait son add-on, sans erreur et sans trace. Le défaut du nœud sert à
        // `config:dump-reference` ; c'est ici que la table complète est reconstituée.
        $container->getDefinition(IconSet::class)
            ->setArgument('$icons', [...IconSet::FONT_AWESOME_ICONS, ...self::stringMap($config['icons'] ?? [])])
            ->setArgument('$currencyIcons', [...IconSet::FONT_AWESOME_CURRENCIES, ...self::stringMap($config['currency_icons'] ?? [])]);

        $datatable = \is_array($config['datatable'] ?? null) ? $config['datatable'] : [];
        $container->getDefinition(AdminDataTableConfig::class)
            ->setArgument('$tenantEndpoint', self::asString($datatable['tenant_endpoint'] ?? null, '/api/organizations'))
            ->setArgument('$tenantLabelKey', self::asString($datatable['tenant_label_key'] ?? null, 'datatable.col.organization'))
            ->setArgument('$tenantLabelDomain', self::asString($datatable['tenant_label_domain'] ?? null, 'messages'));
    }

    /**
     * @return array<string, string>
     */
    private static function stringMap(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $markup) {
            if (\is_string($key) && \is_string($markup)) {
                $map[$key] = $markup;
            }
        }

        return $map;
    }

    private static function asString(mixed $value, string $fallback): string
    {
        return \is_string($value) && '' !== $value ? $value : $fallback;
    }
}
