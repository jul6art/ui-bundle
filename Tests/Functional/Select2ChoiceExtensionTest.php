<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Tests\Fixtures\Enum\Priority;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * No native `<select>` reaches the screen: every list of choices carries the application's Select2
 * controller, without any field having to remember it.
 */
#[CoversNothing]
final class Select2ChoiceExtensionTest extends FormRenderingTestCase
{
    private const array CHOICES = ['choices' => ['Low' => 'low', 'High' => 'high']];

    public function testAListOfChoicesBecomesASelect2(): void
    {
        $html = $this->render(ChoiceType::class, self::CHOICES);

        self::assertStringContainsString('<select', $html);
        self::assertStringContainsString('data-controller="ui--select2"', $html);
        self::assertStringContainsString('data-ui--select2-allow-clear-value="false"', $html, 'Required: nothing to clear.');
    }

    public function testAnOptionalFieldOffersTheClearButton(): void
    {
        $html = $this->render(ChoiceType::class, self::CHOICES + ['required' => false]);

        self::assertStringContainsString('data-ui--select2-allow-clear-value="true"', $html);
    }

    /** `EntityType`, `EnumType`, `CountryType`… inherit from `ChoiceType`: one extension covers them all. */
    public function testAChildTypeOfChoiceIsCoveredToo(): void
    {
        self::assertStringContainsString('data-controller="ui--select2"', $this->render(EnumType::class, ['class' => Priority::class]));
    }

    public function testExpandedChoicesAreNotASelect(): void
    {
        $html = $this->render(ChoiceType::class, self::CHOICES + ['expanded' => true]);

        self::assertStringNotContainsString('ui--select2', $html);
    }

    public function testAFieldKeepsItsOwnControllerAndItsOwnClearSetting(): void
    {
        $view = $this->view(ChoiceType::class, self::CHOICES + ['attr' => [
            'data-controller' => 'form--dependent-field',
            'data-ui--select2-allow-clear-value' => 'true',
        ]]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('form--dependent-field ui--select2', $attr['data-controller']);
        self::assertSame('true', $attr['data-ui--select2-allow-clear-value'], 'The field decided: it keeps its choice.');

        $already = $this->view(ChoiceType::class, self::CHOICES + ['attr' => ['data-controller' => 'ui--select2']])->vars['attr'];
        self::assertIsArray($already);
        self::assertSame('ui--select2', $already['data-controller'], 'Never twice.');
    }

    public function testTheIdentifierIsTheApplications(): void
    {
        $html = $this->render(ChoiceType::class, self::CHOICES, bundleConfig: ['select2_identifier' => 'forms--select2']);

        self::assertStringContainsString('data-controller="forms--select2"', $html);
        self::assertStringContainsString('data-forms--select2-allow-clear-value="false"', $html);
    }

    public function testAnEmptyIdentifierKeepsNativeSelects(): void
    {
        $html = $this->render(ChoiceType::class, self::CHOICES, bundleConfig: ['select2_identifier' => '']);

        self::assertStringContainsString('<select', $html);
        self::assertStringNotContainsString('data-controller', $html);
    }
}
