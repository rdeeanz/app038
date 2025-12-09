#!/bin/bash
# Vault Restore Script for App038
# Restores Vault from snapshot

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/vault}"
VAULT_ADDR="${VAULT_ADDR:-http://localhost:8200}"
VAULT_TOKEN="${VAULT_TOKEN:-}"
SNAPSHOT_FILE="${1:-}"
S3_BUCKET="${S3_BUCKET:-}"
S3_PREFIX="${S3_PREFIX:-vault-backups/}"

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

# Check Vault connection
check_vault() {
    log "Checking Vault connection..."
    
    if ! vault status &>/dev/null; then
        error "Cannot connect to Vault at $VAULT_ADDR"
        exit 1
    fi
    
    log "Vault connection successful"
}

# Download from S3 if needed
download_from_s3() {
    if [ -z "$S3_BUCKET" ] || [ -z "$SNAPSHOT_FILE" ]; then
        return
    fi
    
    log "Downloading snapshot from S3..."
    
    if command -v aws &> /dev/null; then
        aws s3 cp "s3://${S3_BUCKET}/${S3_PREFIX}${SNAPSHOT_FILE}" "$BACKUP_DIR/$SNAPSHOT_FILE"
        
        if [ $? -eq 0 ]; then
            log "Download successful"
            SNAPSHOT_FILE="$BACKUP_DIR/$SNAPSHOT_FILE"
        else
            error "S3 download failed"
            exit 1
        fi
    else
        error "AWS CLI not found"
        exit 1
    fi
}

# Decompress if needed
decompress() {
    local file="$1"
    
    if [[ "$file" == *.gz ]]; then
        log "Decompressing snapshot..."
        gunzip "$file"
        file="${file%.gz}"
        echo "$file"
    else
        echo "$file"
    fi
}

# Restore from snapshot
restore_snapshot() {
    local snapshot_file="$1"
    
    warning "This will restore Vault from snapshot and may overwrite existing data"
    warning "Ensure Vault is in maintenance mode or has no active traffic"
    read -p "Are you sure? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        log "Restore cancelled"
        exit 0
    fi
    
    log "Restoring Vault from snapshot: $snapshot_file"
    
    # Vault must be sealed before restore
    log "Sealing Vault..."
    vault operator seal
    
    # Wait for seal
    sleep 5
    
    # Restore snapshot
    vault operator raft snapshot restore "$snapshot_file"
    
    if [ $? -eq 0 ]; then
        log "Snapshot restore completed"
        log "Vault is sealed. Unseal with: vault operator unseal <key>"
    else
        error "Snapshot restore failed"
        exit 1
    fi
}

# Restore policies
restore_policies() {
    local policies_archive="$1"
    
    log "Restoring Vault policies from: $policies_archive"
    
    local temp_dir=$(mktemp -d)
    tar -xzf "$policies_archive" -C "$temp_dir"
    
    for policy_file in "$temp_dir"/*.hcl; do
        if [ -f "$policy_file" ]; then
            local policy_name=$(basename "$policy_file" .hcl)
            log "Restoring policy: $policy_name"
            vault policy write "$policy_name" "$policy_file"
        fi
    done
    
    rm -rf "$temp_dir"
    
    log "Policies restore completed"
}

# Main execution
main() {
    log "=== Vault Restore Script ==="
    
    # Check Vault connection
    check_vault
    
    # Determine snapshot file
    if [ -z "$SNAPSHOT_FILE" ]; then
        if [ -f "${BACKUP_DIR}/latest_snapshot.txt" ]; then
            SNAPSHOT_FILE=$(cat "${BACKUP_DIR}/latest_snapshot.txt")
            log "Using latest snapshot: $SNAPSHOT_FILE"
        else
            error "Snapshot file not specified and no latest snapshot found"
            error "Usage: $0 <snapshot_file>"
            exit 1
        fi
    fi
    
    # Download from S3 if needed
    if [[ "$SNAPSHOT_FILE" == s3://* ]]; then
        download_from_s3 "$SNAPSHOT_FILE"
    fi
    
    # Decompress if needed
    SNAPSHOT_FILE=$(decompress "$SNAPSHOT_FILE")
    
    # Restore snapshot
    restore_snapshot "$SNAPSHOT_FILE"
    
    log "=== Restore completed successfully ==="
    log "Remember to unseal Vault after restore"
}

# Run main function
main "$@"

