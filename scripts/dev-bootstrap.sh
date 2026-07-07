#!/usr/bin/env bash

set -Eeuo pipefail
IFS=$'\n\t'

readonly ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

log() {
    printf '[bootstrap] %s\n' "$*"
}

fail() {
    printf '[bootstrap] ERROR: %s\n' "$*" >&2
    exit 1
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

is_valid_secret_key() {
    local value="$1"

    [[ "$value" == sk_test_* ]] \
        && [[ ! "$value" =~ ^sk_test_[0-9a-f]{48}$ ]]
}

is_valid_public_key() {
    local value="$1"

    [[ "$value" == pk_test_* ]] \
        && [[ ! "$value" =~ ^pk_test_[0-9a-f]{48}$ ]]
}

prepare_root_environment() {
    if [[ ! -f .env ]]; then
        cp .env.example .env
        chmod 600 .env
        log 'Created .env from .env.example.'
    fi
}

configure_stripe_credentials() {
    local secret_key="${STRIPE_SECRET_KEY:-$(read_env_value .env STRIPE_SECRET_KEY)}"
    local public_key="${STRIPE_PUBLIC_KEY:-$(read_env_value .env STRIPE_PUBLIC_KEY)}"

    if ! is_valid_secret_key "$secret_key"; then
        if [[ ! -t 0 ]]; then
            fail 'Set a real Stripe sandbox STRIPE_SECRET_KEY (sk_test_...) in .env before running setup.'
        fi

        read -r -s -p 'Stripe test secret key (sk_test_...): ' secret_key
        printf '\n'
    fi

    is_valid_secret_key "$secret_key" \
        || fail 'Stripe secret key must be a real sandbox key starting with sk_test_.'

    if ! is_valid_public_key "$public_key"; then
        if [[ ! -t 0 ]]; then
            fail 'Set a real Stripe sandbox STRIPE_PUBLIC_KEY (pk_test_...) in .env before running setup.'
        fi

        read -r -p 'Stripe test publishable key (pk_test_...): ' public_key
    fi

    is_valid_public_key "$public_key" \
        || fail 'Stripe publishable key must be a real sandbox key starting with pk_test_.'

    write_env_value .env STRIPE_SECRET_KEY "$secret_key"
    write_env_value .env STRIPE_PUBLIC_KEY "$public_key"
    write_env_value .env STRIPE_WEBHOOK_SECRET whsec_runtime_from_stripe_cli

    unset STRIPE_SECRET_KEY STRIPE_PUBLIC_KEY
    log 'Stripe sandbox credentials are configured.'
}

main() {
    prepare_root_environment
    configure_stripe_credentials
    exec bash scripts/dev-setup.sh
}

main "$@"
