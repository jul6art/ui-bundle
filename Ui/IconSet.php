<?php

declare(strict_types=1);

namespace Jul6Art\UiBundle\Ui;

/**
 * Resolves a logical icon name into the markup a project actually wants to render.
 *
 * The form types in this bundle ask for `email` or `search`, never for
 * `<i class="fa-solid fa-envelope"></i>`. That indirection exists for one concrete reason: an
 * icon set is a **project** decision. A bundle that hard-codes Font Awesome markup silently makes
 * Font Awesome a dependency of every consumer, and a project on Lucide, Heroicons or an inline SVG
 * sprite gets empty squares in every input — with nothing in any log to explain why.
 *
 * Font Awesome 6 is shipped as the default set, so a project already using it configures nothing
 * and renders exactly what it rendered before. Anything else overrides the keys it cares about:
 *
 * ```yaml
 * ui:
 *     icons:
 *         email: '<svg class="icon"><use href="#mail"/></svg>'
 * ```
 *
 * > ⚠️ **The values are rendered as raw HTML**, because they are markup — that is the whole point.
 * > They come from the application's own configuration, which is the same trust level as a
 * > template. Never feed this map from user input.
 */
final readonly class IconSet
{
    /**
     * Font Awesome 6, shipped so a project already using it configures nothing and renders exactly
     * what it rendered before extraction.
     *
     * A project on Lucide, Heroicons or an SVG sprite overrides the keys it cares about. That
     * indirection is the whole reason the form types ask for `email` rather than for markup: a
     * bundle hard-coding `fa-solid` makes Font Awesome a silent dependency of every consumer, and
     * a project without it gets empty squares in every input with nothing in any log.
     *
     * @var array<string, string>
     */
    public const array FONT_AWESOME_ICONS = [
        'address' => '<i class="fa-solid fa-location-dot"></i>',
        'city' => '<i class="fa-solid fa-city"></i>',
        'email' => '<i class="fa-solid fa-envelope"></i>',
        'key' => '<i class="fa-solid fa-key"></i>',
        'license_plate' => '<i class="fa-solid fa-car"></i>',
        'password' => '<i class="fa-solid fa-eye"></i>',
        'phone' => '<i class="fa-solid fa-phone"></i>',
        'search' => '<i class="fa-solid fa-magnifying-glass"></i>',
        'siret' => '<i class="fa-solid fa-fingerprint"></i>',
        'url' => '<i class="fa-solid fa-link"></i>',
        'vat_number' => '<i class="fa-solid fa-id-card"></i>',
        'zip_code' => '<i class="fa-solid fa-map-pin"></i>',
    ];

    /**
     * The currencies Font Awesome 6 actually has a glyph for. Deliberately not exhaustive: a
     * currency missing here falls back to its ISO code in text, which is correct and readable.
     *
     * @var array<string, string>
     */
    public const array FONT_AWESOME_CURRENCIES = [
        'EUR' => '<i class="fa-solid fa-euro-sign"></i>',
        'USD' => '<i class="fa-solid fa-dollar-sign"></i>',
        'GBP' => '<i class="fa-solid fa-sterling-sign"></i>',
        'JPY' => '<i class="fa-solid fa-yen-sign"></i>',
        'CNY' => '<i class="fa-solid fa-yen-sign"></i>',
        'INR' => '<i class="fa-solid fa-indian-rupee-sign"></i>',
        'RUB' => '<i class="fa-solid fa-ruble-sign"></i>',
        'BRL' => '<i class="fa-solid fa-brazilian-real-sign"></i>',
        'KRW' => '<i class="fa-solid fa-won-sign"></i>',
        'TRY' => '<i class="fa-solid fa-turkish-lira-sign"></i>',
        'ILS' => '<i class="fa-solid fa-shekel-sign"></i>',
        'NGN' => '<i class="fa-solid fa-naira-sign"></i>',
        'PHP' => '<i class="fa-solid fa-peso-sign"></i>',
    ];

    /**
     * @param array<string, string> $icons         logical name → markup
     * @param array<string, string> $currencyIcons ISO 4217 code → markup
     */
    public function __construct(
        private array $icons,
        private array $currencyIcons,
    ) {
    }

    /**
     * The markup for a logical icon, or null when the project has no icon for it.
     *
     * Null rather than an exception: a missing icon must degrade to an input without an add-on,
     * not to a 500 on a form. A typo in a key is caught by the bundle's own test, which asserts
     * that every key the types ask for exists in the default set.
     */
    public function get(string $name): ?string
    {
        // Une valeur vide vaut « pas d'icône » : c'est ainsi qu'un projet en retire une du jeu par
        // défaut, sans avoir à redéclarer les onze autres.
        return '' !== ($this->icons[$name] ?? '') ? $this->icons[$name] : null;
    }

    /**
     * The markup for a currency symbol, or null for a currency with no configured symbol.
     *
     * Null is the normal case for a good third of the world's currencies (CHF, PLN, SEK…), and the
     * caller is expected to fall back to the ISO code as text. Returning a wrong-but-present
     * symbol would be far worse than showing "CHF": a euro sign next to a Swiss-franc amount is a
     * reporting error, not a cosmetic one.
     */
    public function currency(string $code): ?string
    {
        $code = strtoupper($code);

        return '' !== ($this->currencyIcons[$code] ?? '') ? $this->currencyIcons[$code] : null;
    }
}
