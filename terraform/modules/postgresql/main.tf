terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }
}

# Random password if not provided
resource "random_password" "db_password" {
  count   = var.db_password == "" ? 1 : 0
  length  = 32
  special = true
}

# DB Parameter Group
resource "aws_db_parameter_group" "main" {
  count = var.db_parameter_group_name == "" ? 1 : 0

  name   = "${var.project_name}-${var.environment}-postgresql-${replace(var.db_engine_version, ".", "-")}"
  family = "postgres${replace(var.db_engine_version, "/\\.[0-9]+$/", "")}"

  parameter {
    name  = "shared_preload_libraries"
    value = "pg_stat_statements"
  }

  parameter {
    name  = "log_statement"
    value = "all"
  }

  parameter {
    name  = "log_min_duration_statement"
    value = "1000"
  }

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-db-parameter-group"
      Environment = var.environment
      ManagedBy    = "Terraform"
    },
    var.tags
  )
}

# DB Option Group (for PostgreSQL extensions)
resource "aws_db_option_group" "main" {
  count = var.db_option_group_name == "" ? 1 : 0

  name                     = "${var.project_name}-${var.environment}-postgresql-${replace(var.db_engine_version, ".", "-")}"
  option_group_description = "Option group for PostgreSQL ${var.db_engine_version}"
  engine_name              = "postgres"
  major_engine_version      = replace(var.db_engine_version, "/\\.[0-9]+$/", "")

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-db-option-group"
      Environment = var.environment
      ManagedBy    = "Terraform"
    },
    var.tags
  )
}

# DB Subnet Group (passed from networking module)
data "aws_db_subnet_group" "main" {
  name = var.db_subnet_group_id
}

# RDS Instance
resource "aws_db_instance" "main" {
  identifier = "${var.project_name}-${var.environment}-postgresql"

  # Engine configuration
  engine         = "postgres"
  engine_version = var.db_engine_version
  instance_class = var.db_instance_class

  # Database configuration
  db_name  = var.db_name
  username = var.db_username
  password = var.db_password != "" ? var.db_password : random_password.db_password[0].result
  port     = var.db_port

  # Storage configuration
  allocated_storage     = var.db_allocated_storage
  max_allocated_storage = var.db_max_allocated_storage
  storage_type          = var.db_storage_type
  storage_encrypted     = var.db_storage_encrypted
  kms_key_id            = var.db_kms_key_id != "" ? var.db_kms_key_id : null

  # Network configuration
  db_subnet_group_name   = var.db_subnet_group_id
  vpc_security_group_ids  = var.db_security_group_ids
  publicly_accessible     = var.db_publicly_accessible
  multi_az               = var.db_multi_az

  # Backup configuration
  backup_retention_period = var.db_backup_retention_period
  backup_window          = var.db_backup_window
  copy_tags_to_snapshot  = var.db_copy_tags_to_snapshot

  # Maintenance configuration
  maintenance_window         = var.db_maintenance_window
  auto_minor_version_upgrade = var.db_auto_minor_version_upgrade

  # Snapshot configuration
  skip_final_snapshot       = var.db_skip_final_snapshot
  final_snapshot_identifier = var.db_final_snapshot_identifier != "" ? var.db_final_snapshot_identifier : "${var.project_name}-${var.environment}-postgresql-final-snapshot-${formatdate("YYYY-MM-DD-hhmm", timestamp())}"

  # Monitoring configuration
  enabled_cloudwatch_logs_exports = var.db_enabled_cloudwatch_logs_exports
  performance_insights_enabled    = var.db_performance_insights_enabled
  performance_insights_retention_period = var.db_performance_insights_enabled ? var.db_performance_insights_retention_period : null

  # Parameter and option groups
  parameter_group_name = var.db_parameter_group_name != "" ? var.db_parameter_group_name : aws_db_parameter_group.main[0].name
  option_group_name    = var.db_option_group_name != "" ? var.db_option_group_name : aws_db_option_group.main[0].name

  # Protection
  deletion_protection = var.db_deletion_protection

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-postgresql"
      Environment = var.environment
      ManagedBy   = "Terraform"
    },
    var.tags
  )

  lifecycle {
    ignore_changes = [
      final_snapshot_identifier,
    ]
  }
}

# CloudWatch Alarms
resource "aws_cloudwatch_metric_alarm" "cpu_utilization" {
  alarm_name          = "${var.project_name}-${var.environment}-postgresql-cpu-utilization"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "CPUUtilization"
  namespace           = "AWS/RDS"
  period              = "300"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors RDS CPU utilization"
  alarm_actions       = []

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.main.id
  }

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-postgresql-cpu-alarm"
      Environment = var.environment
    },
    var.tags
  )
}

resource "aws_cloudwatch_metric_alarm" "database_connections" {
  alarm_name          = "${var.project_name}-${var.environment}-postgresql-database-connections"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "DatabaseConnections"
  namespace           = "AWS/RDS"
  period              = "300"
  statistic           = "Average"
  threshold           = "80"
  alarm_description   = "This metric monitors RDS database connections"
  alarm_actions       = []

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.main.id
  }

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-postgresql-connections-alarm"
      Environment = var.environment
    },
    var.tags
  )
}

resource "aws_cloudwatch_metric_alarm" "free_storage_space" {
  alarm_name          = "${var.project_name}-${var.environment}-postgresql-free-storage-space"
  comparison_operator = "LessThanThreshold"
  evaluation_periods  = "1"
  metric_name         = "FreeStorageSpace"
  namespace           = "AWS/RDS"
  period              = "300"
  statistic           = "Average"
  threshold           = "2000000000" # 2GB in bytes
  alarm_description   = "This metric monitors RDS free storage space"
  alarm_actions       = []

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.main.id
  }

  tags = merge(
    {
      Name        = "${var.project_name}-${var.environment}-postgresql-storage-alarm"
      Environment = var.environment
    },
    var.tags
  )
}

