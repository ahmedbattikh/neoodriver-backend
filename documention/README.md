# NeoDriver Backend Handover Documentation

This folder is the technical package for the next development team.

## Documents

- [API Reference](./api.md)
- [Services Reference](./services.md)
- [Configuration Reference](./configuration.md)
- [Deployment Runbook](./deployment.md)

## Scope

- Public and secured API endpoints currently exposed by the backend
- Core application services in `src/Service`
- Runtime and framework configuration under `config/`
- Local and production-style deployment with Docker Compose

## Important Notes

- API auth is JWT-based with refresh token support.
- Main secured APIs are under `/api/secure` and `/api/me`.
- Swagger routes are configured at `/api/doc` and `/api/doc.json`.
- Postman assets are available in `postman/`.
