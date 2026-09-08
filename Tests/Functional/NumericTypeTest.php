<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Form\Type\CustomCountType;
use Jul6Art\UiBundle\Form\Type\CustomMoneyType;
use Jul6Art\UiBundle\Form\Type\CustomUnitType;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class NumericTypeTest extends FormRenderingTestCase
{
    /**
     * **Un ENTIER avec son unité — le type qui manquait.**
     *
     * ⚠️ `CustomUnitType` est parenté sur `NumberType` et soumet un FLOAT. Sur une propriété typée
     * `?int` — un délai en heures, un intervalle en mois — cela donne un `TypeError` en mode
     * strict : un 500 sur une saisie parfaitement ordinaire. Le contournement qu'on tente ensuite,
     * `InputGroupAddOnType` directement, est parenté sur `TextType` et soumet une CHAÎNE, qui
     * échoue pour la même raison. Un projet voulant « 12 h » dans la case avait donc trois
     * possibilités, toutes fausses.
     */
    public function testAWholeNumberCarriesItsUnit(): void
    {
        $view = $this->view(CustomCountType::class, ['unit' => 'h']);

        self::assertSame('h', $view->vars['right_addon']);
        self::assertSame('text', $view->vars['right_type']);
        self::assertFalse($view->vars['right_clickable']);
    }

    /**
     * ⚠️ **C'est la raison d'être du type** : parenté sur `IntegerType`, donc une valeur soumise
     * arrive en `int`. Un `float` ici, et chaque mutateur `?int` du produit lèverait.
     */
    public function testAWholeNumberSubmitsAnInteger(): void
    {
        $container = $this->boot();
        $factory = $container->get('form.factory');
        self::assertInstanceOf(\Symfony\Component\Form\FormFactoryInterface::class, $factory);

        $form = $factory->createBuilder(CustomCountType::class, null, ['unit' => 'h'])->getForm();
        $form->submit('12');

        self::assertTrue($form->isValid());
        self::assertSame(12, $form->getData());
    }

    /**
     * ⚠️ Une unité vide ne rend AUCUN add-on, plutôt qu'une case vide : un champ dont l'unité n'est
     * connue qu'à l'exécution dégrade en simple champ numérique au lieu d'afficher une décoration
     * orpheline. Même règle que `CustomUnitType`.
     */
    public function testAnEmptyUnitOnAWholeNumberRendersNoAddOn(): void
    {
        $view = $this->view(CustomCountType::class);

        self::assertNull($view->vars['right_addon']);
        self::assertStringNotContainsString('right-0', $this->render(CustomCountType::class));
    }

    /**
     * ⚠️ Une unité vient d'une donnée métier, pas d'un jeu d'icônes : elle est échappée À LA
     * SOURCE, comme le code ISO d'une devise inconnue.
     */
    public function testAWholeNumberUnitIsEscapedAtTheSource(): void
    {
        $view = $this->view(CustomCountType::class, ['unit' => '"><script>']);

        self::assertSame('&quot;&gt;&lt;script&gt;', $view->vars['right_addon']);
    }

    /**
     * ⚠️ **PAS de contrôleur décimal**, contrairement à ses deux frères : il formate une échelle, et
     * un entier n'en a pas. L'attacher poserait une échelle de zéro sur un champ qui ne peut de
     * toute façon pas montrer de décimale — une déclaration qui ne dit rien et qu'un lecteur croit.
     */
    public function testAWholeNumberGetsNoDecimalController(): void
    {
        $view = $this->view(CustomCountType::class, ['unit' => 'h']);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);

        $controller = $attr['data-controller'] ?? '';
        self::assertIsString($controller);
        self::assertStringNotContainsString('form--decimal', $controller);
        self::assertArrayNotHasKey('data-form--decimal-decimals-value', $attr);
    }

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
