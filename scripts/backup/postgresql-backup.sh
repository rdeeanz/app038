#!/bin/bash
# PostgreSQL Backup Script for App038
# Supports full, incremental, and WAL archiving

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/postgresql}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-app038}"
DB_USER="${DB_USER:-postgres}"
BACKUP_TYPE="${BACKUP_TYPE:-full}"  # full, incremental, wal
COMPRESSION="${COMPRESSION:-gzip}"
S3_BUCKET="${S3_BUCKET:-}"
S3_PREFIX="${S3_PREFIX:-postgresql-backups/}"
VAULT_ADDR="${VAULT_ADDR:-http://vault:8200}"
VAULT_TOKEN="${VAULT_TOKEN:-}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Get database password from Vault if available
get_db_password() {
    if [ -n "$VAULT_ADDR" ] && [ -n "$VAULT_TOKEN" ]; then
        log "Fetching database password from Vault..."
        DB_PASSWORD=$(vault kv get -field=password secret/data/app038/database 2>/dev/null || echo "$DB_PASSWORD")
    fi
    
    if [ -z "$DB_PASSWORD" ]; then
        DB_PASSWORD="${DB_PASSWORD:-postgres}"
    fi
    
    export PGPASSWORD="$DB_PASSWORD"
}

# Create backup directory
create_backup_dir() {
    mkdir -p "$BACKUP_DIR"
    log "Backup directory: $BACKUP_DIR"
}

# Full backup using pg_dump
full_backup() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="${BACKUP_DIR}/full_backup_${timestamp}.sql"
    
    log "Starting full backup..."
    
    if [ "$COMPRESSION" = "gzip" ]; then
        backup_file="${backup_file}.gz"
        pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
            --verbose --clean --if-exists --create \
            | gzip > "$backup_file"
    else
        pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
            --verbose --clean --if-exists --create \
            -f "$backup_file"
    fi
    
    if [ $? -eq 0 ]; then
        log "Full backup completed: $backup_file"
        echo "$backup_file" > "${BACKUP_DIR}/latest_full_backup.txt"
        echo "$backup_file"
    else
        error "Full backup failed"
        exit 1
    fi
}

# Incremental backup using pg_basebackup
incremental_backup() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_dir="${BACKUP_DIR}/incremental_${timestamp}"
    
    log "Starting incremental backup..."
    
    mkdir -p "$backup_dir"
    
    pg_basebackup -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" \
        -D "$backup_dir" \
        -Ft -z -P -v
    
    if [ $? -eq 0 ]; then
        log "Incremental backup completed: $backup_dir"
        echo "$backup_dir" > "${BACKUP_DIR}/latest_incremental_backup.txt"
        echo "$backup_dir"
    else
        error "Incremental backup failed"
        exit 1
    fi
}

# WAL archiving (for continuous backup)
wal_archive() {
    log "Archiving WAL files..."
    
    # This is typically called by PostgreSQL's archive_command
    # For manual archiving, use pg_receivewal or similar
    local wal_file="$1"
    
    if [ -z "$wal_file" ]; then
        error "WAL file not specified"
        exit 1
    fi
    
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local archive_file="${BACKUP_DIR}/wal_${timestamp}_$(basename $wal_file)"
    
    cp "$wal_file" "$archive_file"
    
    if [ "$COMPRESSION" = "gzip" ]; then
        gzip "$archive_file"
        archive_file="${archive_file}.gz"
    fi
    
    log "WAL archived: $archive_file"
    echo "$archive_file"
}

# Upload to S3
upload_to_s3() {
    local file="$1"
    
    if [ -z "$S3_BUCKET" ]; then
        warning "S3_BUCKET not set, skipping S3 upload"
        return
    fi
    
    log "Uploading to S3: s3://${S3_BUCKET}/${S3_PREFIX}$(basename $file)"
    
    if command -v aws &> /dev/null; then
        aws s3 cp "$file" "s3://${S3_BUCKET}/${S3_PREFIX}$(basename $file)"
        
        if [ $? -eq 0 ]; then
            log "Upload successful"
        else
            error "S3 upload failed"
            return 1
        fi
    else
        warning "AWS CLI not found, skipping S3 upload"
    fi
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up backups older than $RETENTION_DAYS days..."
    
    find "$BACKUP_DIR" -type f -name "*.sql*" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -type d -name "incremental_*" -mtime +$RETENTION_DAYS -exec rm -rf {} +
    find "$BACKUP_DIR" -type f -name "wal_*" -mtime +$RETENTION_DAYS -delete
    
    log "Cleanup completed"
}

# Verify backup integrity
verify_backup() {
    local backup_file="$1"
    
    log "Verifying backup integrity..."
    
    if [[ "$backup_file" == *.gz ]]; then
        if gzip -t "$backup_file" 2>/dev/null; then
            log "Backup file is valid (gzip)"
        else
            error "Backup file is corrupted (gzip)"
            return 1
        fi
    else
        # For SQL files, try to check if it's valid
        if head -n 1 "$backup_file" | grep -q "PostgreSQL\|CREATE\|SET" 2>/dev/null; then
            log "Backup file appears valid (SQL)"
        else
            warning "Could not verify backup file integrity"
        fi
    fi
}

# Generate backup report
generate_report() {
    local backup_file="$1"
    local report_file="${BACKUP_DIR}/backup_report_$(date +%Y%m%d_%H%M%S).json"
    
    local file_size=$(du -h "$backup_file" | cut -f1)
    local file_size_bytes=$(stat -f%z "$backup_file" 2>/dev/null || stat -c%s "$backup_file" 2>/dev/null)
    
    cat > "$report_file" <<EOF
{
  "backup_type": "$BACKUP_TYPE",
  "backup_file": "$backup_file",
  "file_size": "$file_size",
  "file_size_bytes": $file_size_bytes,
  "timestamp": "$(date -Iseconds)",
  "database": "$DB_NAME",
  "host": "$DB_HOST",
  "retention_days": $RETENTION_DAYS,
  "compression": "$COMPRESSION"
}
EOF
    
    log "Backup report generated: $report_file"
    echo "$report_file"
}

# Main execution
main() {
    log "=== PostgreSQL Backup Script ==="
    log "Backup Type: $BACKUP_TYPE"
    log "Database: $DB_NAME"
    log "Host: $DB_HOST:$DB_PORT"
    
    # Get database password
    get_db_password
    
    # Create backup directory
    create_backup_dir
    
    # Perform backup based on type
    case "$BACKUP_TYPE" in
        full)
            backup_file=$(full_backup)
            verify_backup "$backup_file"
            generate_report "$backup_file"
            upload_to_s3 "$backup_file"
            ;;
        incremental)
            backup_dir=$(incremental_backup)
            generate_report "$backup_dir"
            upload_to_s3 "$backup_dir"
            ;;
        wal)
            if [ -z "$1" ]; then
                error "WAL file path required for WAL backup"
                exit 1
            fi
            wal_archive "$1"
            ;;
        *)
            error "Unknown backup type: $BACKUP_TYPE"
            exit 1
            ;;
    esac
    
    # Cleanup old backups
    cleanup_old_backups
    
    log "=== Backup completed successfully ==="
}

# Run main function
main "$@"

