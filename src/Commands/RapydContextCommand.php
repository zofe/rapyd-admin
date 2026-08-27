<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RapydContextCommand extends Command
{
    protected $signature = 'rpd:context
        {--format=json  : Output format: json (default) or text}
        {--no-routes    : Omit route listing}
        {--no-models    : Omit model listing}';

    protected $description = 'Dump structured project context for AI agents (Claude Code, etc.)';

    public function handle(): int
    {
        $context = [
            'framework' => $this->getFrameworkInfo(),
            'config' => $this->getConfigSnapshot(),
            'modules' => $this->getModulesInfo(),
            'routes' => $this->option('no-routes') ? null : $this->getRoutes(),
            'models' => $this->option('no-models') ? null : $this->getModels(),
            'extension' => $this->getExtensionGuide(),
        ];

        if ($this->option('format') === 'text') {
            $this->renderText($context);
        } else {
            $this->line(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }

    private function getFrameworkInfo(): array
    {
        $composer = json_decode(File::get(base_path('composer.json')), true) ?? [];
        $allRequire = array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []);

        return [
            'name' => 'rapyd-admin',
            'version' => $allRequire['zofe/rapyd-admin'] ?? 'path',
            'zofe_packages' => array_keys(array_filter($allRequire, fn ($k) => Str::startsWith($k, 'zofe/'), ARRAY_FILTER_USE_KEY)),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
        ];
    }

    private function getConfigSnapshot(): array
    {
        return [
            'companies' => config('rapyd.companies'),
            'auth' => config('rapyd.auth'),
            'users' => config('rapyd.users'),
        ];
    }

    private function getModulesInfo(): array
    {
        $app = [];
        $modulesPath = app_path('Modules');

        if (File::isDirectory($modulesPath)) {
            foreach (File::directories($modulesPath) as $dir) {
                $name = basename($dir);
                $app[$name] = $this->describeModule($dir);
            }
        }

        return [
            'bundled' => ['Layout', 'Auth', 'Companies'],
            'app' => $app,
        ];
    }

    private function describeModule(string $dir): array
    {
        $components = [];
        $lwDir = $dir . '/Livewire';
        if (File::isDirectory($lwDir)) {
            $components = collect(File::files($lwDir))
                ->filter(fn ($f) => $f->getExtension() === 'php' && ! Str::contains($f->getFilename(), '.blade.'))
                ->map(fn ($f) => $f->getBasename('.php'))
                ->values()->all();
        }

        $models = [];
        $modelsDir = $dir . '/Models';
        if (File::isDirectory($modelsDir)) {
            $models = collect(File::files($modelsDir))
                ->map(fn ($f) => $f->getBasename('.php'))
                ->values()->all();
        }

        return [
            'components' => $components,
            'models' => $models,
            'has_routes' => File::exists($dir . '/routes.php'),
            'has_config' => File::exists($dir . '/config.php'),
        ];
    }

    private function getRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($r) => in_array('web', $r->gatherMiddleware()) && $r->getName())
            ->filter(fn ($r) => ! Str::startsWith($r->getName(), ['debugbar', 'ignition', 'livewire', 'sanctum', 'telescope']))
            ->map(fn ($r) => [
                'name' => $r->getName(),
                'uri' => '/' . ltrim($r->uri(), '/'),
                'methods' => array_diff($r->methods(), ['HEAD']),
            ])
            ->values()->all();
    }

    private function getModels(): array
    {
        $models = [];
        $path = app_path('Models');

        if (! File::isDirectory($path)) {
            return $models;
        }

        foreach (File::files($path) as $file) {
            $class = 'App\\Models\\' . $file->getBasename('.php');
            if (! class_exists($class)) {
                continue;
            }

            try {
                $instance = new $class;
                $models[] = [
                    'class' => $class,
                    'table' => $instance->getTable(),
                    'fillable' => $instance->getFillable(),
                ];
            } catch (\Throwable) {
                // skip uninstantiable models (e.g. abstract)
            }
        }

        return $models;
    }

    private function getExtensionGuide(): array
    {
        return [
            'generate_module' => 'php artisan rpd:make ModelName --module=ModuleName',
            'generate_component' => 'php artisan rpd:make ModelName Model',
            'module_structure' => 'app/Modules/{Name}/{Livewire/,Views/,Models/,routes.php,config.php}',
            'blade_components' => 'x-rpd::{table,edit,view,input,select,select-list,date,datetime,checkbox,radiogroup,rich-text,upload,sort,button,nav-link,nav-dropdown}',
            'livewire_pattern' => 'extends Component; use WithDataTable; render() returns view()->layout("module::admin")',
            'field_binding' => 'x-rpd:: use model= prop (wire:model.live.debounce.150ms by default; :lazy="true" for blur)',
            'authorization' => 'use Authorize trait; call $this->authorize("role") in booted()',
            'company_scoping' => 'add HasCompanyScope global scope on models when config rapyd.companies.tiers > 1',
            'add_menu_item' => 'set menu_admin and menu_admin_position keys in module config.php',
            'register_module' => 'add ServiceProvider to config/app.php providers array',
            'livewire_namespace' => 'call Livewire::addNamespace("ns", __DIR__ . "/Livewire") in ServiceProvider::boot()',
        ];
    }

    private function renderText(array $context): void
    {
        $fw = $context['framework'];

        $this->info('=== Rapyd Admin Project Context ===');
        $this->newLine();
        $this->line("rapyd-admin {$fw['version']} / Laravel {$fw['laravel']} / PHP {$fw['php']}");
        $this->line('Zofe packages: ' . implode(', ', $fw['zofe_packages']));

        $this->newLine();
        $this->info('Config:');
        $this->line('  Companies: tiers=' . ($context['config']['companies']['tiers'] ?? 1)
            . ', uuid=' . ($context['config']['companies']['uuid'] ? 'yes' : 'no'));
        $this->line('  Users uuid: ' . ($context['config']['users']['uuid'] ? 'yes' : 'no'));

        $this->newLine();
        $this->info('Modules:');
        $this->line('  Bundled: ' . implode(', ', $context['modules']['bundled']));
        foreach ($context['modules']['app'] as $name => $mod) {
            $components = implode(', ', $mod['components']) ?: '—';
            $this->line("  {$name}: [{$components}]");
        }

        if (is_array($context['routes'])) {
            $this->newLine();
            $this->info('Routes (' . count($context['routes']) . '):');
            foreach ($context['routes'] as $r) {
                $methods = implode('|', $r['methods']);
                $this->line("  [{$methods}] {$r['name']} → {$r['uri']}");
            }
        }

        if (is_array($context['models'])) {
            $this->newLine();
            $this->info('Models:');
            foreach ($context['models'] as $m) {
                $fillable = implode(', ', array_slice($m['fillable'], 0, 8));
                $this->line("  {$m['class']} (table: {$m['table']}) — {$fillable}");
            }
        }

        $this->newLine();
        $this->info('Extension patterns:');
        foreach ($context['extension'] as $key => $val) {
            $this->line("  {$key}: {$val}");
        }
    }
}
