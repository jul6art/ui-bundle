<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Form\Type\CustomMoneyType;
use Jul6Art\UiBundle\Form\Type\CustomUnitType;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class NumericTypeTest extends FormRenderingTestCase
{
    public function testAKnownCurrencyRendersItsSymbol(): void
    {
        $html = $this->render(CustomMoneyType::class, ['currency' => 'EUR']);

        self::assertStringContainsString('fa-euro-sign', $html);
    }

    /**
     * La devise sans symbole configuré tombe sur son code ISO **en texte**, et c'est la bonne
     * réponse : afficher un symbole approchant (« € » à côté d'un montant en francs suisses) est
     * une erreur de reporting, pas un défaut cosmétique.
     */
    public function testAnUnknownCurrencyFallsBackToItsIsoCode(): void
    {
        $view = $this->view(CustomMoneyType::class, ['currency' => 'CHF']);

        self::assertSame('CHF', $view->vars['right_addon']);
        self::assertSame('text', $view->vars['right_type']);
    }

    public function testTheCurrencyIsCaseInsensitive(): void
    {
        $view = $this->view(CustomMoneyType::class, ['currency' => 'eur']);

        self::assertIsString($view->vars['right_addon']);
        self::assertStringContainsString('fa-euro-sign', $view->vars['right_addon']);
    }

    /**
     * Le code ISO arrive dans un add-on rendu en `raw` : il est échappé à la source. Sans cela, une
     * devise venue d'une donnée métier ouvrirait une injection dans chaque formulaire de montant.
     */
    public function testTheIsoFallbackIsEscapedAtTheSource(): void
    {
        $view = $this->view(CustomMoneyType::class, ['currency' => '"><script>']);

        self::assertIsString($view->vars['right_addon']);
        self::assertStringNotContainsString('<script>', $view->vars['right_addon']);
        self::assertStringContainsString('&quot;', $view->vars['right_addon']);
    }

    public function testAProjectCanSupplyItsOwnCurrencySymbols(): void
    {
        $view = $this->view(CustomMoneyType::class, ['currency' => 'CHF'], [
            'currency_icons' => ['CHF' => '<i class="i-chf"></i>'],
        ]);

        self::assertSame('<i class="i-chf"></i>', $view->vars['right_addon']);
        self::assertSame('icon', $view->vars['right_type']);
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function decimalTypes(): iterable
    {
        yield 'money' => [CustomMoneyType::class];
        yield 'unit' => [CustomUnitType::class];
    }

    #[DataProvider('decimalTypes')]
    public function testTheDecimalControllerIsAttachedWithTheScale(string $type): void
    {
        $view = $this->view($type, ['scale' => 3]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('form--decimal', $attr['data-controller']);
        self::assertSame('3', $attr['data-form--decimal-decimals-value']);
    }

    /**
     * `data-controller` est une liste : l'écraser ferait disparaître le comportement que le projet
     * avait posé sur le champ, sans erreur et sans trace.
     */
    #[DataProvider('decimalTypes')]
    public function testAnExistingControllerIsKeptRatherThanReplaced(string $type): void
    {
        $view = $this->view($type, ['attr' => ['data-controller' => 'projet--truc']]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('projet--truc form--decimal', $attr['data-controller']);
    }

    #[DataProvider('decimalTypes')]
    public function testTheControllerIsNotAttachedTwice(string $type): void
    {
        $view = $this->view($type, ['attr' => ['data-controller' => 'form--decimal']]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('form--decimal', $attr['data-controller']);
    }

    /**
     * `inputmode` appartient à `NumberType`, pas à ce bundle : il l'écrit après nous, et le choisit
     * selon `scale` (`numeric` à zéro décimale, `decimal` sinon). Ce test fige ce partage — si un
     * jour ce bundle se met à poser l'attribut, il sera silencieusement écrasé, et ce test dira où
     * regarder.
     */
    #[DataProvider('decimalTypes')]
    public function testTheInputModeIsLeftToTheParentType(string $type): void
    {
        $attr = $this->view($type, ['attr' => ['inputmode' => 'numeric']])->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('decimal', $attr['inputmode'], 'NumberType impose inputmode, y compris contre une valeur explicite.');

        $zero = $this->view($type, ['scale' => 0])->vars['attr'];
        self::assertIsArray($zero);
        self::assertSame('numeric', $zero['inputmode']);
    }

    public function testAUnitIsRenderedAsText(): void
    {
        $view = $this->view(CustomUnitType::class, ['unit' => 'h']);

        self::assertSame('h', $view->vars['right_addon']);
        self::assertSame('text', $view->vars['right_type']);
    }

    /**
     * Une unité vide ne rend **aucun** add-on : un champ dont l'unité vient d'une donnée (l'unité
     * d'un article, par exemple) doit retomber sur une saisie numérique nue, pas sur une boîte vide.
     */
    public function testAnEmptyUnitRendersNoAddOn(): void
    {
        $view = $this->view(CustomUnitType::class, ['unit' => '']);

        self::assertNull($view->vars['right_addon']);
        self::assertStringNotContainsString('right-0', $this->render(CustomUnitType::class, ['unit' => '']));
    }

    public function testAUnitIsEscapedAtTheSource(): void
    {
        $view = $this->view(CustomUnitType::class, ['unit' => '<b>kg</b>']);

        self::assertIsString($view->vars['right_addon']);
        self::assertStringNotContainsString('<b>', $view->vars['right_addon']);
    }

    /**
     * `scale` par défaut : deux décimales, comme une somme d'argent. Une valeur absente ne doit pas
     * produire `data-…-decimals-value=""`, que le contrôleur front lirait comme zéro.
     */
    public function testTheDefaultScaleIsTwo(): void
    {
        $view = $this->view(CustomMoneyType::class);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('2', $attr['data-form--decimal-decimals-value']);
    }
}
