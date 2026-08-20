<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Form\Type\CustomAddressType;
use Jul6Art\UiBundle\Form\Type\CustomCityType;
use Jul6Art\UiBundle\Form\Type\CustomEmailType;
use Jul6Art\UiBundle\Form\Type\CustomKeyType;
use Jul6Art\UiBundle\Form\Type\CustomLicensePlateType;
use Jul6Art\UiBundle\Form\Type\CustomPasswordType;
use Jul6Art\UiBundle\Form\Type\CustomPhoneType;
use Jul6Art\UiBundle\Form\Type\CustomSearchType;
use Jul6Art\UiBundle\Form\Type\CustomSiretType;
use Jul6Art\UiBundle\Form\Type\CustomUrlType;
use Jul6Art\UiBundle\Form\Type\CustomVatNumberType;
use Jul6Art\UiBundle\Form\Type\CustomZipCodeType;
use Jul6Art\UiBundle\Form\Type\InputGroupAddOnType;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class IconInputTypeTest extends FormRenderingTestCase
{
    /**
     * Chaque type demande une icône *logique* et la reçoit résolue. Le test porte sur le rendu et
     * non sur `view.vars` : c'est le seul niveau où l'on constate que le thème est bien branché.
     *
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function iconTypes(): iterable
    {
        yield 'address' => [CustomAddressType::class, 'right', 'fa-location-dot'];
        yield 'city' => [CustomCityType::class, 'right', 'fa-city'];
        yield 'email' => [CustomEmailType::class, 'right', 'fa-envelope'];
        yield 'key' => [CustomKeyType::class, 'right', 'fa-key'];
        yield 'license plate' => [CustomLicensePlateType::class, 'right', 'fa-car'];
        yield 'password' => [CustomPasswordType::class, 'right', 'fa-eye'];
        yield 'phone' => [CustomPhoneType::class, 'right', 'fa-phone'];
        yield 'search' => [CustomSearchType::class, 'left', 'fa-magnifying-glass'];
        yield 'siret' => [CustomSiretType::class, 'right', 'fa-fingerprint'];
        yield 'url' => [CustomUrlType::class, 'right', 'fa-link'];
        yield 'vat number' => [CustomVatNumberType::class, 'right', 'fa-id-card'];
        yield 'zip code' => [CustomZipCodeType::class, 'right', 'fa-map-pin'];
    }

    /**
     * @param class-string $type
     */
    #[DataProvider('iconTypes')]
    public function testTheDefaultSetRendersFontAwesomeMarkup(string $type, string $side, string $glyph): void
    {
        $html = $this->render($type);

        self::assertStringContainsString($glyph, $html);
        self::assertStringContainsString('left' === $side ? 'left-0' : 'right-0', $html);
    }

    /**
     * @param class-string $type
     */
    #[DataProvider('iconTypes')]
    public function testAnAddOnIsOnTheDeclaredSide(string $type, string $side, string $glyph): void
    {
        $view = $this->view($type);

        self::assertNotNull($view->vars[$side.'_addon'] ?? null, 'L\'add-on doit être posé du côté déclaré.');
        self::assertNull($view->vars[('left' === $side ? 'right' : 'left').'_addon'] ?? null, 'Et sur ce seul côté.');
    }

    /**
     * Le point de toute l'indirection : un projet sans Font Awesome remplace le balisage, et rien
     * dans le bundle n'a à changer.
     */
    public function testAProjectCanSubstituteItsOwnIconSet(): void
    {
        $html = $this->render(CustomEmailType::class, bundleConfig: [
            'icons' => ['email' => '<svg class="i-mail"></svg>'],
        ]);

        self::assertStringContainsString('<svg class="i-mail"></svg>', $html);
        self::assertStringNotContainsString('fa-envelope', $html);
    }

    /**
     * Une clé absente ne doit pas faire tomber le formulaire : un jeu d'icônes partiel rend un
     * champ sans add-on, pas une erreur 500 sur une page de saisie.
     */
    public function testARemovedIconRendersTheFieldWithoutAnAddOn(): void
    {
        $html = $this->render(CustomEmailType::class, bundleConfig: ['icons' => ['email' => '']]);

        self::assertStringContainsString('<input', $html);
        self::assertStringNotContainsString('fa-envelope', $html);
        self::assertStringNotContainsString('right-0', $html, 'Pas de boîte vide à la place de l\'icône retirée.');
    }

    /**
     * Le mot de passe est le seul add-on cliquable : c'est un vrai `<button>`, focalisable, et il
     * porte l'action Stimulus que le projet doit brancher. Un `<span>` ne serait pas atteignable au
     * clavier.
     */
    public function testThePasswordAddOnIsAFocusableButton(): void
    {
        $html = $this->render(CustomPasswordType::class);

        self::assertStringContainsString('<button type="button"', $html);
        self::assertStringContainsString('data-action="form--password#toggle"', $html);
        self::assertStringContainsString('type="password"', $html);
    }

    /**
     * `InputGroupAddOnType` est utilisable directement, avec du balisage fourni par l'appelant.
     */
    public function testTheBaseTypeAcceptsAnExplicitAddOn(): void
    {
        $html = $this->render(InputGroupAddOnType::class, [
            'right_addon' => '<i class="fa-solid fa-hashtag"></i>',
        ]);

        self::assertStringContainsString('fa-hashtag', $html);
    }

    /**
     * Tous les types partagent le préfixe de bloc `input_group_addon`, et c'est lui qui les route
     * vers le gabarit du bundle. Le changer casserait les douze d'un coup, silencieusement — le
     * champ rendrait le widget par défaut de Symfony sans rien signaler.
     */
    #[DataProvider('iconTypes')]
    public function testEveryTypeRoutesToTheBundleBlock(string $type, string $side, string $glyph): void
    {
        // La classe `relative w-full` n'existe que dans le bloc du bundle.
        self::assertStringContainsString('relative w-full', $this->render($type));
    }
}
