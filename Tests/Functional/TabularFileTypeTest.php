<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Functional;

use Jul6Art\UiBundle\Form\Type\TabularFileType;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class TabularFileTypeTest extends FormRenderingTestCase
{
    public function testItRendersAsARealFileInput(): void
    {
        $html = $this->render(TabularFileType::class);

        self::assertStringContainsString('type="file"', $html);
    }

    public function testTheAcceptAttributeNarrowsTheFilePicker(): void
    {
        $view = $this->view(TabularFileType::class);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('.csv,.xlsx', $attr['accept']);
    }

    /**
     * ⚠️ A plain `setDefaults(['attr' => [...]])` would be replaced wholesale by this option, and
     * the picker would silently accept every file again.
     */
    public function testAnAttributeSuppliedByTheCallerIsKeptAlongsideAccept(): void
    {
        $view = $this->view(TabularFileType::class, ['attr' => ['class' => 'my-input']]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('.csv,.xlsx', $attr['accept']);
        self::assertSame('my-input', $attr['class']);
    }

    /**
     * An explicit `accept` from the caller is a deliberate choice and wins over the default.
     */
    public function testAnExplicitAcceptIsNotOverridden(): void
    {
        $view = $this->view(TabularFileType::class, ['attr' => ['accept' => '.txt']]);

        $attr = $view->vars['attr'];
        self::assertIsArray($attr);
        self::assertSame('.txt', $attr['accept']);
    }

    /**
     * ⚠️ `FileType`'s own machinery — `multipart`, the upload-error PRE_SUBMIT listener — must still
     * run. This is what would silently stop working if `getParent()` pointed anywhere else.
     */
    public function testTheUnderlyingFileTypeBehaviourIsPreserved(): void
    {
        $view = $this->view(TabularFileType::class);

        self::assertTrue($view->vars['multipart']);
        self::assertSame('file', $view->vars['type']);
    }
}
