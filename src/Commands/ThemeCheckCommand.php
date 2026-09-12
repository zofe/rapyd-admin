<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Verifies that a theme fulfils the layout contract (docs/THEMES.md): the four
 * layout views exist and the named regions, stacks and directives are there.
 * Meant to be run by a developer — or an AI — after integrating a template.
 */
class ThemeCheckCommand extends Command
{
    protected $signature = 'rpd:theme:check
        {path? : Theme views folder (default: the active theme, or the bundled layout)}';

    protected $description = 'Check a theme against the rapyd-admin layout contract';

    /** Tokens that must appear somewhere in the theme views, grouped by layout (an array = any of). */
    public const CONTRACT = [
        'app' => [
            "@yield('title'", '@rapydStyles', '@livewireStyles', "@stack('head_scripts')",
            ["@yield('main')", "@section('main')"], '$slot', '@aiWidget', '@livewireScripts', '@rapydScripts', "@stack('footer_scripts')",
        ],
        'admin' => [
            "rapyd.menus.admin", "@includeIf('menu')", "@yield('role_menu')",
            'search::search-navbar', "@yield('user_info_dropdown')", 'x-rpd::breadcrumbs',
            "@yield('main-content')", "@yield('doc')",
            "@stack('sidebar_footer')", "@stack('navbar_right')", "@stack('page_header')", "@stack('footer')",
            'rapyd.layout.logo_sidebar', "route('logout')",
        ],
        'frontend' => [
            'rapyd.menus.frontend', "'left_navbar'", "@stack('right_navbar')", "@yield('main-content')",
        ],
        'auth' => [
            "@yield('title'", '@rapydStyles', "@yield('main-content')", '@rapydScripts',
        ],
    ];

    public function handle(Filesystem $files): int
    {
        $path = $this->resolvePath();
        $this->info("Theme views: {$path}");

        if (! $files->isDirectory($path)) {
            $this->error('Folder not found.');

            return self::FAILURE;
        }

        $ok = true;
        $all = collect($files->allFiles($path))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
            ->map(fn ($f) => $files->get($f->getPathname()))
            ->implode("\n");

        foreach (self::CONTRACT as $view => $tokens) {
            $file = "{$path}/{$view}.blade.php";
            if (! $files->exists($file)) {
                $this->line("  <fg=red>MISSING</> {$view}.blade.php");
                $ok = false;

                continue;
            }
            $this->line("  <fg=green>OK</>      {$view}.blade.php");

            foreach ($tokens as $token) {
                $alternatives = (array) $token;
                if (! collect($alternatives)->contains(fn ($t) => str_contains($all, $t))) {
                    $this->line('          <fg=red>missing</> ' . implode(' or ', $alternatives));
                    $ok = false;
                }
            }
        }

        $this->newLine();
        $ok ? $this->info('The theme fulfils the layout contract.')
            : $this->error('The theme does not fulfil the layout contract.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    protected function resolvePath(): string
    {
        if ($this->argument('path')) {
            return rtrim($this->argument('path'), '/');
        }

        $active = config('rapyd.theme');
        if ($active && ($root = config("rapyd.themes.{$active}"))) {
            return $root . '/resources/views';
        }

        return dirname(__DIR__) . '/Modules/Layout/Views';
    }
}
