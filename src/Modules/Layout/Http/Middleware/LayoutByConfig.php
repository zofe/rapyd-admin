<?php

namespace Zofe\Rapyd\Modules\Layout\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;

class LayoutByConfig
{
    public function __construct(protected Router $router)
    {
    }

    public function handle($request, Closure $next)
    {
        $action = $this->router->getCurrentRoute()->action;
        $controller = $action['controller'] ?? null;

        if ($controller && str_contains($controller, 'App\\Modules\\')) {
            if (preg_match('/App\\\\Modules\\\\([A-Za-z]+)\\\\/', $controller, $matches)) {
                $moduleName = strtolower($matches[1]);
                if (config($moduleName . '.layout')) {
                    config(['livewire.layout' => config($moduleName . '.layout')]);
                }
            }
        }

        return $next($request);
    }
}
