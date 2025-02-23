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
if (! function_exists('route_lang')) {
    function route_lang($name, $parameters = null, $absolute = true, $lang = null)
    {
        $current_lang = $lang ? $lang : app()->getLocale();
        $default_lang = config('app.fallback_locale');
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
        $default = config('app.fallback_locale');
        $segments = request()->segments();
        $firtsegment = request()->segment(1);

        //first segment already contain $lang
        if ($firtsegment === $lang) {
            return url()->full();
        }
        //first segment is different lang, so remove it
        if (in_array($firtsegment, config('app.locales'))) {
            array_shift($segments);
        }
        //requested $lang is not the default one, prepend $lang segment
        if ($lang !== $default) {
            array_unshift($segments, $lang);
        }

        return "/" . implode('/', $segments).($change?'?clang=1':'');
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
