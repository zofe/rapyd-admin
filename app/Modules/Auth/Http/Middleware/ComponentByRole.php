<?php

namespace App\Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class ComponentByRole
{
    /**
     * This middleware search for priority component instead of the one defaulted by the route
     *
     * By "priority component" we mean a controller that is prefixed with the "role" of the logged-in user
     * e.g., despite the defined route :
     * Route::get('/companies/view/{company:id}', CompaniesView::class)
     * For "Customer" role user the middleware looks for CustomerCompaniesView::class and dispenses that if it exists.
     *
     *
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (Auth::user() && Auth::user()->roles )
        {
            $role = null;
            $prefix = null;

            $route = $request->route();
            $action = $route->getAction();

            foreach (Auth::user()->roles->pluck('name')->toArray() as $r) {
                if (Auth::user()->hasRole('admin')) {
                    break;
                }

                $class_names = config('auth.role_to_component_class.'.$r);
                if($class_names) {
                    if(isset($class_names[$route->getName()])) {
                        if(isset($action['controller']) && isset($action['uses'])) {
                            $uses = substr($action['uses'], strpos($action['uses'],'@'));
                            $controller_role = $class_names[$route->getName()];
                            if(class_exists($controller_role)) {

                                $routeAction = array_merge($route->getAction(), [
                                    'uses' => '\\' . $controller_role . $uses,
                                    'controller' => '\\' . $controller_role,
                                ]);

                                $route->setAction($routeAction);
                                $route->controller = false;
                            }
                            return $next($request);
                        }
                    }
                }

                $prefix = config('auth.role_to_component_prefix.'.$r);
                $role = $r;
                if($prefix) {
                    break;
                }
            }

            if(isset($action['controller']) && isset($action['uses'])) {
                $controller = $action['controller'];
                $uses = substr($action['uses'], strpos($action['uses'],'@'));
                $controller_name = class_basename($controller);
                $controller_role_name = Str::studly($prefix.'_'.$controller_name);
                $controller_role = str_replace($controller_name, $controller_role_name, $controller);

                if(class_exists($controller_role)) {

                    $routeAction = array_merge($route->getAction(), [
                        'uses'       => '\\'.$controller_role.$uses,
                        'controller' => '\\'.$controller_role,
                    ]);

                    $route->setAction($routeAction);
                    $route->controller = false;
                } else {

                    $role_fallback = config('auth.role_to_component_fallback.'.$role);
                    $prefix = config('auth.role_to_component_prefix.'.$role_fallback);
                    if($role_fallback && $prefix) {
                        $controller_role_name = Str::studly($prefix.'_'.$controller_name);
                        $controller_role = str_replace($controller_name, $controller_role_name, $controller);
                        if(class_exists($controller_role)) {
                            $routeAction = array_merge($route->getAction(), [
                                'uses'       => '\\'.$controller_role.$uses,
                                'controller' => '\\'.$controller_role,
                            ]);

                            $route->setAction($routeAction);
                            $route->controller = false;
                        }
                    }
                }

            }

        }

        return $next($request);
    }
}
