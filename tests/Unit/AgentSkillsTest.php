<?php

namespace Zofe\Rapyd\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;
use Zofe\Rapyd\Tests\TestCase;

/**
 * The guideline and the skills shipped for AI agents (resources/boost) stay correct: they exist,
 * carry the required frontmatter, mention only commands that exist and teach the non-interactive
 * generation (--fields).
 */
class AgentSkillsTest extends TestCase
{
    protected string $boost;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boost = dirname(__DIR__, 2) . '/resources/boost';
    }

    protected function skillFiles(): array
    {
        return collect(File::directories($this->boost . '/skills'))
            ->mapWithKeys(fn ($dir) => [basename($dir) => "{$dir}/SKILL.md"])
            ->all();
    }

    /** Every text an agent reads: the guideline, the skills, their references. */
    protected function agentTexts(): array
    {
        $texts = ['guidelines/core.blade.php' => File::get($this->boost . '/guidelines/core.blade.php')];
        foreach (File::allFiles($this->boost . '/skills') as $file) {
            $texts['skills/' . $file->getRelativePathname()] = File::get($file->getPathname());
        }

        return $texts;
    }

    public function test_the_guideline_and_the_two_skills_exist()
    {
        $this->assertFileExists($this->boost . '/guidelines/core.blade.php');
        $this->assertSame(['rapyd-module', 'rapyd-workflow'], array_keys($this->skillFiles()));
    }

    public function test_every_skill_has_a_name_and_a_description_in_its_frontmatter()
    {
        foreach ($this->skillFiles() as $name => $file) {
            $this->assertFileExists($file);
            $content = File::get($file);
            $this->assertMatchesRegularExpression('/\A---\n.*?\n---\n/s', $content, "{$name}: frontmatter");
            preg_match('/\A---\n(.*?)\n---\n/s', $content, $m);
            // Boost parses the frontmatter with symfony/yaml and skips the skill when it is invalid
            // (e.g. an unquoted ':' inside the description), so we parse it the same way.
            $front = Yaml::parse($m[1]);
            $this->assertSame($name, $front['name'] ?? null, "{$name}: name must match the folder");
            $this->assertGreaterThan(20, strlen($front['description'] ?? ''), "{$name}: a description");
        }
    }

    public function test_every_rpd_command_mentioned_exists()
    {
        $commands = array_keys(Artisan::all());
        foreach ($this->agentTexts() as $file => $text) {
            preg_match_all('/(?<![\w\-])rpd:[a-z][a-z:\-]*/', $text, $m);   // rpd:make, rpd:make:model…; not x-rpd::
            foreach (array_unique($m[0]) as $command) {
                $this->assertContains($command, $commands, "{$file} mentions {$command}, which does not exist");
            }
        }
    }

    public function test_the_texts_teach_the_non_interactive_generation()
    {
        // an agent must never run into the interactive rpd:make:model prompt: every example that creates
        // a model shows --fields, and the old keyword forms are not what is taught
        foreach ($this->agentTexts() as $file => $text) {
            $lines = collect(explode("\n", $text))->filter(fn ($l) => preg_match('/rpd:make (datatable|dataview|dataedit)\b/', $l));
            $this->assertCount(0, $lines, "{$file}: old keyword form");
        }
        $skill = File::get("{$this->boost}/skills/rapyd-module/SKILL.md");
        $this->assertStringContainsString('--fields=', $skill, 'the module skill shows --fields');
        $this->assertStringNotContainsString('asks for the fields interactively', $skill);
    }

    public function test_the_guideline_renders_as_blade_and_names_the_skills()
    {
        $rendered = \Illuminate\Support\Facades\Blade::render(File::get($this->boost . '/guidelines/core.blade.php'));
        $this->assertStringContainsString('rapyd-module', $rendered);
        $this->assertStringContainsString('rapyd-workflow', $rendered);
        $this->assertStringContainsString('x-rpd::', $rendered);
    }

    public function test_the_development_skills_are_links_to_the_package_ones()
    {
        $root = dirname(__DIR__, 2);
        foreach (array_keys($this->skillFiles()) as $name) {
            $link = "{$root}/.claude/skills/{$name}";
            $this->assertTrue(is_link($link), "{$link} is a symlink");
            $this->assertSame(realpath("{$this->boost}/skills/{$name}"), realpath($link));
        }
    }
}
