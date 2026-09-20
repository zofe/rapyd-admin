# AI

Rapyd Admin is built with and for AI agents. Three things matter: a brief the agent can read (`rpd:context`), a
verification loop it can run alone (tests, browser), and one AI feature in the product itself (error analysis in
the log viewer).

## Briefing an agent: `rpd:context`

```bash
php artisan rpd:context                      # JSON: version, config, modules, routes, models, extension patterns
php artisan rpd:context --format=text        # readable
php artisan rpd:context --no-routes --no-models
```

Paste the output at the start of a session, or run it from Claude Code with `! php artisan rpd:context --format=text`.
A minimal `CLAUDE.md` / `AGENTS.md` for an app built on Rapyd Admin:

```
Rapyd Admin app (Laravel + Livewire 4 + Bootstrap 5). Read `php artisan rpd:context --format=text` before changing modules.
New modules: `php artisan rpd:make Things Thing --module=Name --fields="name,active:boolean"` (no prompts); follow the structure of the existing app/Modules/*.
Fields and pages use the x-rpd:: components (docs/COMPONENTS.md). Look changes: docs/THEMES.md.
Verify: `vendor/bin/phpunit`, then open the page you touched in the browser and take a screenshot.
```

## Guideline and skills for the agents of your application

An agent working in an application built on Rapyd Admin gets the framework's conventions from two things shipped
with the package, in `resources/boost/`:

- **the guideline** (`guidelines/core.blade.php`): short rules, always loaded (modules, `x-rpd::` components, workflows
  instead of a hand-written `status`, Livewire 4, how to verify);
- **the skills** (`skills/rapyd-module`, `skills/rapyd-workflow`): the procedures, loaded when the task needs them:
  creating or extending a module around `rpd:make`, designing and implementing a state machine (with reference files).

With [Laravel Boost](https://laravel.com/docs/boost) they reach your application by themselves:

```bash
composer require laravel/boost --dev
php artisan boost:install          # picks the guideline and the skills of every installed package, rapyd-admin included
php artisan boost:update           # after a package update; --discover looks for packages added since
```

Boost writes the guideline into `AGENTS.md` / `CLAUDE.md` (and the files of the agents you chose) and the skills into
`.claude/skills` or the equivalent. In CI, in non-interactive mode, `boost:update --discover` may skip a package: add
`zofe/rapyd-admin` to the packages of `boost.json` by hand in that case.

Without Boost:

```bash
php artisan rpd:ai                 # skills → .claude/skills, guideline → AGENTS.md (between markers), @AGENTS.md in CLAUDE.md
php artisan rpd:ai --force         # overwrite a skill your application has modified
```

`rpd:ai` refreshes the guideline block on every run and keeps the rest of `AGENTS.md`; a skill you edited in
`.claude/skills` is left alone unless `--force`. Run it again after updating the package.

The same skills are used to develop the package itself (`.claude/skills` of the repository links to them): one source,
no copies to keep in sync.

### Is the application ready? `rpd:ai:status`

```bash
php artisan rpd:ai:status          # exit code 0 when everything is in place
php artisan rpd:ai:status --json   # for scripts and CI
```

It reads the files of the application, calls nothing, and reports three things:

- **the agent tooling**: guideline present and current (Boost or `rpd:ai`), each skill present, current or customised
  by the application, `CLAUDE.md` importing `AGENTS.md`, Boost installed, MCP servers in `.mcp.json`. Every row that is
  not ok names the command that fixes it;
- **the context the agent loads**, estimated as characters / 4: one memory file (an agent reads `AGENTS.md` or
  `CLAUDE.md`, not both) plus the skill descriptions are resident in every session; skill bodies are read on demand;
- **the modules of the application** (`app/Modules/*`) against the conventions the agent is taught: full-page
  components without `Authorize`, workflows, permissions declared in `config.php`, `Authorizations/` and `Limits/`
  classes, tests mentioning the module.

The same report is a page of the admin with [ai-module](https://github.com/zofe/ai-module) ("AI readiness", route
`ai/status`, permission `view ai status`), with a plain-words view for developers new to AI tooling and an advanced
view with every row, plus what the AI runtime of the application (widget, tools, provider) costs per day.

## The verification loop

- Package tests: `vendor/bin/phpunit` (PHPUnit + Orchestra Testbench, the CI suite). Livewire pages are tested with
  `Livewire::test('module::component')`, permissions with `assertForbidden()` / `assertNotFound()`.
- Browser: a Playwright script that logs in and screenshots the pages you touched, in light and dark mode. Keep
  credentials in env variables and point it at localhost only.
- Themes: `php artisan rpd:theme:check`.

## AI error analysis in the log viewer

Set `ANTHROPIC_API_KEY` (and optionally `LOG_AI_MODEL`, default `claude-haiku-4-5-20251001`) and an **Analyze with AI** button appears in the stack-trace modal of `/log/app`: probable cause and suggested fix, computed server-side, stack truncated to 3000 characters, visible to admins only. **Copy for AI** copies text and stack for any external assistant. Each analysis costs about $0.001 with Haiku.

<details><summary>Guida in italiano</summary>

### Prerequisiti

Nessuna dipendenza aggiuntiva. Il modulo usa direttamente l'API di Anthropic.

Aggiungi al tuo `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-...
```

Il provider e il modello sono configurabili:

```env
# Modello usato per l'analisi (default: claude-haiku-4-5-20251001)
LOG_AI_MODEL=claude-haiku-4-5-20251001
```

Se `ANTHROPIC_API_KEY` non è impostato, il pulsante "Analyze with AI" non compare — il modulo funziona normalmente senza di esso.

---

### App Logs — cosa è cambiato

Vai su `/log/app` (o dalla voce di menu Log → App Logs).

**Filtro per livello**

Accanto al selettore del file di log compare un dropdown `level`. Seleziona `error`, `warning`, `critical` ecc. per filtrare le righe. I livelli disponibili sono: `error`, `critical`, `alert`, `emergency`, `warning`, `notice`, `info`, `debug`, `processed`, `failed`.

**Badge colorato**

Il livello di ogni riga ora appare come badge colorato invece di testo plain:
- Rosso → `error`, `critical`, `alert`, `emergency`
- Giallo → `warning`, `failed`
- Blu → `info`, `notice`, `debug`, `processed`

**Analisi AI di un errore**

1. Clicca sull'icona elenco (☰) nella colonna Stack di una riga con stack trace.
2. Si apre il modal con il testo dell'errore e lo stack completo.
3. Se `ANTHROPIC_API_KEY` è configurato, compare il pulsante **Analyze with AI**.
4. Cliccalo — il pulsante mostra "Analyzing..." mentre la chiamata è in corso.
5. L'analisi appare nel modal stesso, sotto lo stack trace, con:
   - **Causa probabile** — spiegazione sintetica dell'errore
   - **Fix suggerito** — azione specifica e applicabile

Il pulsante **Copy for AI** (sempre visibile) copia testo+stack nella clipboard per incollarlo in qualsiasi AI esterna.

> L'analisi usa il modello configurato in `LOG_AI_MODEL`. Haiku è sufficiente per la maggior parte degli errori Laravel ed è il più economico.

---

## Note tecniche

### Sicurezza

- `ANTHROPIC_API_KEY` non viene mai esposta al frontend. La chiamata API avviene server-side nella Livewire action `analyzeError()`.
- Lo stack trace inviato all'API è troncato a 3000 caratteri.
- Il pulsante "Analyze with AI" è visibile solo agli utenti con permesso `admin` (ereditato dalla protezione di `LogAppTable`).

### Costi stimati

Ogni analisi di errore con claude-haiku-4-5 costa ~0.001$ (input ~500 token, output ~200 token).

### Personalizzare il modello

Per usare un modello più potente su errori complessi, aggiorna il `.env`:

```env
LOG_AI_MODEL=claude-sonnet-4-6
```

</details>
