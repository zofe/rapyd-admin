<?php

namespace Zofe\Rapyd\Localization;

use Illuminate\Http\Request;

/**
 * The languages of the application and how the current one is found.
 *
 * config('rapyd.locales') lists the enabled locales (RAPYD_LOCALES=en,it); the default
 * is config('app.locale') and has no URL prefix, every other locale is a first URL
 * segment: /it/companies. Module routes are registered with the prefix of the current
 * request (see routePrefix()), so route names are the same in every language and
 * route_lang() builds the URL for the current one.
 */
class Locales
{
    public const SESSION_KEY = 'rapyd.locale';

    /** Query flag the switcher adds: the choice is deliberate, remember it (session, user). */
    public const CHANGE_FLAG = 'clang';

    /** Native names for the switcher; anything else falls back to the code. */
    public const NAMES = [
        'en' => 'English', 'it' => 'Italiano', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch',
        'pt' => 'Português', 'pt_BR' => 'Português (Brasil)', 'nl' => 'Nederlands', 'pl' => 'Polski',
        'tr' => 'Türkçe', 'ru' => 'Русский', 'ar' => 'العربية', 'zh' => '中文', 'ja' => '日本語', 'id' => 'Bahasa Indonesia',
    ];

    /** @return list<string> the enabled locales, the default first */
    public function all(): array
    {
        $configured = config('rapyd.locales') ?: config('app.locales') ?: [];
        $locales = array_values(array_unique(array_merge([$this->default()], array_filter((array) $configured))));

        return $locales;
    }

    /** The default language: rapyd.locale (APP_LOCALE). Not app.locale, which setLocale() changes at runtime. */
    public function default(): string
    {
        return config('rapyd.locale') ?: config('app.fallback_locale', 'en');
    }

    public function has(string $locale): bool
    {
        return in_array($locale, $this->all(), true);
    }

    /** More than one language enabled: the switcher shows, the middleware redirects. */
    public function enabled(): bool
    {
        return count($this->all()) > 1;
    }

    public function name(string $locale): string
    {
        return self::NAMES[$locale] ?? $locale;
    }

    /** The URL prefix of a locale: none for the default one. */
    public function prefix(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === $this->default() ? '' : $locale;
    }

    /** The locale named by the first URL segment, if it is one of ours. */
    public function fromRequest(Request $request): ?string
    {
        $segment = $request->segment(1);

        return $segment && $this->has($segment) ? $segment : null;
    }

    /**
     * The prefix module routes are registered with for this request: called by the
     * service providers at boot, so the application locale is set before routing.
     */
    public function routePrefix(): string
    {
        $locale = $this->fromRequest(request());
        if ($locale) {
            app()->setLocale($locale);
        }

        return $locale ? $this->prefix($locale) : '';
    }

    /**
     * The best match of the browser's Accept-Language among the enabled locales (quality
     * order, regions such as pt-BR handled by Symfony); the default one when nothing
     * matches, null when the browser sends no preference.
     */
    public function fromBrowser(Request $request): ?string
    {
        if (! $request->headers->has('Accept-Language')) {
            return null;
        }

        return $request->getPreferredLanguage($this->all());
    }
}
