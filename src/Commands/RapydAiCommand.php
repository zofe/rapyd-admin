<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/**
 * Gives the AI agents of this application the Rapyd Admin guideline and skills, without Laravel Boost:
 * the skills are copied to .claude/skills, the guideline is written into AGENTS.md between two markers
 * (so it can be refreshed) and CLAUDE.md imports AGENTS.md. With Boost installed, boost:install /
 * boost:update do the same from resources/boost of the package: run this only when you do not use Boost.
 */
class RapydAiCommand extends Command
{
    protected $signature = 'rpd:ai
        {--force : Overwrite skills the application has modified}
        {--path= : The application root (default: base_path())}';

    protected $description = 'Install the Rapyd Admin AI guideline and skills in this application (for agents without Laravel Boost)';

    public const START = '<!-- rapyd-admin:guideline:start -->';

    public const END = '<!-- rapyd-admin:guideline:end -->';

    public function handle(): int
    {
        $root = rtrim($this->option('path') ?: base_path(), '/');
        $source = dirname(__DIR__, 2) . '/resources/boost';

        $this->installSkills("{$source}/skills", "{$root}/.claude/skills");
        $this->installGuideline("{$source}/guidelines/core.blade.php", "{$root}/AGENTS.md");
        $this->importInClaudeMd("{$root}/CLAUDE.md");

        $this->newLine();
        $this->info('Agents of this application now know Rapyd Admin. With Laravel Boost, use boost:install / boost:update --discover instead.');

        return self::SUCCESS;
    }

    /** Each skill folder is copied; a skill the application changed is kept unless --force. */
    protected function installSkills(string $from, string $to): void
    {
        foreach (File::directories($from) as $dir) {
            $name = basename($dir);
            $target = "{$to}/{$name}";
            if (File::isDirectory($target) && ! $this->option('force') && $this->differs($dir, $target)) {
                $this->warn("skill {$name}: modified in the application, kept (use --force to overwrite)");
                continue;
            }
            File::ensureDirectoryExists($to);
            File::deleteDirectory($target);
            File::copyDirectory($dir, $target);
            $this->stamp($target);
            $this->line("skill {$name} → .claude/skills/{$name}");
        }
    }

    /** True when the application's copy is neither missing nor identical to the package's skill. */
    protected function differs(string $source, string $target): bool
    {
        foreach (File::allFiles($source) as $file) {
            $copy = $target . '/' . $file->getRelativePathname();
            if (! File::exists($copy) || File::get($copy) !== File::get($file->getPathname())) {
                // a missing or changed file: was it changed by the application, or is the package newer?
                // we cannot tell; treat as modified so nothing is lost without --force
                return $this->applicationTouched($target);
            }
        }

        return false;
    }

    /** Records the hashes of the installed files, to tell later whether the application changed them. */
    protected function stamp(string $target): void
    {
        $hashes = [];
        foreach (File::allFiles($target) as $file) {
            if ($file->getFilename() !== '.rapyd-installed') {
                $hashes[$file->getRelativePathname()] = md5_file($file->getPathname());
            }
        }
        File::put("{$target}/.rapyd-installed", json_encode($hashes, JSON_PRETTY_PRINT));
    }

    /** A marker file records what was installed: if the current files match it, the app did not touch them. */
    protected function applicationTouched(string $target): bool
    {
        $stamp = "{$target}/.rapyd-installed";
        if (! File::exists($stamp)) {
            return true;
        }
        foreach (json_decode(File::get($stamp), true) ?: [] as $file => $hash) {
            if (! File::exists("{$target}/{$file}") || md5_file("{$target}/{$file}") !== $hash) {
                return true;
            }
        }

        return false;
    }

    /** The guideline, rendered (it is a Blade file), between the markers of AGENTS.md. */
    protected function installGuideline(string $blade, string $agentsMd): void
    {
        $rendered = trim(Blade::render(File::get($blade)));
        $block = self::START . "\n" . $rendered . "\n" . self::END;

        $content = File::exists($agentsMd) ? File::get($agentsMd) : '';
        if (str_contains($content, self::START) && str_contains($content, self::END)) {
            $content = preg_replace('/' . preg_quote(self::START, '/') . '.*?' . preg_quote(self::END, '/') . '/s', $block, $content);
            $this->line('guideline refreshed in AGENTS.md');
        } else {
            $content = rtrim($content) . ($content ? "\n\n" : '') . $block . "\n";
            $this->line('guideline added to AGENTS.md');
        }
        File::put($agentsMd, $content);
    }

    /** CLAUDE.md imports AGENTS.md (Claude Code reads @imports); created when missing. */
    protected function importInClaudeMd(string $claudeMd): void
    {
        $content = File::exists($claudeMd) ? File::get($claudeMd) : '';
        if (preg_match('/^@AGENTS\.md\s*$/m', $content)) {
            return;
        }
        File::put($claudeMd, rtrim($content) . ($content ? "\n\n" : '') . "@AGENTS.md\n");
        $this->line('@AGENTS.md imported in CLAUDE.md');
    }
}
