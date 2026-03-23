# Deployment Runbook

This runbook is for handing off deployment and operations to another development team.

## Stack Overview

- Symfony 7.3 backend
- FrankenPHP + Caddy in container `php`
- MySQL in container `mysql`
- Optional local tools:
  - phpMyAdmin (`:8081`)
  - Mailhog/Mailpit (`:8025`)

Primary files:

- `compose.yaml`
- `compose.override.yaml`
- `frankenphp/Caddyfile`

## Environment Variables

Minimum required for app startup:

- `APP_SECRET`
- `DATABASE_URL` or compose mysql env set
- `JWT_PASSPHRASE`
- `JWT_TTL`
- `JWT_REFRESH_TTL`
- `DEFAULT_URI`

Feature-specific variables:

- R2 storage: `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`
- Bolt sync: `BOLT_OIDC_URL`, `BOLT_ORDERS_URL`, `BOLT_CLIENT_ID`, `BOLT_CLIENT_SECRET`, `BOLT_SCOPE`
- Optional OTP test shortcut: `OTP_FIXED_CODE`
- Optional PDF binary path: `WKHTMLTOPDF_PATH`

## Local Bring-Up

1. Build and start:
   - `docker compose build --pull --no-cache`
   - `docker compose up --wait`
2. Open:
   - App: `https://localhost`
   - Health: `https://localhost/health`
   - Swagger: `https://localhost/api/doc`
3. Database migration:
   - `docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction`

## Deployment Steps (Server/CI)

1. Pull repository and provision env secrets.
2. Build release image for `php` service.
3. Start containers:
   - `docker compose -f compose.yaml up -d`
4. Run migrations:
   - `docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction`
5. Warm cache if needed:
   - `docker compose exec php php bin/console cache:clear --env=prod`
6. Verify endpoints:
   - `/health`
   - `/api/open/ping`
   - `/api/doc`

## Post-Deploy Validation Checklist

- Health endpoint returns `status: ok`
- Auth flow works:
  - `/api/login/email`
  - `/api/login/otp`
  - `/token/refresh`
- Secured endpoint works with JWT:
  - `/api/secure/me`
- DB migrations are up to date
- Storage upload paths writable and signed URL generation works
- Mail transport reachable

## Operational Notes

- Access control is first-match based in `config/packages/security.yaml`.
- Refresh token payload key must be `refreshToken`.
- If Swagger UI fails, verify:
  - Nelmio bundle installed
  - routes in `config/routes/nelmio_api_doc.yaml`
  - framework assets enabled in `config/packages/framework.yaml`
