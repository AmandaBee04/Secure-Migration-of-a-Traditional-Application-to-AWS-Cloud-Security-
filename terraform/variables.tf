##############################################################################
# variables.tf — Input variables
##############################################################################

variable "aws_region" {
  description = "AWS region to deploy into"
  type        = string
  default     = "ap-southeast-1" # Singapore — closest to Malaysia
}

variable "project_name" {
  description = "Project name used as resource name prefix"
  type        = string
  default     = "sms"
}

variable "environment" {
  description = "Deployment environment (production / staging)"
  type        = string
  default     = "production"
}

variable "db_name" {
  description = "MySQL database name"
  type        = string
  default     = "sms_db"
}

variable "db_username" {
  description = "MySQL master username"
  type        = string
  default     = "sms_admin"
}

variable "db_password" {
  description = "MySQL master password — set via TF_VAR_db_password env var"
  type        = string
  sensitive   = true
}

variable "password_pepper" {
  description = "Application password pepper — set via TF_VAR_password_pepper env var"
  type        = string
  sensitive   = true
}

variable "backend_image" {
  description = "ECR image URI for the Laravel backend container"
  type        = string
  # Example: 123456789.dkr.ecr.ap-southeast-1.amazonaws.com/sms-backend:latest
}

variable "frontend_image" {
  description = "ECR image URI for the React frontend container"
  type        = string
  # Example: 123456789.dkr.ecr.ap-southeast-1.amazonaws.com/sms-frontend:latest
}

variable "acm_certificate_arn" {
  description = "ACM certificate ARN for HTTPS listener on the ALB"
  type        = string
  default     = ""
}
