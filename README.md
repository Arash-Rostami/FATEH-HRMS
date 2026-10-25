# Intranet & Operations Platform

A modular, dual-panel employee intranet and operations suite built on **Laravel 12**, **Filament v5**, and **Livewire 4**. Persian-first (RTL) with Jalali calendar support; ships as an installable PWA.

> Behavior here is **verified against the codebase** — marketing prose that overstates implemented features is intentionally omitted (see Accuracy Notes).

## Overview

Two cooperating panels over one Laravel app:

- **Admin panel** (Filament v5) — system administration, content management, and configuration of every module.
- **Employee panel** (Livewire 4 dashboard) — the daily workspace: tabbed home, command palette, quick access, and per-module tools.

Access is role-scoped via Filament policies and per-module permissions.

### Highlights & Features

- **30 self-contained modules**, each independently deployable and configurable.
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
| Database | MySQL 8.0 (required — SQLite is not supported: migrations use MySQL collations and `JSON_LENGTH` columns) |
| Frontend | Tailwind CSS 4, Vite 7, Alpine.js, Material Symbols |
| Media/UI | Fancybox 6 (lightbox), Swiper 12 (carousels) |
| Calendar | morilog/jalali 3 (Jalali date helpers) |
| PWA | vite-plugin-pwa + Workbox 7 |
| Testing | PHPUnit 11, Faker, Mockery |

## Modules (30)

Canonical titles (Persian), icons, and descriptions live in `config/modules.php`; both panels iterate it via `moduleMeta()` and `moduleFilamentIcons()`.

| Category | Modules |
|---|---|
| Content & Communications | Announcements (`announce`), Feed (`feed`), Calendar (`calendar`), Gallery (`gallery`) |
| Organizational Knowledge | Reports (`reports`), Links (`links`), FAQ (`faq`), Notification Guide (`notification-guide`) |
| Human Resources | Profile (`profile`), Status (`status`), Skills (`skills`), Onboarding (`onboarding`), Documents (`documents`), Credentials (`credentials`), Job Ads (`ads`), Analytics (`analytics`) |
| Processes & Decisions | Suggestions (`suggestion`), Task Board (`taskboard`), Projects (`projects`), Tasksheet (`tasksheet`), Authorities (`authority`), DMS (`dms`), Tickets (`ths`) |
| Operations | Reservations (`reservation`), Messenger (`contact`), Channels (`channel`), Energy (`energy`) |
| Welfare & Tools | Reminders (`reminder`), AI Assistant (`ai-assistant`), Other Tools (`others`) |

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

PHP **8.2+** (extensions: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, fileinfo, curl, zip, gd, intl); **Composer 2**; **MySQL 8.0** (the setup below runs it in Docker); **Node 20.19+** and **npm**; **Git**. Redis is **not** needed — sessions, cache, and queue use the `database` driver.

## Installation

Four stages: **1.** install tools, **2.** start MySQL, **3.** install the app, **4.** run it. Paste each block top to bottom. Linux and macOS use a bash/zsh terminal; Windows uses **PowerShell** (open a new window after installing tools so `PATH` refreshes).

### 1. Install tools (once per machine)

**Ubuntu 22.04 / 24.04**

```bash
sudo apt update
sudo apt install -y software-properties-common curl unzip git ca-certificates
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-mysql
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
curl -fsSL https://get.docker.com | sudo sh
```

**macOS** (Homebrew from https://brew.sh required)

```bash
brew install php@8.2 composer node@20 git
brew link php@8.2 --force --overwrite
brew link node@20 --force --overwrite
brew install --cask docker
open -a Docker
```

**Windows** (PowerShell)

```powershell
winget install -e --id Git.Git
winget install -e --id PHP.PHP.8.2
winget install -e --id Composer.Composer
winget install -e --id OpenJS.NodeJS.LTS
winget install -e --id Docker.DockerDesktop
```

Open a **new** PowerShell window, start Docker Desktop once, then enable the PHP extensions:

```powershell
$php = Split-Path (Get-Command php).Source
Copy-Item "$php\php.ini-development" "$php\php.ini" -ErrorAction SilentlyContinue
(Get-Content "$php\php.ini") -replace '^;(extension_dir = "ext")$','$1' -replace '^;(extension=(curl|fileinfo|gd|intl|mbstring|openssl|pdo_mysql|zip|bcmath))$','$1' | Set-Content "$php\php.ini"
php -v
node -v
```

Check: `php -v` shows 8.2+ and `node -v` shows 20.19+ (all OS).

> ⚠️ **Windows flag — Horizon:** `laravel/horizon` requires `ext-pcntl` and `ext-posix`, which do not exist on Windows PHP. The app does **not** use Horizon locally (no Redis), so Windows installs skip that check with `--ignore-platform-req` (already in the Windows command below). Linux/macOS install normally.

### 2. Start MySQL 8 (Docker — identical on every OS)

Linux (prefix with `sudo`; on macOS/Windows plain `docker`):

```bash
sudo docker run -d --name fateh-mysql --restart unless-stopped -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=fateh -p 3306:3306 mysql:8.0
until sudo docker exec fateh-mysql mysqladmin ping -h127.0.0.1 -psecret --silent 2>/dev/null; do sleep 2; done
```

macOS:

```bash
docker run -d --name fateh-mysql --restart unless-stopped -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=fateh -p 3306:3306 mysql:8.0
until docker exec fateh-mysql mysqladmin ping -h127.0.0.1 -psecret --silent 2>/dev/null; do sleep 2; done
```

Windows (PowerShell):

```powershell
docker run -d --name fateh-mysql --restart unless-stopped -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=fateh -p 3306:3306 mysql:8.0
do { Start-Sleep 2; docker exec fateh-mysql mysqladmin ping -h127.0.0.1 -psecret --silent 2>$null } while ($LASTEXITCODE -ne 0)
```

The command returns once MySQL accepts connections. Already have MySQL 8 natively? Skip this stage, create a `fateh` database, and edit `DB_*` in `.env` after step 3's first line.

### 3. Install the app

Linux / macOS:

```bash
git clone <repository-url> fateh
cd fateh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link --force
npm install
npm run build
php artisan tinker --execute="App\Models\User::factory()->create(['name'=>'Admin','email'=>'admin@example.com','role'=>'developer']);"
```

Windows (PowerShell):

```powershell
git clone <repository-url> fateh
cd fateh
Copy-Item .env.example .env
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link --force
npm install
npm run build
php artisan tinker --execute="App\Models\User::factory()->create(['name'=>'Admin','email'=>'admin@example.com','role'=>'developer']);"
```

Seeding creates ~50 demo users and sample data for every module (takes about a minute). The last line creates your login: **`admin@example.com` / `password`**.

### 4. Run

```bash
php artisan serve
```

Open **http://127.0.0.1:8000/login** (employee panel) or **http://127.0.0.1:8000/admin** (admin panel) and sign in with `admin@example.com` / `password`. The `developer` role sees every admin module. Seeded demo users also use the password `password`.

Optional, each in its own terminal: `php artisan queue:work` (background jobs), `php artisan schedule:work` (hourly/daily sweeps), `npm run dev` (Vite HMR — replaces the `npm run build` assets).

> `composer dev` runs server + queue + `pail` + Vite in one terminal, but `pail` needs `ext-pcntl`, so use it on Linux/macOS only; on Windows start the processes above separately.

### Reset / stop

```bash
php artisan migrate:fresh --seed --force
```

Stop MySQL with `docker stop fateh-mysql` (Linux: `sudo docker stop fateh-mysql`); data persists until `docker rm -v fateh-mysql`.

### Troubleshooting

- `could not find driver` → the `pdo_mysql` PHP extension is disabled (Windows: rerun the `php.ini` block).
- `Connection refused` on migrate → MySQL is still starting; rerun the wait loop from stage 2.
- Blank page / missing CSS → run `npm run build`.
- Login says the account is inactive → only `status = active` users can sign in (the factory default).

## Available Commands

| Command | Purpose |
|---|---|
| `composer dev` | Run server + queue worker + log tail (`pail`) + Vite concurrently (Linux/macOS only — `pail` needs `pcntl`) |
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

`.env.example` already ships the local MySQL (Docker) values above. For production switch the three drivers to `redis` only if a Redis server and `ext-redis` are present.

- **`config/modules.php`** — the module registry (id, icon, title, category, descriptions + an optional `nav` mapping: `type` tab/route/url, `target`, with optional `key`/`title`/`icon` overrides for the compact workspace face). Edit to add/reorder modules; both panels pick it up automatically, and entries carrying `nav` automatically appear in the user-panel home surfaces (quick-access launchpad + hero gadget picker) via `TabPresenter::shortcuts()`.
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