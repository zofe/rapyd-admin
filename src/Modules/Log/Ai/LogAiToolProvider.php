<?php

namespace Zofe\Rapyd\Modules\Log\Ai;

use Zofe\Rapyd\Contracts\AiTool;
use Zofe\Rapyd\Contracts\AiToolProvider;
use Zofe\Rapyd\Modules\Log\Services\LogParser;

class LogAiToolProvider implements AiToolProvider
{
    public function tools(): array
    {
        return [
            new AiTool(
                name: 'get_recent_errors',
                description: 'Returns recent entries from the Laravel application log. Use this to answer questions about errors, exceptions, warnings, or application health.',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'level' => ['type' => 'string', 'enum' => array_keys(LogParser::LEVEL_CLASSES), 'description' => 'Filter by log level. Omit to return all levels.'],
                        'limit' => ['type' => 'integer', 'default' => 20, 'description' => 'Maximum number of log entries to return.'],
                        'search' => ['type' => 'string', 'description' => 'Optional text to search within messages and stack traces.'],
                    ],
                ],
                handler: fn (array $input) => $this->recentErrors($input['level'] ?? null, (int) ($input['limit'] ?? 20), $input['search'] ?? null),
            ),
            new AiTool(
                name: 'get_error_summary',
                description: 'Returns a grouped summary of recent errors with frequency counts. Useful for understanding which errors are most common.',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'level' => ['type' => 'string', 'enum' => ['error', 'critical', 'alert', 'emergency', 'warning'], 'default' => 'error'],
                        'top' => ['type' => 'integer', 'default' => 10, 'description' => 'How many distinct errors to return, ordered by frequency.'],
                    ],
                ],
                handler: fn (array $input) => (new LogParser())->summary((new LogParser())->latestFile() ?? '', [$input['level'] ?? 'error'], (int) ($input['top'] ?? 10)),
            ),
        ];
    }

    protected function recentErrors(?string $level, int $limit, ?string $search): array
    {
        $parser = new LogParser();
        $entries = $parser->entries($parser->latestFile() ?? '');

        if ($level) {
            $entries = array_filter($entries, fn ($e) => $e['level'] === $level);
        }
        if ($search) {
            $s = strtolower($search);
            $entries = array_filter($entries, fn ($e) => str_contains(strtolower($e['text']), $s) || str_contains(strtolower($e['stack']), $s));
        }

        return array_slice(array_values(array_map(fn ($e) => [
            'level' => $e['level'], 'date' => $e['date'], 'context' => $e['context'],
            'message' => $e['text'], 'stack' => $e['stack'] !== '' ? substr($e['stack'], 0, 500) : null,
        ], $entries)), 0, $limit);
    }
}
