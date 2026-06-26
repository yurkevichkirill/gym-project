#!/bin/sh
set -eu

: "${PGHOST:?PGHOST is required}"
: "${PGDATABASE:?PGDATABASE is required}"
: "${PGUSER:?PGUSER is required}"
: "${PGPASSWORD:?PGPASSWORD is required}"
: "${BACKUP_DIR:?BACKUP_DIR is required}"

retention_days="${BACKUP_RETENTION_DAYS:-14}"
case "$retention_days" in
    ''|*[!0-9]*)
        echo "BACKUP_RETENTION_DAYS must be a non-negative integer." >&2
        exit 1
        ;;
esac

umask 077
mkdir -p "$BACKUP_DIR"

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_file="$BACKUP_DIR/${PGDATABASE}_${timestamp}.dump"
temporary_file="${backup_file}.tmp"
checksum_file="${backup_file}.sha256"

cleanup() {
    rm -f "$temporary_file"
}
trap cleanup EXIT HUP INT TERM

pg_dump \
    --format=custom \
    --compress=9 \
    --no-owner \
    --no-privileges \
    --file="$temporary_file"

pg_restore --list "$temporary_file" >/dev/null
mv "$temporary_file" "$backup_file"
sha256sum "$backup_file" > "$checksum_file"
trap - EXIT HUP INT TERM

find "$BACKUP_DIR" -type f \
    \( -name '*.dump' -o -name '*.dump.sha256' \) \
    -mtime "+$retention_days" \
    -exec rm -f {} \;

echo "PostgreSQL backup created: $backup_file"
