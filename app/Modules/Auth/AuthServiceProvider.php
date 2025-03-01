<?php

namespace App\Modules\Auth;


use App\Modules\Auth\Actions\Fortify\CreateNewUser;
use App\Modules\Auth\Actions\Fortify\ResetUserPassword;
use App\Modules\Auth\Console\Commands\AuthCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;


class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/permission.php' => config_path('permission.php'),
                __DIR__ . '/fortify.php' => config_path('fortify.php'),
            ], 'config');

        }

        $lang_prefix = '';
        $locale = request()->segment(1);

        if (config('app.locales') && in_array($locale, config('app.locales'))) {
            $lang_prefix = ($locale !== config('app.fallback_locale')) ? $locale : '';

            if ($lang_prefix) {
                session(['lang_prefix' => $lang_prefix]);
            }
            app()->setlocale($locale);
        }



        Route::group([
            'namespace' => 'Laravel\Fortify\Http\Controllers',
            'domain' => config('fortify.domain', null),
            'prefix' => $lang_prefix, //config('fortify.prefix'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes_fortify.php');//'base_path('routes/fortify.php')
        }); // this closure

        Fortify::loginView(function () {
            return view('auth::auth.login');
        });
        Fortify::twoFactorChallengeView(function () {
            return view('auth::auth.two-factor-challenge');
        });

        Fortify::registerView(function () {
            return view('auth::auth.register');
        });
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth::auth.forgot-password');
        });
        Fortify::resetPasswordView(function () {
            return view('auth::auth.reset-password');
        });
        Fortify::verifyEmailView(function () {
            return view('auth::auth.verify-email');
        });
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        //Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        //Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        $this->commands([
            AuthCommand::class,
        ]);



    }

    public function register()
    {
        $filesystem = new Filesystem;

        $this->mergeConfigFrom(__DIR__ . '/permission.php', 'permission');

        if (!$filesystem->exists(config_path('fortify.php'))) {
            Config::set('fortify', include __DIR__ . '/fortify.php');
        }

        Fortify::ignoreRoutes();

        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request)
            {
//                if (strpos($request->headers->get('referer'), 'some/bypassed/url')) {
//                    return redirect($request->headers->get('referer'));
//                }

                return redirect('/');
            }
        });


    }

//    public function configAuthorizeModules()
//    {
//
//        $moduleBasePath = $modulePath = app_path(). '/Modules/';
//
//        config(['auth.auth_checks' => []]);
//        if (File::exists($moduleBasePath)) {
//            $dirs = File::directories($moduleBasePath);
//
//            foreach ($dirs as $moduleP) {
//                config(['auth.auth_checks.' => $moduleP]);
//            }
//        }
//
//    }
}
