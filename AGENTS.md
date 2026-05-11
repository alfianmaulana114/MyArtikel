# MyArtikel — Agent Guide

Laravel 13 monolith with Alpine.js + Tailwind CSS 3 frontend, backed by MySQL. Two companion Node.js services live alongside.

## Quick start

```bash
composer setup          # full install: composer install → .env → key:generate → migrate → npm build
composer dev            # runs 3 processes: php artisan serve + queue:listen + npm run dev (via concurrently)
composer test           # php artisan config:clear && php artisan test
npm run build           # vite build
npm run dev             # vite dev server
```

## Architecture

- **`app/`** — Laravel MVC: Controllers (`Http/Controllers/`), Jobs (`Jobs/`), Models (`Models/`), Services (`Services/`)
- **`routes/`** — web.php (main UI), auth.php, bookmarks.php, jobs.php, pdf.php, api-summaries.php (Sanctum auth), console.php
- **`resources/views/`** — Blade templates with layouts at `layouts/`
- **`resources/js/`** — Alpine.js app (`app.js` + `bootstrap.js` + `theme.js`)
- **`resources/css/`** — Tailwind CSS entry point
- **`backend/`** — Standalone Node.js Express sync service (Sequelize + PostgreSQL + WebSocket, port 3000)
- **`article-ingestion/`** — Standalone TypeScript article ingestion package (Jest tests)

## Key commands

```bash
# Laravel
php artisan queue:manage status           # queue operations
php artisan queue:work --queue=high-priority,default
php artisan pdf:export-manager stats      # PDF export management

# Backend service
cd backend && npm run dev                 # nodemon app.js (port 3000)
cd backend && npm test                    # Jest

# Article ingestion
cd article-ingestion && npm test          # Jest
cd article-ingestion && npm run build     # tsc
```

## Testing

- **Laravel** (PHPUnit): `composer test` runs `config:clear` then `artisan test`. Tests in `tests/Feature/` and `tests/Unit/`. Uses SQLite `:memory:` in testing.
- **backend/**: Jest tests.
- **article-ingestion/**: Jest tests.

## Framework quirks

- **Queue driver**: `database` by default (`QUEUE_CONNECTION=database`). `sync` in testing.
- **Dark mode**: `[data-theme="dark"]` class strategy, toggled via Alpine.js in `resources/js/theme.js`.
- **PDF export**: Uses `barryvdh/laravel-dompdf`. Config at `config/dompdf.php`.
- **Summarization**: Gemini API (`config: GEMINI_API_KEY`) with local fallback and daily quota tracking.
- **Auth**: Laravel Breeze (Blade stack). Sanctum for API routes.
- **Testing**: `composer test` depends on `config:clear` running first — do not skip.
- **Laravel Pint** for PHP code style (no custom config file found, uses defaults).

## Sub-projects

Do not mix dependencies — each project has its own `package.json` and dependency tree:
- Root `package.json` — Vite + Tailwind + Alpine.js (frontend assets)
- `backend/package.json` — Express + Sequelize (sync service)
- `article-ingestion/package.json` — Article ingestion library

## Design notes

UI inspired by Frieren anime: earth tones, desaturated, cozy, reader-first, minimal distraction. Dark mode is a core feature.
