output "alb_arn"      { value = aws_lb.main.arn }
output "alb_dns_name" { value = aws_lb.main.dns_name }
output "cluster_name" { value = aws_ecs_cluster.main.name }
output "backend_ecr_url"  { value = aws_ecr_repository.backend.repository_url }
output "frontend_ecr_url" { value = aws_ecr_repository.frontend.repository_url }
