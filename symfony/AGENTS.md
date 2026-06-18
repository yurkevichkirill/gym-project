# Repository Guidelines

## Project Structure & Module Organization

This is a Symfony 8 API application using PHP 8.4. Application code lives in `src/`, organized mostly by domain: `Booking`, `Client`, `Membership`, `Payment`, `Trainer`, and `Training`. Domain modules commonly contain `DTO`, `Entity`, `Enum`, `Exception`, `Mapper`, `Query`, `Repository`, `Resolver`, `Security`, and `Service` classes. Symfony configuration is in `config/`, database migrations are in `migrations/`, Twig templates are in `templates/`, frontend entry files are in `assets/`, public assets and uploads are in `public/`, and test bootstrap files are in `tests/`.

## Build, Test, and Development Commands

- `composer install` installs PHP dependencies and runs Symfony auto-scripts.
- `symfony server:start` runs the app locally when the Symfony CLI is available.
- `php -S 127.0.0.1:8000 -t public` is a simple local fallback server.
- `php bin/console doctrine:migrations:migrate` applies database migrations.
- `php bin/console cache:clear` clears Symfony cache.
- `php bin/phpunit` runs the PHPUnit test suite configured by `phpunit.dist.xml`.
- `vendor/bin/phpstan analyse` runs static analysis using `phpstan.dist.neon`.

## Coding Style & Naming Conventions

Follow PSR-4 autoloading with the `App\` namespace mapped to `src/`. Use 4-space indentation for PHP. Keep domain classes inside matching module directories and use existing suffixes such as `Manager`, `Repository`, `Mapper`, `Resolver`, `Voter`, `DTO`, and `Enum`. Prefer constructor dependency injection and Symfony services. PHPStan runs at `level: max` with strict rules; do not add `dump()` calls because they are explicitly disallowed.

## Testing Guidelines

PHPUnit 12 loads `tests/bootstrap.php`, uses `APP_ENV=test`, and scans all files under `tests/`. Add tests under `tests/` with names ending in `Test.php`, mirroring the covered domain or service. Focus on business rules, query behavior, validators, message handlers, and controllers touched by a change. Run `php bin/phpunit` before submitting changes, and run `vendor/bin/phpstan analyse` for changes under `src/`, `config/`, or `bin/`.

## Commit & Pull Request Guidelines

Recent history uses short, imperative-style summaries such as `Added payment cleanup command to scheduler and run docker service` and `Fixed bugs with admin data in admin booking controller`. Keep commits focused and describe the behavior changed. Pull requests should include a concise description, linked issue when applicable, migration notes, new environment variables, and test/static-analysis results. Include API examples when changing public responses.

## Security & Configuration Tips

Do not commit secrets, runtime data, cache files, logs, or uploaded private data. Keep local values in environment-specific files and document required variables for services such as Stripe, JWT authentication, mailer, Redis, S3/Flysystem, Messenger, and ClickHouse.
