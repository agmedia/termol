# AG Shop

Admin-first webshop platform (Livewire admin), with planned Vue-based front templates (desktop + mobile/PWA).

## Requirements

- PHP `8.4`
- Composer `2.x`
- Node.js `22.x` and npm
- MySQL/MariaDB

## Local Development Setup

1. Install dependencies:
```bash
composer install
npm install
```

2. Create environment file:
```bash
cp .env.example .env
```

3. Configure `.env`:
- `APP_URL` (for Herd/local domain)
- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

4. Generate app key:
```bash
php artisan key:generate
```

5. After `.env` DB credentials are valid and the target database already exists, run migrations:
```bash
php artisan migrate
```

6. Seed data:
```bash
php artisan db:seed
```
Expected prompt:
```text
Seed large dummy webshop dataset (100 categories, 1000 products, 3000 orders, 500 users, etc.)? (yes/no) [no]:
```
- Answer `no` for standard local baseline data.
- Answer `yes` when you need a large dummy dataset for performance/testing.
- You can also force this non-interactively with `SEED_DUMMY_DATA=true`.

7. Link storage:
```bash
php artisan storage:link
```

8. Build/start frontend assets:
```bash
npm run dev
```

9. Start app/runtime processes (if needed):
```bash
php artisan serve
php artisan queue:work
```

## Default Seeded Users (Non-Superadmin)

These are default local users for quick testing:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@agshop.local` | `admin` |
| Editor | `editor@agshop.local` | `editor` |
| Customer | `customer@agshop.local` | `customer` |

Super-admin users are intentionally not listed in this table.

## API Access Notes

- Wholesale API endpoints are under `/api/v1/wholesale`.
- API access is controlled per user (`api_access_enabled`) and via token abilities.
- CLI token creation:
```bash
php artisan wholesale:token user@example.com client-name
```

## Asistent24 Integration Module (AGshop Connector)

- Health check: `GET /api/v1/asistent24/ping`
- OpenCart-compatible export: `GET /api/v1/asistent24/export-catalog`
- Generic custom export: `GET /api/v1/asistent24/export-custom`

### Required env/config

```env
ASISTENT24_CONNECTOR_ENABLED=true
ASISTENT24_STORE_KEY=pk_agshop_store_123
ASISTENT24_SYNC_SECRET=sk_agshop_sync_secret_123
ASISTENT24_ALLOWED_SKEW_SECONDS=300
ASISTENT24_EXPORT_INCLUDE_INACTIVE=false
ASISTENT24_EXPORT_LOCALE=en
```

### Signature format

- Message: `{store_key}|{timestamp}`
- Signature: `hex(HMAC_SHA256(message, ASISTENT24_SYNC_SECRET))`
- Query params: `store_key`, `timestamp`, `signature`
- Optional: `changed_since=YYYY-MM-DD HH:MM:SS`, `include_inactive=1`, `locale=hr`

### Example request (bash)

```bash
STORE_KEY="pk_agshop_store_123"
SYNC_SECRET="sk_agshop_sync_secret_123"
TS=$(date +%s)
SIG=$(printf "%s|%s" "$STORE_KEY" "$TS" | openssl dgst -sha256 -hmac "$SYNC_SECRET" -binary | xxd -p -c 256)

curl -s "http://agshop.test/api/v1/asistent24/export-catalog?store_key=${STORE_KEY}&timestamp=${TS}&signature=${SIG}" | jq .
curl -s "http://agshop.test/api/v1/asistent24/export-custom?store_key=${STORE_KEY}&timestamp=${TS}&signature=${SIG}" | jq .
```

## Useful Commands

```bash
php artisan optimize:clear
php artisan test
php artisan route:list
```
