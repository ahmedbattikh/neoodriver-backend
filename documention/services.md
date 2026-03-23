# Services Reference

This file documents core application services under `src/Service`.

## Application Services

### OtpLoginService

- File: `src/Service/OtpLoginService.php`
- Purpose: OTP request/verification flow for email login.
- Main behavior:
  - Generates 6-digit OTP with cooldown and TTL
  - Stores hashed OTP in `LoginOtp`
  - Sends OTP email via Symfony Mailer
  - Verifies and consumes OTP on success
- Key defaults:
  - TTL: 300 seconds
  - Cooldown: 60 seconds
  - Max attempts: 5
- Important parameter:
  - `otp.fixed_code` can force deterministic code for development/testing

### BoltService

- File: `src/Service/BoltService.php`
- Purpose: Integrates with Bolt OIDC and fleet orders APIs.
- Main behavior:
  - Retrieves access token (default or integration-specific credentials)
  - Fetches fleet orders in a time window
  - Logs request/response metadata
- Configuration inputs:
  - `bolt.oidc_url`, `bolt.orders_url`, `bolt.client_id`, `bolt.client_secret`, `bolt.scope`

### IntegrationSyncService

- File: `src/Service/IntegrationSyncService.php`
- Purpose: Synchronizes external integration orders into `PaymentOperation`.
- Main behavior:
  - Runs sync window and tracks status in `IntegrationSyncLog`
  - Loads integration accounts by integration
  - Retrieves Bolt orders and maps by `driver_uuid`
  - Upserts payment operations with normalized EUR amounts

### R2Client

- File: `src/Service/Storage/R2Client.php`
- Purpose: Cloudflare R2 object storage client for attachments.
- Main behavior:
  - Uploads objects with content type
  - Generates signed URLs
  - Creates user folder placeholders
- Configuration inputs:
  - `r2.account_id`, `r2.access_key_id`, `r2.secret_access_key`, `r2.bucket`, `r2.endpoint`

### PayslipPdfBuilder

- File: `src/Service/PayslipPdfBuilder.php`
- Purpose: Generates payslip PDF from Twig HTML template.
- Main behavior:
  - Renders `admin/payslip_pdf.html.twig`
  - Runs `wkhtmltopdf` command
  - Returns PDF binary content
- Runtime dependency:
  - `wkhtmltopdf` binary or `WKHTMLTOPDF_PATH` env override

### BackofficeMenuBuilder

- File: `src/Service/BackofficeMenuBuilder.php`
- Purpose: Builds backoffice navigation entries from route names.
- Main behavior:
  - Generates menu URLs via Symfony router
  - Groups configuration area links

## Service Wiring

Primary wiring file: `config/services.yaml`

- `autowire: true`, `autoconfigure: true`
- `App\` resource registration (excluding entities/controllers)
- Explicit constructor argument wiring:
  - `App\Service\Storage\R2Client`
  - `App\Service\BoltService`
- Controller services are tagged with `controller.service_arguments`

## Event Subscribers

Configured in `config/services.yaml`:

- `App\EventSubscriber\UserSubscriber`
- `App\EventSubscriber\PaymentOperationBalanceSubscriber`

These run as Doctrine subscribers and apply domain side effects on persistence events.
