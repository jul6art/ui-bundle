<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * Boots form + Twig and renders for real.
 *
 * Asserting on `view.vars` alone proves that a type set an option; it does not prove that anything
 * appears on screen. The two failure modes that matter — a theme not registered, a block prefix
 * that no longer routes to the shipped block — leave every `view.vars` assertion green and every
 * form silently plain. So the assertions here go through `render()` wherever the claim is about
 * what a user sees.
 */
abstract class FormRenderingTestCase extends AbstractFunctionalTestCase
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $bundleConfig
     */
    final protected function view(string $type, array $options = [], array $bundleConfig = []): FormView
    {
        $container = $this->boot(bundleConfig: $bundleConfig);

        $factory = $container->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        return $factory->createBuilder($type, null, $options)->getForm()->createView();
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $bundleConfig
     */
    final protected function render(string $type, array $options = [], array $bundleConfig = []): string
    {
        $container = $this->boot(bundleConfig: $bundleConfig);

        $factory = $container->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        $twig = $container->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $view = $factory->createBuilder($type, null, $options)->getForm()->createView();

        // Le gabarit le plus proche de ce qu'écrit un projet : `form_widget`, qui traverse la
        // chaîne de thèmes exactement comme en production.
        return $twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $view]);
    }
}
