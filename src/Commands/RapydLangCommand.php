<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Localization\Catalogue;
use Zofe\Rapyd\Localization\Locales;
use Zofe\Rapyd\Localization\TranslationScanner;

/**
 * Collects the phrases of the views and components into Lang/{locale}.json (English
 * phrase => translation): every module of app/Modules into its own Lang/, the rest of
 * the application into lang/. New phrases are added untranslated and listed as pending;
 * --translate fills them with the AI of ai-module and marks them for review; --check
 * fails while something is pending (for CI). Raw HTML text that no __() reaches is
 * reported: wrap it in {{ __('…') }} to translate it.
 */
class RapydLangCommand extends Command
{
    protected $signature = 'rpd:lang
        {locale?* : The locales to fill (default: every enabled one but the default)}
        {--module= : Only this module of app/Modules}
        {--path= : Scan this folder (a module or a package) instead of the application}
        {--out= : Folder of the .json catalogues (default: Lang/ of the scanned folder)}
        {--translate : Fill the pending phrases with the AI of ai-module, marked for review}
        {--check : Exit 1 when phrases are pending (CI)}
        {--prune : Remove the phrases no scanned file uses any more}
        {--literals : List the raw HTML texts that are not translated}';

    protected $description = 'Collect the phrases of views and components into Lang/{locale}.json, translate the pending ones';

    public function handle(Locales $locales, TranslationScanner $scanner): int
    {
        $targets = $this->targets();
        $wanted = $this->argument('locale') ?: array_values(array_diff($locales->all(), [$locales->default()]));
        if (! $wanted) {
            $this->warn('One locale enabled (' . $locales->default() . '): pass the locales to fill, e.g. rpd:lang it fr');

            return self::FAILURE;
        }

        $pending = 0;
        foreach ($targets as $name => [$root, $langDir, $only]) {
            $scan = $scanner->scan($root, $only);
            $this->components->twoColumnDetail("<options=bold>{$name}</>", count($scan['phrases']) . ' phrases, ' . count($scan['literals']) . ' raw texts');
            if ($this->option('literals')) {
                foreach ($scan['literals'] as $l) {
                    $this->components->twoColumnDetail("  {$l['file']}:{$l['line']}", '<comment>' . $l['text'] . '</comment>');
                }
            }
            foreach ($scanner->collisions($scan['phrases'], $root) as $phrase) {
                $this->components->twoColumnDetail("  <error>rename</error> \"{$phrase}\"", 'same name as a PHP language file: __() returns that file instead of a translation');
            }
            if (! $scan['phrases']) {
                continue;
            }

            foreach ($wanted as $locale) {
                $catalogue = new Catalogue($langDir, $locale);
                // phrases the package already translates (Back, Save, Status…) need no line in a module's file
                $own = array_values(array_diff($scan['phrases'], $this->inherited($locale, $langDir)));
                $added = $catalogue->add($own);
                $stale = $catalogue->stale($own);
                if ($stale && $this->option('prune')) {
                    $catalogue->remove($stale);
                }
                if ($this->option('translate') && $catalogue->pending()) {
                    $this->translate($catalogue, $locale);
                }
                $catalogue->save();
                $pending += count($catalogue->pending());

                $this->components->twoColumnDetail(
                    "  {$locale}.json",
                    sprintf(
                        '%d added, %d pending, %d machine-translated to review%s',
                        count($added),
                        count($catalogue->pending()),
                        count($catalogue->machine()),
                        $stale && ! $this->option('prune') ? ', ' . count($stale) . ' stale (--prune)' : ''
                    )
                );
            }
        }

        if ($this->option('check') && $pending) {
            $this->error("{$pending} phrases pending: translate them (rpd:lang --translate) or by hand in the .json files");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * What to scan and where its catalogue lives: [name => [root, Lang dir, subfolders]].
     */
    protected function targets(): array
    {
        if ($path = $this->option('path')) {
            $root = rtrim(base_path($path), '/');
            if (! is_dir($root)) {
                $root = rtrim($path, '/');
            }

            return [basename($root) => [$root, $this->option('out') ? rtrim($this->option('out'), '/') : "{$root}/Lang", []]];
        }
        if ($module = $this->option('module')) {
            $root = base_path("app/Modules/{$module}");

            return [$module => [$root, "{$root}/Lang", []]];
        }

        $targets = [];
        foreach (is_dir(base_path('app/Modules')) ? File::directories(base_path('app/Modules')) : [] as $dir) {
            $targets[basename($dir)] = [$dir, "{$dir}/Lang", []];
        }
        $targets['application'] = [base_path(), base_path('lang'), ['app/Livewire', 'app/View', 'resources/views']];

        return $targets;
    }

    /**
     * The phrases the package's own catalogue (resources/lang/{locale}.json) translates already:
     * a module or an application does not repeat them. Nothing is inherited when the package
     * catalogue itself is the target.
     *
     * @return list<string>
     */
    protected function inherited(string $locale, string $langDir): array
    {
        $own = dirname(__DIR__, 2) . '/resources/lang';
        if (realpath($langDir) === realpath($own)) {
            return [];
        }
        $file = "{$own}/{$locale}.json";

        return is_file($file) ? array_keys(json_decode(File::get($file), true) ?: []) : [];
    }

    /** The pending phrases through the AI of the application (ai-module), in batches. */
    protected function translate(Catalogue $catalogue, string $locale): void
    {
        if (! class_exists(\Zofe\Ai\Services\AiService::class)) {
            $this->warn('  --translate needs zofe/ai-module (composer require zofe/ai-module) with a provider configured');

            return;
        }
        $service = app(\Zofe\Ai\Services\AiService::class);
        foreach (array_chunk($catalogue->pending(), 40) as $batch) {
            $prompt = "Translate the following user-interface phrases of a web application from English to {$locale} "
                . "(locale code). Keep placeholders like :name and :count untouched, keep the same capitalisation style, "
                . "prefer the short wording used in software menus and buttons. Answer with a JSON object only, "
                . "the English phrase as key and the translation as value.\n\n"
                . json_encode(array_values($batch), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $reply = $service->chat([['role' => 'user', 'content' => $prompt]], false);
            $map = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', trim($reply))), true);
            if (! is_array($map)) {
                $this->warn('  the model did not answer with JSON for a batch; left pending');

                continue;
            }
            $catalogue->fill(array_intersect_key($map, array_flip($batch)), machine: true);
        }
    }
}
