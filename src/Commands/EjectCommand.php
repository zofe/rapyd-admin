<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EjectCommand extends Command
{
    protected $signature = 'rpd:eject {module : Module name (Auth, Companies, ...)} {--force : Overwrite existing files}';
    protected $description = 'Eject a bundled rapyd module to app/Modules/ for customization';

    public function handle(Filesystem $files): int
    {
        $module = Str::studly($this->argument('module'));
        $srcDir = __DIR__ . "/../../src/Modules/{$module}";
        $destDir = app_path("Modules/{$module}");

        if (! $files->isDirectory($srcDir)) {
            $this->error("Bundled module [{$module}] not found at: {$srcDir}");

            return self::FAILURE;
        }

        if ($files->isDirectory($destDir) && ! $this->option('force')) {
            $this->error("Module [{$module}] already exists at: {$destDir}");
            $this->line('Use --force to overwrite.');

            return self::FAILURE;
        }

        $this->info("Ejecting module [{$module}] to: {$destDir}");

        $files->copyDirectory($srcDir, $destDir);

        $this->rewriteNamespaces($files, $destDir, $module);

        $this->info("Done. The module is now in app/Modules/{$module}/");
        $this->line("Views, routes and migrations are loaded automatically by Rapyd.");
        $this->newLine();
        $this->line("You can now edit the files in app/Modules/{$module}/ freely.");

        return self::SUCCESS;
    }

    protected function rewriteNamespaces(Filesystem $files, string $dir, string $module): void
    {
        $packageNamespace = "Zofe\\Rapyd\\Modules\\{$module}";
        $appNamespace = "App\\Modules\\{$module}";

        foreach ($files->allFiles($dir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getRealPath());

            $updated = str_replace($packageNamespace, $appNamespace, $content);

            // Remove the isEjected() guard so the provider works if registered directly.
            $updated = preg_replace(
                '/\s*if\s*\(\s*\$this->isEjected\(\)\s*\)\s*\{\s*return;\s*\}/s',
                '',
                $updated
            );

            if ($updated !== $content) {
                file_put_contents($file->getRealPath(), $updated);
            }
        }
    }
}
