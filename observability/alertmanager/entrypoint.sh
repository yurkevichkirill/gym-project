#!/bin/sh
set -eu

: "${ALERTMANAGER_WEBHOOK_URL:?ALERTMANAGER_WEBHOOK_URL is required}"

escaped_webhook_url=$(printf '%s' "$ALERTMANAGER_WEBHOOK_URL" | sed 's/[&|]/\\&/g')
sed "s|__ALERTMANAGER_WEBHOOK_URL__|${escaped_webhook_url}|g" \
    /etc/alertmanager/config.yml.template \
    > /tmp/alertmanager.yml

exec /bin/alertmanager --config.file=/tmp/alertmanager.yml "$@"
