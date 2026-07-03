# Repository Guidelines

## Executing Commands

Run project commands from the PHP-FPM container unless a task specifically targets local tooling:
`docker compose exec -it php-fpm sh`, then execute the needed command inside the shell.

## Project Structure & Module Organization

This gym management system is split into `nextjs/` and `symfony/`. Frontend routes live in `nextjs/src/app`, page sections in `nextjs/src/scenes`, shared UI in `nextjs/src/shared`, API wrappers in `nextjs/src/api`, stores in `nextjs/src/store`, and typed contracts in `nextjs/src/types`. The Symfony backend lives in `symfony/src`, organized by domains such as `Booking`, `Client`, `Membership`, `Payment`, `Trainer`, and `Training`. Docker and infrastructure files are under `docker/`; observability assets are under `observability/`.

## Build, Test, and Development Commands

- `docker compose up -d` starts the local stack: Nginx, PHP-FPM, PostgreSQL, Redis, RabbitMQ, workers, ClickHouse, Mailpit, and MinIO.
- `cd nextjs && pnpm install` installs frontend dependencies.
- `cd nextjs && pnpm dev` runs the Next.js frontend at `http://localhost:3000`.
- `cd nextjs && pnpm lint` runs ESLint with Next.js TypeScript and Core Web Vitals rules.
- `cd symfony && composer install` installs backend dependencies.
- `cd symfony && php bin/phpunit` runs PHPUnit.
- `cd symfony && vendor/bin/phpstan analyse` runs strict static analysis.

## Coding Style & Naming Conventions

Use TypeScript and React function components on the frontend. Follow existing folder patterns in `src/scenes`, `src/hooks`, and `src/store`. Component files use PascalCase when exporting a component; Next.js route files use framework names such as `page.tsx` and `layout.tsx`. Backend code uses PHP 8.4, PSR-4 namespaces under `App\`, and 4-space indentation. Keep suffixes consistent: `Controller`, `DTO`, `Entity`, `Enum`, `Repository`, `Resolver`, `Mapper`, `Service`, and `Voter`.

## Testing Guidelines

Backend tests use PHPUnit 12 and PHPStan at max level. Add tests under `symfony/tests`, mirror the changed domain, and name files with the `Test.php` suffix. For frontend changes, run `pnpm lint` and `pnpm build`; the frontend currently exposes linting and build checks rather than a dedicated test runner.

## Commit & Pull Request Guidelines

Recent commits use short imperative summaries, for example `Added payment cleanup command to scheduler and run docker service` or `Fixed bugs with admin data in admin booking controller`. Keep commits focused on one behavior change. Pull requests should include a concise description, linked issue when relevant, migration or environment notes, screenshots for UI changes, and the validation commands run.

## Security & Configuration Tips

Do not commit secrets, runtime data, uploaded files, logs, cache directories, or local database state. Document new environment variables for Stripe, JWT, mailer, Redis, RabbitMQ, S3/MinIO, ClickHouse, and observability changes.
