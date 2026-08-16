<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'rpd:install
        {--uuid-users : Publish the migration that converts users.id to UUID}
        {--no-user-model : Skip trait injection into app/Models/User.php}
        {--companies : Also add HasCompanies trait to the User model}
        {--force : Overwrite already published config files}';

    protected $description = 'Install rapyd-admin v2 — injects traits into User model and publishes configs';

    public function handle(Filesystem $files): int
    {
        $this->info('Installing rapyd-admin...');
        $this->newLine();

        $this->publishConfigs($files);
        $this->publishPermissionMigrations();

        if (! $this->option('no-user-model')) {
            $this->injectUserTraits($files);
        }

        if ($this->option('uuid-users')) {
            $this->publishUuidMigration($files);
        }

        $this->printNextSteps();

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    protected function publishPermissionMigrations(): void
    {
        $this->callSilently('vendor:publish', [
            '--provider' => 'Spatie\Permission\PermissionServiceProvider',
            '--tag'      => 'permission-migrations',
        ]);
        $this->line("  <fg=green>DONE</> Published spatie/laravel-permission migrations");
        $this->newLine();
    }

    protected function publishConfigs(Filesystem $files): void
    {
        $configs = [
            __DIR__ . '/../../config/permission.php' => config_path('permission.php'),
            __DIR__ . '/../../config/fortify.php'    => config_path('fortify.php'),
            __DIR__ . '/../../config/rapyd.php'      => config_path('rapyd.php'),
        ];

        foreach ($configs as $src => $dest) {
            $name = basename($dest);
            if ($files->exists($dest) && ! $this->option('force')) {
                $this->line("  <fg=yellow>SKIP</> config/{$name} (already exists — use --force to overwrite)");
                continue;
            }
            $files->copy($src, $dest);
            $this->line("  <fg=green>DONE</> Published config/{$name}");
        }

        $this->newLine();
    }

    protected function injectUserTraits(Filesystem $files): void
    {
        $userPath = app_path('Models/User.php');

        if (! $files->exists($userPath)) {
            $this->warn("  app/Models/User.php not found — skipping trait injection.");
            $this->line("  Add the following traits manually to your User model:");
            $this->printTraitInstructions();
            return;
        }

        $content = $files->get($userPath);

        // ---- Imports --------------------------------------------------------
        $imports = [
            'use Illuminate\Database\Eloquent\Concerns\HasUuids;',
            'use Zofe\Rapyd\Modules\Auth\Traits\HasRoles;',
            'use Zofe\Rapyd\Modules\Auth\Traits\Authorize;',
            'use Zofe\Rapyd\Modules\Auth\Traits\Limit;',
        ];

        if ($this->option('companies')) {
            $imports[] = 'use Zofe\Rapyd\Modules\Companies\Traits\HasCompanies;';
        }

        $content = $this->injectImports($content, $imports);

        // ---- Trait uses inside class body -----------------------------------
        $traits = ['HasUuids', 'HasRoles', 'Authorize', 'Limit'];

        if ($this->option('companies')) {
            $traits[] = 'HasCompanies';
        }

        $content = $this->injectTraitUses($content, $traits);

        $files->put($userPath, $content);
        $this->line("  <fg=green>DONE</> Injected traits into app/Models/User.php");
        $this->newLine();
    }

    protected function publishUuidMigration(Filesystem $files): void
    {
        $stub = __DIR__ . '/../Modules/Auth/stubs/convert_users_to_uuid.php.stub';
        $dest = database_path('migrations/2000_01_01_000000_convert_users_to_uuid.php');

        if ($files->exists($dest) && ! $this->option('force')) {
            $this->line("  <fg=yellow>SKIP</> UUID migration already exists.");
            return;
        }

        $files->copy($stub, $dest);
        $this->line("  <fg=green>DONE</> Published database/migrations/2000_01_01_000000_convert_users_to_uuid.php");
        $this->warn("  !! Run this migration on a FRESH database only. See file comments.");
        $this->newLine();
    }

    // -------------------------------------------------------------------------

    protected function injectImports(string $content, array $imports): string
    {
        foreach ($imports as $import) {
            if (Str::contains($content, $import)) {
                continue;
            }

            // Insert after the last top-level "use X;" line
            if (preg_match_all('/^use [A-Za-z\\\\{][^;]+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $last  = end($matches[0]);
                $pos   = $last[1] + strlen($last[0]);
                $content = substr_replace($content, "\n" . $import, $pos, 0);
            }
        }

        return $content;
    }

    protected function injectTraitUses(string $content, array $traits): string
    {
        // Find the class opening brace and the first "use X, Y;" inside it
        $classBodyStart = strpos($content, '{', strpos($content, 'class '));

        if ($classBodyStart === false) {
            return $content;
        }

        $classBody = substr($content, $classBodyStart);

        if (preg_match('/(\n\s*use\s+)((?:[A-Za-z_\\\\]+,?\s*)+);/', $classBody, $match, PREG_OFFSET_CAPTURE)) {
            // There's an existing "use X, Y;" block — append missing traits
            $existingLine  = $match[0][0];
            $existingTraits = preg_split('/[\s,]+/', trim($match[2][0]));
            $existingTraits = array_filter($existingTraits);

            $toAdd = array_filter($traits, fn ($t) => ! in_array($t, $existingTraits));

            if (empty($toAdd)) {
                return $content;
            }

            $newLine = rtrim($existingLine, ';') . ', ' . implode(', ', $toAdd) . ';';
            $pos     = $classBodyStart + $match[0][1];
            $content = substr_replace($content, $newLine, $pos, strlen($existingLine));
        } else {
            // No existing trait use — insert one right after the class opening brace
            $insertPos = $classBodyStart + 1;
            $indent    = '    ';
            $newUse    = "\n{$indent}use " . implode(', ', $traits) . ";\n";
            $content   = substr_replace($content, $newUse, $insertPos, 0);
        }

        return $content;
    }

    protected function printTraitInstructions(): void
    {
        $this->line('');
        $this->line('  <fg=cyan>Imports to add at the top of User.php:</>');
        $this->line('    use Illuminate\Database\Eloquent\Concerns\HasUuids;');
        $this->line('    use Zofe\Rapyd\Modules\Auth\Traits\HasRoles;');
        $this->line('    use Zofe\Rapyd\Modules\Auth\Traits\Authorize;');
        $this->line('    use Zofe\Rapyd\Modules\Auth\Traits\Limit;');
        $this->line('');
        $this->line('  <fg=cyan>Trait uses inside the class body:</>');
        $this->line('    use HasUuids, HasRoles, Authorize, Limit;');
        $this->line('');
    }

    protected function printNextSteps(): void
    {
        $this->line('<fg=cyan>Next steps:</>');
        $this->line('  1. Run: <fg=white>php artisan migrate</>');
        $this->line('  2. Run: <fg=white>php artisan vendor:publish --tag=laravel-assets</>');
        $this->line('  3. Seed roles/permissions: <fg=white>php artisan db:seed --class=RoleSeeder</>  (coming soon)');
        $this->line('');
        $this->line('  To add Companies module:');
        $this->line('    <fg=white>php artisan rpd:install --companies</>');
        $this->line('');
        $this->line('  To convert users.id to UUID (fresh install only!):');
        $this->line('    <fg=white>php artisan rpd:install --uuid-users</>');
        $this->line('');
    }
}
