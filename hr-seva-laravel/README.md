# HR Seva — Laravel

Laravel migration of the HR Seva multi-tenant HR portal. The existing frontend (HTML/JS/CSS) is served unchanged from `public/`. All `/api/*` endpoints are handled by the legacy business logic in `legacy/backend/`, bridged through Laravel for routing, configuration, and future incremental refactoring.

## Frontend (Blade)

Portal pages are rendered from Blade templates in `resources/views/`:

- **Layouts**: `layouts/portal.blade.php`, `layouts/auth.blade.php`, `layouts/landing.blade.php`
- **Shared content**: `resources/views/pages/content/` — one partial per module (client + super-admin share the same content)
- **Routes**: `routes/portal.php` — preserves legacy URLs (`/client/client-employee-master.html`, etc.)

Static assets (CSS/JS/images) remain in `public/assets/` and `public/assets-02/`.

To regenerate Blade views from HTML sources (if you update the original static files):

```bash
# Restore HTML sources to public/client and public/super-admin first, then:
php scripts/convert-html-to-blade.php
```

## Stack

- **Laravel 13** (PHP 8.3+)
- **SQLite** (central + per-tenant databases under `storage/app/clients/`)
- **Frontend**: unchanged static HTML/JS from the original project (`public/client/`, `public/super-admin/`, `public/assets/`)

## Quick start

```bash
cd hr-seva-laravel
php artisan serve --host=127.0.0.1 --port=8012
```

### URLs

| Page | URL |
|------|-----|
| Landing | http://127.0.0.1:8012/ |
| Client login | http://127.0.0.1:8012/client/client-login.html |
| Super-admin login | http://127.0.0.1:8012/super-admin/super-admin-login.html |
| API health | http://127.0.0.1:8012/api/health |

### Default super-admin credentials

- `admin@hrseva.com` / `123456` (also `admin` / `123456`)

## Architecture

```
public/                  # Static frontend (UI unchanged)
legacy/backend/          # Thin wrappers delegating to app/Services/*
app/Services/Shift/      # Native shift/roster domain classes
storage/app/clients/     # SQLite databases (auto-created)
  app.db                 # Central DB (clients, subscriptions, auth)
  tenant_{id}/app.db     # Per-tenant HR data
```

### Multi-tenancy

Same as the original app:
- Client/employee tokens carry `clientId` in the JWT
- Super-admin selects tenant via `X-Client-Id` header
- Tenant DBs are SQLite files under `storage/app/clients/tenant_{id}/`

### Database switch (MySQL/PostgreSQL)

The legacy layer currently uses PDO SQLite directly. To switch databases:

1. Update connection settings in `.env`
2. Refactor `legacy/backend/api.php` `db_open()` to use Laravel's database layer
3. Convert `init_schema()` to Laravel migrations

SQLite is the default for now to preserve 1-to-1 behaviour with the existing deployment.

## API parity

All API endpoints are registered as explicit Laravel routes (`routes/api.php`, `routes/api-modules.php`). Business logic runs through native `app/Services/*` classes (generators, repositories) with legacy `api.php` bridges for backward compatibility. Request/response JSON shapes match the original app.

PHPUnit contract tests under `tests/Feature/Api/` cover auth, payroll pipeline parity, settlement modules, CRUD modules (employees, leaves, master data, incentives, overtime, compliance, loans, advances), normalized storage, and full API route reachability. CI runs these on SQLite and MySQL (see `.github/workflows/hr-seva-tests.yml`).

## Development

```bash
# PHP syntax check on legacy backend
php -l legacy/backend/api.php

# Run Laravel dev server
php artisan serve --host=127.0.0.1 --port=8012
```

## Reset database

```bash
rm -f storage/app/clients/app.db
rm -rf storage/app/clients/tenant_*
```

Databases are recreated automatically on the next API request.
