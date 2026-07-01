##############################################################################
# outputs.tf — Outputs shown after terraform apply
##############################################################################

output "alb_dns_name" {
  description = "Application Load Balancer DNS name — use this to access the app"
  value       = module.ecs.alb_dns_name
}

output "rds_endpoint" {
  description = "RDS database endpoint (private)"
  value       = module.rds.db_endpoint
  sensitive   = true
}

output "vpc_id" {
  description = "VPC ID"
  value       = module.vpc.vpc_id
}

output "cloudtrail_bucket" {
  description = "S3 bucket storing CloudTrail logs"
  value       = module.monitoring.cloudtrail_bucket_name
}
