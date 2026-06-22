# AGENTS.md

## Cursor Cloud specific instructions

### Stack overview
HR Seva is a **zero-build PHP + SQLite multi-tenant HR portal**. There is no Composer/npm
build step for the app itself — static HTML/JS/CSS is served directly and all `/api/*`
routes are handled by the procedural monolith `backend/api.php`.

- Marketing landing: `index.html`
- Client portal: `client/`
- Super-admin portal: `super-admin/`
- Backend API: `backend/api.php` (routing, auth, payroll, compliance, face attendance)
- Dev router: `router.php` (serves static files, routes `/api/*` to `backend/api.php`)
- Prod routing: `.htaccess` (Apache only — NOT applied by the PHP dev server)

### Running the app (dev)
Start the PHP built-in server from the repo root:

```
php -S 127.0.0.1:8012 router.php
```

URLs:
- Landing: `http://127.0.0.1:8012/`
- Client login: `http://127.0.0.1:8012/client/client-login.html`
- Super-admin login: `http://127.0.0.1:8012/super-admin/super-admin-login.html`

Default super-admin credentials (seeded automatically): `admin@hrseva.com` / `123456`
(also `admin` / `123456`). The login API field names are `username` and `password`.

### Database (auto-created, gitignored)
No manual DB setup is needed. SQLite databases are created on first request under
`storage/clients/`:
- `storage/clients/app.db` — central DB (clients, subscriptions, staff users, SMTP)
- `storage/clients/tenant_{id}/app.db` — per-tenant DB (employees, payroll, attendance)

To reset to a clean slate, stop the server and delete `storage/clients/app.db` and any
`storage/clients/tenant_*` directories; they are recreated on the next request. The whole
`storage/clients/**` tree is gitignored (except `.gitkeep`).

### Multi-tenancy gotcha
Super-admin requests select a tenant via the `X-Client-Id` header (the portal injects it
from the session via `assets/js/app-common.js`). When calling tenant endpoints (e.g.
`/api/employees`) directly with curl, you MUST pass `X-Client-Id: <clientId>` plus the
`Authorization: Bearer <token>` header, otherwise `db()` points at the central DB.

### Lint / test / build
- There is no build step, no Composer dependencies, and no PHPUnit test suite in the repo.
- Lint PHP syntax with: `php -l backend/api.php` (repeat for other `.php` files).
- An optional Playwright UI audit lives under `qa/` but that directory is gitignored and
  not part of the committed repo; ignore it unless you intentionally recreate it.

### Notes
- `php -S` does NOT apply `.htaccess`; security headers/route blocks only apply under Apache.
- Face attendance pages need a real browser with camera + `localhost`/`https` and pull
  `face-api.js` models from a CDN on first load (won't work headless).
- SMTP is optional/disabled by default; copy `backend/mail-config.example.php` to
  `backend/mail-config.php` only if you need outbound email.
