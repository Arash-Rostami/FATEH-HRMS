# Intranet & Operations Platform

A modular, dual-panel employee intranet and operations suite built on **Laravel 12**, **Filament v5**, and **Livewire 4**. Persian-first (RTL) with Jalali calendar support; ships as an installable PWA.

> Behavior here is **verified against the codebase** — marketing prose that overstates implemented features is intentionally omitted (see Accuracy Notes).

## Overview

Two cooperating panels over one Laravel app:

- **Admin panel** (Filament v5) — system administration, content management, and configuration of every module.
- **Employee panel** (Livewire 4 dashboard) — the daily workspace: tabbed home, command palette, quick access, and per-module tools.

Access is role-scoped via Filament policies and per-module permissions.

### Highlights & Features

- **23 self-contained modules**, each independently deployable and configurable.
- **Dual panel** sharing one registry — add/reorder a module in `config/modules.php` once; it appears in both.
- **Command palette** + **deep-linking** (`FocusOnRecord`) for keyboard-driven nav and URL-pinned records.
- **Global + individual search** — boolean full-text where supported, scoped per module.
- **Theming** — dark/light mode with 15 color themes, synced across open tabs.
- **PWA** — installable, offline shell, persistent tool dock that survives SPA navigation; Alpine-driven polling for unread counts, polls, and presence.
- **Security** — Laravel Fortify auth, encrypted credential storage (`Crypt`), HTML sanitization on rich text, IP-based internal/external routing.
- **Patterns** — Action / Validator / Presenter / Service (Livewire); Schemas / Actions / Pages (Filament); reusable traits (`HasNudgeTracking`, `HasCountdown`, `FocusOnRecord`, `HasPublicAssetUrl`).

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Laravel Fortify (auth) |
| Admin panel | Filament v5 |
| User panel | Livewire 4.1 (islands, `wire:navigate`) |
| Database | MySQL 5.7+ / 8.0 (SQLite for quick local dev) |
| Frontend | Tailwind CSS 4, Vite 7, Alpine.js, Material Symbols |
| Media/UI | Fancybox 6 (lightbox), Swiper 12 (carousels) |
| Calendar | morilog/jalali 3 (Jalali date helpers) |
| PWA | vite-plugin-pwa + Workbox 7 |
| Testing | PHPUnit 11, Faker, Mockery |

## Modules (23)

Canonical titles (Persian), icons, and descriptions live in `config/modules.php`; both panels iterate it via `moduleMeta()` and `moduleFilamentIcons()`.

| Category | Modules |
|---|---|
| Content & Communications | Announcements (`announce`), Feed (`feed`), Calendar (`calendar`), Gallery (`gallery`) |
| Organizational Knowledge | Reports (`reports`), Links (`links`), FAQ (`faq`) |
| Human Resources | Profile (`profile`), Onboarding (`onboarding`), Documents (`documents`), Credentials (`credentials`), Job Ads (`ads`) |
| Processes & Decisions | Suggestions (`suggestion`), Task Board (`taskboard`), DMS (`dms`), Tickets (`ths`) |
| Operations | Reservations (`reservation`), Messenger (`contact`), Channels (`channel`), Energy (`energy`) |
| Governance | Authorities (`auth`) |
| Welfare & Tools | Live Radio (`radio`), Other Tools (`others`) |

## Architecture

```
app/
├── Filament/Resources/    Admin panel (Schemas/Actions/Pages) + Widgets/
├── Livewire/Dashboard/    Employee panel: Tab/, <Module>/+Actions/, Navbar/
├── Models/  Services/  Rules/  Enums/  Traits/
config/modules.php         Single module registry → BOTH panels
resources/{views,css,js}/  Blade, Tailwind, Alpine components
```

- **Cross-cutting traits**: `HasNudgeTracking` (read/fresh state), `HasCountdown` (event countdown), `FocusOnRecord` (deep-link pin), `HasPublicAssetUrl` (safe asset URLs).
- **Search**: a `Search/` service hierarchy powers global and per-module search (boolean full-text where supported).

## Requirements

PHP **8.2+** with standard Laravel extensions (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath, fileinfo); **MySQL 5.7+ / 8.0** recommended (SQLite works for a quick local run); **Node 18+** and **npm**; writable `storage/` and `bootstrap/cache/`.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Edit `.env` first: `DB_*`, `APP_URL`, and brand keys (consumed by `config/app.php`); use `npm run dev` for HMR. One-shot alternative:

```bash
composer setup
```

## Available Commands

| Command | Purpose |
|---|---|
| `composer dev` | Run server + queue worker + log tail (`pail`) + Vite concurrently |
| `php artisan serve` | Start the dev server |
| `npm run dev` | Vite dev server with HMR |
| `npm run build` | Production Vite build (+ PWA service worker) |
| `php artisan migrate --seed` | Run migrations + seeders |
| `php artisan test` | Run the test suite (PHPUnit) |
| `php artisan optimize` | Config + route + view cache for production |
| `php artisan tinker` | REPL |

## Configuration

Key `.env` values (see `.env.example`):

```
APP_NAME=...  APP_ENV=local  APP_URL=http://localhost
DB_CONNECTION=mysql  DB_HOST=127.0.0.1  DB_DATABASE=...
SESSION_DRIVER=database  QUEUE_CONNECTION=database  CACHE_STORE=database  MAIL_MAILER=log
```

- **`config/modules.php`** — the module registry (id, icon, title, category, descriptions). Edit to add/reorder modules; both panels pick it up automatically.
- **`config/app.php`** — env-driven brand keys (name, company/organization, slogan, logo, version, support) consumed across Blade views.
- **Theme / PWA** — Tailwind 4 theme tokens and the Workbox PWA config live under `resources/` and `vite.config.js`.

## Accuracy Notes

The following are **not** implemented and are deliberately not claimed here (they appear in some older prose but not in the code):

- Calendar seasonal theming, birthday countdown, and congratulation messages/SMS.
- Email blast notifications on announcement creation.
- Server-side PDF/document generation (admins upload PDF/DOC; no generator).
- Documents approval workflow / reviewer state (presence of a file is the "approved" state).
- Reservation building/gate/car-entry integration and interactive office/parking maps.
- Radio admin station management (stations come from the public API + hardcoded fallback).
- Google Translate language picker (fa→en only).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). This application's own source is provided under the same license unless stated otherwise in a deployed environment.