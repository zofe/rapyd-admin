<?php

namespace App\Modules\Layout;




use App\Modules\Layout\Console\Commands\LayoutCommand;
use App\Modules\Layout\Http\Middleware\LayoutByConfig;
use Illuminate\Support\ServiceProvider;



class LayoutServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            LayoutCommand::class,
        ]);

        $this->publishes([
            __DIR__.'/public' => public_path('vendor/layout'),
        ], 'laravel-assets');

        // Registra il middleware solo per il gruppo 'web'
        $this->app['router']->pushMiddlewareToGroup('web', LayoutByConfig::class);

    }

    public function register()
    {
        // Code for registering bindings or services
    }



}
