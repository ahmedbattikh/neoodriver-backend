# API Reference

Base URL examples:

- Local HTTPS: `https://localhost`
- Health endpoint: `/health`

Auth model:

- Access token: `Authorization: Bearer <jwt>`
- Refresh endpoint payload field: `refreshToken`

## Public Endpoints

| Method | Path | Description | Auth |
|---|---|---|---|
| GET | `/health` | Health check and deploy tag/time | Public |
| GET | `/api/open/ping` | Public ping | Public |
| POST | `/api/register` | Register/update user and request OTP | Public |
| POST | `/api/login/email` | Request OTP code by email | Public |
| POST | `/api/login/otp` | Login with OTP and receive JWT + refresh token | Public |
| POST | `/api/login` | JSON login with email/password (firewall) | Public |
| POST | `/token/refresh` | Refresh JWT with refresh token | Public |
| GET | `/api/doc` | Swagger UI route | Public |
| GET | `/api/doc.json` | OpenAPI JSON route | Public |

## Secured Endpoints

### Secure Basic

| Method | Path | Description |
|---|---|---|
| GET | `/api/secure/ping` | Secured ping |
| GET | `/api/secure/me` | Current user profile + goals |
| PATCH | `/api/secure/user` | Partial update of user fields |

### Driver and Vehicle

| Method | Path | Description |
|---|---|---|
| POST | `/api/secure/driver` | Create/update driver profile and driver docs |
| POST | `/api/secure/vehicle` | Create vehicle and optional attachments |
| PATCH | `/api/secure/driver-documents` | Update documents and upload files |
| PATCH | `/api/secure/vehicle/{id}` | Update existing vehicle |

### Expense Notes

| Method | Path | Description |
|---|---|---|
| POST | `/api/secure/expense-notes` | Create expense note with invoice file |
| GET | `/api/secure/expense-notes` | List expense notes with pagination |
| GET | `/api/secure/expense-notes/{id}` | Expense note detail |

### Payments

| Method | Path | Description |
|---|---|---|
| GET | `/api/secure/driver-integrations` | Driver integration list inferred from operations |
| GET | `/api/secure/payments-summary` | Daily and integration summaries |
| GET | `/api/secure/payment-operations` | Filtered payment operation list |
| GET | `/api/secure/payment-batches` | Batch list by integration/date |
| GET | `/api/secure/payslip` | Payslip computation output |

### Driver Requests

| Method | Path | Description |
|---|---|---|
| GET | `/api/me/conge-requests` | List leave requests |
| POST | `/api/me/conge-requests` | Create leave request |
| GET | `/api/me/advance-requests` | List advance requests |
| GET | `/api/me/advance-requests/allowed-amount` | Compute allowed advance amount |
| POST | `/api/me/advance-requests` | Create advance request |

## Common Query Parameters

- Pagination: `page`, `size`
- Sorting: `sort`, `dir`
- Date filters:
  - Payments summary: `dateBegin`, `dateEnd`
  - Payment operations: `dateFrom`, `dateTo`
  - Payment batches: `periodFrom`, `periodTo`
  - Payslip: `dateBegin`, `dateEnd` (required)

## Request Body Formats

- `application/json`: login endpoints, user patch
- `multipart/form-data`: registration with photo, driver/vehicle/document uploads, expense note creation
- Form-data for requests:
  - Conge request: `amount`, `description`
  - Advance request: `amount`, `description`

## Testing Assets

- Postman collection: `postman/NeoDriver-API.postman_collection.json`
- Postman environment: `postman/NeoDriver-Local.postman_environment.json`
