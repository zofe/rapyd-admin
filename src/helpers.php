<?php

/**
 * from dot notation string to "property.anotherproperty.." to $instance->property->anotherproperty
 *
 * @param $instance
 * @param $str
 * @return mixed
 */

if (! function_exists('dot_to_property')) {
    function dot_to_property($instance, $str)
    {
        if ($instance == '') {
            return;
        }

        $params = explode('.', $str);

        try {
            foreach ($params as $param) {
                $instance = $instance->{$param};
            }
        } catch (Exception $e) {
        }

        return $instance;
    }
}

if (! function_exists('url_contains')) {
    function url_contains($needle, $strict = false)
    {
        $needle = trim($needle, ' \\/');
        $haystack = request()->path();
        $existance = strpos($haystack, $needle);
        if ($existance !== false) {
            if ($strict) {
                return $existance === 0 ? true : false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

if (! function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $length = strlen($needle);

        return $length > 0 ? substr($haystack, -$length) === $needle : true;
    }
}

if (! function_exists('namespace_module')) {
    function namespace_module($namespace, $module = null)
    {
        if ($module) {
            return str_replace("App\\", "App\\Modules\\".$module."\\", $namespace);
        }

        return $namespace;
    }
}

if (! function_exists('path_module')) {
    function path_module($path, $module = null)
    {
        if ($module) {
            $path = str_replace("app/", "app/Modules/".$module."/", $path);
            $path = str_replace("resources/views/livewire", "app/Modules/".$module."/Views", $path);
            $path = str_replace("routes/web.php", "app/Modules/".$module."/routes.php", $path);
        }

        return $path;
    }
}


/**
 * route helper to build links forcing presence of 'language prefix',
 *
 * ie:  route_lang('named_route', ['id'=>2]], true, 'it')
 * will return  http://localhost/{it}/myroute/id/2
 *
 * or: route_lang('named_route')
 * will return http://localhost/{es}/myroute if app()->getLocale() is 'es' and it's not the default language
 *
 * assuming default language is config('app.fallback_locale')
 *
 * @param $name
 * @param null $parameters
 * @param null $absolute
 * @param null $lang
 * @return string
 */
/**
 * Where the brand of the admin sidebar / navbar links to: config rapyd.layout.brand_route
 * (a route name or a URL, e.g. "demo" on a public demo site) or, by default, the admin home,
 * the home, the root. In the language of the page. Themes use it too.
 */
if (! function_exists('rapyd_brand_url')) {
    function rapyd_brand_url(): string
    {
        $target = config('rapyd.layout.brand_route');
        if ($target) {
            return \Illuminate\Support\Facades\Route::has($target) ? route_lang($target) : url($target);
        }
        foreach (['admin.home', 'home'] as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route_lang($name);
            }
        }

        return url('/');
    }
}

if (! function_exists('route_lang')) {
    function route_lang($name, $parameters = null, $absolute = true, $lang = null)
    {
        $current_lang = $lang ? $lang : app()->getLocale();
        $default_lang = app(\Zofe\Rapyd\Localization\Locales::class)->default();
        $current_lang_slug = ($current_lang === $default_lang) ? '' : $current_lang;

        $link = route($name, $parameters, $absolute);


        if ($absolute && ($current_lang !== $default_lang)) {
            $link = str_replace(url(''), url('') . "/" . $current_lang_slug, $link);
            $link = str_replace('/' . $current_lang_slug . '/' . $current_lang_slug, '/' . $current_lang_slug, $link);
        }

        return $link;
    }
}

/**
 * url helper to build links forcing presence of 'language prefix',
 *
 * @param $lang
 * @return string
 */
if (! function_exists('url_lang')) {
    function url_lang($lang, $change = false)
    {
        $locales = app(\Zofe\Rapyd\Localization\Locales::class);
        $segments = request()->segments();
        $firstSegment = request()->segment(1);

        // the first segment is a language: drop it
        if ($firstSegment && $locales->has($firstSegment)) {
            array_shift($segments);
        }
        // a language other than the default one is a first segment
        if ($lang !== $locales->default()) {
            array_unshift($segments, $lang);
        }

        $query = request()->query();
        unset($query[\Zofe\Rapyd\Localization\Locales::CHANGE_FLAG]);
        if ($change) {
            $query[\Zofe\Rapyd\Localization\Locales::CHANGE_FLAG] = 1;
        }

        return '/' . implode('/', $segments) . ($query ? '?' . http_build_query($query) : '');
    }
}

/**
 * @param $lang
 * @return string
 */
if (! function_exists('item_active')) {
    function item_active($active, $route = null, $params = [], $url = null)
    {
        if (strpos($active, '|')) {
            $actives = explode('|', $active);
        } else {
            $actives[] = $active;
        }

        foreach ($actives as $active) {

            if (is_string($active) && strlen($active) > 2 && ! in_array(strtolower($active), ['true','false'])) {
                $active = url_contains($active);
            }
            if ($route) {
                //$href = route($route, $params);
                $active = $active ?: request()->routeIs($route);
            } elseif ($url) {
                $href = url($url);
                $active = $active  ?: $href == url()->current();
            }

            if ($active) {
                break;
            }
        }

        return $active;
    }
}

if (! function_exists('item_href')) {
    function item_href($route = null, $params = [], $url = null)
    {

        $href = '#';
        if ($route) {
            $href = route_lang($route, $params);
        } elseif ($url) {
            $href = url($url);
        }

        return $href;
    }
}

if (! function_exists('log_activity')) {
    /**
     * Record a user/application event in the activity log.
     *
     * @param  mixed  $causer  null = current user, false = no causer (system)
     */
    function log_activity(string $logName, ?\Illuminate\Database\Eloquent\Model $subject = null, array $properties = [], ?string $description = null, $causer = null): ?\Spatie\Activitylog\Models\Activity
    {
        if (! function_exists('activity') || ! config('rapyd.log.activity.enabled', true)) {
            return null;
        }

        $activity = activity($logName);
        if ($causer !== false) {
            $activity->causedBy($causer ?? auth()->user());
        }
        if ($subject) {
            $activity->performedOn($subject);
        }
        if ($properties) {
            $activity->withProperties($properties);
        }

        return $activity->log($description ?? str_replace('_', ' ', $logName));
    }
}
