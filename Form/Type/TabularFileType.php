<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A file upload for a CSV or `.xlsx` import — `jul6art/dataflow-bundle`'s intake screen, given a
 * form field of its own.
 *
 * ```php
 * $builder->add('file', TabularFileType::class);
 * ```
 *
 * ## Why there is no `mimeTypes` constraint here
 *
 * ⚠️ **A browser's declared MIME type is exactly what this ecosystem learned not to trust.** A real
 * `.xls` sends `application/vnd.ms-excel`, which a browser also sends for a `.csv` saved out of
 * Excel — so an allow list strict enough to admit the second admits the first too, and a `File`
 * constraint here would either reject legitimate CSV uploads or let the binary workbook straight
 * through, depending on which way the list leans. `dataflow-bundle` settled this by reading the
 * file's own BYTES (`SpreadsheetSignature`, and each reader's own `supports()`) rather than
 * anything the browser claims — decided once, downstream, where the actual bytes are available.
 * A constraint here would be a second, weaker gate that disagrees with the real one on the exact
 * files that matter (D-14).
 *
 * `accept=".csv,.xlsx"` is set as a plain UX hint — it narrows the browser's file picker, and nothing
 * more. The upload is still validated by content once it reaches the bundle.
 *
 * @extends AbstractType<mixed>
 */
final class TabularFileType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return FileType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        // A normalizer, not a plain default: `setDefaults(['attr' => [...]])` would be REPLACED
        // wholesale by a consumer passing its own `attr` (e.g. a CSS class), silently dropping the
        // accept hint. Merging under the consumer's own keys lets an explicit `accept` still win.
        $resolver->setNormalizer(
            'attr',
            static fn (OptionsResolver $options, array $attr): array => ['accept' => '.csv,.xlsx', ...$attr],
        );
    }
}
