<?php

namespace Zofe\Rapyd\Modules\Log\Services;

use Illuminate\Support\Facades\Cache;

class LogParser
{
    public const LEVEL_CLASSES = [
        'debug' => 'secondary', 'info' => 'info', 'notice' => 'info', 'processed' => 'info',
        'warning' => 'warning', 'failed' => 'warning',
        'error' => 'danger', 'critical' => 'danger', 'alert' => 'danger', 'emergency' => 'danger',
    ];

    public function __construct(protected ?string $directory = null)
    {
        $this->directory = $directory ?: storage_path('logs');
    }

    /**
     * Log files, newest first (daily files sort naturally by name).
     */
    public function files(): array
    {
        $files = glob($this->directory . '/*.log') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map('basename', $files);
    }

    public function latestFile(): ?string
    {
        return $this->files()[0] ?? null;
    }

    /**
     * Parsed entries, newest first. Cached per file + mtime + size so the file is read
     * once per change, and later filtering happens on the array.
     */
    public function entries(string $file): array
    {
        $path = $this->directory . '/' . basename($file);
        if (! is_file($path)) {
            return [];
        }

        $key = 'rapyd.log.' . md5($path . '|' . filemtime($path) . '|' . filesize($path));

        return Cache::remember($key, config('rapyd.log.app.cache_ttl', 300), fn () => $this->parse($this->tail($path)));
    }

    public function entry(string $file, string $id): ?array
    {
        foreach ($this->entries($file) as $entry) {
            if ($entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Distinct messages of the given levels with their frequency, most frequent first.
     */
    public function summary(string $file, array $levels = ['error', 'critical', 'alert', 'emergency'], int $top = 5): array
    {
        $groups = [];
        foreach ($this->entries($file) as $entry) {
            if (! in_array($entry['level'], $levels)) {
                continue;
            }
            $key = md5($entry['text']);
            $groups[$key] ??= ['message' => $entry['text'], 'level' => $entry['level'], 'count' => 0, 'last_seen' => $entry['date'], 'id' => $entry['id']];
            $groups[$key]['count']++;
        }
        usort($groups, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice(array_values($groups), 0, $top);
    }

    protected function parse(string $content): array
    {
        // Standard Laravel line: [2026-09-11 10:00:00] local.ERROR: message
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?)\] (\w+)\.(\w+): /m';
        if (! preg_match_all($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $entries = [];
        $count = count($m[0]);
        for ($i = 0; $i < $count; $i++) {
            $bodyStart = $m[0][$i][1] + strlen($m[0][$i][0]);
            $bodyEnd = $i + 1 < $count ? $m[0][$i + 1][1] : strlen($content);
            $body = trim(substr($content, $bodyStart, $bodyEnd - $bodyStart));
            $nl = strpos($body, "\n");
            $text = $nl === false ? $body : substr($body, 0, $nl);
            $stack = $nl === false ? '' : trim(substr($body, $nl + 1));
            $level = strtolower($m[3][$i][0]);

            $entries[] = [
                'id' => substr(md5($m[0][$i][1] . $text), 0, 12),
                'date' => $m[1][$i][0],
                'context' => $m[2][$i][0],
                'level' => $level,
                'level_class' => self::LEVEL_CLASSES[$level] ?? 'secondary',
                'text' => $text,
                'stack' => $stack,
            ];
        }

        return array_reverse($entries);
    }

    protected function tail(string $path): string
    {
        $maxBytes = (int) config('rapyd.log.app.max_bytes', 2 * 1024 * 1024);
        $size = filesize($path);
        if ($size === 0) {
            return '';
        }

        $fp = fopen($path, 'rb');
        if ($size > $maxBytes) {
            fseek($fp, -$maxBytes, SEEK_END);
            fgets($fp);
        }
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }
}
