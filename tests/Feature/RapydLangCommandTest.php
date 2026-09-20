<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Localization\Catalogue;
use Zofe\Rapyd\Tests\TestCase;

/**
 * rpd:lang on a throwaway module: the phrases of its views land in Lang/{locale}.json,
 * pending until translated, --check fails while they are, --prune drops the stale ones,
 * the raw texts and the colliding phrases are reported.
 */
class RapydLangCommandTest extends TestCase
{
    protected string $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = sys_get_temp_dir() . '/rapyd-lang-' . uniqid();
        File::ensureDirectoryExists("{$this->module}/Views");
        File::ensureDirectoryExists("{$this->module}/Lang/en");
        File::put("{$this->module}/Lang/en/user.php", "<?php return ['name' => 'Name'];");
        File::put("{$this->module}/Views/articles_table.blade.php", <<<'BLADE'
            <x-rpd::table title="Articles" :items="$items">
                <x-slot name="buttons"><a href="#">{{ __('Reset') }}</a> <a href="#">Add</a></x-slot>
                <th>{{ __('Title') }}</th> <th>{{ __('User') }}</th>
            </x-rpd::table>
            BLADE);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->module);
        parent::tearDown();
    }

    public function test_the_phrases_are_collected_pending_and_the_raw_texts_and_collisions_reported()
    {
        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module, '--literals' => true])
            ->expectsOutputToContain('4 phrases, 1 raw texts')
            ->expectsOutputToContain('Add')
            ->expectsOutputToContain('rename')   // "User" is also Lang/en/user.php
            ->expectsOutputToContain('4 added, 4 pending')
            ->assertSuccessful();

        $json = json_decode(File::get("{$this->module}/Lang/it.json"), true);
        $this->assertSame(['Articles' => 'Articles', 'Reset' => 'Reset', 'Title' => 'Title', 'User' => 'User'], $json);
        $sidecar = json_decode(File::get("{$this->module}/Lang/" . Catalogue::SIDECAR), true);
        $this->assertSame(['Articles', 'Reset', 'Title', 'User'], $sidecar['it']['pending']);
    }

    public function test_check_fails_while_phrases_are_pending_and_passes_once_translated()
    {
        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module, '--check' => true])->assertFailed();

        $catalogue = new Catalogue("{$this->module}/Lang", 'it');
        $catalogue->fill(['Articles' => 'Articoli', 'Reset' => 'Azzera', 'Title' => 'Titolo', 'User' => 'Utente']);
        $catalogue->save();
        $this->assertFileDoesNotExist("{$this->module}/Lang/" . Catalogue::SIDECAR, 'nothing pending, no sidecar');

        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module, '--check' => true])
            ->expectsOutputToContain('0 added, 0 pending')
            ->assertSuccessful();
        $this->assertSame('Articoli', json_decode(File::get("{$this->module}/Lang/it.json"), true)['Articles'], 'translations kept');
    }

    public function test_stale_phrases_are_reported_and_pruned_and_machine_translations_marked()
    {
        $catalogue = new Catalogue("{$this->module}/Lang", 'it');
        $catalogue->add(['Articles', 'Old phrase']);
        $catalogue->fill(['Old phrase' => 'Vecchia', 'Articles' => 'Articoli'], machine: true);
        $catalogue->save();
        $this->assertSame(['Old phrase', 'Articles'], json_decode(File::get("{$this->module}/Lang/" . Catalogue::SIDECAR), true)['it']['machine']);

        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module])
            ->expectsOutputToContain('1 stale (--prune)');
        $this->assertArrayHasKey('Old phrase', json_decode(File::get("{$this->module}/Lang/it.json"), true));
        $this->assertCount(2, json_decode(File::get("{$this->module}/Lang/" . Catalogue::SIDECAR), true)['it']['machine'], 'machine translations kept, to review');

        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module, '--prune' => true]);
        $this->assertArrayNotHasKey('Old phrase', json_decode(File::get("{$this->module}/Lang/it.json"), true));
    }

    public function test_without_ai_module_translate_says_what_it_needs()
    {
        if (class_exists(\Zofe\Ai\Services\AiService::class)) {
            $this->markTestSkipped('ai-module installed');
        }
        $this->artisan('rpd:lang', ['locale' => ['it'], '--path' => $this->module, '--translate' => true])
            ->expectsOutputToContain('needs zofe/ai-module');
    }
}
