<?php

namespace Zofe\Rapyd\Localization;

use Illuminate\Support\Facades\File;

/**
 * Collects the phrases a folder of views and components shows, for Lang/{locale}.json.
 *
 * Phrases are the arguments of __() / @lang() / trans() (not the namespaced or dotted
 * keys of PHP language files), and the static label / title / placeholder / actionLabel
 * attributes of x-rpd:: components, which pass them through __() themselves.
 * Raw text in the HTML (<a>Back</a>, <th>Name</th>) is reported apart: it is not
 * translated until it is wrapped in {{ __('…') }}.
 */
class TranslationScanner
{
    public const ATTRIBUTES = ['label', 'title', 'placeholder', 'actionLabel', 'help'];

    /**
     * @return array{phrases: list<string>, literals: list<array{file: string, line: int, text: string}>}
     */
    public function scan(string $root, array $only = []): array
    {
        $phrases = [];
        $literals = [];
        foreach ($this->files($root, $only) as $file) {
            $content = File::get($file);
            $relative = ltrim(substr($file, strlen($root)), '/');
            foreach ($this->phrasesIn($content) as $phrase) {
                $phrases[$phrase] = true;
            }
            if (str_ends_with($file, '.blade.php')) {
                foreach ($this->literalsIn($content) as [$line, $text]) {
                    $literals[] = ['file' => $relative, 'line' => $line, 'text' => $text];
                }
            }
        }
        $phrases = array_keys($phrases);
        sort($phrases, SORT_NATURAL | SORT_FLAG_CASE);

        return ['phrases' => $phrases, 'literals' => $literals];
    }

    /** @return list<string> */
    public function phrasesIn(string $content): array
    {
        $found = [];
        // __('…'), __("…"), @lang('…'), trans('…')
        preg_match_all('/(?:__|@lang|\btrans)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', $content, $m);
        foreach ($m[2] as $i => $phrase) {
            $phrase = stripcslashes($phrase);
            if ($this->isPhrase($phrase)) {
                $found[] = $phrase;
            }
        }
        // static attributes of x-rpd:: components: label="…", title='…' (not :label="…");
        // the "->" of a bound attribute (:params="$article->id") must not end the tag
        $attrs = implode('|', self::ATTRIBUTES);
        preg_match_all('/<x-rpd::[a-z\-]+\b[^>]*?>/s', str_replace(['->', '=>'], '  ', $content), $tags);
        foreach ($tags[0] as $tag) {
            preg_match_all('/(?<![:\w\-])(' . $attrs . ')=(["\'])(.*?)\2/s', $tag, $a);
            foreach ($a[3] as $value) {
                if ($this->isPhrase($value) && ! str_contains($value, '{{')) {
                    $found[] = $value;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Text written directly in the HTML of a Blade file that reaches the page untranslated.
     *
     * @return list<array{0: int, 1: string}>
     */
    public function literalsIn(string $content): array
    {
        $found = [];
        // out of the way, keeping the line numbers: comments, PHP blocks, scripts, echoes, "->" of expressions
        $stripped = preg_replace_callback(
            '/\{\{--.*?--\}\}|@php\b.*?@endphp|<\?php.*?\?>|<script\b.*?<\/script>|\{\{.*?\}\}|\{!!.*?!!\}/s',
            fn ($m) => preg_replace('/[^\n]/', ' ', $m[0]),
            $content
        );
        $stripped = str_replace(['->', '=>'], '  ', $stripped);
        // between a closing ">" and an opening "<": plain text, no Blade directive
        preg_match_all('/>([^<>{}@]*[A-Za-z]{2,}[^<>{}@]*)</', $stripped, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[1] as [$text, $offset]) {
            $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text)));
            $text = trim($text, " \u{a0}");
            if ($text === '' || ! preg_match('/[A-Za-z]{2,}/', $text) || preg_match('/^[\W\d]*$/', $text)) {
                continue;
            }
            $found[] = [substr_count($stripped, "\n", 0, $offset) + 1, $text];
        }

        return $found;
    }

    /**
     * Phrases that are also the name of a PHP language file (lang/en/auth.php, a module's
     * Lang/en/user.php): for them __() returns that file's array instead of a translation,
     * on case-insensitive file systems whatever the capitalisation. Rename the phrase.
     *
     * @return list<string>
     */
    public function collisions(array $phrases, string $root): array
    {
        $groups = ['auth', 'validation', 'passwords', 'pagination'];
        foreach ([$root . '/Lang/en', $root . '/lang/en', base_path('lang/en')] as $dir) {
            foreach (is_dir($dir) ? File::files($dir) : [] as $file) {
                $groups[] = strtolower($file->getBasename('.php'));
            }
        }

        return array_values(array_filter($phrases, fn ($p) => in_array(strtolower($p), $groups, true)));
    }

    /** A JSON phrase, not the key of a PHP language file (auth::user.name, dashboard.title). */
    public function isPhrase(string $text): bool
    {
        $text = trim($text);
        if ($text === '' || str_contains($text, '::') || str_contains($text, '$')) {
            return false;
        }

        return ! preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)+$/', $text);
    }

    /** @return list<string> */
    protected function files(string $root, array $only): array
    {
        $dirs = $only ?: ['Views', 'Livewire', 'Components', 'resources/views', 'app', 'src', 'routes'];
        $files = [];
        if (! $only && is_file(rtrim($root, '/') . '/routes.php')) {   // a module's routes: breadcrumb labels
            $files[rtrim($root, '/') . '/routes.php'] = true;
        }
        foreach ($dirs as $dir) {
            $path = rtrim($root, '/') . '/' . $dir;
            if (! is_dir($path)) {
                continue;
            }
            foreach (File::allFiles($path) as $file) {
                if (in_array($file->getExtension(), ['php'])) {
                    $files[$file->getPathname()] = true;
                }
            }
        }

        return array_keys($files);
    }
}
