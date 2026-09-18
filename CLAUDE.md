# rapyd-admin — package development

This is the source of `zofe/rapyd-admin`. What an application built on it must know is in
`resources/boost/guidelines/core.blade.php` (always loaded by Laravel Boost) and in the skills under
`resources/boost/skills/` (`rapyd-module`, `rapyd-workflow`); the same skills are linked in `.claude/skills` so they
apply here too. Read the guideline first:

@resources/boost/guidelines/core.blade.php

## Working on the package

- Bundled modules: `src/Modules/{Name}` (provider, models, migrations) + `app/Modules/{Name}` (Livewire, Views, Lang,
  wrapper traits); tests in `tests/Feature/{Name}Test.php`. The `x-rpd::` components are `resources/views/components`.
- Tests: `composer test` (PHPUnit + Testbench, the CI suite). Assets: `npm run build` → `public/`.
- A change to a generator, a component or a module is verified in a real app: `scripts/sandbox.sh` creates
  `.sandbox/app` (a fresh Laravel app with this repo as a path repository and Laravel Boost), then open the pages.
- `evals/evals.json` lists requests an agent should handle in the sandbox and what its answer must contain: run one in
  a separate session inside `.sandbox/app` to check the guideline and the skills work.
- Docs: `docs/*.md` (AI, MODULES, COMPONENTS, AUTH, THEMES). `rpd:context` and the guideline must say the same thing
  as the docs: when one changes, update the others.
- Releases: annotated tag `vX.Y.Z` after the Actions are green, `git push origin vX.Y.Z`; never touch legacy tags.

## Files that stay out of the Composer package

`.claude/`, `CLAUDE.md`, `AGENTS.md`, `evals/`, `scripts/` are development files (`export-ignore` in `.gitattributes`);
`resources/boost/` ships with the package.
