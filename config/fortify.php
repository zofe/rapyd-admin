<?php

use Laravel\Fortify\Features;

return [

    'guard'     => 'web',
    'passwords' => 'users',
    'username'  => 'email',
    'email'     => 'email',
    'home'      => '/',
    'prefix'    => '',
    'domain'    => null,
    'middleware' => ['web'],

    'limiters' => [
        'login'      => 'login',
        'two-factor' => 'two-factor',
    ],

    'views' => true,

    // Registration is closed unless RAPYD_REGISTRATION=true: an admin panel adds its users itself, a SaaS
    // that signs people up opens it (the Register links appear with the route).
    'features' => array_values(array_filter([
        env('RAPYD_REGISTRATION', false) ? Features::registration() : null,
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirmPassword' => false,
        ]),
    ])),

];
