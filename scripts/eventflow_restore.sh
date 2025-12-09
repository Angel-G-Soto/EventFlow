#!/bin/bash

# EventFlow Restore Script
# Restores the EventFlow database and documents from backup files.
#
# Defaults assume:
#   - DB name: eventflow
#   - Laravel app dir: /var/www/EventFlow
#   - Web user: www-data
#
# Usage:
#   sudo ./eventflow_restore.sh \
#       --db-backup /path/to/dump.sql.gz \
#       --docs-backup /path/to/documents.zip
#
# Optional flags:
#   --db-name <name>      (default: eventflow)
#   --app-dir <path>      (default: /var/www/EventFlow)
#   --web-user <user>     (default: www-data)
#   --help / -h           Show help

set -e  # fail on errors by default

APP_DB_NAME="eventflow"
APP_DIR="/var/www/EventFlow"
APP_WEB_USER="www-data"

DB_BACKUP_FILE=""
DOCS_BACKUP_ZIP=""

# Use env var if provided, otherwise we'll prompt
MARIADB_ROOT_PASSWORD="password"

# -----------------------------
# Root check
# -----------------------------
if [ "$EUID" -ne 0 ]; then
    echo "Please run this script as root or with sudo."
    exit 1
fi

# -----------------------------
# Parse CLI arguments
# -----------------------------
while [[ $# -gt 0 ]]; do
    case "$1" in
        --db-backup)
            DB_BACKUP_FILE="$2"
            shift 2
            ;;
        --docs-backup)
            DOCS_BACKUP_ZIP="$2"
            shift 2
            ;;
        --db-name)
            APP_DB_NAME="$2"
            shift 2
            ;;
        --app-dir)
            APP_DIR="$2"
            shift 2
            ;;
        --web-user)
            APP_WEB_USER="$2"
            shift 2
            ;;
        -h|--help)
            echo "Usage: $0 [options]"
            echo ""
            echo "  --db-backup <file>     Path to dbdump.sql.gz"
            echo "  --docs-backup <file>   Path to documents.zip"
            echo "  --db-name <name>       MariaDB database name (default: eventflow)"
            echo "  --app-dir <path>       EventFlow app dir (default: /var/www/EventFlow)"
            echo "  --web-user <user>      Web user (default: www-data)"
            echo "  -h, --help             Show this help"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Run with --help for usage."
            exit 1
            ;;
    esac
done

echo "========================================="
echo "EventFlow Restore Script"
echo "========================================="
echo "Database name : ${APP_DB_NAME}"
echo "App directory : ${APP_DIR}"
echo "Web user      : ${APP_WEB_USER}"
echo "DB backup     : ${DB_BACKUP_FILE:-<none>}"
echo "Docs backup   : ${DOCS_BACKUP_ZIP:-<none>}"
echo "========================================="
echo ""

# Temporarily allow failures for DB/docs restore so one doesn't kill the other
set +e

# -----------------------------
# Restore database (if backup provided)
# -----------------------------
if [ -n "$DB_BACKUP_FILE" ] && [ -f "$DB_BACKUP_FILE" ]; then
    echo "Restoring database '${APP_DB_NAME}' from: $DB_BACKUP_FILE"

    # Prompt for root password if not already set via env
    if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
        read -sp "Enter MariaDB root password: " MARIADB_ROOT_PASSWORD
        echo ""
    fi

    # Basic existence checks
    if ! command -v mariadb >/dev/null 2>&1; then
        echo "ERROR: 'mariadb' client not found. Install MariaDB client and try again."
    else
        gunzip -c "$DB_BACKUP_FILE" | mariadb -u root -p"${MARIADB_ROOT_PASSWORD}" "${APP_DB_NAME}"
        if [ $? -eq 0 ]; then
            echo "✓ Database restore completed"
        else
            echo "WARNING: Database restore failed."
        fi
    fi
else
    echo "No DB backup file found or path empty; skipping DB restore."
fi

echo ""

# -----------------------------
# Restore documents (if backup provided)
# -----------------------------
if [ -n "$DOCS_BACKUP_ZIP" ] && [ -f "$DOCS_BACKUP_ZIP" ]; then
    echo "Restoring documents from: $DOCS_BACKUP_ZIP"

    # Ensure unzip is available
    if ! command -v unzip >/dev/null 2>&1; then
        echo "ERROR: 'unzip' command not found. Install it with:"
        echo "  sudo apt install unzip"
        echo "Then re-run this script."
    else
        DOCS_DIR="${APP_DIR}/storage/app/documents"
        mkdir -p "$DOCS_DIR"

        unzip -o "$DOCS_BACKUP_ZIP" -d "$DOCS_DIR"
        if [ $? -eq 0 ]; then
            chown -R "$APP_WEB_USER":"$APP_WEB_USER" "$DOCS_DIR"
            echo "✓ Documents restore completed"
        else
            echo "WARNING: Documents restore failed."
        fi
    fi
else
    echo "No documents backup file found or path empty; skipping documents restore."
fi

echo ""
set -e

echo "========================================="
echo "EventFlow restore script finished."
echo "========================================="
