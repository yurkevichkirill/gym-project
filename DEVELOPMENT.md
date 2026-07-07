# Development deployment

The local development environment uses the root `docker-compose.yml`. Symfony and Next.js source directories are bind-mounted into their containers, the PHP image uses the development stage with optional Xdebug, and the frontend runs `pnpm dev` with Next.js Fast Refresh.

This guide reflects the current Compose stack. It starts Nginx/OpenResty, PHP-FPM, PostgreSQL, Redis, RabbitMQ, four Messenger workers, ClickHouse, MinIO, and the Next.js frontend.

## Host prerequisites

- Git;
- Docker Engine or Docker Desktop with Docker Compose v2;
- `mkcert` (recommended) or OpenSSL for local TLS certificates;
- permission to edit the local hosts file;
- free local ports configured in the root `.env` file.

## Create development environment files

From the repository root:

```bash
cp .env.example .env
cp symfony/.env.example symfony/.env
cp nextjs/.env.example nextjs/.env.local
```

These files contain local secrets and are ignored by Git. Do not commit them.

### Root `.env`

Replace every `REPLACE_WITH_*` value from `.env.example`. The credentials in the root `.env` are used by Docker Compose to configure PostgreSQL, RabbitMQ, ClickHouse, and MinIO.

The current Compose file also resolves several application variables directly from the root `.env`. Add at least the following development values:

```dotenv
APP_ENV=dev
APP_TIMEZONE=UTC
DEFAULT_URI=https://api.evogym.local
CLIENT_ACTIVATION_URL=https://evogym.local/activate/
ACTIVATION_EMAIL_SENDER=noreply@evogym.local
AUTH_COOKIE_DOMAIN=.evogym.local
CORS_ALLOW_ORIGIN='^https://evogym\.local$'

JWT_PASSPHRASE=REPLACE_WITH_STRONG_JWT_PASSPHRASE
STRIPE_SECRET_KEY=REPLACE_WITH_STRIPE_SECRET_KEY
STRIPE_PUBLIC_KEY=REPLACE_WITH_STRIPE_PUBLIC_KEY
STRIPE_WEBHOOK_SECRET=REPLACE_WITH_STRIPE_WEBHOOK_SECRET
```

`APP_ENV=dev` must be set in the root `.env`. The Compose environment is injected into PHP-FPM and the workers and takes precedence over `APP_ENV` from `symfony/.env`. Without this override, the current Compose default is `prod`.

Keep `BIND_ADDRESS=127.0.0.1` unless remote access is intentionally protected by a firewall and authentication.

### Symfony `.env`

Replace every placeholder in `symfony/.env` and keep the service credentials aligned with the root `.env`:

- the PostgreSQL username, password, and database in `DATABASE_URL` must match `POSTGRES_USER`, `POSTGRES_PASSWORD`, and `POSTGRES_DB`;
- the RabbitMQ credentials in `MESSENGER_TRANSPORT_DSN` must match `RABBITMQ_DEFAULT_USER` and `RABBITMQ_DEFAULT_PASS`;
- ClickHouse and MinIO credentials must match the root `.env` values;
- `JWT_PASSPHRASE` must match the passphrase used to generate the local JWT key pair.

Set `APP_ENV=dev` in `symfony/.env` as well so Symfony commands remain in development mode if they are ever executed outside Compose. Repository commands should normally be executed through the PHP container.

### Next.js `.env.local`

The expected local API settings are:

```dotenv
NEXT_PUBLIC_API_URL=https://api.evogym.local/api
INTERNAL_API_URL=http://nginx:8080/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=REPLACE_WITH_STRIPE_PUBLISHABLE_KEY
```

`INTERNAL_API_URL` uses the internal Nginx endpoint available only on the Compose network. Browser requests use `NEXT_PUBLIC_API_URL`.

## Current mail limitation

The current `docker-compose.yml` does not declare a `mailpit` service, although the default `MAILER_DSN` points to `smtp://mailpit:1025`. Account activation and other email flows will fail unless `MAILER_DSN` is changed to an SMTP server reachable from the PHP containers or Mailpit is restored to the development Compose stack in a separate infrastructure change.

The current Compose file also does not declare PgAdmin and does not publish the RabbitMQ management UI, ClickHouse, or MinIO ports. Do not rely on older README URLs for those services unless the Compose stack is changed.

## Configure local domains

Add the following line to `/etc/hosts` on Linux/macOS or `C:\Windows\System32\drivers\etc\hosts` on Windows:

```text
127.0.0.1 evogym.local api.evogym.local
```

## Generate local TLS certificates

Nginx expects these exact files:

```text
certs/evogym.local.pem
certs/evogym.local-key.pem
```

Recommended `mkcert` setup:

```bash
mkdir -p certs
mkcert -install
mkcert \
  -cert-file certs/evogym.local.pem \
  -key-file certs/evogym.local-key.pem \
  evogym.local api.evogym.local
```

OpenSSL fallback:

```bash
mkdir -p certs
openssl req \
  -x509 \
  -nodes \
  -newkey rsa:2048 \
  -sha256 \
  -days 825 \
  -keyout certs/evogym.local-key.pem \
  -out certs/evogym.local.pem \
  -subj '/CN=evogym.local' \
  -addext 'subjectAltName=DNS:evogym.local,DNS:api.evogym.local'
```

A plain OpenSSL certificate is self-signed and must be trusted manually by the browser. `mkcert` installs a local CA and avoids the warning after the CA is trusted.

## First build and startup

Validate interpolation before building. This catches missing required secrets in the root `.env`:

```bash
docker compose config --quiet
```

Build the development images and install Symfony dependencies into the bind-mounted application directory:

```bash
docker compose build
docker compose run --rm php-fpm composer install
```

Generate the local JWT key pair in `symfony/config/jwt/`:

```bash
docker compose run --rm php-fpm \
  php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

Start the stack and apply database migrations:

```bash
docker compose up -d
docker compose exec php-fpm \
  php bin/console doctrine:migrations:migrate --no-interaction
```

Load development demo data with the same accounts, membership plans, training types, trainers, and trainer work times as the production demo-data command:

```bash
docker compose exec php-fpm \
  php bin/console doctrine:fixtures:load --no-interaction
```

All demo users use `password` as the password. The seeded accounts are `admin@evogym.test`, `client1@evogym.test` through `client5@evogym.test`, and `trainer1@evogym.test` through `trainer3@evogym.test`.

Inspect container state and startup logs:

```bash
docker compose ps
docker compose logs --tail=200 nginx php-fpm frontend postgres redis rabbitmq
```

## Local URLs and ports

With the default root `.env` ports:

| Service | Address |
| --- | --- |
| Frontend through Nginx | `https://evogym.local` |
| Symfony API | `https://api.evogym.local` |
| OpenAPI JSON | `https://api.evogym.local/api/doc.json` |
| Direct Next.js development server | `http://127.0.0.1:3000` |
| PostgreSQL | `127.0.0.1:5432` |
| Redis | `127.0.0.1:6379` |
| RabbitMQ AMQP | `127.0.0.1:5672` |

The actual host ports come from `.env`. Nginx also exposes HTTP and redirects the configured local domains to HTTPS.

## Daily start and stop

Start or reconcile the current development stack:

```bash
docker compose up -d
```

Follow the main application logs:

```bash
docker compose logs -f nginx php-fpm frontend
```

Stop and remove containers without deleting persisted data:

```bash
docker compose down
```

## Applying development changes

### Next.js source changes

`./nextjs` is bind-mounted at `/app`, and the development image runs `pnpm dev`. React and Next.js changes normally appear through Fast Refresh without restarting the container.

Restart the frontend after changing `nextjs/.env.local` because Next.js environment values are loaded when the development process starts:

```bash
docker compose restart frontend
```

After changing `nextjs/package.json` or `nextjs/pnpm-lock.yaml`, install the exact dependencies into the persistent `frontend_node_modules` volume:

```bash
docker compose exec frontend pnpm install --frozen-lockfile
```

Rebuild the frontend image after changing its Dockerfile or base image:

```bash
docker compose up -d --build frontend
```

### Symfony source changes

`./symfony` is bind-mounted at `/var/www`. Controller, service, DTO, repository, and configuration changes are normally visible to PHP-FPM on the next request.

Messenger consumers are long-running PHP processes. Restart them after changing code used by message handlers, scheduled tasks, cache invalidation, analytics, or payment processing:

```bash
docker compose restart \
  messenger-worker \
  analytics-worker \
  scheduler-worker \
  payment-worker
```

After changing `symfony/composer.json` or `symfony/composer.lock`:

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

Clear the Symfony cache when configuration or cached container metadata remains stale:

```bash
docker compose exec php-fpm php bin/console cache:clear
```

### Compose, Dockerfile, or container environment changes

After changing `docker-compose.yml`, a Dockerfile, or values injected from the root `.env`, rebuild or recreate the affected services:

```bash
docker compose config --quiet
docker compose up -d --build --remove-orphans
```

Use `--force-recreate` when only container environment or Compose settings changed and Docker would otherwise keep an existing container:

```bash
docker compose up -d --force-recreate --remove-orphans
```

After changing `docker/conf/default.conf` or local certificates, restart Nginx:

```bash
docker compose restart nginx
```

## Validation commands

Run frontend checks inside the frontend container:

```bash
docker compose exec frontend pnpm lint
docker compose exec frontend pnpm typecheck
docker compose exec frontend pnpm build
```

Run Symfony checks inside the PHP container:

```bash
docker compose exec php-fpm php bin/phpunit
docker compose exec php-fpm vendor/bin/phpstan analyse
```

## Resetting local data

`docker compose down` preserves data. The development stack stores PostgreSQL, Redis, and MinIO in bind-mounted host directories and RabbitMQ and ClickHouse in named volumes.

A full reset is destructive:

```bash
docker compose down -v
rm -rf docker/db/data data/redis data/minio
```

This removes local application data. Do not run it when the current development database must be preserved.
