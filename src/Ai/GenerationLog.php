<?php

namespace Zofe\Rapyd\Ai;

use Illuminate\Support\Facades\File;

/**
 * What rpd:make generated in this application: one entry per run with the module,
 * the model, the files written and their size. Kept in storage/rapyd/generated.json;
 * the "Develop with AI" page reads it to tell generated modules from hand-written
 * ones and to estimate the boilerplate the model did not have to write.
 */
class GenerationLog
{
    public function __construct(protected ?string $file = null)
    {
        $this->file = $this->file ?: storage_path('rapyd/generated.json');
    }

    /** Files under the watched folders: path => [size, mtime]. Taken before a generation. */
    public function snapshot(string $root): array
    {
        $files = [];
        foreach (['app', 'database/migrations', 'routes', 'resources/views'] as $dir) {
            if (! is_dir("{$root}/{$dir}")) {
                continue;
            }
            foreach (File::allFiles("{$root}/{$dir}") as $f) {
                $files[substr($f->getPathname(), strlen($root) + 1)] = [$f->getSize(), $f->getMTime()];
            }
        }

        return $files;
    }

    /** Compares with a snapshot and records the files created or changed since. */
    public function record(string $root, array $before, array $meta): array
    {
        $files = [];
        foreach ($this->snapshot($root) as $path => [$size, $mtime]) {
            if (! isset($before[$path])) {
                $files[] = ['path' => $path, 'chars' => $size, 'new' => true];
            } elseif ($before[$path] !== [$size, $mtime]) {
                $files[] = ['path' => $path, 'chars' => max(0, $size - $before[$path][0]), 'new' => false];
            }
        }
        if (! $files) {
            return [];
        }
        $entry = $meta + ['at' => now()->toDateTimeString(), 'files' => $files];
        $all = $this->all();
        $all[] = $entry;
        File::ensureDirectoryExists(dirname($this->file));
        File::put($this->file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $entry;
    }

    /** @return list<array{module: ?string, model: string, component: string, at: string, files: list<array>}> */
    public function all(): array
    {
        return is_file($this->file) ? (json_decode(File::get($this->file), true) ?: []) : [];
    }

    /** The runs of a module (null = outside a module), oldest first. */
    public function forModule(?string $module): array
    {
        return array_values(array_filter($this->all(), fn ($e) => ($e['module'] ?? null) === $module));
    }

    /** Characters written by the generator in the given runs. */
    public static function chars(array $entries): int
    {
        $chars = 0;
        foreach ($entries as $entry) {
            foreach ($entry['files'] as $file) {
                $chars += $file['chars'];
            }
        }

        return $chars;
    }
}
