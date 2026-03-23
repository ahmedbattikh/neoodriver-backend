# Configuration Reference

Main config directories:

- `config/packages/`
- `config/routes/`
- `config/services.yaml`

## Core Runtime

### framework.yaml

- App secret from `APP_SECRET`
- Trusted proxies from `TRUSTED_PROXIES`
- Session enabled with secure/samesite settings
- Assets enabled (`assets: ~`) for UI bundles such as Swagger UI

### routing.yaml (package)

- Uses `DEFAULT_URI` as router default URI for URL generation outside HTTP context

## Security and Authentication

### security.yaml

- User provider: Doctrine entity `App\Entity\User` by `email`
- Firewalls:
  - `admin`: session/form login
  - `backoffice`: session/form login
  - `main`: stateless JWT + refresh JWT
- Public access rules include:
  - `/api/login`, `/api/login/email`, `/api/login/otp`
  - `/api/register`
  - `/api/doc`, `/api/doc.json`
  - `/token/refresh`
  - `/health`
  - `/api/open/*`
- Other `/api/*` routes require full authentication

### lexik_jwt_authentication.yaml

- JWT key paths:
  - `config/jwt/private.pem`
  - `config/jwt/public.pem`
- Env variables:
  - `JWT_PASSPHRASE`
  - `JWT_TTL`

### gesdinet_jwt_refresh_token.yaml

- Refresh TTL from `JWT_REFRESH_TTL`
- Firewall: `main`
- Identity field: `email`
- Request token field: `refreshToken`

## API and Serialization

### fos_rest.yaml

- JSON format listeners for `/api` and `/health`
- View response listener enabled

### nelmio_api_doc.yaml

- OpenAPI documentation metadata
- Bearer security scheme `bearerAuth`
- Documented path patterns:
  - `/api` (excluding docs routes)
  - `/token/refresh`

### routes/nelmio_api_doc.yaml

- `GET /api/doc` using `nelmio_api_doc.controller.swagger_ui`
- `GET /api/doc.json` using `nelmio_api_doc.controller.swagger`
- Default area: `default`

## Data Layer

### doctrine.yaml

- DBAL URL from `DATABASE_URL`
- ORM mapping via PHP attributes in `src/Entity`
- Production cache pools for doctrine result/system cache

### doctrine_migrations.yaml

- Migration paths configured under `migrations/`

## Mail and Templates

### mailer.yaml

- Mailer DSN from environment

### twig.yaml and twig_component.yaml

- Twig runtime setup and UX Twig component support

## App Parameters and Env Mapping

Defined in `config/services.yaml`:

- OTP:
  - `OTP_FIXED_CODE`
- R2:
  - `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`
- Bolt:
  - `BOLT_OIDC_URL`, `BOLT_ORDERS_URL`, `BOLT_CLIENT_ID`, `BOLT_CLIENT_SECRET`, `BOLT_SCOPE`
- Network:
  - `TRUSTED_PROXIES`

## Keys and Secrets Checklist

Before transfer to another team/environment:

- Ensure JWT keys exist in `config/jwt/`
- Ensure `.env` secrets are provided out-of-repo
- Ensure DB credentials and R2/Bolt credentials are set in deployment secret store
