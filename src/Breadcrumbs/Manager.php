<?php

namespace Zofe\Rapyd\Breadcrumbs;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Traits\Macroable;

class Manager
{
    use Macroable;

    /**
     * The breadcrumb generator.
     *
     * @var Trail
     */
    public $generator;

    /**
     * Create the instance of the manager.
     *
     * @param Trail $generator
     *
     * @return void
     */
    public function __construct(Trail $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Register a breadcrumb definition by passing it off to the registrar.
     *
     * @param string   $route
     * @param \Closure $definition
     *
     * @return void
     * @throws \Throwable
     *
     */
    public function for(string $route, Closure $definition)
    {
        $this->generator->register($route, $definition);
    }

    /**
     * @param null $parameters
     *
     * @return Collection
     * @throws \Throwable
     */
    public function current($parameters = null): Collection
    {
        $name = optional(Route::current())->getName();

        if ($name === null) {
            return collect();
        }

        return $this->generate($name, $parameters);
    }

    /**
     * @param string $route
     * @param mixed|null $parameters
     *
     * @return Collection
     * @throws \Throwable
     */
    public function generate(string $route, $parameters = null): Collection
    {
        $parameters = Arr::wrap($parameters);

        return $this->generator->generate($route, $parameters);
    }

    /**
     * @param string|null $name
     *
     * @return bool
     */
    public function has(string $name = null): bool
    {
        $name = $name ?? Route::currentRouteName();

        if ($name === null) {
            return false;
        }

        return $this->generator->has($name);
    }
}
