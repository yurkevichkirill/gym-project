# Development environment

The development environment combines `docker-compose.yml` with `docker-compose.dev.yml`. The generated root `.env` sets `COMPOSE_FILE`, so ordinary `docker compose` commands automatically use both files.

Symfony and Next.js sources are bind-mounted into their containers. PHP uses the development image with optional Xdebug, and the frontend runs `pnpm dev` with Fast Refresh.

## Bootstrap a clean environment

From the repository root:

```bash
bash scripts/dev-setup.sh
```

Or, when GNU Make is available:

```bash
make setup
```

The bootstrap script is safe to rerun. Existing environment values are preserved unless they still contain a `REPLACE_WITH_*` placeholder. The script performs the following sequence:

1. creates local environment files from the committed examples;
2. generates and synchronizes local secrets and infrastructure credentials;
3. generates TLS certificates;
4. validates the merged Compose configuration;
5. builds PHP-FPM and frontend images;
6. starts PostgreSQL, Redis, RabbitMQ, ClickHouse, MinIO, and Mailpit and waits for readiness;
7. installs Composer dependencies without auto-scripts;
8. generates JWT keys and then runs Composer auto-scripts;
9. applies Doctrine migrations;
10. initializes the MinIO bucket;
11. starts Nginx, PHP-FPM, the frontend, PgAdmin, and all workers.

The host only needs Git, Docker Compose v2, and either `mkcert` or OpenSSL. PHP, Composer, Node.js, and pnpm run inside containers.

## Environment files

### Root `.env`

The root file controls Compose interpolation, development service selection, published ports, infrastructure credentials, and application values injected into PHP services.

Important development values are:

```dotenv
COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
COMPOSE_PATH_SEPARATOR=:
APP_ENV=dev
DEFAULT_URI=https://api.evogym.local
CLIENT_ACTIVATION_URL=https://evogym.local/activate/
AUTH_COOKIE_DOMAIN=.evogym.local
CORS_ALLOW_ORIGIN='^https://evogym\.local$'
```

Keep `BIND_ADDRESS=127.0.0.1` unless remote access is intentionally protected.

### Symfony `.env`

`scripts/dev-setup.sh` synchronizes these values from the root environment:

- `APP_SECRET`;
- PostgreSQL credentials in `DATABASE_URL`;
- RabbitMQ credentials in `MESSENGER_TRANSPORT_DSN`;
- JWT passphrase;
- Stripe settings;
- ClickHouse credentials;
- MinIO credentials and bucket.

Repository commands should still be executed through the PHP container, but the Symfony file also makes direct host commands predictable when a compatible local PHP installation is used.

### Next.js `.env.local`

The local frontend uses:

```dotenv
NEXT_PUBLIC_API_URL=https://api.evogym.local/api
INTERNAL_API_URL=http://nginx:8080/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_<generated-local-placeholder>
```

`INTERNAL_API_URL` is reachable only inside the Compose network. Browser requests use `NEXT_PUBLIC_API_URL`.

## Local domains and TLS

Add the domains to the host system:

```text
127.0.0.1 evogym.local api.evogym.local
```

Nginx expects:

```text
certs/evogym.local.pem
certs/evogym.local-key.pem
```

When `mkcert` is installed, the bootstrap script installs its local CA and creates a trusted certificate. Otherwise it uses OpenSSL and the certificate must be trusted manually.

## Service topology

The base Compose file defines the application and core infrastructure. The development override adds:

- Mailpit and its SMTP/web interface ports;
- PgAdmin;
- RabbitMQ management port;
- ClickHouse host ports;
- MinIO API and console ports;
- the idempotent `minio-init` bucket initializer.

The override is selected automatically from `.env`; do not invoke only `docker-compose.yml` unless the reduced base stack is intentional.

## Daily start and stop

```bash
make up
make ps
make logs
make down
```

Direct equivalents:

```bash
docker compose up -d --remove-orphans
docker compose ps
docker compose logs -f nginx php-fpm frontend
docker compose down
```

`docker compose down` preserves bind-mounted and named-volume data.

## Applying changes

### Next.js source

`./nextjs` is mounted at `/app`. Source changes normally appear through Fast Refresh.

Restart the frontend after changing `nextjs/.env.local`:

```bash
docker compose restart frontend
```

Install changed dependencies into the persistent volume after modifying `package.json` or `pnpm-lock.yaml`:

```bash
docker compose exec frontend pnpm install --frozen-lockfile
```

Rebuild after changing the Node Dockerfile or base image:

```bash
docker compose up -d --build frontend
```

### Symfony source

`./symfony` is mounted at `/var/www`. Request-handling changes are visible to PHP-FPM on the next request.

Restart long-running workers after changing handlers, scheduled tasks, cache invalidation, analytics, or payment processing:

```bash
docker compose restart \
  messenger-worker \
  analytics-worker \
  scheduler-worker \
  payment-worker
```

After changing `composer.json` or `composer.lock`:

```bash
docker compose exec php-fpm composer install
docker compose restart \
  php-fpm \
  messenger-worker \
  analytics-worker \
  scheduler-worker \
  payment-worker
```

After adding a Doctrine migration:

```bash
docker compose exec php-fpm \
  php bin/console doctrine:migrations:migrate --no-interaction
```

Clear the Symfony cache when configuration remains stale:

```bash
docker compose exec php-fpm php bin/console cache:clear
```

### Compose and environment changes

After changing Compose files, Dockerfiles, or root environment values:

```bash
docker compose config --quiet
docker compose up -d --build --remove-orphans
```

Use `--force-recreate` when only container environment values changed:

```bash
docker compose up -d --force-recreate --remove-orphans
```

## Stripe CLI

Replace the local Stripe placeholders before exercising payment flows. For local webhook forwarding, use the HTTPS API domain and update `STRIPE_WEBHOOK_SECRET` with the secret printed by Stripe CLI:

```bash
stripe listen --forward-to https://api.evogym.local/api/webhooks/stripe/
```

After changing the webhook secret, synchronize and recreate PHP services:

```bash
bash scripts/dev-setup.sh
docker compose up -d --force-recreate php-fpm payment-worker
```

## Validation

```bash
make test
make phpstan
make typecheck
make lint
make build
```

Direct commands:

```bash
docker compose exec php-fpm php bin/phpunit
docker compose exec php-fpm vendor/bin/phpstan analyse
docker compose exec frontend pnpm typecheck
docker compose exec frontend pnpm lint
docker compose exec frontend pnpm build
```

Validate infrastructure interpolation separately:

```bash
docker compose config --quiet
```

## Resetting local data

A full reset deletes all application data:

```bash
docker compose down -v
rm -rf docker/db/data data/redis data/minio
bash scripts/dev-setup.sh
```

Do not run this when the current development database must be preserved.
