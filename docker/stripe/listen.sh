#!/bin/sh

set -eu

secret_file="${STRIPE_WEBHOOK_SECRET_FILE:-/run/stripe-webhook/secret}"
forward_to="${STRIPE_FORWARD_TO:-http://nginx:8080/api/webhooks/stripe/}"
events="${STRIPE_EVENTS:-payment_intent.succeeded,payment_intent.payment_failed,payment_intent.canceled,charge.refunded,charge.dispute.created,charge.dispute.funds_reinstated,refund.failed,refund.updated}"

: "${STRIPE_SECRET_KEY:?STRIPE_SECRET_KEY is required}"

secret_dir="$(dirname "$secret_file")"
output_pipe="/tmp/stripe-listen-output.$$"
stripe_pid=''

cleanup() {
    if [ -n "$stripe_pid" ] && kill -0 "$stripe_pid" 2>/dev/null; then
        kill "$stripe_pid" 2>/dev/null || true
        wait "$stripe_pid" 2>/dev/null || true
    fi

    rm -f "$output_pipe"
}

trap cleanup EXIT INT TERM

mkdir -p "$secret_dir"
rm -f "$secret_file"
mkfifo "$output_pipe"

/bin/stripe listen \
    --api-key "$STRIPE_SECRET_KEY" \
    --skip-update \
    --events "$events" \
    --forward-to "$forward_to" \
    >"$output_pipe" 2>&1 &
stripe_pid=$!

while IFS= read -r line; do
    printf '%s\n' "$line"

    secret="$(printf '%s\n' "$line" | sed -n 's/.*\(whsec_[[:alnum:]_]*\).*/\1/p')"
    if [ -z "$secret" ]; then
        continue
    fi

    temporary_file="${secret_file}.tmp.$$"
    printf '%s' "$secret" > "$temporary_file"
    chmod 0444 "$temporary_file"
    mv -f "$temporary_file" "$secret_file"
    printf '[stripe-listener] Webhook signing secret synchronized for Symfony.\n'
done < "$output_pipe"

wait "$stripe_pid"
