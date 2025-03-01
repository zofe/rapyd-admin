<?php

namespace App\Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ForceLangByUserPreference
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (Auth::user())
        {
            $lang_prefix = '';
            $locale = $request->segment(1);


            $lang = config('app.fallback_locale');
            if (in_array($locale, config('app.locales', ['en']))) {
                $lang = $locale;
            }

            $routeName = $request->route()->getName();
            $excludedRoutes = ['livewire.message-localized', 'livewire.message', 'weathermaps.ajax_weathermap_create_object', 'ajax_continuity_internal_ips_create', 'ajax_ztna_internal_ips_create'];

            if(!in_array($routeName, $excludedRoutes) && Auth::user()->locale && $lang != Auth::user()->locale && !$request->get('clang')) {
                return redirect()->to(url_lang(Auth::user()->locale));
            }
        }

        return $next($request);

    }
}
