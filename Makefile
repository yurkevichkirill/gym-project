.PHONY: setup up down restart ps logs migrate test phpstan lint typecheck build

setup:
	bash scripts/dev-setup.sh

up:
	docker compose up -d --remove-orphans

down:
	docker compose down

restart:
	docker compose restart

ps:
	docker compose ps

logs:
	docker compose logs -f nginx php-fpm frontend

migrate:
	docker compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction

test:
	docker compose exec php-fpm php bin/phpunit

phpstan:
	docker compose exec php-fpm vendor/bin/phpstan analyse

lint:
	docker compose exec frontend pnpm lint

typecheck:
	docker compose exec frontend pnpm typecheck

build:
	docker compose exec frontend pnpm build
