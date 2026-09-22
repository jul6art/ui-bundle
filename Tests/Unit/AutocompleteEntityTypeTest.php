<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Jul6Art\UiBundle\Form\Type\AutocompleteEntityType;
use Jul6Art\UiBundle\Tests\Fixtures\Entity\Person;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

/**
 * A Select2-AJAX entity field that never writes its table into the page.
 *
 * ## The defect this type exists for
 *
 * A form declared `data-ui--select2-url-value` on a plain `EntityType` and believed itself AJAX: the
 * widget did search the API, but Symfony still loaded and wrote EVERY row the `query_builder`
 * allowed — 801 `<option>` for a work order picker, growing with the business (cereezer report
 * 2026-09-22). The test that pinned it did so on a false premise: that validation compares the
 * posted value to the rendered options. It compares it to what the choice LOADER resolves.
 *
 * ## The bound is the point
 *
 * Superp's lazy loaders resolve posted ids with a bare `findBy(['id' => $ids])`. Copied as such, a
 * form that offered "active technicians only" would accept a manager posted by hand. This type
 * resolves the posted ids THROUGH the field's own `query_builder`, so what the list refused stays
 * refused.
 */
#[CoversClass(AutocompleteEntityType::class)]
final class AutocompleteEntityTypeTest extends TestCase
{
    private EntityManager $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $config = ORMSetup::createAttributeMetadataConfig([__DIR__.'/../Fixtures/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $this->entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        new SchemaTool($this->entityManager)->createSchema([$this->entityManager->getClassMetadata(Person::class)]);
    }

    /**
     * The whole point: however many rows the table holds, the page gets the empty option and
     * nothing else on a new form.
     */
    public function testANewFormRendersNoRowOfTheTable(): void
    {
        $this->persist(...array_map(static fn (int $i): Person => new Person('Person '.$i), range(1, 30)));

        $view = $this->form()->createView();

        self::assertSame([], $view->vars['choices']);
    }

    /**
     * ⚠️ Not only the page: the SERVER must not load the table either. The view is rebuilt from the
     * current value, so a loader that fetched every row would leave the test above green while
     * paying a full-table query on every render.
     */
    public function testTheChoiceListIsNeverLoaded(): void
    {
        $this->persist(...array_map(static fn (int $i): Person => new Person('Person '.$i), range(1, 30)));

        $loader = $this->form()->getConfig()->getOption('choice_loader');
        self::assertInstanceOf(ChoiceLoaderInterface::class, $loader);

        self::assertSame([], $loader->loadChoiceList()->getChoices());
    }

    /**
     * An edit form names its current value — the only option written — so the widget shows it.
     */
    public function testAnEditFormRendersItsCurrentValueAlone(): void
    {
        [$ada] = $this->persist(new Person('Ada'), new Person('Grace'), new Person('Hedy'));

        $view = $this->form($ada)->createView();
        $choices = $view->vars['choices'];

        self::assertIsArray($choices);
        self::assertCount(1, $choices);
        $choice = array_first($choices);
        self::assertInstanceOf(ChoiceView::class, $choice);
        self::assertSame('Ada', $choice->label);
        self::assertSame((string) $ada->getId(), $view->vars['value']);
    }

    public function testAPostedValueInsideTheBoundIsAccepted(): void
    {
        [$ada] = $this->persist(new Person('Ada'));

        $form = $this->form();
        $form->submit((string) $ada->getId());

        self::assertTrue($form->isSynchronized());
        self::assertSame($ada, $form->getData());
    }

    /**
     * ⚠️ The case a bare `findBy()` gets wrong: a row that exists, posted by hand, that the field's
     * `query_builder` excludes.
     */
    public function testAPostedValueOutsideTheBoundIsRefused(): void
    {
        [, $retired] = $this->persist(new Person('Ada'), new Person('Retired', active: false));

        $form = $this->form();
        $form->submit((string) $retired->getId());

        self::assertFalse($form->isSynchronized(), 'Une valeur hors du query_builder doit être refusée.');
        self::assertNull($form->getData());
    }

    public function testTheWidgetIsWiredForTheSelect2Controller(): void
    {
        $attr = self::attrOf($this->form(options: ['text_key' => 'fullName', 'depends_on' => '#site', 'depends_param' => 'site']));

        self::assertSame('ui--select2', $attr['data-controller']);
        self::assertSame('/api/people', $attr['data-ui--select2-url-value']);
        self::assertSame('fullName', $attr['data-ui--select2-text-key-value']);
        // ⚠️ Not the controller's default: it searches `?<textKey>=`, which no API resource exposes —
        // they carry an OrSearchFilter under `?search=`.
        self::assertSame('search', $attr['data-ui--select2-search-key-value']);
        self::assertSame('#site', $attr['data-ui--select2-depends-on-value']);
        self::assertSame('site', $attr['data-ui--select2-depends-param-value']);
    }

    /**
     * The empty option is what the Select2 clear button restores; without it the button has nothing
     * to go back to.
     */
    public function testTheEmptyOptionIsRendered(): void
    {
        self::assertSame('', $this->form()->createView()->vars['placeholder']);
    }

    public function testAProjectAttributeIsKeptAlongsideTheWiring(): void
    {
        $attr = self::attrOf($this->form(options: ['attr' => ['class' => 'custom']]));

        self::assertSame('custom', $attr['class']);
        self::assertSame('ui--select2', $attr['data-controller']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return FormInterface<mixed>
     */
    private function form(?Person $data = null, array $options = []): FormInterface
    {
        return $this->factory()->create(AutocompleteEntityType::class, $data, $options + [
            'class' => Person::class,
            'choice_label' => 'name',
            'autocomplete_url' => '/api/people',
            'query_builder' => static fn (EntityRepository $repository): QueryBuilder => $repository->createQueryBuilder('p')
                ->andWhere('p.active = true'),
        ]);
    }

    /**
     * @param FormInterface<mixed> $form
     *
     * @return array<array-key, mixed>
     */
    private static function attrOf(FormInterface $form): array
    {
        $attr = $form->createView()->vars['attr'];
        self::assertIsArray($attr);

        return $attr;
    }

    private function factory(): FormFactoryInterface
    {
        $registry = self::createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);
        $registry->method('getManager')->willReturn($this->entityManager);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new DoctrineOrmExtension($registry))
            ->addType(new AutocompleteEntityType())
            ->getFormFactory();
    }

    /**
     * @return list<Person>
     */
    private function persist(Person ...$people): array
    {
        foreach ($people as $person) {
            $this->entityManager->persist($person);
        }
        $this->entityManager->flush();

        return array_values($people);
    }
}
