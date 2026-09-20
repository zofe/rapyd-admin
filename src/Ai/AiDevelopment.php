<?php

namespace Zofe\Rapyd\Ai;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Zofe\Rapyd\Commands\RapydAiCommand;

/**
 * Developing this application with an AI coding assistant: what the agent can do here
 * (the guideline, the skills, Boost, MCP, the generators), what was built and whether it
 * follows the conventions (authorized pages, permissions, Limits, workflows, tests),
 * how much boilerplate rpd:make wrote instead of the model, which prompts to try.
 *
 * Everything is read from the files of the application: no provider is called and
 * nothing is written. `rpd:ai:develop` prints it, ai-module shows it as a page.
 */
class AiDevelopment
{
    public const BOOST_MARK = RapydAiCommand::BOOST_MARK;

    protected GenerationLog $log;

    public function __construct(protected ?string $root = null, ?GenerationLog $log = null)
    {
        $this->root = rtrim($this->root ?: base_path(), '/');
        $this->log = $log ?: new GenerationLog("{$this->root}/storage/rapyd/generated.json");
    }

    /**
     * @return array{capabilities: array, generators: array, context: array, modules: array, prompts: array, summary: array}
     */
    public function report(): array
    {
        $capabilities = $this->capabilities();
        $modules = $this->modules();
        $generated = $this->log->all();

        $ok = count(array_filter($capabilities, fn ($c) => $c['status'] === 'ok'));
        $conventional = count(array_filter($modules, fn ($m) => $m['conventional']));
        $files = array_sum(array_map(fn ($e) => count($e['files']), $generated));

        return [
            'capabilities' => $capabilities,
            'generators' => $this->generators(),
            'context' => $this->context(),
            'modules' => $modules,
            'prompts' => $this->prompts(),
            'summary' => [
                'capabilities_ok' => $ok,
                'capabilities_total' => count($capabilities),
                'agent_ready' => $ok === count($capabilities),
                'modules_total' => count($modules),
                'modules_conventional' => $conventional,
                'generated_runs' => count($generated),
                'generated_files' => $files,
                'generated_tokens' => (int) ceil(GenerationLog::chars($generated) / 4),
                'ready' => $ok === count($capabilities) && $conventional === count($modules),
            ],
        ];
    }

    /**
     * What the coding agent can do in this application, one row each: status ok | warn | missing,
     * a detail and, when not ok, the command that unlocks it.
     *
     * @return list<array{key: string, label: string, status: string, detail: string, fix: ?string}>
     */
    public function capabilities(): array
    {
        $rows = [];
        $agents = $this->read('AGENTS.md');
        $claude = $this->read('CLAUDE.md');

        // guideline: written by Boost (heading) or by rpd:ai (markers), in AGENTS.md or CLAUDE.md
        $where = str_contains($agents, self::BOOST_MARK) || str_contains($agents, RapydAiCommand::START) ? 'AGENTS.md'
            : (str_contains($claude, self::BOOST_MARK) ? 'CLAUDE.md' : null);
        $byBoost = str_contains($agents, self::BOOST_MARK) || str_contains($claude, self::BOOST_MARK);
        $refresh = $byBoost ? 'php artisan boost:update' : 'php artisan rpd:ai';

        if ($where) {
            $current = $this->normalize($this->guideline());
            $installed = $this->normalize($where === 'AGENTS.md' ? $agents : $claude);
            $upToDate = $current !== '' && str_contains($installed, $current);
            $rows[] = $this->row(
                'guideline',
                'Knows Rapyd Admin',
                $upToDate ? 'ok' : 'warn',
                $upToDate ? "the guideline is in {$where} and current" : "the guideline in {$where} is older than the installed package",
                $upToDate ? null : $refresh
            );
        } else {
            $rows[] = $this->row(
                'guideline',
                'Knows Rapyd Admin',
                'missing',
                'no guideline in AGENTS.md / CLAUDE.md: the agent writes plain Laravel, not Rapyd modules',
                'php artisan rpd:ai (or boost:install with zofe/rapyd-admin selected)'
            );
        }

        $skills = [
            'rapyd-module' => ['Builds modules', 'a list, a detail and a form with permissions, scoping and tests from one request'],
            'rapyd-workflow' => ['Designs workflows', 'places, transitions, guards and the embed on the page instead of a hand-written status'],
        ];
        foreach ($this->packageSkills() as $name) {
            [$label, $what] = $skills[$name] ?? [Str::headline($name), ''];
            $target = "{$this->root}/.claude/skills/{$name}";
            $stamped = is_file("{$target}/.rapyd-installed");
            if (! is_dir($target)) {
                $rows[] = $this->row("skill:{$name}", $label, 'missing', "skill {$name} not installed: {$what}", $refresh);
            } elseif ($this->sameFiles($this->skillsPath($name), $target)) {
                $rows[] = $this->row("skill:{$name}", $label, 'ok', "skill {$name}, current: {$what}", null);
            } elseif ($stamped && $this->applicationTouched($target)) {
                $rows[] = $this->row("skill:{$name}", $label, 'ok', "skill {$name}, customised by this application", null);
            } else {
                $rows[] = $this->row("skill:{$name}", $label, 'warn', "skill {$name} is older than the installed package", $stamped ? 'php artisan rpd:ai --force' : 'php artisan boost:update');
            }
        }

        $imports = preg_match('/^@AGENTS\.md\s*$/m', $claude) || str_contains($claude, self::BOOST_MARK);
        $rows[] = $where === 'AGENTS.md' && ! $imports
            ? $this->row('claude_md', 'Claude Code reads the guideline', 'warn', 'CLAUDE.md does not import AGENTS.md', 'php artisan rpd:ai')
            : $this->row(
                'claude_md',
                'Claude Code reads the guideline',
                $claude !== '' ? 'ok' : 'warn',
                $claude !== '' ? 'CLAUDE.md present' : 'no CLAUDE.md (other agents read AGENTS.md)',
                $claude !== '' ? null : 'php artisan rpd:ai'
            );

        $boost = class_exists(\Laravel\Boost\BoostServiceProvider::class)
            ? (InstalledVersions::isInstalled('laravel/boost') ? InstalledVersions::getPrettyVersion('laravel/boost') : 'installed')
            : null;
        $rows[] = $this->row(
            'boost',
            'Knows Laravel and Livewire',
            $boost ? 'ok' : 'warn',
            $boost ? "Laravel Boost {$boost}: version-specific guidelines and docs search" : 'Laravel Boost not installed: the agent guesses the Laravel and Livewire versions',
            $boost ? null : 'composer require laravel/boost --dev && php artisan boost:install'
        );

        $mcp = $this->mcpServers();
        $rows[] = $this->row(
            'mcp',
            'Reads the schema, the logs, the routes',
            in_array('laravel-boost', $mcp) ? 'ok' : ($mcp ? 'warn' : 'missing'),
            $mcp ? 'MCP servers: ' . implode(', ', $mcp) : 'no .mcp.json: the agent has no live access to the application',
            in_array('laravel-boost', $mcp) ? null : 'php artisan boost:install (registers laravel-boost)'
        );

        return $rows;
    }

    /** The generators the agent (or you) can call, and what each writes. */
    public function generators(): array
    {
        return [
            ['command' => 'php artisan rpd:make Things Thing --module=Name --fields="name,active:boolean"',
                'writes' => 'model with uuid key, migration (run), table / view / edit pages with Authorize and Limit, views, routes behind auth, permissions in config.php (seeded), Authorizations/ and Limits/, menu entry'],
            ['command' => 'php artisan rpd:make:home', 'writes' => 'the dashboard page of the application'],
            ['command' => 'php artisan rpd:eject Module', 'writes' => 'a copy of a bundled module in app/Modules, to change it'],
            ['command' => 'php artisan rpd:context', 'writes' => 'nothing: a briefing of the application for an agent (modules, models, routes, permissions)'],
        ];
    }

    /**
     * Estimated tokens (characters / 4) the agent loads: resident every session (one
     * memory file, AGENTS.md or CLAUDE.md, plus the skill descriptions) and on demand
     * (skill bodies, read when a skill is used).
     *
     * @return array{resident: int, on_demand: int, files: array<string, int>}
     */
    public function context(): array
    {
        $files = [];
        $memory = 0;
        foreach (['AGENTS.md', 'CLAUDE.md'] as $file) {
            if (($content = $this->read($file)) !== '') {
                $files[$file] = $this->tokens($content);
                $memory = max($memory, $files[$file]);   // an agent reads one of the two
            }
        }
        $onDemand = 0;
        foreach (File::glob("{$this->root}/.claude/skills/*/SKILL.md") as $skill) {
            $content = File::get($skill);
            $name = basename(dirname($skill));
            preg_match('/^description:\s*(.*)$/m', $content, $m);
            $files["skill {$name} (description)"] = $this->tokens($m[1] ?? $name);
            $onDemand += $this->tokens($content);
        }

        $descriptions = array_sum($files) - array_sum(array_intersect_key($files, array_flip(['AGENTS.md', 'CLAUDE.md'])));

        return ['resident' => $memory + $descriptions, 'on_demand' => $onDemand, 'files' => $files];
    }

    /**
     * The application's own modules (app/Modules/*): what rpd:make generated in them,
     * and whether they follow the conventions the agent is taught.
     *
     * @return list<array<string, mixed>>
     */
    public function modules(): array
    {
        $rows = [];
        $dirs = is_dir("{$this->root}/app/Modules") ? File::directories("{$this->root}/app/Modules") : [];
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $components = File::glob("{$dir}/Livewire/*.php");
            $fullPage = $unprotected = [];
            foreach ($components as $file) {
                $content = File::get($file);
                if (str_contains($content, '->layout(')) {
                    $fullPage[] = basename($file, '.php');
                    if (! str_contains($content, 'Authorize')) {
                        $unprotected[] = basename($file, '.php');
                    }
                }
            }
            $config = is_file("{$dir}/config.php") ? (require "{$dir}/config.php") : [];
            $workflows = is_file("{$dir}/workflow.php") ? (require "{$dir}/workflow.php") : [];
            $runs = $this->log->forModule($name);
            $permissions = is_array($config) ? ($config['permissions'] ?? []) : [];
            $tests = $this->testsMentioning($name);

            $rows[] = [
                'name' => $name,
                'components' => count($components),
                'pages' => count($fullPage),
                'unprotected' => $unprotected,
                'workflows' => is_array($workflows) ? array_keys($workflows) : [],
                'permissions' => $permissions,
                'authorizations' => count(File::glob("{$dir}/Authorizations/*.php")),
                'limits' => count(File::glob("{$dir}/Limits/*.php")),
                'tests' => $tests,
                'generated' => $runs ? [
                    'runs' => count($runs),
                    'at' => end($runs)['at'],
                    'models' => array_values(array_unique(array_column($runs, 'model'))),
                    'files' => array_sum(array_map(fn ($r) => count($r['files']), $runs)),
                    'tokens' => (int) ceil(GenerationLog::chars($runs) / 4),
                ] : null,
                // the checklist of the rapyd-module skill: every page authorized, permissions declared, a test
                'conventional' => $unprotected === [] && ($permissions !== [] || $fullPage === []) && ($tests > 0 || $fullPage === []),
            ];
        }

        return $rows;
    }

    /**
     * Prompts to give the agent, from resources/ai/prompts.json; `available` says whether the
     * packages a prompt needs are installed, `missing` lists the ones that are not.
     */
    public function prompts(): array
    {
        $file = dirname(__DIR__, 2) . '/resources/ai/prompts.json';
        $prompts = is_file($file) ? (json_decode(File::get($file), true)['prompts'] ?? []) : [];

        return array_map(function ($p) {
            $missing = array_values(array_filter($p['requires'] ?? [], fn ($pkg) => ! InstalledVersions::isInstalled($pkg)));

            return $p + ['available' => $missing === [], 'missing' => $missing];
        }, $prompts);
    }

    protected function testsMentioning(string $module): int
    {
        $count = 0;
        foreach (File::glob("{$this->root}/tests/{,*/}*.php", GLOB_BRACE) as $file) {
            $content = File::get($file);
            if (str_contains($content, "Modules\\{$module}\\") || str_contains($content, Str::lower($module) . '::')) {
                $count++;
            }
        }

        return $count;
    }

    /** The guideline of the installed package, rendered as rpd:ai / Boost do. */
    public function guideline(): string
    {
        $file = dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php';

        return is_file($file) ? trim(Blade::render(File::get($file))) : '';
    }

    /** @return list<string> */
    public function packageSkills(): array
    {
        return array_map('basename', File::directories(dirname(__DIR__, 2) . '/resources/boost/skills'));
    }

    protected function skillsPath(string $name): string
    {
        return dirname(__DIR__, 2) . "/resources/boost/skills/{$name}";
    }

    /** @return list<string> */
    protected function mcpServers(): array
    {
        $json = json_decode($this->read('.mcp.json'), true);

        return array_keys($json['mcpServers'] ?? []);
    }

    protected function sameFiles(string $source, string $target): bool
    {
        foreach (File::allFiles($source) as $file) {
            $copy = $target . '/' . $file->getRelativePathname();
            if (! File::exists($copy) || File::get($copy) !== File::get($file->getPathname())) {
                return false;
            }
        }

        return true;
    }

    /** The stamp of rpd:ai says what was installed: if the files still match it, the app did not touch them. */
    protected function applicationTouched(string $target): bool
    {
        foreach (json_decode(File::get("{$target}/.rapyd-installed"), true) ?: [] as $file => $hash) {
            if (! File::exists("{$target}/{$file}") || md5_file("{$target}/{$file}") !== $hash) {
                return true;
            }
        }

        return false;
    }

    protected function read(string $file): string
    {
        return is_file("{$this->root}/{$file}") ? File::get("{$this->root}/{$file}") : '';
    }

    protected function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public static function tokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    protected function row(string $key, string $label, string $status, string $detail, ?string $fix): array
    {
        return compact('key', 'label', 'status', 'detail', 'fix');
    }
}
