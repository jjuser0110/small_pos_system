<!-- Guidance for AI coding agents working on this Laravel project -->
# Copilot instructions for small_pos_system

This file gives concise, actionable context so an AI coding agent can be immediately productive.

- **Project type & key versions:** Laravel 10 app, PHP ^8.1, Vite for frontend (see `composer.json` and `package.json`).

- **Where to look first:**
  - Routes: `routes/` — this project splits route definitions into many files (e.g. `routes/product.php`, `routes/branch.php`).
  - Controllers: `app/Http/Controllers/` (namespace `App\Http\Controllers`).
  - Models: `app/Models/` (Eloquent models like `BatchItem.php`).
  - Providers: `app/Providers/RouteServiceProvider.php` — important because it dynamically `require_once`-loads all files in `routes/`.
  - Jobs/Console: `app/Jobs/`, `app/Console/Kernel.php` — scheduled tasks and registered console commands live here.

- **Architecture & routing patterns (big picture):**
  - `RouteServiceProvider::mapWebRoutes()` iterates `routes/` and requires each file. Adding a file to `routes/` auto-registers its routes.
  - Many route files use the string controller syntax (e.g. `ProductController@store`) and expect controllers under `App\Http\Controllers`.
  - Middleware like `auth` is commonly applied at the route file level.

- **Model & data patterns:**
  - Models live under `app/Models` and follow Eloquent conventions. Example: `app/Models/BatchItem.php` uses `fillable`, `SoftDeletes`, `belongsTo` relations and a polymorphic `morphMany('App\Models\StockLog', 'content')` relationship.
  - Look for `*_id` foreign keys and `fillable` arrays when adding or updating models.

- **Background work & scheduled commands:**
  - Console commands are registered in `app/Console/Kernel.php` (e.g. `DoClosings`). Jobs live in `app/Jobs/` (example: `DoClosing.php`).
  - If you add a scheduled job, register it in the Kernel and/or a command class.

- **Third-party integrations to be aware of:**
  - `maatwebsite/excel` used for exports (`app/Exports/*`).
  - `silber/bouncer` used for roles/permissions (check `app/Models/Role.php` and guards).
  - `barryvdh/laravel-snappy` for PDF generation.

- **Build / run / test commands (Windows PowerShell examples):**
  - Install PHP deps: `composer install`
  - Copy env (if needed): `copy .env.example .env` or let composer script handle it
  - Install Node deps and start Vite: `npm install` then `npm run dev` (or `npm run build` for production)
  - Link storage (if uploading files): `php artisan storage:link`
  - Migrate DB: `php artisan migrate` (use `--seed` when appropriate)
  - Run tests: `php vendor/bin/phpunit` (see `phpunit.xml` — in-memory sqlite is commented and can be enabled for faster tests)
  - Run server (local dev with Laragon): use Laragon GUI or `php artisan serve`.

- **Tests & CI hints:** `phpunit.xml` sets test environment variables (e.g. `CACHE_DRIVER=array`, `QUEUE_CONNECTION=sync`). If tests should use an in-memory database, uncomment the sqlite lines in `phpunit.xml` or set `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.

- **Common gotchas & patterns for editing code:**
  - Because route files are `require_once`d, route definitions may use both array-style controllers and string-style (`Controller@method`). Static analysis should resolve both forms to `App\Http\Controllers`.
  - When changing models with new relationships or columns, update migration files and factories under `database/factories/`.
  - Logging and runtime errors are written to `storage/logs/` — prefer reading logs there during debugging.

- **Where to update when adding features:**
  - New HTTP endpoints: add file in `routes/`, create controller under `app/Http/Controllers/`, add views in `resources/views/` and front-end assets under `resources/js` / `resources/css`.
  - New background tasks: add `Job` under `app/Jobs/` and register or schedule in `app/Console/Kernel.php`.

- **Prompting tips for AI agents working here:**
  - Mention the exact file to change (path) and the action (e.g. "Add route in `routes/product.php` to POST `/product/import` and implement `ProductController@import`").
  - When modifying routes, check `RouteServiceProvider` behavior and whether the file will be auto-loaded.
  - For model changes, list migration changes and which factory/test to update.

If anything in this guidance is unclear or you want examples expanded (e.g. a walkthrough adding a route + controller + migration), tell me which area to expand.
