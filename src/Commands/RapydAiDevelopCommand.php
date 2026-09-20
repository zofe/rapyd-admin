<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Zofe\Rapyd\Ai\AiDevelopment;

/**
 * Prints what the coding agent can do in this application, what was built and whether it
 * follows the conventions, the boilerplate rpd:make wrote, the prompts to try (see
 * AiDevelopment). --json for scripts and for the page of ai-module.
 */
class RapydAiDevelopCommand extends Command
{
    protected $signature = 'rpd:ai:develop {--json : Machine-readable output} {--path= : The application root (default: base_path())}';

    protected $description = 'Developing this application with an AI agent: capabilities, modules built, boilerplate generated, prompts to try';

    public function handle(): int
    {
        $report = (new AiDevelopment($this->option('path')))->report();
        $s = $report['summary'];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $s['ready'] ? self::SUCCESS : self::FAILURE;
        }

        $icons = ['ok' => '<info>✔</info>', 'warn' => '<comment>!</comment>', 'missing' => '<error>✘</error>'];

        $this->components->twoColumnDetail('<options=bold>What your agent can do here</>', "{$s['capabilities_ok']} / {$s['capabilities_total']}");
        foreach ($report['capabilities'] as $row) {
            $this->components->twoColumnDetail("{$icons[$row['status']]} {$row['label']}", $row['detail']);
            if ($row['fix']) {
                $this->components->twoColumnDetail('    unlock', "<comment>{$row['fix']}</comment>");
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('<options=bold>Built in this project</>', "{$s['modules_conventional']} / {$s['modules_total']} modules follow the conventions");
        foreach ($report['modules'] as $m) {
            $status = $m['conventional'] ? '<info>✔</info>' : '<comment>!</comment>';
            $how = $m['generated'] ? "generated {$m['generated']['at']}, {$m['generated']['files']} files" : 'by hand';
            $detail = sprintf('%s; %d pages, %d workflows, %d permissions, %d limits, %d tests', $how, $m['pages'], count($m['workflows']), count($m['permissions']), $m['limits'], $m['tests']);
            $this->components->twoColumnDetail("{$status} {$m['name']}", $detail);
            if ($m['unprotected']) {
                $this->components->twoColumnDetail('    pages without Authorize', '<comment>' . implode(', ', $m['unprotected']) . '</comment>');
            }
        }
        if (! $report['modules']) {
            $this->components->twoColumnDetail('  (none in app/Modules yet)', 'ask the agent, or ' . $report['generators'][0]['command']);
        }
        if ($s['generated_runs']) {
            $this->components->twoColumnDetail('  rpd:make wrote', "{$s['generated_files']} files in {$s['generated_runs']} runs, ~" . number_format($s['generated_tokens']) . ' tokens the model did not generate');
        }

        $this->newLine();
        $this->components->twoColumnDetail('<options=bold>Context per session (est.)</>', "{$report['context']['resident']} tokens resident, {$report['context']['on_demand']} on demand");

        $this->newLine();
        $this->line('<options=bold>Prompts to try</>');
        foreach ($report['prompts'] as $p) {
            $note = $p['available'] ? '' : ' <comment>(needs ' . implode(', ', $p['missing']) . ')</comment>';
            $this->line("  - {$p['title']}{$note}: <fg=gray>{$p['prompt']}</>");
        }

        $this->newLine();
        $this->line($s['ready']
            ? '<info>Ready: the agent knows Rapyd Admin and what was built follows the conventions.</info>'
            : '<comment>Something to unlock or to fix: see above.</comment>');

        return $s['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
