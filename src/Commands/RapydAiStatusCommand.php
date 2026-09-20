<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Zofe\Rapyd\Ai\AiReadiness;

/**
 * Prints how ready the application is for AI-assisted development (see AiReadiness):
 * the agent tooling, the estimated context cost and the app modules against the
 * conventions. --json for scripts and for the page of ai-module.
 */
class RapydAiStatusCommand extends Command
{
    protected $signature = 'rpd:ai:status {--json : Machine-readable output} {--path= : The application root (default: base_path())}';

    protected $description = 'Is this application ready for AI-assisted development? Guideline, skills, MCP, modules';

    public function handle(): int
    {
        $report = (new AiReadiness($this->option('path')))->report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report['summary']['ready'] ? self::SUCCESS : self::FAILURE;
        }

        $icons = ['ok' => '<info>✔</info>', 'warn' => '<comment>!</comment>', 'missing' => '<error>✘</error>'];

        $this->components->twoColumnDetail('<options=bold>Agent tooling</>', "{$report['summary']['tooling_ok']} / {$report['summary']['tooling_total']} ok");
        foreach ($report['tooling'] as $row) {
            $this->components->twoColumnDetail("{$icons[$row['status']]} {$row['label']}", $row['detail']);
            if ($row['fix']) {
                $this->components->twoColumnDetail('    fix', "<comment>{$row['fix']}</comment>");
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('<options=bold>Context per session (est.)</>', "{$report['context']['resident']} tokens resident, {$report['context']['on_demand']} on demand");
        foreach ($report['context']['files'] as $file => $tokens) {
            $this->components->twoColumnDetail("  {$file}", "{$tokens}");
        }

        $this->newLine();
        $this->components->twoColumnDetail('<options=bold>Application modules</>', "{$report['summary']['modules_protected']} / {$report['summary']['modules_total']} with every page authorized");
        foreach ($report['modules'] as $m) {
            $status = $m['unprotected'] ? '<error>✘</error>' : '<info>✔</info>';
            $detail = sprintf(
                '%d pages, %d workflows, %d permissions, %d auth, %d limits, %d tests',
                $m['pages'],
                count($m['workflows']),
                count($m['permissions']),
                $m['authorizations'],
                $m['limits'],
                $m['tests']
            );
            $this->components->twoColumnDetail("{$status} {$m['name']}", $detail);
            if ($m['unprotected']) {
                $this->components->twoColumnDetail('    without Authorize', '<comment>' . implode(', ', $m['unprotected']) . '</comment>');
            }
        }
        if (! $report['modules']) {
            $this->components->twoColumnDetail('  (none in app/Modules yet)', 'php artisan rpd:make Things Thing --module=Name --fields=...');
        }

        $this->newLine();
        $this->line($report['summary']['ready']
            ? '<info>Ready: the agent knows Rapyd Admin and the modules follow the conventions.</info>'
            : '<comment>Not ready yet: run the fixes above.</comment>');

        return $report['summary']['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
