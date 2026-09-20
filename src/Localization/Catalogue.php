<?php

namespace Zofe\Rapyd\Localization;

use Illuminate\Support\Facades\File;

/**
 * One Lang/{locale}.json catalogue (English phrase => translation) and, next to it,
 * .rpd-lang.json: which phrases are still pending (value = the English phrase) and
 * which were translated by a machine and wait for a review. rpd:lang writes both.
 */
class Catalogue
{
    public const SIDECAR = '.rpd-lang.json';

    protected array $translations = [];

    protected array $pending = [];

    protected array $machine = [];

    public function __construct(protected string $dir, protected string $locale)
    {
        $this->dir = rtrim($dir, '/');
        if (is_file($this->file())) {
            $this->translations = json_decode(File::get($this->file()), true) ?: [];
        }
        $meta = $this->sidecar()[$locale] ?? [];
        // a phrase translated by hand in the .json (value no longer the phrase) is not pending any more
        $this->pending = array_values(array_filter($meta['pending'] ?? [], fn ($p) => ($this->translations[$p] ?? null) === $p));
        $this->machine = array_values(array_intersect($meta['machine'] ?? [], array_keys($this->translations)));
    }

    public function file(): string
    {
        return "{$this->dir}/{$this->locale}.json";
    }

    public function translations(): array
    {
        return $this->translations;
    }

    /** @return list<string> */
    public function pending(): array
    {
        return $this->pending;
    }

    /** @return list<string> */
    public function machine(): array
    {
        return $this->machine;
    }

    /** Adds the phrases not in the catalogue yet, untranslated (value = phrase); returns them. */
    public function add(array $phrases): array
    {
        $added = [];
        foreach ($phrases as $phrase) {
            if (! array_key_exists($phrase, $this->translations)) {
                $this->translations[$phrase] = $phrase;
                $this->pending[] = $phrase;
                $added[] = $phrase;
            }
        }
        $this->pending = array_values(array_unique($this->pending));

        return $added;
    }

    /** Phrases in the catalogue that no scanned file uses any more. */
    public function stale(array $phrases): array
    {
        return array_values(array_diff(array_keys($this->translations), $phrases));
    }

    public function remove(array $phrases): void
    {
        foreach ($phrases as $phrase) {
            unset($this->translations[$phrase]);
        }
        $this->pending = array_values(array_diff($this->pending, $phrases));
        $this->machine = array_values(array_diff($this->machine, $phrases));
    }

    /** Sets translations; from a machine they are marked for review, from a person they are final. */
    public function fill(array $map, bool $machine = false): void
    {
        foreach ($map as $phrase => $translation) {
            if (! is_string($translation) || trim($translation) === '') {
                continue;
            }
            $this->translations[$phrase] = $translation;
            $this->pending = array_values(array_diff($this->pending, [$phrase]));
            $this->machine = array_values(array_diff($this->machine, [$phrase]));
            if ($machine) {
                $this->machine[] = $phrase;
            }
        }
    }

    public function save(): void
    {
        ksort($this->translations, SORT_NATURAL | SORT_FLAG_CASE);
        File::ensureDirectoryExists($this->dir);
        File::put($this->file(), json_encode($this->translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $sidecar = $this->sidecar();
        $sidecar[$this->locale] = ['pending' => $this->pending, 'machine' => $this->machine];
        $sidecar = array_filter($sidecar, fn ($m) => ($m['pending'] ?? []) || ($m['machine'] ?? []));
        if ($sidecar) {
            File::put("{$this->dir}/" . self::SIDECAR, json_encode($sidecar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        } elseif (is_file("{$this->dir}/" . self::SIDECAR)) {
            File::delete("{$this->dir}/" . self::SIDECAR);
        }
    }

    protected function sidecar(): array
    {
        $file = "{$this->dir}/" . self::SIDECAR;

        return is_file($file) ? (json_decode(File::get($file), true) ?: []) : [];
    }
}
