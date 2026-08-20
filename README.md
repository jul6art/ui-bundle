<p align="center">
    <a href="https://devinthehood.com"><img src="https://github.com/jul6art/symfony-skeleton-generator/blob/master/public/img/logo.png?raw=true" alt="logo dev in the hood" width="400"></a>
</p>

<p align="center">
    <a href="https://opensource.org/licenses/MIT" target="_blank"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License"></a>
    <img src="https://img.shields.io/static/v1?label=stable&message=v1&color=orange" alt="Version">
</p>

Symfony UI bundle
=================

Symfony UI bundle

Requirements
------------

- PHP ^8.5
- Symfony ^7.4 || ^8.0

Installation
------------

```shell
composer require jul6art/ui-bundle
```

Then register it in `config/bundles.php` (Flex does this for you):

```php
Jul6Art\UiBundle\UiBundle::class => ['all' => true],
```

Configuration
-------------

```yaml
# config/packages/ui.yaml
ui:
    # Leaves the bundle installed and inert when false.
    enabled: true
```

`ui.enabled` is also exposed as a container parameter.

Usage
-----

Fifteen form types, a Twig theme that renders them, and the base class behind a server-driven
datatable. Extracted from an application that runs all of it.

### Register the form theme — nothing renders without it

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@Ui/form/input_group_addon.html.twig'
```

> ⚠️ **This is the step that fails silently.** The types set view variables; the theme is what
> turns them into markup. Skip it and every field still renders — as a plain input, with the icon
> gone, nothing in any log and no test failing. Assert on rendered HTML somewhere in the project,
> not on `view.vars`.

The shipped markup is Tailwind-flavoured, because a form theme *is* markup and a bundle shipping
one has to pick a vocabulary. A project on another framework registers its own theme **after** this
one and redeclares `input_group_addon_widget`; Symfony takes the last definition.

### The twelve icon types

```php
$builder
    ->add('email', CustomEmailType::class)
    ->add('phone', CustomPhoneType::class)
    ->add('website', CustomUrlType::class)
    ->add('q', CustomSearchType::class);       // magnifier on the left
```

`CustomAddressType`, `CustomCityType`, `CustomEmailType`, `CustomKeyType`,
`CustomLicensePlateType`, `CustomPasswordType`, `CustomPhoneType`, `CustomSearchType`,
`CustomSiretType`, `CustomUrlType`, `CustomVatNumberType`, `CustomZipCodeType`. Each builds on the
right HTML input — `EmailType`, `TelType`, `UrlType`, `SearchType` — so browser-side keyboard and
validation come for free.

Every add-on is **decoration**. Nothing here validates a SIRET, a VAT number or a phone number:
validation belongs on the entity, where an import and an API write go through it too.

### The icons are yours, not the bundle's

The types ask for a logical name (`email`), never for markup. Font Awesome 6 ships as the default,
so a project already using it configures nothing:

```yaml
# config/packages/ui.yaml
ui:
    icons:
        email: '<svg class="icon"><use href="#mail"/></svg>'   # override one
        phone: ''                                              # remove one
```

Overriding one key **keeps** the other eleven — the bundle re-merges the defaults, because a
prototype config node otherwise replaces the whole map and eleven add-ons vanish at once.

The available names: `address`, `city`, `email`, `key`, `license_plate`, `password`, `phone`,
`search`, `siret`, `url`, `vat_number`, `zip_code`.

### Amounts and quantities

```php
$builder
    ->add('total', CustomMoneyType::class, ['currency' => 'CHF', 'scale' => 2])
    ->add('duration', CustomUnitType::class, ['unit' => 'h']);
```

`CustomMoneyType` builds on `NumberType`, not Symfony's `MoneyType`: `MoneyType` divides by 100 and
stores integer cents, while this keeps the scalar a `decimal` column maps to.

A configured currency shows its symbol; one without shows its **ISO code as text**. That fallback
is correct for a good third of the world's currencies (CHF, PLN, SEK…) and is deliberately not
approximated — a euro sign beside a Swiss-franc amount is a reporting error, not a cosmetic one.
Add symbols with `ui.currency_icons`, keyed by ISO code.

`CustomUnitType` with an empty `unit` renders no add-on at all, so a field whose unit comes from
data degrades to a plain number input rather than an empty box.

> ⚠️ **Both types attach a `form--decimal` Stimulus controller that this bundle does not ship.**
> Exposing Stimulus controllers would mean choosing AssetMapper or Encore for every consumer. Write
> the controller in the project — thousands separator, decimal comma, keystroke filtering — reading
> `data-form--decimal-decimals-value`. Without it the field is a plain, unformatted number input,
> which works but is not what the type promises. Same for `form--password`, which the reveal button
> in the theme is wired to.
>
> The attachment appends to `data-controller` rather than replacing it, so a field that already
> carries a project controller keeps it.

### A field with an add-on of your own

```php
$builder->add('reference', InputGroupAddOnType::class, [
    'right_addon' => '<i class="fa-solid fa-hashtag"></i>',
    'right_type'  => 'button',    // icon | button | text — only `button` is focusable
    'right_clickable' => true,
]);
```

### Datatables

```php
final class WidgetDataTableConfigProvider extends AbstractDataTableConfigProvider
{
    public function getColumns(): array
    {
        return [
            $this->column('id', 'datatable.col.id', responsivePriority: 1),
            $this->column('name', 'widget.field.name', 'widget'),
            $this->column('reference', 'widget.field.reference', 'widget', sortField: 'sortableReference'),
            $this->readOnlyColumn('tags', 'widget.field.tags', 'widget', render: 'badges'),
        ];
    }

    public function getFilters(): array
    {
        return [
            $this->dateRangeFilter('issuedAt', 'issuedAt', 'widget.filter.issued', 'widget'),
            $this->apiFilter('category', 'category', 'widget.filter.category', '/api/categories'),
        ];
    }
}
```

Each helper returns a plain array, and the arrays are meant to be serialised to a front-end table.
Three things stay in the project: the JSON endpoint the table reads, the JavaScript that draws it,
and the API filters the `param` names point at.

The reason to use the helpers rather than literal arrays is that every label goes through the
translator. Hand-written arrays are how a table ends up with one translated header and one raw key
— which no test catches, because a datatable configuration has no expected output.

> ⚠️ **`dateRangeFilter`\'s `granularity` is not cosmetic.** `'date'` (the default) sends the civil
> date as picked, for a `date_immutable` column. `'datetime'` converts it to a UTC instant, for a
> `datetime` column. Getting it wrong shifts every result by one day for every user whose browser
> is not on UTC — an invoice dated the 1st stops matching a range starting the 1st.

### Cross-tenant listings

```php
$columns = $admin->decorateColumns($provider->getColumns());
$filters = [...$provider->getFilters(), $admin->tenantFilter()];
```

`AdminDataTableConfig` inserts the tenant column — second, right after `id` — and its autocomplete
filter, so a super-admin page reuses the *same* provider a tenant user sees instead of a parallel
copy that drifts one column at a time.

```yaml
ui:
    datatable:
        tenant_endpoint: /api/organizations
        tenant_label_key: datatable.col.organization
```

The endpoint must expose a search filter under `?search=` and return `id` and `name`. That is a
real coupling, and it is why these are configuration rather than constants: a "tenant" is an
`Organization` here and a `Workspace` elsewhere.

Quality assurance
-----------------

```shell
composer qa            # cs-check + rector-check + phpstan (level max) + phpunit
```

Run `composer qa`, not the single tool you have in mind: the CI's "Coding standards" job runs
Rector too, and its `lowest deps` job installs the minimum of every constraint — which is where
this ecosystem has repeatedly found what a local run could not.

`extra.symfony.require` states which Symfony line this bundle targets; the CI enforces it with
`SYMFONY_REQUIRE` on both the highest and the lowest job. A local `composer install` may still
resolve a newer Symfony, which broadens what you exercise rather than narrowing it — but it means
the toolchain can propose something that only makes sense on one branch. `rector.php` skips one
such rule already, with the reason written next to it.

Whatever you do, keep the code free of classes that exist on only one of the declared branches.
A bundle promising `^7.4 || ^8.0` has to hold both.

License
-------

The UI bundle is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

&copy; 2026 [jul6art](https://devinthehood.com)
