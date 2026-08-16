<?php

namespace Zofe\Rapyd\Modules\Auth;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class AuthModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Auth';

    public function register(): void
    {
        // Always merge the permission config so models are resolvable.
        $this->mergeConfigFrom(__DIR__ . '/../../../config/permission.php', 'permission');

        if ($this->isEjected()) {
            return;
        }

        // Load Fortify config from package defaults if not already published by the app.
        if (! (new Filesystem)->exists(config_path('fortify.php'))) {
            Config::set('fortify', include __DIR__ . '/fortify.php');
        }

        if (class_exists(\Laravel\Fortify\Fortify::class)) {
            \Laravel\Fortify\Fortify::ignoreRoutes();

            $this->app->instance(
                \Laravel\Fortify\Contracts\LogoutResponse::class,
                new class implements \Laravel\Fortify\Contracts\LogoutResponse {
                    public function toResponse($request)
                    {
                        return redirect('/');
                    }
                }
            );
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/permission.php' => config_path('permission.php'),
            ], 'rapyd-auth-config');
        }

        if ($this->isEjected()) {
            return;
        }

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));
        $this->loadViewsFrom($this->srcPath('Views'), 'auth');

        $this->bootFortify();
    }

    protected function bootFortify(): void
    {
        if (! class_exists(\Laravel\Fortify\Fortify::class)) {
            return;
        }

        $langPrefix = $this->detectLangPrefix();

        \Illuminate\Support\Facades\Route::group([
            'domain' => config('fortify.domain', null),
            'prefix' => $langPrefix,
        ], function () {
            $routesFile = $this->srcPath('routes_fortify.php');
            if (file_exists($routesFile)) {
                $this->loadRoutesFrom($routesFile);
            }
        });

        \Laravel\Fortify\Fortify::loginView(fn () => view('auth::auth.login'));
        \Laravel\Fortify\Fortify::registerView(fn () => view('auth::auth.register'));
        \Laravel\Fortify\Fortify::twoFactorChallengeView(fn () => view('auth::auth.two-factor-challenge'));
        \Laravel\Fortify\Fortify::requestPasswordResetLinkView(fn () => view('auth::auth.forgot-password'));
        \Laravel\Fortify\Fortify::resetPasswordView(fn () => view('auth::auth.reset-password'));
        \Laravel\Fortify\Fortify::verifyEmailView(fn () => view('auth::auth.verify-email'));

        \Illuminate\Support\Facades\RateLimiter::for('login', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->email . $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('two-factor', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        if (class_exists(\Zofe\Rapyd\Modules\Auth\Actions\Fortify\CreateNewUser::class)) {
            \Laravel\Fortify\Fortify::createUsersUsing(\Zofe\Rapyd\Modules\Auth\Actions\Fortify\CreateNewUser::class);
        }
        if (class_exists(\Zofe\Rapyd\Modules\Auth\Actions\Fortify\ResetUserPassword::class)) {
            \Laravel\Fortify\Fortify::resetUserPasswordsUsing(\Zofe\Rapyd\Modules\Auth\Actions\Fortify\ResetUserPassword::class);
        }
    }

    protected function detectLangPrefix(): string
    {
        $locale = request()->segment(1);
        if (config('app.locales') && in_array($locale, config('app.locales', []))) {
            return ($locale !== config('app.fallback_locale')) ? $locale : '';
        }
        return '';
    }
}
