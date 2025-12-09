#!/bin/bash
# PostgreSQL Restore Script for App038
# Restores from full or incremental backups

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/postgresql}"
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-app038}"
DB_USER="${DB_USER:-postgres}"
BACKUP_FILE="${1:-}"
S3_BUCKET="${S3_BUCKET:-}"
S3_PREFIX="${S3_PREFIX:-postgresql-backups/}"
VAULT_ADDR="${VAULT_ADDR:-http://vault:8200}"
VAULT_TOKEN="${VAULT_TOKEN:-}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Get database password from Vault
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

# Download from S3 if needed
download_from_s3() {
    if [ -z "$S3_BUCKET" ] || [ -z "$BACKUP_FILE" ]; then
        return
    fi
    
    log "Downloading backup from S3..."
    
    if command -v aws &> /dev/null; then
        aws s3 cp "s3://${S3_BUCKET}/${S3_PREFIX}${BACKUP_FILE}" "$BACKUP_DIR/$BACKUP_FILE"
        
        if [ $? -eq 0 ]; then
            log "Download successful"
            BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"
        else
            error "S3 download failed"
            exit 1
        fi
    else
        error "AWS CLI not found"
        exit 1
    fi
}

# Restore from full backup
restore_full() {
    local backup_file="$1"
    
    log "Starting full restore from: $backup_file"
    
    # Drop existing database (WARNING: Destructive!)
    warning "This will drop the existing database: $DB_NAME"
    read -p "Are you sure? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        log "Restore cancelled"
        exit 0
    fi
    
    # Drop and recreate database
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d postgres \
        -c "DROP DATABASE IF EXISTS $DB_NAME;" \
        -c "CREATE DATABASE $DB_NAME;"
    
    # Restore backup
    if [[ "$backup_file" == *.gz ]]; then
        gunzip -c "$backup_file" | psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME"
    else
        psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$backup_file"
    fi
    
    if [ $? -eq 0 ]; then
        log "Full restore completed successfully"
    else
        error "Full restore failed"
        exit 1
    fi
}

# Restore from incremental backup
restore_incremental() {
    local backup_dir="$1"
    
    log "Starting incremental restore from: $backup_dir"
    
    warning "Incremental restore requires PostgreSQL to be stopped"
    warning "This is a complex operation and may require manual intervention"
    
    # This would typically involve:
    # 1. Stopping PostgreSQL
    # 2. Restoring base backup
    # 3. Restoring WAL files
    # 4. Starting PostgreSQL
    
    error "Incremental restore not fully automated. Please refer to PostgreSQL documentation."
    exit 1
}

# Point-in-time recovery
point_in_time_recovery() {
    local backup_file="$1"
    local recovery_time="${2:-}"
    
    log "Starting point-in-time recovery..."
    
    if [ -z "$recovery_time" ]; then
        error "Recovery time not specified"
        exit 1
    fi
    
    warning "Point-in-time recovery requires WAL archiving and specific PostgreSQL configuration"
    error "Point-in-time recovery not fully automated. Please refer to PostgreSQL documentation."
    exit 1
}

# Verify restore
verify_restore() {
    log "Verifying restore..."
    
    # Check if database exists and is accessible
    if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1;" &>/dev/null; then
        log "Database is accessible"
        
        # Check table count
        table_count=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
            -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';")
        
        log "Tables found: $table_count"
    else
        error "Database verification failed"
        exit 1
    fi
}

# Main execution
main() {
    log "=== PostgreSQL Restore Script ==="
    
    # Get database password
    get_db_password
    
    # Determine backup file
    if [ -z "$BACKUP_FILE" ]; then
        # Try to use latest backup
        if [ -f "${BACKUP_DIR}/latest_full_backup.txt" ]; then
            BACKUP_FILE=$(cat "${BACKUP_DIR}/latest_full_backup.txt")
            log "Using latest backup: $BACKUP_FILE"
        else
            error "Backup file not specified and no latest backup found"
            error "Usage: $0 <backup_file> [recovery_time]"
            exit 1
        fi
    fi
    
    # Download from S3 if needed
    if [[ "$BACKUP_FILE" == s3://* ]]; then
        download_from_s3 "$BACKUP_FILE"
    fi
    
    # Determine backup type and restore
    if [ -d "$BACKUP_FILE" ]; then
        restore_incremental "$BACKUP_FILE"
    elif [[ "$BACKUP_FILE" == *.sql ]] || [[ "$BACKUP_FILE" == *.sql.gz ]]; then
        restore_full "$BACKUP_FILE"
    else
        error "Unknown backup file type: $BACKUP_FILE"
        exit 1
    fi
    
    # Verify restore
    verify_restore
    
    log "=== Restore completed successfully ==="
}

# Run main function
main "$@"

