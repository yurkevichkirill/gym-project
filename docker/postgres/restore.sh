#!/bin/sh
set -eu

: "${PGHOST:?PGHOST is required}"
: "${PGDATABASE:?PGDATABASE is required}"
: "${PGUSER:?PGUSER is required}"
: "${PGPASSWORD:?PGPASSWORD is required}"
: "${BACKUP_DIR:?BACKUP_DIR is required}"
: "${BACKUP_FILE:?BACKUP_FILE is required}"

if [ "${ALLOW_DATABASE_RESTORE:-0}" != "1" ]; then
    echo "Set ALLOW_DATABASE_RESTORE=1 to confirm the destructive restore." >&2
    exit 1
fi

case "$BACKUP_FILE" in
    /*)
        backup_path="$BACKUP_FILE"
        ;;
    *)
        backup_path="$BACKUP_DIR/$BACKUP_FILE"
        ;;
esac

if [ ! -f "$backup_path" ]; then
    echo "Backup file does not exist: $backup_path" >&2
    exit 1
fi

checksum_path="${backup_path}.sha256"
if [ -f "$checksum_path" ]; then
    (cd "$(dirname "$backup_path")" && sha256sum -c "$(basename "$checksum_path")")
fi

pg_restore --list "$backup_path" >/dev/null
pg_restore \
    --clean \
    --if-exists \
    --no-owner \
    --no-privileges \
    --exit-on-error \
    --single-transaction \
    --dbname="$PGDATABASE" \
    "$backup_path"

echo "PostgreSQL restore completed from: $backup_path"
