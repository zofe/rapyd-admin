# Security

## Reporting a vulnerability

Please do not open a public issue for a security problem.

Use GitHub's private report: **Security → Report a vulnerability** on
[github.com/zofe/rapyd-admin](https://github.com/zofe/rapyd-admin/security/advisories/new). Only the maintainer
sees it. You get an answer within a week, usually sooner; the fix comes out as a patch release with an advisory
that names the versions affected and credits you, unless you prefer not to be named.

A report is most useful with the version (`composer show zofe/rapyd-admin`), the steps to reproduce on a stock
install (`composer create-project laravel/laravel`, `composer require zofe/rapyd-admin`, `php artisan rpd:make:setup`),
and what you were able to see or do that you should not.

## Supported versions

| Version | Security fixes |
|---|---|
| 9.x | yes |
| 0.12 and earlier (`zofe/rapyd-admin` 0.x, `zofe/rapyd-livewire`, `zofe/rapyd`) | no — please upgrade |

## What a stock install exposes

- Registration is closed by default; `RAPYD_REGISTRATION=true` opens it (`config/fortify.php`). Open, a visitor
  can create an account with no role, which sees nothing but the home.
- The first admin is `admin@laravel`, with a password printed once by `rpd:make:setup` (or `RAPYD_ADMIN_PASSWORD`).
  Change it, or delete the account, before the application is reachable.
- Rich-text values go through `rapyd_clean_html()` before display; `rapyd.html_allowed` in `config/rapyd.php` is
  the allow-list.

## Past advisories

- **9.20.0 (2026-09-28)** — a company member could reach, edit and delete any account, the super admin included,
  through the users modal of the Companies module. Reported against a stock install; fixed in 9.20.0, details in
  the [CHANGELOG](CHANGELOG.md#9200---2026-09-28).
