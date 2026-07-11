# HRMS

HRMS is a Laravel 13 application using Inertia, React, Vite, Fortify, passkeys, roles and permissions, queue monitoring, application health checks, log viewing, and developer tooling.

## Requirements

- PHP 8.3 or newer, with the extensions required by Laravel.
- Composer 2.x.
- Node.js 22, as defined in `.nvmrc`.
- npm.
- A supported database. Local defaults use SQLite; production should use MySQL, MariaDB, PostgreSQL, or another supported Laravel database.
- Redis for production queues, cache, and Laravel Horizon.
- A web server such as Nginx or Apache pointing to the `public` directory.
- A process supervisor for production queue workers and Horizon, such as Supervisor or systemd.

## Main Packages

Runtime packages:

- Laravel Horizon for Redis queue monitoring.
- Spatie Laravel Permission for roles and permissions.
- Spatie Laravel Query Builder for API filtering, sorting, and includes.
- MoneyPHP for money value objects.
- Opcodes Log Viewer for application log inspection.
- Spatie Laravel Health for health checks.
- Spatie Laravel Settings for typed application settings.

Development packages:

- Laravel Telescope.
- Laravel Pail.
- Laravel Pao.
- Laravel IDE Helper.
- Rector.
- Spatie Laravel Web Tinker.
- Larastan, Pint, PHPUnit, and Sail.

## Local Installation

Install PHP dependencies:

```bash
composer install
```

Install JavaScript dependencies:

```bash
npm install
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

For the default SQLite setup, create the database file if it does not exist:

```bash
touch database/database.sqlite
```

Run migrations, then run the application setup command:

```bash
php artisan migrate
php artisan app:setup --force
```

This creates the default local access accounts:

```text
Super Admin: super@example.com / password
Admin: admin@example.com / password
```

Start the development environment:

```bash
composer run dev
```

The Vite-only frontend server can also be started with:

```bash
npm run dev
```

## Local Quality Checks

Run the PHP test suite:

```bash
php artisan test
```

Run the full Composer check suite:

```bash
composer run ci:check
```

Run individual checks:

```bash
composer run lint:check
composer run types:check
npm run lint:check
npm run format:check
npm run types:check
```

## Package Setup Notes

The package config and migration files have been published for Horizon, Telescope, Spatie Permission, Spatie Health, Spatie Settings, Opcodes Log Viewer, and Spatie Web Tinker.

After a fresh install, run:

```bash
php artisan migrate
php artisan app:setup --force
```

Migrations apply the application tables plus package tables for settings, Telescope, permissions, and health checks. Setup creates the built-in roles, permissions, and initial access users.

The `App\Models\User` model uses Spatie's `HasRoles` trait, so role and permission APIs are available on users.

## Production Deployment

Clone or release the application to the server, then install optimized PHP dependencies without development packages:

```bash
composer install --no-dev --optimize-autoloader
```

Install frontend dependencies and build production assets:

```bash
npm ci
npm run build
```

Create and configure `.env` for production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms
DB_USERNAME=hrms
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Generate an app key only once for a new production environment:

```bash
php artisan key:generate
```

Run migrations, then run the one-time application setup command for a new production environment:

```bash
php artisan migrate --force

php artisan app:setup \
  --super-admin-name="Super Admin" \
  --super-admin-email="owner@company.com" \
  --admin-name="Admin" \
  --admin-email="admin@company.com" \
  --generate-passwords \
  --mail-passwords \
  --force
```

This creates the built-in access records and emails generated passwords to the setup users. Configure mail before running this command.

For later releases that add permissions, run:

```bash
php artisan migrate --force
php artisan app:sync-access --force
```

`app:sync-access` creates new product permissions and default roles when they are missing. It does not change existing role assignments by default; the organization's Super Admin decides which roles receive newly published permissions.

To intentionally reapply the code-defined initial permissions to controlled default roles, run:

```bash
php artisan app:sync-access --sync-role-defaults --force
```

Cache Laravel bootstrap files after environment variables are correct:

```bash
php artisan optimize
```

If config, routes, or views change during a release, refresh caches:

```bash
php artisan optimize:clear
php artisan optimize
```

Ensure writable directories are owned by the web server user:

```bash
chmod -R ug+rw storage bootstrap/cache
```

Point the web server document root to:

```text
public
```

## Queues And Horizon

Production should use Redis queues for Horizon:

```dotenv
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

Start Horizon under a process supervisor:

```bash
php artisan horizon
```

Restart Horizon after every deployment:

```bash
php artisan horizon:terminate
```

The process supervisor should automatically start Horizon again.

## Scheduler

Run Laravel's scheduler every minute from cron:

```cron
* * * * * cd /path/to/hrms && php artisan schedule:run >> /dev/null 2>&1
```

## Monitoring And Admin Tools

- Horizon is available at `/horizon`.
- Log Viewer is available at `/log-viewer`.
- Laravel Health can expose configured health endpoints.
- Telescope and Web Tinker are development dependencies. They are not installed when production uses `composer install --no-dev`.

Review the authorization gates and middleware before exposing Horizon or Log Viewer outside a trusted environment.

## Useful Commands

Generate IDE helper files in development:

```bash
php artisan ide-helper:generate
php artisan ide-helper:models
```

Run Rector in development:

```bash
vendor/bin/rector process --dry-run
```

View logs locally with Pail:

```bash
php artisan pail
```

## Deployment Checklist

- `.env` is configured for production.
- `APP_DEBUG=false`.
- `APP_KEY` is set and kept stable between releases.
- Database credentials are correct.
- Redis is configured for cache, sessions, queues, and Horizon.
- `composer install --no-dev --optimize-autoloader` has completed.
- `npm ci` and `npm run build` have completed.
- `php artisan migrate --force` has completed.
- `php artisan optimize` has completed.
- `storage` and `bootstrap/cache` are writable.
- Horizon is supervised and restarted with `php artisan horizon:terminate`.
- The scheduler cron entry is installed.
- Horizon, Log Viewer, health endpoints, and any admin routes are access-controlled.
