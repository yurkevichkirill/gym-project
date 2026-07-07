# Development environment

The development environment combines `docker-compose.yml` with `docker-compose.dev.yml`. The generated root `.env` sets `COMPOSE_FILE`, so ordinary `docker compose` commands automatically use both files.

Symfony and Next.js sources are bind-mounted into their containers. PHP uses the development image with optional Xdebug, the frontend runs `pnpm dev`, and Stripe CLI runs as a dedicated webhook-forwarding service.

## Bootstrap a clean environment

From the repository root:

```bash
bash scripts/dev-bootstrap.sh
```

Or, when GNU Make is available:

```bash
make setup
```

When valid Stripe sandbox credentials are not already present in `.env`, the bootstrap prompts for:

- `STRIPE_SECRET_KEY` starting with `sk_test_`;
- `STRIPE_PUBLIC_KEY` starting with `pk_test_`.

The secret key input is hidden. Existing valid credentials are preserved.

The bootstrap then runs `scripts/dev-setup.sh`, which is safe to rerun and performs this sequence:

1. creates local environment files from committed examples;
2. generates and synchronizes local application and infrastructure secrets;
3. generates TLS certificates;
4. validates the merged Compose configuration;
5. builds PHP-FPM and frontend images;
6. starts PostgreSQL, Redis, RabbitMQ, ClickHouse, MinIO, and Mailpit and waits for readiness;
7. installs Composer dependencies without auto-scripts;
8. generates JWT keys and runs Composer auto-scripts;
9. applies Doctrine migrations;
10. initializes the MinIO bucket;
11. starts Nginx, PHP-FPM, the frontend, PgAdmin, workers, and Stripe CLI.

The host needs Git, Docker Compose v2, and either `mkcert` or OpenSSL. PHP, Composer, Node.js, pnpm, and Stripe CLI run inside containers.

## Environment files

### Root `.env`

The root file controls Compose interpolation, published ports, infrastructure credentials, Stripe sandbox credentials, and values injected into application services.

Important development values are:

```dotenv
COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
COMPOSE_PATH_SEPARATOR=:
APP_ENV=dev
DEFAULT_URI=https://api.evogym.local
CLIENT_ACTIVATION_URL=https://evogym.local/activate/
AUTH_COOKIE_DOMAIN=.evogym.local
CORS_ALLOW_ORIGIN='^https://evogym\.local$'
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
```

Keep `BIND_ADDRESS=127.0.0.1` unless remote access is intentionally protected.

### Symfony `.env`

`scripts/dev-setup.sh` synchronizes:

- `APP_SECRET`;
- PostgreSQL credentials in `DATABASE_URL`;
- RabbitMQ credentials in `MESSENGER_TRANSPORT_DSN`;
- JWT passphrase;
- Stripe API credentials and fallback webhook secret;
- ClickHouse credentials;
- MinIO credentials and bucket.

Inside Docker, the active Stripe listener signing secret is read from `/run/stripe-webhook/secret`. Outside Docker, Symfony falls back to `STRIPE_WEBHOOK_SECRET`.

### Next.js `.env.local`

The local frontend uses:

```dotenv
NEXT_PUBLIC_API_URL=https://api.evogym.local/api
INTERNAL_API_URL=http://nginx:8080/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
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

When `mkcert` is installed, the bootstrap installs its local CA and creates a trusted certificate. Otherwise it uses OpenSSL and the certificate must be trusted manually.

## Service topology

The base Compose file defines the application and core infrastructure. The development override adds:

- Mailpit and its SMTP/web interface ports;
- PgAdmin;
- RabbitMQ management port;
- ClickHouse host ports;
- MinIO API and console ports;
- the idempotent `minio-init` bucket initializer;
- Stripe CLI webhook forwarding;
- a private named volume shared between Stripe CLI and PHP-FPM for the current webhook signing secret.

The override is selected automatically from `.env`; do not invoke only `docker-compose.yml` unless the reduced base stack is intentional.

## Stripe CLI webhook forwarding

The `stripe-cli` service is the Compose equivalent of running:

```bash
stripe listen \
  --forward-to https://api.evogym.local/api/webhooks/stripe/ \
  --skip-verify
```

Inside Docker it uses the private network endpoint instead:

```text
http://nginx:8080/api/webhooks/stripe/
```

Because that connection is plain HTTP inside the isolated Compose network, TLS verification and `--skip-verify` are unnecessary.

The listener:

1. authenticates with the root `STRIPE_SECRET_KEY`;
2. subscribes only to events handled by `StripeWebhookService`;
3. forwards events to Nginx;
4. extracts the active session's `whsec_*` value from Stripe CLI output;
5. atomically writes it to the `stripe_webhook_secret` named volume;
6. lets Symfony validate signatures using that current value.

Manual copying of the webhook signing secret is not required. When Stripe CLI reconnects and receives a new session secret, the shared file is replaced automatically.

Inspect the listener:

```bash
docker compose ps stripe-cli
make stripe-logs
```

If it is unhealthy or restarting, verify the sandbox keys in `.env`, then run:

```bash
bash scripts/dev-bootstrap.sh
docker compose up -d --force-recreate stripe-cli php-fpm frontend
make stripe-logs
```

## Daily start and stop

```bash
make up
make ps
make logs
make stripe-logs
make down
```

Direct equivalents:

```bash
docker compose up -d --remove-orphans
docker compose ps
docker compose logs -f nginx php-fpm frontend stripe-cli
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

Install changed dependencies after modifying `package.json` or `pnpm-lock.yaml`:

```bash
docker compose exec frontend pnpm install --frozen-lockfile
```

### Symfony source

`./symfony` is mounted at `/var/www`. Request-handling changes are visible on the next request.

Restart long-running workers after changing handlers, scheduled tasks, analytics, cache invalidation, or payment processing:

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
docker compose restart php-fpm messenger-worker analytics-worker scheduler-worker payment-worker
```

After adding a Doctrine migration:

```bash
docker compose exec php-fpm \
  php bin/console doctrine:migrations:migrate --no-interaction
```

Clear Symfony cache when configuration remains stale:

```bash
docker compose exec php-fpm php bin/console cache:clear
```

### Compose and environment changes

```bash
docker compose config --quiet
docker compose up -d --build --remove-orphans
```

Use `--force-recreate` when only container environment values changed:

```bash
docker compose up -d --force-recreate --remove-orphans
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
docker compose config --quiet
```

## Resetting local data

A full reset deletes all application data and the shared Stripe listener volume:

```bash
docker compose down -v
rm -rf docker/db/data data/redis data/minio
bash scripts/dev-bootstrap.sh
```

Do not run this when the current development database must be preserved.
