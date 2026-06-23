# EvoGym

EvoGym is a gym management application with a Next.js frontend and a Symfony API. The local Docker stack includes Nginx/OpenResty, PHP-FPM, PostgreSQL, Redis, RabbitMQ, ClickHouse, Mailpit, MinIO, PgAdmin, and background workers.

## Requirements

- Git
- Docker Engine or Docker Desktop with Docker Compose v2
- `mkcert` (recommended) or OpenSSL for local TLS certificates
- Permission to edit the local `hosts` file

## Local setup

### 1. Clone the repository

```bash
git clone https://github.com/yurkevichkirill/gym-project.git
cd gym-project
```

### 2. Create local environment files

Linux/macOS/Git Bash:

```bash
cp .env.example .env
cp symfony/.env.example symfony/.env
cp nextjs/.env.example nextjs/.env.local
```

PowerShell:

```powershell
Copy-Item .env.example .env
Copy-Item symfony/.env.example symfony/.env
Copy-Item nextjs/.env.example nextjs/.env.local
```

These files contain local secrets and are ignored by Git. Do not commit them.

The Symfony application currently stores only `symfony/.env.example`, so a base `symfony/.env` must be created before running Composer or Symfony commands. After that, machine-specific overrides may additionally be placed in `symfony/.env.local`.

For local development, update at least the following values:

- in `.env`, replace every `REPLACE_WITH_*` placeholder, including PostgreSQL, PgAdmin, RabbitMQ, ClickHouse and MinIO credentials;
- keep `BIND_ADDRESS=127.0.0.1` unless remote access is intentionally protected by a firewall and authentication;
- in `symfony/.env`, set `APP_ENV=dev`, generate a strong `APP_SECRET`, set `DEFAULT_URI=https://api.evogym.local`, set `CLIENT_ACTIVATION_URL=https://evogym.local/activate/`, and replace all placeholders;
- in `nextjs/.env.local`, set the Stripe publishable key when payment flows are needed.

Values used by application containers must match the infrastructure credentials:

| Root `.env` | Symfony setting |
| --- | --- |
| `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB` | credentials and database in `DATABASE_URL` |
| Redis service hostname | host in `REDIS_URL` must remain `redis` inside containers |
| `RABBITMQ_DEFAULT_USER`, `RABBITMQ_DEFAULT_PASS` | credentials in `MESSENGER_TRANSPORT_DSN` |
| `CLICKHOUSE_DB`, `CLICKHOUSE_USER`, `CLICKHOUSE_PASSWORD` | `CLICKHOUSE_DB`, `CLICKHOUSE_USER`, `CLICKHOUSE_PASSWORD` |
| `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD` | `MINIO_ACCESS_KEY`, `MINIO_SECRET_KEY` |

A random Symfony application secret can be generated with:

```bash
openssl rand -hex 32
```

### 3. Add local domains

Add this line to `/etc/hosts` on Linux/macOS or to `C:\Windows\System32\drivers\etc\hosts` on Windows:

```text
127.0.0.1 evogym.local api.evogym.local
```

### 4. Generate TLS certificates

Nginx expects these exact files:

- `certs/evogym.local.pem`
- `certs/evogym.local-key.pem`

Recommended option using `mkcert`:

```bash
mkdir -p certs
mkcert -install
mkcert -cert-file certs/evogym.local.pem -key-file certs/evogym.local-key.pem evogym.local api.evogym.local
```

OpenSSL fallback:

```bash
mkdir -p certs
openssl req -x509 -nodes -newkey rsa:2048 -sha256 -days 825 -keyout certs/evogym.local-key.pem -out certs/evogym.local.pem -subj "/CN=evogym.local" -addext "subjectAltName=DNS:evogym.local,DNS:api.evogym.local"
```

A certificate created by plain OpenSSL is self-signed and will not be trusted by the browser automatically. `mkcert` installs a local development CA and avoids that warning.

### 5. Install dependencies and generate JWT keys

```bash
docker compose build
docker compose run --rm php-fpm composer install
docker compose run --rm php-fpm php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

The JWT key pair is generated in `symfony/config/jwt/` and is ignored by Git. The command uses `JWT_PASSPHRASE` from `symfony/.env`.

### 6. Start the stack and migrate the database

```bash
docker compose config --quiet
docker compose up -d
docker compose ps
docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction
```

The fixture set does not currently provide demo records. Running the following command purges existing application data, so use it only when a database reset is intended:

```bash
docker compose exec php-fpm php bin/console doctrine:fixtures:load --no-interaction
```

## Local URLs

| Service | URL |
| --- | --- |
| Frontend | `https://evogym.local` |
| Symfony API | `https://api.evogym.local` |
| OpenAPI JSON | `https://api.evogym.local/api/doc.json` |
| PgAdmin | `http://localhost:8080` |
| RabbitMQ management | `http://localhost:15672` |
| Mailpit | `http://localhost:8025` |
| MinIO console | `http://localhost:9001` |
| MinIO API | `http://localhost:9005` |
| ClickHouse HTTP API | `http://localhost:8123` |

Ports can be changed in the root `.env` file. By default all published ports bind only to `127.0.0.1`; container-to-container communication still uses the private Compose network.

## Validation commands

```bash
docker compose config --quiet
docker compose exec php-fpm php bin/phpunit
docker compose exec php-fpm vendor/bin/phpstan analyse
docker compose exec frontend pnpm lint
docker compose exec frontend pnpm build
```

Run commands inside the PHP container when working with Composer or Symfony:

```bash
docker compose exec -it php-fpm sh
```

## Common startup problems

### Nginx exits immediately

Check that both certificate files exist under `certs/` with the exact names shown above:

```bash
docker compose logs nginx
```

### API cannot connect to PostgreSQL, Redis, RabbitMQ, ClickHouse, or MinIO

Check that credentials and DSNs in `symfony/.env` match the corresponding values in the root `.env`. Container hostnames must remain `postgres`, `redis`, `rabbitmq`, `clickhouse`, and `minio`; do not replace them with `localhost`.

### Frontend sends requests to an invalid URL

Check that `nextjs/.env.local` contains:

```dotenv
NEXT_PUBLIC_API_URL=https://api.evogym.local/api
```

Recreate the frontend container after changing public Next.js environment variables:

```bash
docker compose up -d --build frontend
```

### Browser reports an untrusted certificate

Use the `mkcert` workflow above, or import the self-signed OpenSSL certificate into the local trust store.

## Observability

The logging stack has its own environment file and mandatory alert receiver:

```bash
cd observability
cp .env.example .env
# Replace the Grafana password and ALERTMANAGER_WEBHOOK_URL.
docker compose -f docker-compose.observability.yml config --quiet
docker compose -f docker-compose.observability.yml up -d
```

See `observability/README.md` for alert payload, retention and local access details.

## Stop or reset the project

Stop containers without deleting data:

```bash
docker compose down
```

Delete named volumes and local service data only when a full reset is intended:

```bash
docker compose down -v
rm -rf docker/db/data data/redis data/minio
```
