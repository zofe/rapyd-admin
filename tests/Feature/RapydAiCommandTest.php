<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Commands\RapydAiCommand;
use Zofe\Rapyd\Tests\TestCase;

class RapydAiCommandTest extends TestCase
{
    protected string $appDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appDir = sys_get_temp_dir() . '/rapyd-ai-' . uniqid();
        File::ensureDirectoryExists($this->appDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->appDir);
        parent::tearDown();
    }

    public function test_it_installs_the_skills_and_the_guideline_in_an_application()
    {
        $this->artisan('rpd:ai', ['--path' => $this->appDir])->assertSuccessful();

        $this->assertFileExists("{$this->appDir}/.claude/skills/rapyd-module/SKILL.md");
        $this->assertFileExists("{$this->appDir}/.claude/skills/rapyd-workflow/references/workflow.php");

        $agents = File::get("{$this->appDir}/AGENTS.md");
        $this->assertStringContainsString(RapydAiCommand::START, $agents);
        $this->assertStringContainsString('## Rapyd Admin', $agents);
        $this->assertStringContainsString(RapydAiCommand::END, $agents);
        $this->assertStringNotContainsString('@verbatim', $agents, 'the Blade guideline is rendered');

        $this->assertSame("@AGENTS.md\n", File::get("{$this->appDir}/CLAUDE.md"));
    }

    public function test_it_refreshes_the_guideline_and_keeps_the_rest_of_the_files()
    {
        File::put("{$this->appDir}/AGENTS.md", "# My app\n\nHouse rules.\n\n" . RapydAiCommand::START . "\nold\n" . RapydAiCommand::END . "\n\nMore.\n");
        File::put("{$this->appDir}/CLAUDE.md", "Project notes.\n");

        $this->artisan('rpd:ai', ['--path' => $this->appDir])->assertSuccessful();

        $agents = File::get("{$this->appDir}/AGENTS.md");
        $this->assertStringContainsString("# My app\n\nHouse rules.", $agents);
        $this->assertStringContainsString("More.", $agents);
        $this->assertStringNotContainsString("\nold\n", $agents);
        $this->assertSame(1, substr_count($agents, RapydAiCommand::START));

        $this->assertSame("Project notes.\n\n@AGENTS.md\n", File::get("{$this->appDir}/CLAUDE.md"));
        $this->artisan('rpd:ai', ['--path' => $this->appDir]);
        $this->assertSame(1, substr_count(File::get("{$this->appDir}/CLAUDE.md"), '@AGENTS.md'), 'imported once');
    }

    public function test_a_skill_modified_by_the_application_is_kept_unless_forced()
    {
        $this->artisan('rpd:ai', ['--path' => $this->appDir]);
        $skill = "{$this->appDir}/.claude/skills/rapyd-module/SKILL.md";
        File::append($skill, "\nOur own rule.\n");

        $this->artisan('rpd:ai', ['--path' => $this->appDir])->expectsOutputToContain('modified in the application, kept');
        $this->assertStringContainsString('Our own rule.', File::get($skill));

        $this->artisan('rpd:ai', ['--path' => $this->appDir, '--force' => true]);
        $this->assertStringNotContainsString('Our own rule.', File::get($skill));
    }
}
