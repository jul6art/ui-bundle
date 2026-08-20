<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Ui\IconSet;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * The wiring the configuration layer cannot do on its own.
 */
#[CoversNothing]
final class UiExtensionTest extends AbstractFunctionalTestCase
{
    /**
     * Le point de bascule du bundle : déclarer une icône garde les autres.
     *
     * La couche de configuration, elle, remplace la table (cf. ConfigurationTest) — c'est
     * l'extension qui refusionne. Sans elle, `ui.icons.email: '<svg/>'` dans un projet ferait
     * disparaître onze add-ons d'un coup, sans erreur, sans log, et sans qu'aucun test de
     * formulaire ne le voie puisque chacun ne regarde que son propre champ.
     */
    public function testDeclaringOneIconKeepsTheDefaults(): void
    {
        $icons = $this->icons(['icons' => ['email' => '<svg class="i-mail"/>']]);

        self::assertSame('<svg class="i-mail"/>', $icons->get('email'));
        self::assertSame('<i class="fa-solid fa-phone"></i>', $icons->get('phone'));
    }

    public function testDeclaringOneCurrencyKeepsTheDefaults(): void
    {
        $icons = $this->icons(['currency_icons' => ['CHF' => '<i class="i-chf"></i>']]);

        self::assertSame('<i class="i-chf"></i>', $icons->currency('CHF'));
        self::assertSame('<i class="fa-solid fa-euro-sign"></i>', $icons->currency('EUR'));
    }

    /**
     * Retirer une icône du jeu par défaut : une valeur vide, plutôt que de redéclarer les onze
     * autres pour en enlever une.
     */
    public function testAnEmptyValueRemovesAnIcon(): void
    {
        self::assertNull($this->icons(['icons' => ['email' => '']])->get('email'));
    }

    public function testAnUnknownNameHasNoIcon(): void
    {
        self::assertNull($this->icons()->get('rien-de-tel'));
    }

    public function testTheBundleExposesWhetherItIsActive(): void
    {
        self::assertTrue($this->boot()->getParameter('ui.enabled'));
    }

    /**
     * @param array<string, mixed> $bundleConfig
     */
    private function icons(array $bundleConfig = []): IconSet
    {
        $icons = $this->boot(bundleConfig: $bundleConfig)->get(IconSet::class);
        self::assertInstanceOf(IconSet::class, $icons);

        return $icons;
    }
}
