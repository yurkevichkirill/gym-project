# Production deployment

`docker-compose.yml` remains the local development stack. Production uses immutable application images and the standalone `docker-compose.prod.yml` stack: source directories are not bind-mounted, Xdebug is not installed, Next.js runs the standalone server, and infrastructure ports other than HTTP/HTTPS are not published.

## Host prerequisites

- Docker Engine with Docker Compose v2;
- DNS records for the frontend and API hosts;
- one TLS certificate covering both hosts;
- persistent storage for Docker named volumes;
- a backup directory stored on a separate disk or copied to off-host/object storage;
- `openssl` for generating the JWT key pair.

## Configure secrets and host paths

```bash
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Replace every `REPLACE_WITH_*` value. Passwords embedded into `DATABASE_URL` or `MESSENGER_TRANSPORT_DSN` must be URL-encoded.

Create the directories configured by `TLS_CERTS_DIR`, `JWT_KEYS_DIR`, and `POSTGRES_BACKUP_DIR`. The TLS directory must contain these files:

```text
fullchain.pem
privkey.pem
```

Generate the JWT keys using the same passphrase as `JWT_PASSPHRASE`:

```bash
mkdir -p /srv/evogym/secrets/jwt
openssl genpkey \
  -algorithm RSA \
  -aes-256-cbc \
  -pass pass:'REPLACE_WITH_STRONG_JWT_PASSPHRASE' \
  -pkeyopt rsa_keygen_bits:4096 \
  -out /srv/evogym/secrets/jwt/private.pem
openssl pkey \
  -in /srv/evogym/secrets/jwt/private.pem \
  -passin pass:'REPLACE_WITH_STRONG_JWT_PASSPHRASE' \
  -pubout \
  -out /srv/evogym/secrets/jwt/public.pem
chmod 600 /srv/evogym/secrets/jwt/private.pem
chmod 644 /srv/evogym/secrets/jwt/public.pem
```

The PHP entrypoint copies the read-only JWT key pair into each container with application-only permissions. The user running Docker must be able to read the TLS and JWT source files and write to the backup directory.

## Build and deploy

Validate the configuration and build the production targets:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml config --quiet
docker compose --env-file .env.prod -f docker-compose.prod.yml build
```

Apply migrations once as an explicit release step. Migrations are not run independently by every PHP-FPM or worker container, avoiding concurrent migration attempts:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  --profile release run --rm migrate
```

Start or update the runtime services:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  up -d --remove-orphans
```

The PHP production entrypoint runs `cache:warmup` before PHP-FPM and each worker process. The `migrate` command is handled by the same entrypoint but only in the one-shot release service.

Inspect the deployment:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml ps
docker compose --env-file .env.prod -f docker-compose.prod.yml logs --tail=200 php-fpm nginx frontend
```

For registry-based deployment, set `PHP_IMAGE`, `NGINX_IMAGE`, and `FRONTEND_IMAGE` to immutable tags, build and push them in CI, then deploy those exact tags.

## PostgreSQL backups

The backup service creates a compressed custom-format dump, validates it with `pg_restore --list`, writes a SHA-256 checksum, and removes files older than `POSTGRES_BACKUP_RETENTION_DAYS`.

Run a backup manually:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  --profile backup run --rm postgres-backup
```

Schedule it on the host, for example every day at 02:00 UTC:

```cron
0 2 * * * cd /srv/evogym && docker compose --env-file .env.prod -f docker-compose.prod.yml --profile backup run --rm postgres-backup >> /var/log/evogym-postgres-backup.log 2>&1
```

The default daily schedule and 14-day retention provide a baseline RPO of up to 24 hours. A directory on the same server is not sufficient protection: copy completed `.dump` and `.sha256` files to encrypted off-host or object storage and alert on failed or missing backups.

## PostgreSQL restore

Test restore procedures regularly in a non-production environment. Before a production restore, preserve the current database with a new backup and stop services that write to PostgreSQL:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml stop \
  nginx php-fpm messenger-worker analytics-worker scheduler-worker payment-worker frontend
```

List available backups in `POSTGRES_BACKUP_DIR`, then restore one by filename. The explicit confirmation variable prevents accidental execution:

```bash
BACKUP_FILE=gym_database_20260101T020000Z.dump \
ALLOW_DATABASE_RESTORE=1 \
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  --profile restore run --rm postgres-restore
```

The restore verifies the checksum when present and runs `pg_restore` with `--clean`, `--exit-on-error`, and `--single-transaction`. After restoring an older backup, apply any newer migrations and restart the stack:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  --profile release run --rm migrate
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  up -d --remove-orphans
```

Record restore duration during drills to establish an evidence-based RTO for the actual database size and storage throughput.
