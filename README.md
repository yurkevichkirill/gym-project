# EvoGym

EvoGym is a gym management application with a Next.js frontend and a Symfony API. The local Docker environment includes Nginx/OpenResty, PHP-FPM, PostgreSQL, Redis, RabbitMQ, ClickHouse, Mailpit, MinIO, PgAdmin, Stripe CLI, the frontend, and Messenger workers.

## Requirements

- Git;
- Docker Engine or Docker Desktop;
- a recent Docker Compose v2 release;
- a Stripe sandbox secret key (`sk_test_...`) and publishable key (`pk_test_...`);
- `mkcert` for trusted local certificates, or OpenSSL as a fallback;
- permission to add local domains to the system hosts file.

PHP, Composer, Node.js, pnpm, and Stripe CLI do not need to be installed on the host.

## First launch on a clean machine

Clone the repository and run the clean-environment bootstrap:

```bash
git clone https://github.com/yurkevichkirill/gym-project.git
cd gym-project
bash scripts/dev-bootstrap.sh
```

The equivalent Make target is:

```bash
make setup
```

When Stripe credentials are not already present in `.env`, the bootstrap securely prompts for the sandbox secret key and then for the publishable key. The secret key input is not echoed to the terminal.

The bootstrap is idempotent and can be run again after an interrupted installation. It:

1. creates `.env`, `symfony/.env`, and `nextjs/.env.local` when they are missing;
2. stores and synchronizes the supplied Stripe sandbox credentials;
3. generates local application, database, RabbitMQ, ClickHouse, MinIO, PgAdmin, and JWT secrets;
4. keeps existing non-placeholder secrets unchanged;
5. selects `docker-compose.dev.yml` automatically through `COMPOSE_FILE`;
6. generates TLS certificates with `mkcert` or OpenSSL;
7. builds the PHP and frontend development images;
8. starts and waits for the infrastructure services;
9. installs Composer dependencies and generates the JWT key pair;
10. runs Symfony auto-scripts and Doctrine migrations;
11. creates the private MinIO bucket and starts the complete stack, including Stripe CLI.

### Local domains

The scripts do not edit system files. Add this line to `/etc/hosts` on Linux/macOS or to `C:\Windows\System32\drivers\etc\hosts` on Windows:

```text
127.0.0.1 evogym.local api.evogym.local
```

On Linux/macOS:

```bash
echo '127.0.0.1 evogym.local api.evogym.local' \
  | sudo tee -a /etc/hosts
```

When `mkcert` is unavailable, the setup creates a self-signed OpenSSL certificate. The browser will display a warning until that certificate is trusted manually.

## Stripe webhook forwarding

The development stack contains a `stripe-cli` service equivalent to running a local listener for:

```text
/api/webhooks/stripe/
```

Inside the Compose network it forwards events to:

```text
http://nginx:8080/api/webhooks/stripe/
```

This is the container-network equivalent of forwarding from the host to `https://api.evogym.local/api/webhooks/stripe/`. It does not need `--skip-verify` because the internal connection uses HTTP and never leaves the private Compose network.

The listener automatically:

- authenticates with `STRIPE_SECRET_KEY`;
- subscribes only to Stripe events handled by the application;
- writes the active session's `whsec_*` signing secret to a private named volume;
- exposes that volume read-only to PHP-FPM;
- makes Symfony use the active listener secret in `dev` environment.

Do not copy the signing secret from logs into `.env`. It is refreshed automatically when the Stripe listener session changes.

Check listener status and logs:

```bash
docker compose ps stripe-cli
make stripe-logs
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
make up           # start or reconcile the stack
make down         # stop containers without deleting data
make ps           # show service state
make logs         # follow Nginx, PHP-FPM, frontend, and Stripe CLI logs
make stripe-logs  # follow only Stripe webhook forwarding
make migrate      # apply Doctrine migrations
```

The direct Docker Compose equivalents remain available:

```bash
docker compose up -d --remove-orphans
docker compose down
docker compose ps
docker compose logs -f nginx php-fpm frontend stripe-cli
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

### Stripe CLI is restarting or unhealthy

Verify that `.env` contains real sandbox credentials rather than generated or example values:

```dotenv
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
```

Then synchronize the application files and recreate the affected services:

```bash
bash scripts/dev-bootstrap.sh
docker compose up -d --force-recreate stripe-cli php-fpm frontend
make stripe-logs
```

### Compose starts only the base services

The generated root `.env` must contain:

```dotenv
COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
COMPOSE_PATH_SEPARATOR=:
```

Run `bash scripts/dev-bootstrap.sh` again when these values are missing.

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

Container DSNs must use Compose service names such as `postgres`, `redis`, `rabbitmq`, `clickhouse`, `mailpit`, `minio`, and `nginx`, not `localhost`.

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
bash scripts/dev-bootstrap.sh
```
