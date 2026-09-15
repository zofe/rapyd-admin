<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    | Populated at runtime by ModuleServiceProvider. Do not set manually.
    */
    'modules' => [],
    'menus'   => [],

    /*
    |--------------------------------------------------------------------------
    | Theme / Layout
    |--------------------------------------------------------------------------
    | theme: name of the active theme (a package or folder whose ServiceProvider
    |        extends Zofe\Rapyd\Themes\RapydThemeServiceProvider). Null = the
    |        bundled look. See docs/THEMES.md for the contract a theme fulfils.
    | layout: branding read by every theme.
    */
    'theme' => env('RAPYD_THEME'),

    // Per-visitor theme: a picker in the navbar and ?rapyd_theme=<name> (kept in the session)
    // switch between the bundled look and the registered themes. For demos and for trying themes side by side.
    'theme_switch' => env('RAPYD_THEME_SWITCH', false),
    // false: no palette icon in the navbars; the switch still works through links (?rapyd_theme=<name>).
    'theme_picker' => env('RAPYD_THEME_PICKER', true),

    'layout' => [
        'brand'        => env('RAPYD_BRAND'),          // null → app.name
        'logo_sidebar' => env('RAPYD_LOGO_SIDEBAR'),   // url of the sidebar logo, null → brand text
        'logo_login'   => env('RAPYD_LOGO_LOGIN'),     // url of the login logo, null → brand text
        'favicon'      => env('RAPYD_FAVICON', 'img/favicon.png'),
        'custom_css'   => env('RAPYD_CUSTOM_CSS'),     // extra stylesheet url loaded after the theme
        'auth_links'   => env('RAPYD_AUTH_LINKS', true), // Login / Register links in the frontend navbar (the routes stay)

        // Runtime palette: any CSS colour, applied without recompiling the theme
        // (see docs/THEMES.md, "Design tokens"). Null = the theme's own colour.
        'palette' => [
            'primary'      => env('RAPYD_PRIMARY'),        // buttons, links, active items, badges
            'sidebar_bg'   => env('RAPYD_SIDEBAR_BG'),
            'sidebar_text' => env('RAPYD_SIDEBAR_TEXT'),
            'topbar_bg'    => env('RAPYD_TOPBAR_BG'),
            'content_bg'   => env('RAPYD_CONTENT_BG'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    'users' => [
        'uuid' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Companies / Multi-tenancy
    |--------------------------------------------------------------------------
    | tiers: 1 = flat (all companies equal)
    |        2 = two levels: tier1 sees own + children
    |        3 = three levels: tier1 → tier2 → tier3
    |
    | tier_labels: UI labels only — code always uses tier1/tier2/tier3.
    | Configure via .env: RPD_TIERS=2, RPD_TIER1_LABEL=partner, RPD_TIER2_LABEL=customer
    */
    'companies' => [
        'enabled'     => true,
        'uuid'        => true,
        'tiers'       => env('RPD_TIERS', 1),
        'tier_labels' => [
            'tier1' => env('RPD_TIER1_LABEL', 'customer'),
            'tier2' => env('RPD_TIER2_LABEL', 'reseller'),
            'tier3' => env('RPD_TIER3_LABEL', 'partner'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth / Roles
    |--------------------------------------------------------------------------
    | super_admin_roles: bypass all global scopes (Authorize + Limit).
    | Roles and permissions are defined in config/permission.php.
    */
    'auth' => [
        'super_admin_roles' => ['admin'],
    ],

];
