#!/bin/bash
# Vault Backup Script for App038
# Backs up Vault data, policies, and configuration

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/vault}"
RETENTION_DAYS="${RETENTION_DAYS:-90}"
VAULT_ADDR="${VAULT_ADDR:-http://localhost:8200}"
VAULT_TOKEN="${VAULT_TOKEN:-}"
S3_BUCKET="${S3_BUCKET:-}"
S3_PREFIX="${S3_PREFIX:-vault-backups/}"
BACKUP_TYPE="${BACKUP_TYPE:-full}"  # full, policies, snapshot

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
        error "Ensure VAULT_ADDR and VAULT_TOKEN are set correctly"
        exit 1
    fi
    
    log "Vault connection successful"
}

# Create backup directory
create_backup_dir() {
    mkdir -p "$BACKUP_DIR"
    log "Backup directory: $BACKUP_DIR"
}

# Full backup (snapshot)
full_backup() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="${BACKUP_DIR}/vault_snapshot_${timestamp}.snap"
    
    log "Starting full Vault snapshot..."
    
    # Vault snapshot requires operator privileges
    vault operator raft snapshot save "$backup_file"
    
    if [ $? -eq 0 ]; then
        log "Full snapshot completed: $backup_file"
        echo "$backup_file" > "${BACKUP_DIR}/latest_snapshot.txt"
        
        # Compress snapshot
        if command -v gzip &> /dev/null; then
            gzip "$backup_file"
            backup_file="${backup_file}.gz"
            log "Snapshot compressed: $backup_file"
        fi
        
        echo "$backup_file"
    else
        error "Full snapshot failed"
        exit 1
    fi
}

# Backup policies
backup_policies() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local policies_dir="${BACKUP_DIR}/policies_${timestamp}"
    
    log "Backing up Vault policies..."
    
    mkdir -p "$policies_dir"
    
    # List all policies
    local policies=$(vault policy list)
    
    for policy in $policies; do
        log "Backing up policy: $policy"
        vault policy read "$policy" > "${policies_dir}/${policy}.hcl"
    done
    
    # Create archive
    local archive_file="${BACKUP_DIR}/policies_${timestamp}.tar.gz"
    tar -czf "$archive_file" -C "$policies_dir" .
    rm -rf "$policies_dir"
    
    log "Policies backup completed: $archive_file"
    echo "$archive_file"
}

# Backup secrets (read-only, metadata only)
backup_secrets_metadata() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local metadata_file="${BACKUP_DIR}/secrets_metadata_${timestamp}.json"
    
    log "Backing up secrets metadata (read-only)..."
    
    # Note: This only backs up metadata, not actual secret values
    # For security reasons, actual secrets should not be backed up
    
    cat > "$metadata_file" <<EOF
{
  "timestamp": "$(date -Iseconds)",
  "mounts": $(vault auth list -format=json),
  "secrets_engines": $(vault secrets list -format=json),
  "policies": $(vault policy list -format=json)
}
EOF
    
    log "Secrets metadata backup completed: $metadata_file"
    echo "$metadata_file"
}

# Backup audit logs (if available)
backup_audit_logs() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local audit_dir="${BACKUP_DIR}/audit_${timestamp}"
    
    log "Backing up audit logs..."
    
    mkdir -p "$audit_dir"
    
    # List audit devices
    local audit_devices=$(vault audit list -format=json | jq -r 'keys[]' 2>/dev/null || echo "")
    
    if [ -z "$audit_devices" ]; then
        warning "No audit devices configured"
        return
    fi
    
    for device in $audit_devices; do
        log "Backing up audit device: $device"
        vault audit read "$device" > "${audit_dir}/${device}.json" 2>/dev/null || true
    done
    
    # Create archive
    local archive_file="${BACKUP_DIR}/audit_${timestamp}.tar.gz"
    tar -czf "$archive_file" -C "$audit_dir" . 2>/dev/null || true
    rm -rf "$audit_dir"
    
    log "Audit logs backup completed: $archive_file"
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
    
    find "$BACKUP_DIR" -type f -name "*.snap*" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -type f -name "policies_*.tar.gz" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -type f -name "secrets_metadata_*.json" -mtime +$RETENTION_DAYS -delete
    find "$BACKUP_DIR" -type f -name "audit_*.tar.gz" -mtime +$RETENTION_DAYS -delete
    
    log "Cleanup completed"
}

# Verify backup
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
    elif [[ "$backup_file" == *.snap ]]; then
        if [ -f "$backup_file" ] && [ -s "$backup_file" ]; then
            log "Backup file exists and is not empty"
        else
            error "Backup file is invalid"
            return 1
        fi
    fi
}

# Generate backup report
generate_report() {
    local backup_files=("$@")
    local report_file="${BACKUP_DIR}/backup_report_$(date +%Y%m%d_%H%M%S).json"
    
    local report_data="{"
    report_data+="\"timestamp\": \"$(date -Iseconds)\","
    report_data+="\"backup_type\": \"$BACKUP_TYPE\","
    report_data+="\"vault_addr\": \"$VAULT_ADDR\","
    report_data+="\"files\": ["
    
    local first=true
    for file in "${backup_files[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            report_data+=","
        fi
        
        local file_size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        report_data+="{\"file\": \"$(basename $file)\", \"size_bytes\": $file_size}"
    done
    
    report_data+="],"
    report_data+="\"retention_days\": $RETENTION_DAYS"
    report_data+="}"
    
    echo "$report_data" | jq '.' > "$report_file" 2>/dev/null || echo "$report_data" > "$report_file"
    
    log "Backup report generated: $report_file"
    echo "$report_file"
}

# Main execution
main() {
    log "=== Vault Backup Script ==="
    log "Backup Type: $BACKUP_TYPE"
    log "Vault Address: $VAULT_ADDR"
    
    # Check Vault connection
    check_vault
    
    # Create backup directory
    create_backup_dir
    
    local backup_files=()
    
    # Perform backup based on type
    case "$BACKUP_TYPE" in
        full)
            backup_file=$(full_backup)
            verify_backup "$backup_file"
            backup_files+=("$backup_file")
            
            # Also backup policies and metadata
            policies_file=$(backup_policies)
            backup_files+=("$policies_file")
            
            metadata_file=$(backup_secrets_metadata)
            backup_files+=("$metadata_file")
            ;;
        policies)
            policies_file=$(backup_policies)
            backup_files+=("$policies_file")
            ;;
        snapshot)
            backup_file=$(full_backup)
            verify_backup "$backup_file"
            backup_files+=("$backup_file")
            ;;
        *)
            error "Unknown backup type: $BACKUP_TYPE"
            exit 1
            ;;
    esac
    
    # Generate report
    report_file=$(generate_report "${backup_files[@]}")
    backup_files+=("$report_file")
    
    # Upload to S3
    for file in "${backup_files[@]}"; do
        upload_to_s3 "$file"
    done
    
    # Cleanup old backups
    cleanup_old_backups
    
    log "=== Backup completed successfully ==="
    log "Backup files:"
    for file in "${backup_files[@]}"; do
        log "  - $file"
    done
}

# Run main function
main "$@"

