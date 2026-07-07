# EvoGym

EvoGym is a gym management application with a Next.js frontend and a Symfony API. The local Docker environment includes Nginx/OpenResty, PHP-FPM, PostgreSQL, Redis, RabbitMQ, ClickHouse, Mailpit, MinIO, PgAdmin, the frontend, and Messenger workers.

## Requirements

- Git;
- Docker Engine or Docker Desktop;
- a recent Docker Compose v2 release;
- `mkcert` for trusted local certificates, or OpenSSL as a fallback;
- permission to add local domains to the system hosts file.

PHP, Composer, Node.js, and pnpm do not need to be installed on the host.

## First launch on a clean machine

Clone the repository and run the development bootstrap script:

```bash
git clone https://github.com/yurkevichkirill/gym-project.git
cd gym-project
bash scripts/dev-setup.sh
```

The equivalent Make target is:

```bash
make setup
```

The setup is idempotent and can be run again after an interrupted installation. It:

1. creates `.env`, `symfony/.env`, and `nextjs/.env.local` when they are missing;
2. generates local application, database, RabbitMQ, ClickHouse, MinIO, PgAdmin, and JWT secrets;
3. keeps existing non-placeholder secrets unchanged;
4. selects `docker-compose.dev.yml` automatically through `COMPOSE_FILE`;
5. generates TLS certificates with `mkcert` or OpenSSL;
6. builds the PHP and frontend development images;
7. starts and waits for the infrastructure services;
8. installs Composer dependencies and generates the JWT key pair;
9. runs Symfony auto-scripts and Doctrine migrations;
10. creates the private MinIO bucket and starts the complete stack.

### Local domains

The script does not edit system files. Add this line to `/etc/hosts` on Linux/macOS or to `C:\Windows\System32\drivers\etc\hosts` on Windows:

```text
127.0.0.1 evogym.local api.evogym.local
```

When `mkcert` is unavailable, the script creates a self-signed OpenSSL certificate. The browser will display a warning until that certificate is trusted manually.

### Stripe test credentials

A clean setup uses non-secret local Stripe placeholders, so the application can start without a Stripe account. Replace these values before testing card payments or webhooks:

- `STRIPE_SECRET_KEY`, `STRIPE_PUBLIC_KEY`, and `STRIPE_WEBHOOK_SECRET` in `.env`;
- `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` in `nextjs/.env.local`.

Run the setup script again after changing the root Stripe values to synchronize the Symfony and Next.js environment files, then recreate the affected services:

```bash
bash scripts/dev-setup.sh
docker compose up -d --force-recreate php-fpm frontend messenger-worker payment-worker
```

## Local URLs

| Service | URL |
| --- | --- |
| Frontend | `https://evogym.local` |
| Symfony API | `https://api.evogym.local` |
| OpenAPI JSON | `https://api.evogym.local/api/doc.json` |
| Direct Next.js server | `http://127.0.0.1:3000` |
| PgAdmin | `http://127.0.0.1:8080` |
| RabbitMQ management | `http://127.0.0.1:15672` |
| Mailpit | `http://127.0.0.1:8025` |
| MinIO console | `http://127.0.0.1:9001` |
| MinIO API | `http://127.0.0.1:9005` |
| ClickHouse HTTP API | `http://127.0.0.1:8123` |

Published ports and credentials are configured in the root `.env`. They bind to `127.0.0.1` by default.

## Daily development commands

```bash
make up       # start or reconcile the stack
make down     # stop containers without deleting data
make ps       # show service state
make logs     # follow Nginx, PHP-FPM, and frontend logs
make migrate  # apply Doctrine migrations
```

The direct Docker Compose equivalents remain available:

```bash
docker compose up -d
docker compose down
docker compose ps
docker compose logs -f nginx php-fpm frontend
```

Run Symfony and Composer commands through the PHP container:

```bash
docker compose exec -it php-fpm sh
```

## Validation commands

```bash
make test
make phpstan
make typecheck
make lint
make build
```

Or run them directly:

```bash
docker compose exec php-fpm php bin/phpunit
docker compose exec php-fpm vendor/bin/phpstan analyse
docker compose exec frontend pnpm typecheck
docker compose exec frontend pnpm lint
docker compose exec frontend pnpm build
```

## Common startup problems

### Compose starts only the base services

The generated root `.env` must contain:

```dotenv
COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
COMPOSE_PATH_SEPARATOR=:
```

Recreate `.env` from `.env.example` or run `bash scripts/dev-setup.sh` again.

### Nginx exits immediately

Check that both certificate files exist:

```text
certs/evogym.local.pem
certs/evogym.local-key.pem
```

Then inspect the logs:

```bash
docker compose logs nginx
```

### A service cannot connect to another container

Container DSNs must use Compose service names such as `postgres`, `redis`, `rabbitmq`, `clickhouse`, `mailpit`, and `minio`, not `localhost`. Running the setup script synchronizes the generated credentials between the root and Symfony environment files.

### Ports are already in use

Change the conflicting published port in `.env`, then recreate the stack:

```bash
docker compose up -d --force-recreate
```

### Frontend environment changes are not applied

Next.js reads public variables when the process starts. Recreate the frontend container:

```bash
docker compose up -d --force-recreate frontend
```

## Observability

The logging and alerting stack is managed separately:

```bash
cd observability
cp .env.example .env
# Replace the Grafana password and ALERTMANAGER_WEBHOOK_URL.
docker compose -f docker-compose.observability.yml config --quiet
docker compose -f docker-compose.observability.yml up -d
```

See `observability/README.md` for details.

## Reset local data

Stopping the project preserves local data:

```bash
docker compose down
```

A full reset is destructive:

```bash
docker compose down -v
rm -rf docker/db/data data/redis data/minio
```

After a full reset, run `bash scripts/dev-setup.sh` again.
