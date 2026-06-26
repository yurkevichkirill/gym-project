#!/bin/sh
set -eu

: "${API_HOST:?API_HOST is required}"
: "${FRONTEND_HOST:?FRONTEND_HOST is required}"

case "$API_HOST$FRONTEND_HOST" in
    *[!A-Za-z0-9.-]*)
        echo "API_HOST and FRONTEND_HOST may contain only letters, digits, dots and hyphens." >&2
        exit 1
        ;;
esac

sed \
    -e "s/__API_HOST__/$API_HOST/g" \
    -e "s/__FRONTEND_HOST__/$FRONTEND_HOST/g" \
    /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

exec "$@"
