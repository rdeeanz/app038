#!/bin/bash
# Backup Cron Script
# Schedules and manages automated backups

set -e

BACKUP_DIR="${BACKUP_DIR:-/backups}"
LOG_DIR="${LOG_DIR:-/var/log/backups}"

# Create directories
mkdir -p "$BACKUP_DIR" "$LOG_DIR"

# PostgreSQL Backup Schedule
# Full backup daily at 2 AM
cat > /etc/cron.d/postgresql-backup <<EOF
0 2 * * * root /scripts/backup/postgresql-backup.sh full >> $LOG_DIR/postgresql-backup.log 2>&1
EOF

# Incremental backup every 6 hours
cat > /etc/cron.d/postgresql-backup-incremental <<EOF
0 */6 * * * root /scripts/backup/postgresql-backup.sh incremental >> $LOG_DIR/postgresql-backup-incremental.log 2>&1
EOF

# Vault Backup Schedule
# Full backup daily at 3 AM
cat > /etc/cron.d/vault-backup <<EOF
0 3 * * * root /scripts/backup/vault-backup.sh full >> $LOG_DIR/vault-backup.log 2>&1
EOF

# Policies backup weekly
cat > /etc/cron.d/vault-backup-policies <<EOF
0 4 * * 0 root /scripts/backup/vault-backup.sh policies >> $LOG_DIR/vault-backup-policies.log 2>&1
EOF

log "Backup cron jobs configured"
log "PostgreSQL: Full daily at 2 AM, Incremental every 6 hours"
log "Vault: Full daily at 3 AM, Policies weekly on Sunday at 4 AM"

