<?php

namespace Zofe\Rapyd\Modules\Layout\Http\Middleware;

use Closure;
use Zofe\Rapyd\Themes\ThemeManager;

/**
 * Per-visitor theme (config rapyd.theme_switch): ?rapyd_theme=<name> stores the
 * choice in the session and every following request renders with that theme.
 * Nothing stored = the configured theme (RAPYD_THEME).
 */
class ThemeBySession
{
    public function __construct(protected ThemeManager $themes)
    {
    }

    public function handle($request, Closure $next)
    {
        if (! config('rapyd.theme_switch') || ! $request->hasSession()) {
            return $next($request);
        }

        $wanted = $request->query(ThemeManager::QUERY);
        if ($wanted !== null && $this->themes->has($wanted)) {
            $request->session()->put(ThemeManager::SESSION_KEY, $wanted);

            return redirect()->to($request->fullUrlWithoutQuery(ThemeManager::QUERY));
        }

        $chosen = $request->session()->get(ThemeManager::SESSION_KEY);
        if ($chosen && $this->themes->has($chosen)) {
            $this->themes->activate($chosen);
        }

        return $next($request);
    }
}
