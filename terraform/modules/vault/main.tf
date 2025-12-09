terraform {
  required_version = ">= 1.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0"
    }
    http = {
      source  = "hashicorp/http"
      version = "~> 3.4"
    }
  }
}

locals {
  vault_cluster_name = var.vault_cluster_name != "" ? var.vault_cluster_name : "${var.project_name}-${var.environment}-vault"
  vault_log_group    = var.vault_cloudwatch_log_group_name != "" ? var.vault_cloudwatch_log_group_name : "/aws/ec2/${local.vault_cluster_name}"
}

# KMS Key for Vault Auto-Unseal
resource "aws_kms_key" "vault" {
  count = var.vault_enable_auto_unseal && var.vault_kms_key_id == "" ? 1 : 0

  description             = "KMS key for Vault auto-unseal"
  deletion_window_in_days = 7
  enable_key_rotation     = true

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-kms-key"
      Environment = var.environment
    },
    var.tags
  )
}

resource "aws_kms_alias" "vault" {
  count = var.vault_enable_auto_unseal && var.vault_kms_key_id == "" ? 1 : 0

  name          = "alias/${local.vault_cluster_name}"
  target_key_id = aws_kms_key.vault[0].key_id
}

# S3 Bucket for Vault Storage (if using S3 backend)
resource "aws_s3_bucket" "vault" {
  count = var.vault_storage_backend == "s3" && var.vault_s3_bucket == "" ? 1 : 0

  bucket = "${local.vault_cluster_name}-storage"

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-storage"
      Environment = var.environment
    },
    var.tags
  )
}

resource "aws_s3_bucket_versioning" "vault" {
  count = var.vault_storage_backend == "s3" && var.vault_s3_bucket == "" ? 1 : 0

  bucket = aws_s3_bucket.vault[0].id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "vault" {
  count = var.vault_storage_backend == "s3" && var.vault_s3_bucket == "" ? 1 : 0

  bucket = aws_s3_bucket.vault[0].id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "vault" {
  count = var.vault_storage_backend == "s3" && var.vault_s3_bucket == "" ? 1 : 0

  bucket = aws_s3_bucket.vault[0].id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets  = true
}

# DynamoDB Table for Vault Storage (if using DynamoDB backend)
resource "aws_dynamodb_table" "vault" {
  count = var.vault_storage_backend == "dynamodb" && var.vault_dynamodb_table == "" ? 1 : 0

  name           = "${local.vault_cluster_name}-storage"
  billing_mode   = "PAY_PER_REQUEST"
  hash_key       = "Path"
  range_key      = "Key"

  attribute {
    name = "Path"
    type = "S"
  }

  attribute {
    name = "Key"
    type = "S"
  }

  server_side_encryption {
    enabled = true
  }

  point_in_time_recovery {
    enabled = true
  }

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-storage"
      Environment = var.environment
    },
    var.tags
  )
}

# IAM Role for Vault
resource "aws_iam_role" "vault" {
  name = "${local.vault_cluster_name}-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "ec2.amazonaws.com"
        }
      }
    ]
  })

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-role"
      Environment = var.environment
    },
    var.tags
  )
}

# IAM Policy for Vault
resource "aws_iam_role_policy" "vault" {
  name = "${local.vault_cluster_name}-policy"
  role = aws_iam_role.vault.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = concat(
      # KMS permissions for auto-unseal
      var.vault_enable_auto_unseal ? [
        {
          Effect = "Allow"
          Action = [
            "kms:Encrypt",
            "kms:Decrypt",
            "kms:DescribeKey"
          ]
          Resource = var.vault_kms_key_id != "" ? var.vault_kms_key_id : aws_kms_key.vault[0].arn
        }
      ] : [],
      # S3 permissions
      var.vault_storage_backend == "s3" ? [
        {
          Effect = "Allow"
          Action = [
            "s3:PutObject",
            "s3:GetObject",
            "s3:DeleteObject",
            "s3:ListBucket"
          ]
          Resource = var.vault_s3_bucket != "" ? [
            "arn:aws:s3:::${var.vault_s3_bucket}",
            "arn:aws:s3:::${var.vault_s3_bucket}/*"
          ] : [
            aws_s3_bucket.vault[0].arn,
            "${aws_s3_bucket.vault[0].arn}/*"
          ]
        }
      ] : [],
      # DynamoDB permissions
      var.vault_storage_backend == "dynamodb" ? [
        {
          Effect = "Allow"
          Action = [
            "dynamodb:DescribeLimits",
            "dynamodb:DescribeTimeToLive",
            "dynamodb:ListTagsOfResource",
            "dynamodb:DescribeReservedCapacityOfferings",
            "dynamodb:DescribeReservedCapacity",
            "dynamodb:ListTables",
            "dynamodb:BatchGetItem",
            "dynamodb:BatchWriteItem",
            "dynamodb:CreateTable",
            "dynamodb:DeleteItem",
            "dynamodb:GetItem",
            "dynamodb:GetRecords",
            "dynamodb:PutItem",
            "dynamodb:Query",
            "dynamodb:UpdateItem",
            "dynamodb:Scan",
            "dynamodb:DescribeTable"
          ]
          Resource = var.vault_dynamodb_table != "" ? "arn:aws:dynamodb:*:*:table/${var.vault_dynamodb_table}" : aws_dynamodb_table.vault[0].arn
        }
      ] : [],
      # CloudWatch Logs permissions
      var.vault_cloudwatch_logs_enabled ? [
        {
          Effect = "Allow"
          Action = [
            "logs:CreateLogGroup",
            "logs:CreateLogStream",
            "logs:PutLogEvents",
            "logs:DescribeLogStreams"
          ]
          Resource = "arn:aws:logs:*:*:log-group:${local.vault_log_group}*"
        }
      ] : []
    )
  })
}

resource "aws_iam_instance_profile" "vault" {
  name = "${local.vault_cluster_name}-profile"
  role = aws_iam_role.vault.name

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-profile"
      Environment = var.environment
    },
    var.tags
  )
}

# Security Group for Vault
resource "aws_security_group" "vault" {
  name        = "${local.vault_cluster_name}-sg"
  description = "Security group for Vault cluster"
  vpc_id      = var.vpc_id

  ingress {
    description = "Vault API"
    from_port   = 8200
    to_port     = 8200
    protocol    = "tcp"
    cidr_blocks = length(var.vault_allowed_cidr_blocks) > 0 ? var.vault_allowed_cidr_blocks : [var.vpc_id != "" ? data.aws_vpc.main.cidr_block : "0.0.0.0/0"]
  }

  ingress {
    description = "Vault cluster communication"
    from_port   = 8201
    to_port     = 8201
    protocol    = "tcp"
    self        = true
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = length(var.vault_allowed_cidr_blocks) > 0 ? var.vault_allowed_cidr_blocks : [var.vpc_id != "" ? data.aws_vpc.main.cidr_block : "10.0.0.0/8"]
  }

  egress {
    description = "Allow all outbound"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-sg"
      Environment = var.environment
    },
    var.tags
  )
}

data "aws_vpc" "main" {
  id = var.vpc_id
}

# CloudWatch Log Group
resource "aws_cloudwatch_log_group" "vault" {
  count = var.vault_cloudwatch_logs_enabled ? 1 : 0

  name              = local.vault_log_group
  retention_in_days = 7

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-logs"
      Environment = var.environment
    },
    var.tags
  )
}

# User Data Script for Vault
locals {
  vault_user_data = var.vault_user_data != "" ? var.vault_user_data : templatefile("${path.module}/templates/vault-user-data.sh", {
    vault_version          = var.vault_version
    vault_storage_backend  = var.vault_storage_backend
    vault_s3_bucket        = var.vault_s3_bucket != "" ? var.vault_s3_bucket : (var.vault_storage_backend == "s3" ? aws_s3_bucket.vault[0].id : "")
    vault_dynamodb_table   = var.vault_dynamodb_table != "" ? var.vault_dynamodb_table : (var.vault_storage_backend == "dynamodb" ? aws_dynamodb_table.vault[0].name : "")
    vault_kms_key_id        = var.vault_kms_key_id != "" ? var.vault_kms_key_id : (var.vault_enable_auto_unseal ? aws_kms_key.vault[0].id : "")
    vault_enable_ui         = var.vault_enable_ui
    vault_log_level         = var.vault_log_level
    vault_log_group          = local.vault_log_group
    vault_license            = var.vault_license
    vault_api_addr           = var.vault_api_addr
    vault_cluster_addr       = var.vault_cluster_addr
  })
}

# Launch Template for Vault
resource "aws_launch_template" "vault" {
  name_prefix   = "${local.vault_cluster_name}-"
  image_id      = var.vault_ami_id != "" ? var.vault_ami_id : data.aws_ami.vault.id
  instance_type = var.vault_instance_type
  key_name      = var.vault_key_name

  vpc_security_group_ids = concat(
    [aws_security_group.vault.id],
    var.security_group_ids
  )

  iam_instance_profile {
    name = aws_iam_instance_profile.vault.name
  }

  user_data = base64encode(local.vault_user_data)

  tag_specifications {
    resource_type = "instance"
    tags = merge(
      {
        Name        = "${local.vault_cluster_name}"
        Environment = var.environment
        ManagedBy   = "Terraform"
      },
      var.tags
    )
  }

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-template"
      Environment = var.environment
    },
    var.tags
  )
}

# AMI Data Source for Vault
data "aws_ami" "vault" {
  most_recent = true
  owners      = ["099720109477"] # Canonical

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}

# Auto Scaling Group for Vault
resource "aws_autoscaling_group" "vault" {
  name                = "${local.vault_cluster_name}-asg"
  vpc_zone_identifier = var.subnet_ids
  min_size            = var.vault_instance_count
  max_size            = var.vault_instance_count
  desired_capacity    = var.vault_instance_count

  launch_template {
    id      = aws_launch_template.vault.id
    version = "$Latest"
  }

  health_check_type         = "ELB"
  health_check_grace_period = 300

  tag {
    key                 = "Name"
    value               = "${local.vault_cluster_name}"
    propagate_at_launch = true
  }

  tag {
    key                 = "Environment"
    value               = var.environment
    propagate_at_launch = true
  }

  dynamic "tag" {
    for_each = var.tags
    content {
      key                 = tag.key
      value               = tag.value
      propagate_at_launch = true
    }
  }
}

# Application Load Balancer for Vault
resource "aws_lb" "vault" {
  count = var.vault_alb_enabled ? 1 : 0

  name               = "${local.vault_cluster_name}-alb"
  internal           = var.vault_alb_internal
  load_balancer_type = "application"
  security_groups    = [aws_security_group.vault_alb[0].id]
  subnets            = var.subnet_ids

  enable_deletion_protection = false

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-alb"
      Environment = var.environment
    },
    var.tags
  )
}

# Security Group for ALB
resource "aws_security_group" "vault_alb" {
  count = var.vault_alb_enabled ? 1 : 0

  name        = "${local.vault_cluster_name}-alb-sg"
  description = "Security group for Vault ALB"
  vpc_id      = var.vpc_id

  ingress {
    description = "HTTPS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = length(var.vault_allowed_cidr_blocks) > 0 ? var.vault_allowed_cidr_blocks : ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = length(var.vault_allowed_cidr_blocks) > 0 ? var.vault_allowed_cidr_blocks : ["0.0.0.0/0"]
  }

  egress {
    description = "Allow all outbound"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-alb-sg"
      Environment = var.environment
    },
    var.tags
  )
}

# Target Group for Vault
resource "aws_lb_target_group" "vault" {
  count = var.vault_alb_enabled ? 1 : 0

  name     = "${local.vault_cluster_name}-tg"
  port     = 8200
  protocol = "HTTP"
  vpc_id   = var.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 2
    timeout             = 5
    interval            = 30
    path                = "/v1/sys/health"
    matcher             = "200,429"
  }

  tags = merge(
    {
      Name        = "${local.vault_cluster_name}-tg"
      Environment = var.environment
    },
    var.tags
  )
}

# ALB Listener (HTTPS)
resource "aws_lb_listener" "vault_https" {
  count = var.vault_alb_enabled && var.vault_alb_certificate_arn != "" ? 1 : 0

  load_balancer_arn = aws_lb.vault[0].arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = var.vault_alb_certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.vault[0].arn
  }
}

# ALB Listener (HTTP - redirect to HTTPS)
resource "aws_lb_listener" "vault_http" {
  count = var.vault_alb_enabled ? 1 : 0

  load_balancer_arn = aws_lb.vault[0].arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type = var.vault_alb_certificate_arn != "" ? "redirect" : "forward"

    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }

    target_group_arn = var.vault_alb_certificate_arn == "" ? aws_lb_target_group.vault[0].arn : null
  }
}

# Attach Target Group to Auto Scaling Group
resource "aws_autoscaling_attachment" "vault" {
  count = var.vault_alb_enabled ? 1 : 0

  autoscaling_group_name = aws_autoscaling_group.vault.id
  lb_target_group_arn    = aws_lb_target_group.vault[0].arn
}

