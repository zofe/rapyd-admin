<?php

namespace Zofe\Rapyd\Localization;

use Closure;
use Illuminate\Http\Request;

/**
 * The language of the request, in this order: the URL prefix (/it/...), then for
 * requests without one (the default-language URLs, the Livewire update endpoint)
 * the choice remembered in the session, the preference of the logged-in user, the
 * browser's Accept-Language. A page asked in the default language by a visitor
 * whose choice is another one is redirected to that one; the switcher marks a
 * deliberate change with ?clang=1, which is remembered (session and, if logged
 * in, users.locale). One language enabled: nothing happens.
 */
class SetLocale
{
    public function __construct(protected Locales $locales)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->locales->enabled()) {
            return $next($request);
        }

        $session = $request->hasSession() ? $request->session() : null;
        $user = $request->user();
        $inUrl = $this->locales->fromRequest($request);
        $change = $request->has(Locales::CHANGE_FLAG);

        if ($inUrl || $change) {
            $locale = $inUrl ?: $this->locales->default();
            app()->setLocale($locale);
            $session?->put(Locales::SESSION_KEY, $locale);
            if ($change && $user && ($user->locale ?? null) !== $locale) {
                $this->remember($user, $locale);
            }

            return $next($request);
        }

        // no prefix: the default language, unless something says otherwise
        $preferred = $session?->get(Locales::SESSION_KEY)
            ?: ($user->locale ?? null)
            ?: ($this->livewire($request) ? null : $this->locales->fromBrowser($request));

        if ($preferred && $this->locales->has($preferred) && $preferred !== $this->locales->default()) {
            if ($request->isMethod('GET') && ! $this->livewire($request) && ! $request->expectsJson()) {
                return redirect()->to(url_lang($preferred));
            }
            app()->setLocale($preferred);   // Livewire and non-GET requests: same language as the page
        }

        return $next($request);
    }

    /** The Livewire update endpoint has no locale segment: it takes the one of the page. */
    protected function livewire(Request $request): bool
    {
        return $request->is('livewire/*') || $request->routeIs('livewire.*');
    }

    protected function remember($user, string $locale): void
    {
        if (method_exists($user, 'forceFill') && \Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'locale')) {
            $user->forceFill(['locale' => $locale])->save();
        }
    }
}
