variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
}

variable "cluster_name" {
  description = "Name of the EKS cluster"
  type        = string
  default     = ""
}

variable "cluster_version" {
  description = "Kubernetes version for the cluster"
  type        = string
  default     = "1.28"
}

variable "vpc_id" {
  description = "ID of the VPC"
  type        = string
}

variable "subnet_ids" {
  description = "List of subnet IDs for the cluster"
  type        = list(string)
}

variable "cluster_security_group_id" {
  description = "Security group ID for the cluster"
  type        = string
  default     = ""
}

variable "endpoint_private_access" {
  description = "Enable private API server endpoint"
  type        = bool
  default     = true
}

variable "endpoint_public_access" {
  description = "Enable public API server endpoint"
  type        = bool
  default     = true
}

variable "endpoint_public_access_cidrs" {
  description = "CIDR blocks allowed to access the public endpoint"
  type        = list(string)
  default     = ["0.0.0.0/0"]
}

variable "enabled_cluster_log_types" {
  description = "List of control plane logging types to enable"
  type        = list(string)
  default     = ["api", "audit", "authenticator", "controllerManager", "scheduler"]
}

variable "cluster_log_retention_in_days" {
  description = "Number of days to retain cluster logs"
  type        = number
  default     = 7
}

variable "node_groups" {
  description = "Map of node group configurations"
  type = map(object({
    instance_types     = list(string)
    capacity_type     = string
    min_size          = number
    max_size          = number
    desired_size      = number
    disk_size         = number
    ami_type          = string
    labels            = map(string)
    taints            = list(object({
      key    = string
      value  = string
      effect = string
    }))
  }))
  default = {}
}

variable "fargate_profiles" {
  description = "Map of Fargate profile configurations"
  type = map(object({
    selectors = list(object({
      namespace = string
      labels    = map(string)
    }))
    subnet_ids = list(string)
  }))
  default = {}
}

variable "addons" {
  description = "Map of EKS addon configurations"
  type = map(object({
    version = string
  }))
  default = {
    vpc-cni = {
      version = "v1.14.0-eksbuild.1"
    }
    kube-proxy = {
      version = "v1.28.1-eksbuild.1"
    }
    coredns = {
      version = "v1.10.1-eksbuild.1"
    }
    ebs-csi-driver = {
      version = "v1.20.0-eksbuild.1"
    }
  }
}

variable "enable_irsa" {
  description = "Enable IAM Roles for Service Accounts"
  type        = bool
  default     = true
}

variable "tags" {
  description = "Additional tags to apply to resources"
  type        = map(string)
  default     = {}
}

variable "create_aws_auth_configmap" {
  description = "Create aws-auth configmap"
  type        = bool
  default     = true
}

variable "manage_aws_auth_configmap" {
  description = "Manage aws-auth configmap"
  type        = bool
  default     = true
}

variable "aws_auth_users" {
  description = "List of AWS users to add to aws-auth configmap"
  type = list(object({
    userarn  = string
    username = string
    groups   = list(string)
  }))
  default = []
}

variable "aws_auth_roles" {
  description = "List of AWS roles to add to aws-auth configmap"
  type = list(object({
    rolearn  = string
    username = string
    groups   = list(string)
  }))
  default = []
}

