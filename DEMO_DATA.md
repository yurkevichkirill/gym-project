# Demo database data

The Symfony command `app:load-demo-data` loads a predefined set of demo records into the application database.

The command is implemented in:

```text
symfony/src/Command/LoadDemoDataCommand.php
```

## Important safety notes

This command is not a replacement for Doctrine migrations and does not create the database schema.
Run all migrations before loading demo data.

The command intentionally refuses to run unless all of the following conditions are true:

- `APP_ENV=prod`;
- the database host in `DATABASE_URL` is `postgres`;
- the database name in `DATABASE_URL` is `gym_database`.

It does not purge the database. The command is designed to be repeatable and reuses records found by name or email. However, for the predefined demo email addresses it also updates profile data, activates the accounts, resets balances, and resets passwords to the shared demo password. Do not run it against a real production database containing accounts with the same email addresses.

All demo users use this password:

```text
password
```

This password is intentionally weak and suitable only for a temporary demo installation. Change or remove the demo accounts before exposing the application to real users.

## Data created or updated

The command creates or updates:

- four training types: Strength Training, Functional Training, Yoga, and Boxing;
- three membership plans: Basic, Standard, and Unlimited;
- one administrator;
- five clients;
- three trainers;
- trainer work times from `09:00` to `18:00` on `2026-08-01`, `2026-08-03`, and `2026-08-05` for every demo trainer.

The fixed work-time dates may already be in the past when the command is run. Update `LoadDemoDataCommand` before loading data when future trainer availability is required.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@evogym.test` | `password` |
| Client | `client1@evogym.test` | `password` |
| Client | `client2@evogym.test` | `password` |
| Client | `client3@evogym.test` | `password` |
| Client | `client4@evogym.test` | `password` |
| Client | `client5@evogym.test` | `password` |
| Trainer | `trainer1@evogym.test` | `password` |
| Trainer | `trainer2@evogym.test` | `password` |
| Trainer | `trainer3@evogym.test` | `password` |

## Load data in the production Docker stack

From the repository root, verify that the production environment file is configured and that the stack is running:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  up -d
```

Open a shell in the PHP-FPM container:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec -it php-fpm sh
```

Inside the container, apply migrations and load the demo records:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console app:load-demo-data --env=prod
```

A successful execution prints:

```text
Demo data loaded.
Demo passwords verified.
```

Exit the container shell:

```bash
exit
```

## Run as one-off host commands

The same operations can be run without opening an interactive shell:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod

docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console app:load-demo-data --env=prod
```

## Verify the command registration

To confirm that Symfony sees the command:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console list app --env=prod
```

To display the command help:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console help app:load-demo-data --env=prod
```

## Re-running the command

The command can be executed more than once. Existing training types, membership plans, and users are reused where possible, and existing trainer work times for the configured dates are skipped.

Re-running still resets the predefined demo users to the data declared in `LoadDemoDataCommand`, including the shared `password`, active status, balances, and trainer debt. Review the command before re-running it on any database that is not disposable.

## Common errors

### `Refusing to load demo data into an unexpected database.`

Check the values available inside the PHP-FPM container:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  sh -lc 'printf "APP_ENV=%s\nDATABASE_URL=%s\n" "$APP_ENV" "$DATABASE_URL"'
```

The command requires `APP_ENV=prod`, the host `postgres`, and the database name `gym_database`.

### Database tables do not exist

Apply the migrations first:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

### The command is missing

Clear the Symfony production cache and check the command list again:

```bash
docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console cache:clear --env=prod

docker compose \
  --env-file .env.prod \
  -f docker-compose.prod.yml \
  exec php-fpm \
  php bin/console list app --env=prod
```
