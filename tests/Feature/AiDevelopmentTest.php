<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Ai\AiDevelopment;
use Zofe\Rapyd\Ai\GenerationLog;
use Zofe\Rapyd\Tests\TestCase;

/**
 * AiDevelopment reads a throwaway application: empty, after rpd:ai, with an older
 * guideline, with a module whose page does not authorize, with a generation log.
 */
class AiDevelopmentTest extends TestCase
{
    protected string $appDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appDir = sys_get_temp_dir() . '/rapyd-readiness-' . uniqid();
        File::ensureDirectoryExists($this->appDir);   // no app/Modules yet, like a fresh application
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->appDir);
        parent::tearDown();
    }

    protected function toolingRow(string $key, array $report): array
    {
        return collect($report['capabilities'])->firstWhere('key', $key);
    }

    public function test_an_empty_application_is_not_ready_and_every_row_names_its_fix()
    {
        $report = (new AiDevelopment($this->appDir))->report();

        $this->assertFalse($report['summary']['ready']);
        $this->assertSame('missing', $this->toolingRow('guideline', $report)['status']);
        $this->assertStringContainsString('rpd:ai', $this->toolingRow('guideline', $report)['fix']);
        $this->assertSame('missing', $this->toolingRow('skill:rapyd-module', $report)['status']);
        $this->assertSame('missing', $this->toolingRow('mcp', $report)['status']);
        foreach ($report['capabilities'] as $row) {
            if ($row['status'] !== 'ok') {
                $this->assertNotEmpty($row['fix'], "{$row['key']} names its fix");
            }
        }
        $this->assertSame(0, $report['context']['resident']);
        $this->assertSame([], $report['modules']);
    }

    public function test_after_rpd_ai_the_guideline_and_the_skills_are_current()
    {
        $this->artisan('rpd:ai', ['--path' => $this->appDir])->assertSuccessful();

        $report = (new AiDevelopment($this->appDir))->report();

        $this->assertSame('ok', $this->toolingRow('guideline', $report)['status']);
        $this->assertSame('ok', $this->toolingRow('skill:rapyd-module', $report)['status']);
        $this->assertSame('ok', $this->toolingRow('skill:rapyd-workflow', $report)['status']);
        $this->assertSame('ok', $this->toolingRow('claude_md', $report)['status'], 'CLAUDE.md imports AGENTS.md');
        $this->assertGreaterThan(500, $report['context']['resident'], 'the guideline is resident');
        $this->assertGreaterThan($report['context']['resident'], $report['context']['on_demand'] + $report['context']['resident']);
        $this->assertArrayHasKey('skill rapyd-module (description)', $report['context']['files']);
    }

    public function test_an_older_guideline_and_a_customised_skill_are_told_apart()
    {
        $this->artisan('rpd:ai', ['--path' => $this->appDir])->assertSuccessful();
        File::put("{$this->appDir}/AGENTS.md", str_replace('Rapyd Admin', 'Rapyd Admin (old)', File::get("{$this->appDir}/AGENTS.md")));
        File::append("{$this->appDir}/.claude/skills/rapyd-workflow/SKILL.md", "\nOur own rule.\n");

        $report = (new AiDevelopment($this->appDir))->report();

        $this->assertSame('warn', $this->toolingRow('guideline', $report)['status']);
        $this->assertSame('php artisan rpd:ai', $this->toolingRow('guideline', $report)['fix']);
        $this->assertSame('ok', $this->toolingRow('skill:rapyd-workflow', $report)['status']);
        $this->assertStringContainsString('customised', $this->toolingRow('skill:rapyd-workflow', $report)['detail']);
    }

    public function test_a_boost_installation_is_refreshed_with_boost_update()
    {
        File::put("{$this->appDir}/AGENTS.md", "<laravel-boost-guidelines>\n" . AiDevelopment::BOOST_MARK . "\n\nold text\n</laravel-boost-guidelines>\n");
        File::put("{$this->appDir}/CLAUDE.md", File::get("{$this->appDir}/AGENTS.md"));
        File::put("{$this->appDir}/.mcp.json", json_encode(['mcpServers' => ['laravel-boost' => ['command' => 'php']]]));

        $report = (new AiDevelopment($this->appDir))->report();

        $this->assertSame('warn', $this->toolingRow('guideline', $report)['status']);
        $this->assertSame('php artisan boost:update', $this->toolingRow('guideline', $report)['fix']);
        $this->assertSame('php artisan boost:update', $this->toolingRow('skill:rapyd-module', $report)['fix']);
        $this->assertSame('ok', $this->toolingRow('mcp', $report)['status']);
    }

    public function test_a_module_page_without_authorize_is_reported()
    {
        File::ensureDirectoryExists("{$this->appDir}/app/Modules/Blog/Livewire");
        File::ensureDirectoryExists("{$this->appDir}/app/Modules/Blog/Limits");
        File::ensureDirectoryExists("{$this->appDir}/tests/Feature");
        File::put("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesTable.php", "<?php\nuse Authorize;\nreturn view('x')->layout('layout::admin');");
        File::put("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesView.php", "<?php\nreturn view('x')->layout('layout::admin');");
        File::put("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesEmbed.php", "<?php\nreturn view('x');");
        File::put("{$this->appDir}/app/Modules/Blog/Limits/ArticleLimit.php", '<?php');
        File::put("{$this->appDir}/app/Modules/Blog/config.php", "<?php return ['permissions' => ['view articles', 'edit articles']];");
        File::put("{$this->appDir}/app/Modules/Blog/workflow.php", "<?php return ['article' => []];");
        File::put("{$this->appDir}/tests/Feature/BlogTest.php", "<?php Livewire::test('blog::articles-table');");

        $report = (new AiDevelopment($this->appDir))->report();
        $blog = $report['modules'][0];

        $this->assertSame('Blog', $blog['name']);
        $this->assertSame(3, $blog['components']);
        $this->assertSame(2, $blog['pages'], 'the embed is not a page');
        $this->assertSame(['ArticlesView'], $blog['unprotected']);
        $this->assertSame(['article'], $blog['workflows']);
        $this->assertSame(['view articles', 'edit articles'], $blog['permissions']);
        $this->assertSame(1, $blog['limits']);
        $this->assertSame(0, $blog['authorizations']);
        $this->assertSame(1, $blog['tests']);
        $this->assertNull($blog['generated'], 'written by hand');
        $this->assertFalse($blog['conventional']);
        $this->assertSame(0, $report['summary']['modules_conventional']);
    }

    public function test_generated_modules_are_told_from_hand_written_ones_and_the_boilerplate_is_counted()
    {
        File::ensureDirectoryExists("{$this->appDir}/app/Modules/Blog/Livewire");
        File::put("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesTable.php", "<?php\nuse Authorize;\nreturn view('x')->layout('layout::admin');");
        File::put("{$this->appDir}/app/Modules/Blog/config.php", "<?php return ['permissions' => ['view articles']];");
        File::ensureDirectoryExists("{$this->appDir}/tests");
        File::put("{$this->appDir}/tests/BlogTest.php", "<?php Livewire::test('blog::articles-table');");

        $log = new GenerationLog("{$this->appDir}/storage/rapyd/generated.json");
        $before = $log->snapshot($this->appDir);
        File::put("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesView.php", str_repeat('x', 400));
        File::append("{$this->appDir}/app/Modules/Blog/Livewire/ArticlesTable.php", "\n// " . str_repeat('y', 36));   // 40 chars appended
        $entry = $log->record($this->appDir, $before, ['module' => 'Blog', 'model' => 'Article', 'component' => 'Articles']);

        $this->assertCount(2, $entry['files']);
        $this->assertSame(440, GenerationLog::chars([$entry]), 'a new file counts whole, an appended one its growth');

        $report = (new AiDevelopment($this->appDir))->report();
        $blog = $report['modules'][0];
        $this->assertSame(1, $blog['generated']['runs']);
        $this->assertSame(['Article'], $blog['generated']['models']);
        $this->assertSame(110, $blog['generated']['tokens']);
        $this->assertSame(110, $report['summary']['generated_tokens']);
        $this->assertSame(2, $report['summary']['generated_files']);
    }

    public function test_prompts_say_which_packages_they_need()
    {
        $prompts = (new AiDevelopment($this->appDir))->prompts();

        $this->assertNotEmpty($prompts);
        $crud = collect($prompts)->firstWhere('id', 'module-crud');
        $this->assertTrue($crud['available']);
        $excel = collect($prompts)->firstWhere('id', 'excel-pricelist');
        $this->assertFalse($excel['available']);
        $this->assertContains('zofe/documents-module', $excel['missing']);
    }

    public function test_the_command_prints_the_report_and_fails_until_ready()
    {
        $this->artisan('rpd:ai:develop', ['--path' => $this->appDir])
            ->expectsOutputToContain('Knows Rapyd Admin')
            ->expectsOutputToContain('Something to unlock')
            ->assertFailed();

        $this->artisan('rpd:ai:develop', ['--path' => $this->appDir, '--json' => true])->run();
        $this->artisan('rpd:ai', ['--path' => $this->appDir]);
        File::put("{$this->appDir}/.mcp.json", json_encode(['mcpServers' => ['laravel-boost' => []]]));

        $report = (new AiDevelopment($this->appDir))->report();
        $notOk = collect($report['capabilities'])->where('status', '!=', 'ok')->pluck('key')->all();
        $this->assertSame(class_exists(\Laravel\Boost\BoostServiceProvider::class) ? [] : ['boost'], $notOk);
    }
}
