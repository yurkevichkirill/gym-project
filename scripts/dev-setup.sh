#!/usr/bin/env bash

set -Eeuo pipefail
IFS=$'\n\t'

readonly ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

log() {
    printf '[setup] %s\n' "$*"
}

warn() {
    printf '[setup] WARNING: %s\n' "$*" >&2
}

fail() {
    printf '[setup] ERROR: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' was not found."
}

random_hex() {
    local byte_count="${1:-32}"

    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex "$byte_count"
        return
    fi

    require_command od
    od -An -N"$byte_count" -tx1 /dev/urandom | tr -d ' \n'
}

copy_if_missing() {
    local source_file="$1"
    local target_file="$2"

    if [[ -f "$target_file" ]]; then
        return
    fi

    cp "$source_file" "$target_file"
    chmod 600 "$target_file"
    log "Created $target_file from $source_file."
}

read_env_value() {
    local file="$1"
    local key="$2"
    local line
    local value

    line="$(grep -E "^${key}=" "$file" | tail -n 1 || true)"
    value="${line#*=}"

    if [[ "$value" == \"*\" && "$value" == *\" ]]; then
        value="${value:1:${#value}-2}"
    elif [[ "$value" == \'*\' && "$value" == *\' ]]; then
        value="${value:1:${#value}-2}"
    fi

    printf '%s' "$value"
}

write_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"
    local temporary_file="${file}.tmp.$$"

    awk -v key="$key" -v value="$value" '
        BEGIN { found = 0 }
        $0 ~ "^" key "=" {
            print key "=" value
            found = 1
            next
        }
        { print }
        END {
            if (found == 0) {
                print key "=" value
            }
        }
    ' "$file" > "$temporary_file"

    chmod 600 "$temporary_file"
    mv "$temporary_file" "$file"
}

replace_placeholder() {
    local file="$1"
    local key="$2"
    local replacement="$3"
    local current_value

    current_value="$(read_env_value "$file" "$key")"

    if [[ -z "$current_value" || "$current_value" == REPLACE_WITH_* ]]; then
        write_env_value "$file" "$key" "$replacement"
    fi
}

write_if_missing() {
    local file="$1"
    local key="$2"
    local value="$3"

    if [[ -z "$(read_env_value "$file" "$key")" ]]; then
        write_env_value "$file" "$key" "$value"
    fi
}

prepare_environment_files() {
    copy_if_missing .env.example .env
    copy_if_missing symfony/.env.example symfony/.env
    copy_if_missing nextjs/.env.example nextjs/.env.local

    write_if_missing .env COMPOSE_PROJECT_NAME evogym-dev
    write_if_missing .env COMPOSE_FILE docker-compose.yml:docker-compose.dev.yml
    write_if_missing .env COMPOSE_PATH_SEPARATOR :
    write_if_missing .env APP_ENV dev
    write_if_missing .env APP_TIMEZONE UTC
    write_if_missing .env DEFAULT_URI https://api.evogym.local
    write_if_missing .env CLIENT_ACTIVATION_URL https://evogym.local/activate/
    write_if_missing .env ACTIVATION_EMAIL_SENDER noreply@evogym.local
    write_if_missing .env AUTH_COOKIE_DOMAIN .evogym.local
    write_if_missing .env CORS_ALLOW_ORIGIN "'^https://evogym\.local$'"
    write_if_missing .env MAILER_DSN smtp://mailpit:1025
    write_if_missing .env CONTACT_RECIPIENT_EMAIL contact@evogym.local
    write_if_missing .env CONTACT_SENDER_EMAIL noreply@evogym.local
    write_if_missing .env MINIO_BUCKET evogym-bucket

    replace_placeholder .env APP_SECRET "$(random_hex 32)"
    replace_placeholder .env POSTGRES_PASSWORD "$(random_hex 24)"
    replace_placeholder .env PGADMIN_DEFAULT_PASSWORD "$(random_hex 24)"
    replace_placeholder .env RABBITMQ_DEFAULT_PASS "$(random_hex 24)"
    replace_placeholder .env CLICKHOUSE_PASSWORD "$(random_hex 24)"
    replace_placeholder .env MINIO_ROOT_USER evogym
    replace_placeholder .env MINIO_ROOT_PASSWORD "$(random_hex 24)"
    replace_placeholder .env JWT_PASSPHRASE "$(random_hex 32)"
    replace_placeholder .env STRIPE_SECRET_KEY "sk_test_$(random_hex 24)"
    replace_placeholder .env STRIPE_PUBLIC_KEY "pk_test_$(random_hex 24)"
    replace_placeholder .env STRIPE_WEBHOOK_SECRET "whsec_$(random_hex 24)"

    local app_secret
    local postgres_user
    local postgres_password
    local postgres_database
    local rabbitmq_user
    local rabbitmq_password
    local clickhouse_database
    local clickhouse_user
    local clickhouse_password
    local minio_user
    local minio_password
    local minio_bucket
    local jwt_passphrase
    local stripe_secret_key
    local stripe_public_key
    local stripe_webhook_secret

    app_secret="$(read_env_value .env APP_SECRET)"
    postgres_user="$(read_env_value .env POSTGRES_USER)"
    postgres_password="$(read_env_value .env POSTGRES_PASSWORD)"
    postgres_database="$(read_env_value .env POSTGRES_DB)"
    rabbitmq_user="$(read_env_value .env RABBITMQ_DEFAULT_USER)"
    rabbitmq_password="$(read_env_value .env RABBITMQ_DEFAULT_PASS)"
    clickhouse_database="$(read_env_value .env CLICKHOUSE_DB)"
    clickhouse_user="$(read_env_value .env CLICKHOUSE_USER)"
    clickhouse_password="$(read_env_value .env CLICKHOUSE_PASSWORD)"
    minio_user="$(read_env_value .env MINIO_ROOT_USER)"
    minio_password="$(read_env_value .env MINIO_ROOT_PASSWORD)"
    minio_bucket="$(read_env_value .env MINIO_BUCKET)"
    jwt_passphrase="$(read_env_value .env JWT_PASSPHRASE)"
    stripe_secret_key="$(read_env_value .env STRIPE_SECRET_KEY)"
    stripe_public_key="$(read_env_value .env STRIPE_PUBLIC_KEY)"
    stripe_webhook_secret="$(read_env_value .env STRIPE_WEBHOOK_SECRET)"

    write_env_value symfony/.env APP_ENV dev
    write_env_value symfony/.env APP_SECRET "$app_secret"
    write_env_value symfony/.env DEFAULT_URI https://api.evogym.local
    write_env_value symfony/.env DATABASE_URL "postgresql://${postgres_user}:${postgres_password}@postgres:5432/${postgres_database}?serverVersion=16&charset=utf8"
    write_env_value symfony/.env MESSENGER_TRANSPORT_DSN "amqp://${rabbitmq_user}:${rabbitmq_password}@rabbitmq:5672/%2f/messages"
    write_env_value symfony/.env JWT_PASSPHRASE "$jwt_passphrase"
    write_env_value symfony/.env STRIPE_SECRET_KEY "$stripe_secret_key"
    write_env_value symfony/.env STRIPE_PUBLIC_KEY "$stripe_public_key"
    write_env_value symfony/.env STRIPE_WEBHOOK_SECRET "$stripe_webhook_secret"
    write_env_value symfony/.env CLICKHOUSE_DB "$clickhouse_database"
    write_env_value symfony/.env CLICKHOUSE_USER "$clickhouse_user"
    write_env_value symfony/.env CLICKHOUSE_PASSWORD "$clickhouse_password"
    write_env_value symfony/.env MINIO_ACCESS_KEY "$minio_user"
    write_env_value symfony/.env MINIO_SECRET_KEY "$minio_password"
    write_env_value symfony/.env MINIO_BUCKET "$minio_bucket"
    write_env_value nextjs/.env.local NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY "$stripe_public_key"

    log 'Local environment files are ready.'
}

prepare_certificates() {
    local certificate_file=certs/evogym.local.pem
    local key_file=certs/evogym.local-key.pem

    if [[ -f "$certificate_file" && -f "$key_file" ]]; then
        log 'Local TLS certificates already exist.'
        return
    fi

    mkdir -p certs

    if command -v mkcert >/dev/null 2>&1; then
        log 'Generating trusted local TLS certificates with mkcert.'
        mkcert -install
        mkcert \
            -cert-file "$certificate_file" \
            -key-file "$key_file" \
            evogym.local api.evogym.local
        return
    fi

    require_command openssl
    warn 'mkcert is unavailable; generating a self-signed certificate with OpenSSL.'
    openssl req \
        -x509 \
        -nodes \
        -newkey rsa:2048 \
        -sha256 \
        -days 825 \
        -keyout "$key_file" \
        -out "$certificate_file" \
        -subj '/CN=evogym.local' \
        -addext 'subjectAltName=DNS:evogym.local,DNS:api.evogym.local'
}

check_local_domains() {
    if [[ -r /etc/hosts ]] \
        && grep -Eq '(^|[[:space:]])evogym\.local([[:space:]]|$)' /etc/hosts \
        && grep -Eq '(^|[[:space:]])api\.evogym\.local([[:space:]]|$)' /etc/hosts; then
        return
    fi

    warn 'Local domains are not present in /etc/hosts.'
    warn 'Add: 127.0.0.1 evogym.local api.evogym.local'
}

install_backend() {
    log 'Installing Symfony dependencies.'
    docker compose run --rm --no-deps php-fpm \
        composer install --prefer-dist --no-progress --no-interaction --no-scripts

    log 'Generating the JWT key pair.'
    docker compose run --rm --no-deps php-fpm \
        php bin/console lexik:jwt:generate-keypair --skip-if-exists

    docker compose run --rm --no-deps php-fpm sh -ec '
        chgrp www-data config/jwt/private.pem config/jwt/public.pem
        chmod 640 config/jwt/private.pem
        chmod 644 config/jwt/public.pem
    '

    log 'Running Composer auto-scripts.'
    docker compose run --rm --no-deps php-fpm \
        composer run-script --no-interaction post-install-cmd

    docker compose run --rm --no-deps php-fpm sh -ec '
        mkdir -p var/cache var/log public/uploads
        chmod -R a+rwX var public/uploads
    '
}

start_infrastructure() {
    log 'Starting local infrastructure.'
    docker compose up -d --wait --wait-timeout 180 \
        postgres redis rabbitmq clickhouse minio mailpit
}

initialize_services() {
    log 'Applying Doctrine migrations.'
    docker compose run --rm --no-deps php-fpm \
        php bin/console doctrine:migrations:migrate --no-interaction

    log 'Creating the local MinIO bucket.'
    docker compose run --rm minio-init
}

start_application() {
    log 'Starting the complete development stack.'
    docker compose up -d --remove-orphans
    docker compose ps
}

main() {
    require_command docker
    docker compose version >/dev/null 2>&1 || fail 'Docker Compose v2 is required.'

    prepare_environment_files
    prepare_certificates
    check_local_domains

    log 'Validating the merged development Compose configuration.'
    docker compose config --quiet

    log 'Building PHP and frontend development images.'
    docker compose build php-fpm frontend

    start_infrastructure
    install_backend
    initialize_services
    start_application

    log 'EvoGym is ready at https://evogym.local.'
    log 'API documentation: https://api.evogym.local/api/doc.json'
}

main "$@"
