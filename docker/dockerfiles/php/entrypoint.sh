#!/bin/sh
set -eu

cd /var/www

jwt_source_dir="${JWT_SOURCE_DIR:-/run/secrets/jwt}"

if [ ! -r "$jwt_source_dir/private.pem" ] || [ ! -r "$jwt_source_dir/public.pem" ]; then
    echo "JWT key pair is missing or unreadable in $jwt_source_dir." >&2
    exit 1
fi

mkdir -p config/jwt var/cache var/log
cp "$jwt_source_dir/private.pem" config/jwt/private.pem
cp "$jwt_source_dir/public.pem" config/jwt/public.pem
chown -R www-data:www-data config/jwt var
chmod 0600 config/jwt/private.pem
chmod 0644 config/jwt/public.pem

if [ "${1:-}" = "migrate" ]; then
    shift
    exec su-exec www-data php bin/console doctrine:migrations:migrate \
        --no-interaction \
        --allow-no-migration \
        "$@"
fi

if [ "${APP_CACHE_WARMUP:-1}" = "1" ]; then
    su-exec www-data php bin/console cache:warmup --no-interaction
fi

if [ "${1:-}" = "php-fpm" ]; then
    exec "$@"
fi

exec su-exec www-data "$@"
