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
        $this->line("The package will automatically defer to your local copy.");
        $this->newLine();
        $this->line("Next steps:");
        $this->line("  1. Run: composer dump-autoload");
        $this->line("  2. Add the ejected module's namespace to composer.json autoload if not using psr-4 wildcard.");

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

            if ($updated !== $content) {
                file_put_contents($file->getRealPath(), $updated);
            }
        }
    }
}
