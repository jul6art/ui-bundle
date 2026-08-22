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

Fifteen form types and the Twig theme that renders them. Extracted from an application that runs
all of it.

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

> **Les datatables ont déménagé.** `AbstractDataTableConfigProvider` et `AdminDataTableConfig`
> vivent désormais dans **`jul6art/datatable-bundle`** depuis la v2.0.0 de ce bundle. Elles
> n'avaient de sens qu'au-dessus d'une collection API Platform, dont `ui-bundle` ne dépend pas —
> et ce bundle-ci reste utile à une application qui n'expose aucune API. Le remplacement est un
> changement de namespace :
>
> ```diff
> -use Jul6Art\UiBundle\DataTable\AbstractDataTableConfigProvider;
> +use Jul6Art\DatatableBundle\DataTable\AbstractDataTableConfigProvider;
> ```
>
> La configuration passe de `ui.datatable.tenant_*` à `datatable.tenant.*`.


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
