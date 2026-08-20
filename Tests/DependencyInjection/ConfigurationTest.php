<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\DependencyInjection;

use Jul6Art\UiBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The configuration tree is public API: an application writes against it and a rename breaks
 * someone's deployment. Assert the **whole** processed shape rather than one key at a time — that
 * is what makes an accidental addition or a changed default visible in a diff.
 */
#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testItsRootNodeIsTheBundleAlias(): void
    {
        self::assertSame('ui', new Configuration()->getConfigTreeBuilder()->buildTree()->getName());
    }

    /**
     * L'assertion porte sur la forme **entière** : c'est ce qui rend visible dans un diff une clé
     * ajoutée par mégarde ou un défaut modifié. Et le jeu d'icônes par défaut est du contrat —
     * retirer une clé que les types demandent fait disparaître une icône sans rien signaler, cf.
     * testEveryIconTheTypesAskForHasADefault().
     */
    public function testItAppliesItsDefaults(): void
    {
        $config = $this->process([]);

        self::assertTrue($config['enabled']);
        self::assertSame([
            'tenant_endpoint' => '/api/organizations',
            'tenant_label_key' => 'datatable.col.organization',
            'tenant_label_domain' => 'messages',
        ], $config['datatable']);
        self::assertSame(['enabled', 'icons', 'currency_icons', 'datatable'], array_keys($config));
    }

    /**
     * Le jeu par défaut doit couvrir exactement ce que les types demandent. Une clé manquante rend
     * un champ sans add-on — silencieusement, et sur toutes les pages où le type est utilisé.
     */
    public function testEveryIconTheTypesAskForHasADefault(): void
    {
        $icons = $this->process([])['icons'];
        self::assertIsArray($icons);

        foreach ([
            'address', 'city', 'email', 'key', 'license_plate', 'password',
            'phone', 'search', 'siret', 'url', 'vat_number', 'zip_code',
        ] as $name) {
            self::assertArrayHasKey($name, $icons, \sprintf('Le type qui demande « %s » rendrait sans add-on.', $name));
        }
    }

    /**
     * ⚠️ Un nœud prototype **remplace** la table entière : ce que la configuration rend ici ne
     * contient que ce que le projet a écrit. La reconstitution du jeu complet est le travail de
     * l'extension, et c'est UiExtensionTest qui la vérifie. Ce test fige la moitié surprenante,
     * pour que personne ne recâble l'extension en croyant la couche de configuration fusionnante.
     */
    public function testAProvidedIconMapReplacesTheDefaultsAtThisLayer(): void
    {
        $icons = $this->process([['icons' => ['email' => '<svg/>']]])['icons'];
        self::assertIsArray($icons);

        self::assertSame(['email' => '<svg/>'], $icons);
    }

    public function testLaterConfigsOverrideEarlierOnes(): void
    {
        self::assertTrue($this->process([['enabled' => false], ['enabled' => true]])['enabled']);
    }

    /**
     * A `booleanNode` refuses anything but a boolean, which is what you want — and the reason an
     * env var cannot gate service registration.
     */
    #[DataProvider('nonBooleanValues')]
    public function testItRejectsNonBooleanValues(mixed $value): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([['enabled' => $value]]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonBooleanValues(): iterable
    {
        yield 'string' => ['yes'];
        yield 'int' => [0];
        yield 'array' => [[]];
    }

    /**
     * @param array<int, array<string, mixed>> $configs
     *
     * @return array<array-key, mixed>
     */
    private function process(array $configs): array
    {
        return new Processor()->processConfiguration(new Configuration(), $configs);
    }
}
