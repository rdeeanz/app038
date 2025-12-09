#!/bin/bash
set -euo pipefail

# Install Vault
VAULT_VERSION="${vault_version}"
VAULT_ZIP="vault_${VAULT_VERSION}_linux_amd64.zip"
VAULT_URL="https://releases.hashicorp.com/vault/${VAULT_VERSION}/${VAULT_ZIP}"

curl -fsSL "${VAULT_URL}" -o /tmp/${VAULT_ZIP}
unzip -q /tmp/${VAULT_ZIP} -d /usr/local/bin/
chmod +x /usr/local/bin/vault
rm /tmp/${VAULT_ZIP}

# Create Vault user
useradd --system --home /etc/vault.d --shell /bin/false vault

# Create Vault directories
mkdir -p /etc/vault.d
mkdir -p /opt/vault/data
mkdir -p /var/log/vault

# Vault configuration
cat > /etc/vault.d/vault.hcl <<EOF
ui = ${vault_enable_ui}

storage "${vault_storage_backend}" {
%{ if vault_storage_backend == "s3" }
  bucket     = "${vault_s3_bucket}"
  region     = "us-west-2"
%{ endif }
%{ if vault_storage_backend == "dynamodb" }
  table      = "${vault_dynamodb_table}"
  region     = "us-west-2"
%{ endif }
}

%{ if vault_enable_auto_unseal }
seal "awskms" {
  region     = "us-west-2"
  kms_key_id = "${vault_kms_key_id}"
}
%{ endif }

listener "tcp" {
  address     = "0.0.0.0:8200"
  tls_disable = 1
}

%{ if vault_api_addr != "" }
api_addr = "${vault_api_addr}"
%{ endif }

%{ if vault_cluster_addr != "" }
cluster_addr = "${vault_cluster_addr}"
%{ endif }

log_level = "${vault_log_level}"

%{ if vault_license != "" }
license_path = "/etc/vault.d/vault.hclic"
%{ endif }
EOF

%{ if vault_license != "" }
# Write license file
cat > /etc/vault.d/vault.hclic <<'LICENSE'
${vault_license}
LICENSE
chmod 600 /etc/vault.d/vault.hclic
%{ endif }

# Set ownership
chown -R vault:vault /etc/vault.d /opt/vault /var/log/vault

# CloudWatch Logs agent (if enabled)
%{ if vault_cloudwatch_logs_enabled }
# Install CloudWatch Logs agent
wget https://s3.amazonaws.com/amazoncloudwatch-agent/amazon_linux/amd64/latest/amazon-cloudwatch-agent.rpm
rpm -U ./amazon-cloudwatch-agent.rpm

# Configure CloudWatch Logs
cat > /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json <<'CWLOGS'
{
  "logs": {
    "logs_collected": {
      "files": {
        "collect_list": [
          {
            "file_path": "/var/log/vault/vault.log",
            "log_group_name": "${vault_log_group}",
            "log_stream_name": "{instance_id}"
          }
        ]
      }
    }
  }
}
CWLOGS

systemctl enable amazon-cloudwatch-agent
systemctl start amazon-cloudwatch-agent
%{ endif }

# Systemd service for Vault
cat > /etc/systemd/system/vault.service <<'EOF'
[Unit]
Description=HashiCorp Vault
Documentation=https://www.vaultproject.io/docs/
After=network-online.target
Wants=network-online.target

[Service]
Type=notify
User=vault
Group=vault
ProtectSystem=full
ProtectHome=read-only
PrivateTmp=yes
PrivateDevices=yes
SecureBits=keep-caps
AmbientCapabilities=CAP_IPC_LOCK
CapabilityBoundingSet=CAP_SYSLOG CAP_IPC_LOCK
NoNewPrivileges=yes
ExecStart=/usr/local/bin/vault server -config=/etc/vault.d/vault.hcl
ExecReload=/bin/kill --signal HUP $MAINPID
KillMode=process
Restart=on-failure
RestartSec=5
TimeoutStopSec=30
LimitNOFILE=65536
LimitMEMLOCK=infinity

[Install]
WantedBy=multi-user.target
EOF

# Enable and start Vault
systemctl daemon-reload
systemctl enable vault
systemctl start vault

