#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html/zareenstore/onu"
CONFIG_FILE="${APP_DIR}/config.php"
BACKUP_ROOT="/var/backups/zareenstore"
RCLONE_REMOTE="${RCLONE_REMOTE:-gdrive:ZareenStoreBackups}"
KEEP_DAYS="${KEEP_DAYS:-30}"

log() {
  printf '[%s] %s\n' "$(date '+%F %T')" "$*"
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required command: $1" >&2
    exit 1
  }
}

extract_php_constant() {
  local const_name="$1"
  sed -n "s/.*define('${const_name}',[[:space:]]*'\\([^']*\\)'.*/\\1/p" "$CONFIG_FILE" | head -n 1
}

require_cmd mysqldump
require_cmd gzip
require_cmd tar
require_cmd sha256sum
require_cmd rclone

if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Config file not found: $CONFIG_FILE" >&2
  exit 1
fi

DB_HOST="$(extract_php_constant DB_HOST)"
DB_USER="$(extract_php_constant DB_USER)"
DB_PASS="$(extract_php_constant DB_PASS)"
DB_NAME="$(extract_php_constant DB_NAME)"

if [[ -z "$DB_HOST" || -z "$DB_USER" || -z "$DB_NAME" ]]; then
  echo "Failed to read DB credentials from $CONFIG_FILE" >&2
  exit 1
fi

DATE_TAG="$(date +%F_%H-%M-%S)"
WORK_DIR="${BACKUP_ROOT}/${DATE_TAG}"
DB_DUMP_FILE="${WORK_DIR}/db_${DB_NAME}_${DATE_TAG}.sql.gz"
APP_ARCHIVE_FILE="${WORK_DIR}/app_onu_${DATE_TAG}.tar.gz"
MYSQL_CNF_FILE="${WORK_DIR}/.mysqldump.cnf"

mkdir -p "$WORK_DIR"

cat > "$MYSQL_CNF_FILE" <<EOF
[client]
host=${DB_HOST}
user=${DB_USER}
password=${DB_PASS}
EOF
chmod 600 "$MYSQL_CNF_FILE"

log "Dumping database ${DB_NAME}..."
mysqldump --defaults-extra-file="$MYSQL_CNF_FILE" \
  --single-transaction --routines --triggers --no-tablespaces "$DB_NAME" \
  | gzip -9 > "$DB_DUMP_FILE"

rm -f "$MYSQL_CNF_FILE"

if [[ ! -s "$DB_DUMP_FILE" ]]; then
  echo "Database dump file is empty: $DB_DUMP_FILE" >&2
  exit 1
fi

log "Archiving application files from ${APP_DIR}..."
tar -czf "$APP_ARCHIVE_FILE" -C "$(dirname "$APP_DIR")" "$(basename "$APP_DIR")"

log "Generating checksums..."
(
  cd "$WORK_DIR"
  sha256sum ./*.gz > SHA256SUMS.txt
)

REMOTE_NAME="${RCLONE_REMOTE%%:*}"
log "Validating rclone remote ${REMOTE_NAME}: ..."
rclone lsd "${REMOTE_NAME}:" >/dev/null

log "Uploading backup to ${RCLONE_REMOTE}/${DATE_TAG} ..."
rclone copy "$WORK_DIR" "${RCLONE_REMOTE}/${DATE_TAG}" \
  --transfers 4 --checkers 8 --create-empty-src-dirs --stats-one-line

log "Cleaning up local backups older than ${KEEP_DAYS} days..."
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +"$KEEP_DAYS" -exec rm -rf {} +

log "Cleaning up remote backups older than ${KEEP_DAYS} days..."
rclone delete "$RCLONE_REMOTE" --min-age "${KEEP_DAYS}d" --rmdirs

log "Backup completed successfully."
