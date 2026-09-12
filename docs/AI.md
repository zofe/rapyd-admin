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
New modules: `php artisan rpd:make all Model --module=Name`; follow the structure of the existing app/Modules/*.
Fields and pages use the x-rpd:: components (docs/COMPONENTS.md). Look changes: docs/THEMES.md.
Verify: `vendor/bin/phpunit`, then open the page you touched in the browser and take a screenshot.
```

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
