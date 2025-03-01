<?php

namespace App\Modules\Layout\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;

class LayoutByConfig
{
    public function __construct(Router $router)
    {
        $this->router = $router;
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $action = $this->router->getCurrentRoute()->action;
        $controller = $action['controller'] ?? null;

        if ($controller && strpos($controller, 'App\\Modules\\') === 0) {
            $pattern = '/App\\\\Modules\\\\([A-Za-z]+)\\\\/';
            if (preg_match($pattern, $controller, $matches)) {
                $moduleName = strtolower($matches[1]);

                if (config($moduleName . '.layout')) {
                    config(['livewire.layout' => config($moduleName . '.layout')]);
                }
            }
        }
        return $next($request);

    }
}
