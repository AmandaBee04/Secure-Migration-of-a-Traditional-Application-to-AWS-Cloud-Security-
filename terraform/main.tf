##############################################################################
# main.tf — Root module
# Student Management System — Secure AWS Deployment
# TC1L_GROUP 6 | CCS6344 T2610
##############################################################################

terraform {
  required_version = ">= 1.5.0"
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

provider "aws" {
  region = var.aws_region
}

# ─── Random suffix to avoid naming collisions ────────────────────────────────
resource "random_id" "suffix" {
  byte_length = 4
}

locals {
  name_prefix = "${var.project_name}-${var.environment}"
  tags = {
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "Terraform"
    Group       = "TC1L_GROUP6"
  }
}

# ─── Networking (VPC, subnets, gateways) ─────────────────────────────────────
module "vpc" {
  source      = "./modules/vpc"
  name_prefix = local.name_prefix
  tags        = local.tags
}

# ─── Security (IAM roles, Security Groups, NACLs) ────────────────────────────
module "security" {
  source      = "./modules/security"
  name_prefix = local.name_prefix
  vpc_id      = module.vpc.vpc_id
  tags        = local.tags
}

# ─── RDS MySQL (Multi-AZ, encrypted) ─────────────────────────────────────────
module "rds" {
  source               = "./modules/rds"
  name_prefix          = local.name_prefix
  private_subnet_ids   = module.vpc.private_subnet_ids
  db_security_group_id = module.security.db_security_group_id
  db_name              = var.db_name
  db_username          = var.db_username
  db_password          = var.db_password
  tags                 = local.tags
}

# ─── ECS/Fargate (application tier) ──────────────────────────────────────────
module "ecs" {
  source                     = "./modules/ecs"
  name_prefix                = local.name_prefix
  vpc_id                     = module.vpc.vpc_id
  public_subnet_ids          = module.vpc.public_subnet_ids
  private_subnet_ids         = module.vpc.private_subnet_ids
  ecs_security_group_id      = module.security.ecs_security_group_id
  alb_security_group_id      = module.security.alb_security_group_id
  backend_image              = var.backend_image
  frontend_image             = var.frontend_image
  db_host                    = module.rds.db_endpoint
  db_name                    = var.db_name
  db_username                = var.db_username
  db_password                = var.db_password
  ecs_task_execution_role_arn = module.security.ecs_task_execution_role_arn
  ecs_task_role_arn           = module.security.ecs_task_role_arn
  password_pepper             = var.password_pepper
  acm_certificate_arn         = var.acm_certificate_arn
  tags                        = local.tags
}

# ─── WAF (Web Application Firewall) ──────────────────────────────────────────
module "waf" {
  source      = "./modules/waf"
  name_prefix = local.name_prefix
  alb_arn     = module.ecs.alb_arn
  tags        = local.tags
}

# ─── Monitoring (CloudTrail, CloudWatch) ─────────────────────────────────────
module "monitoring" {
  source      = "./modules/monitoring"
  name_prefix = local.name_prefix
  tags        = local.tags
  suffix      = random_id.suffix.hex
}
