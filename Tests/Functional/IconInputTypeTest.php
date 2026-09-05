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
     * ⚠️ **La cible tactile de l'add-on : 44 px, et pas un utilitaire de plus.**
     *
     * Mesuré à 360 px sur un produit consommateur le 2026-09-05 : le bouton « afficher le mot de
     * passe » rendait **20 × 24**. Une icône de 20 px sans rembourrage propre, dans un parent qui
     * la CENTRAIT au lieu de la laisser s'étirer. Un doigt vise 44 ; sur un formulaire
     * d'inscription, c'est la première friction que rencontre un nouveau compte.
     *
     * Deux propriétés, et chacune a déjà été perdue une fois dans la famille de bundles :
     *
     * 1. le parent ne porte **plus** `items-center` — un enfant de flex s'étire alors de lui-même
     *    (`align-items: stretch` est le défaut), donc le bouton fait la hauteur du champ **sans
     *    qu'aucune hauteur soit écrite** ;
     * 2. le rembourrage est passé du parent au **bouton** : l'icône ne bouge pas d'un pixel, seule
     *    la surface tapable grandit. Un correctif qui déplace l'icône est un correctif qu'on
     *    annule à la première relecture visuelle.
     *
     * ⚠️ **Et rien de tout cela n'introduit un utilitaire nouveau.** Ce bundle n'a pas de feuille
     * de style : il dépend du Tailwind du CONSOMMATEUR, qui ne scanne pas ces gabarits. C'est la
     * leçon du commit `4f6a11c` d'`admin-bundle` — un correctif de cible tactile appuyé sur un
     * utilitaire que les consommateurs ne génèrent pas, donc un correctif qui n'existait que dans
     * le dépôt.
     *
     * @param class-string $type
     */
    #[DataProvider('iconTypes')]
    public function testTheAddonCarriesATappableAreaWithoutMovingTheIcon(string $type, string $side, string $glyph): void
    {
        $html = $this->render($type);

        self::assertStringContainsString(
            'flex items-center px-3',
            $html,
            'L\'add-on doit porter son propre rembourrage et son propre centrage : sans eux, la '
            .'zone tapable est celle de l\'icône — 20 × 24 mesurés.',
        );

        self::assertStringNotContainsString(
            'flex items-center '.('left' === $side ? 'pl-3' : 'pr-3').'"',
            $html,
            'Le PARENT ne doit plus centrer ni rembourrer : c\'est `items-center` sur le parent qui '
            .'empêchait le bouton de s\'étirer sur la hauteur du champ, et le rembourrage doit vivre '
            .'là où vit la cible.',
        );
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

    /**
     * Les add-ons se colorent dans le vocabulaire de la coquille — `slate`, pas `gray`.
     *
     * ⚠️ Ce n'est pas une préférence esthétique. Une application dont le contenu Tailwind ne
     * scanne pas ce répertoire ne génère QUE les classes écrites ailleurs ; `gray` n'étant utilisé
     * nulle part dans l'écosystème, `text-gray-400` était purgée et l'icône héritait de la couleur
     * du texte — NOIRE en clair, blanche en sombre. Mesuré chez wovex le 2026-08-24 :
     * `rgb(15, 23, 42)` pour une classe pourtant présente sur l'élément.
     */
    #[DataProvider('iconTypes')]
    public function testAnAddOnUsesTheSlateVocabulary(string $type, string $side, string $glyph): void
    {
        $html = $this->render($type);

        self::assertStringContainsString('text-slate-400', $html);
        self::assertStringNotContainsString('text-gray-', $html, 'La palette `gray` n\'est utilisée nulle part dans cet écosystème.');
    }

    /**
     * L'add-on est posé en `absolute` : il RECOUVRE le champ. Sans gouttière réservée sur
     * l'`<input>`, le texte saisi passe dessous — mesuré chez wovex le 2026-08-24 : 8 px de
     * chevauchement sur un champ e-mail (zone de texte jusqu'à 1043 px, icône à partir de 1035).
     *
     * La classe passe par `attr` et non par le balisage : la règle centrale des thèmes de cet
     * écosystème veut qu'un `class=` écrit dans le bloc écrase celle que le projet a posée en
     * option (`form-control`), et rende le champ invisible.
     */
    #[DataProvider('iconTypes')]
    public function testTheInputReservesAGutterForItsAddOn(string $type, string $side, string $glyph): void
    {
        $html = $this->render($type);
        $expected = 'left' === $side ? 'pl-10' : 'pr-10';

        self::assertMatchesRegularExpression(
            '/<input[^>]*class="[^"]*'.$expected.'/',
            $html,
            \sprintf('L\'`<input>` doit réserver %s pour son add-on %s.', $expected, $side),
        );
    }

    /**
     * Et pas de gouttière là où il n'y a pas d'add-on : un champ nu garde ses 12 px de padding.
     */
    public function testAFieldWithoutAnAddOnKeepsItsDefaultPadding(): void
    {
        $html = $this->render(CustomEmailType::class, bundleConfig: ['icons' => ['email' => '']]);

        self::assertStringNotContainsString('pr-10', $html);
        self::assertStringNotContainsString('pl-10', $html);
    }
}
