# EvoGym

EvoGym is a gym management application with a Next.js frontend and a Symfony API. The local Docker stack includes Nginx/OpenResty, PHP-FPM, PostgreSQL, Redis, RabbitMQ, ClickHouse, Mailpit, MinIO, PgAdmin, and background workers.

## Documentation

- [DEVELOPMENT.md](DEVELOPMENT.md) is the source of truth for local development: environment files, Docker Compose usage, local domains and TLS certificates, first startup, database migrations, fixtures, daily start/stop commands, validation commands, test database preparation, and development troubleshooting.
- [PRODUCTION.md](PRODUCTION.md) documents production deployment.
- [observability/README.md](observability/README.md) documents the Grafana, Loki, Promtail, and Alertmanager stack.

## Local development

Use [DEVELOPMENT.md](DEVELOPMENT.md) for all local setup and validation commands. Keep development commands there instead of duplicating them in this README so the project has one source of truth.

## Project structure

- `nextjs/` contains the Next.js frontend.
- `symfony/` contains the Symfony API.
- `docker/` contains Docker, Nginx/OpenResty, PostgreSQL, Redis, ClickHouse, MinIO, and related service configuration.
- `observability/` contains Loki, Promtail, Alertmanager, and Grafana configuration.
