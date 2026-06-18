# Repository Guidelines

## Project Structure & Module Organization

This gym management system is split across `nextjs/` and `symfony/`. The frontend keeps routes in `nextjs/src/app`, page sections in `nextjs/src/scenes`, shared UI in `nextjs/src/shared`, API wrappers in `nextjs/src/api`, stores in `nextjs/src/store`, and typed contracts in `nextjs/src/types`. The Symfony 8 backend lives in `symfony/src`, organized by domains such as `Booking`, `Client`, `Membership`, `Payment`, `Trainer`, and `Training`; see `symfony/AGENTS.md` for backend-specific details. Docker, Nginx, and ClickHouse setup files are under `docker/`; Loki/Grafana assets are under `observability/`.

## Build, Test, and Development Commands

- `docker compose up -d` starts the main local stack: Nginx, PHP-FPM, PostgreSQL, Redis, RabbitMQ, workers, ClickHouse, Mailpit, and MinIO.
- `cd nextjs && pnpm install` installs frontend dependencies from `pnpm-lock.yaml`.
- `cd nextjs && pnpm dev` runs the frontend at `http://localhost:3000`.
- `cd nextjs && pnpm lint` runs ESLint with Next.js TypeScript and Core Web Vitals rules.
- `cd symfony && composer install` installs backend dependencies.
- `cd symfony && php bin/phpunit` runs PHPUnit.
- `cd symfony && vendor/bin/phpstan analyse` runs strict static analysis.

## Coding Style & Naming Conventions

Use TypeScript for frontend code, React function components, and existing folder patterns in `src/scenes`, `src/hooks`, and `src/store`. Component files use PascalCase when exporting a component; route files follow Next.js names such as `page.tsx` and `layout.tsx`. Use PHP 8.4, PSR-4 namespaces under `App\`, and 4-space indentation in Symfony. Keep backend suffixes consistent: `Controller`, `DTO`, `Entity`, `Enum`, `Repository`, `Resolver`, `Mapper`, `Service`, and `Voter`.

## Testing Guidelines

The backend uses PHPUnit 12 and PHPStan at max level. Add PHP tests under `symfony/tests` with names ending in `Test.php`, mirroring the changed domain. The frontend currently exposes linting and build checks rather than a test runner; run `pnpm lint` and `pnpm build` for UI changes.

## Commit & Pull Request Guidelines

Recent commits use short imperative summaries, for example `Added payment cleanup command to scheduler and run docker service` or `Fixed bugs with admin data in admin booking controller`. Keep commits focused on one behavior change. Pull requests should include a concise description, linked issue when relevant, migration or environment notes, screenshots for UI changes, and the commands run for validation.

## Security & Configuration Tips

Do not commit secrets, runtime data, uploaded files, logs, cache directories, or local database state. Document new environment variables for Stripe, JWT, mailer, Redis, RabbitMQ, S3/MinIO, ClickHouse, and observability changes.
