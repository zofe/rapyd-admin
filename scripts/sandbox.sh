#!/usr/bin/env bash
# A fresh Laravel app in .sandbox/app with this repository as a path repository and Laravel Boost,
# to try the generators, the components and the agent guideline / skills as a user would.
#
#   scripts/sandbox.sh            create (or reuse) the sandbox
#   scripts/sandbox.sh --fresh    delete and recreate it
#   scripts/sandbox.sh --serve    create if needed, then php artisan serve on port 8020
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SANDBOX="$ROOT/.sandbox"
APP="$SANDBOX/app"
PORT="${SANDBOX_PORT:-8020}"

if [[ "${1:-}" == "--fresh" ]]; then
    rm -rf "$APP"
fi

if [[ ! -d "$APP" ]]; then
    mkdir -p "$SANDBOX"
    echo "▶ creating a Laravel app in $APP"
    composer create-project laravel/laravel "$APP" --no-interaction --quiet

    cd "$APP"
    echo "▶ zofe/rapyd-admin from $ROOT (path repository)"
    composer config repositories.rapyd-admin path "$ROOT"
    composer require "zofe/rapyd-admin:@dev" -W --no-interaction --quiet
    php artisan rpd:make:setup --no-interaction

    echo "▶ laravel/boost"
    composer require laravel/boost --dev --no-interaction --quiet
    # boost:install is interactive (agents, features); in a sandbox we take the defaults for Claude Code
    php artisan boost:install --no-interaction || echo "boost:install needs a terminal: run it in $APP"

    echo
    echo "sandbox ready: $APP"
    echo "  login: admin@laravel / admin"
    echo "  guideline + skills of rapyd-admin: check AGENTS.md / CLAUDE.md and .claude/skills in the app"
    echo "  without Boost: php artisan rpd:ai"
fi

if [[ "${1:-}" == "--serve" || "${2:-}" == "--serve" ]]; then
    cd "$APP" && php artisan serve --port="$PORT"
fi
