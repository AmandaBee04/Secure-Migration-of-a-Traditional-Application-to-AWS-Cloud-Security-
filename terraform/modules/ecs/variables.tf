variable "name_prefix"                 { type = string }
variable "vpc_id"                      { type = string }
variable "public_subnet_ids"           { type = list(string) }
variable "private_subnet_ids"          { type = list(string) }
variable "ecs_security_group_id"       { type = string }
variable "alb_security_group_id"       { type = string }
variable "backend_image"               { type = string }
variable "frontend_image"              { type = string }
variable "db_host"                     { type = string }
variable "db_name"                     { type = string }
variable "db_username"                 { type = string }
variable "ecs_task_execution_role_arn" { type = string }
variable "ecs_task_role_arn"           { type = string }
variable "tags"                        { type = map(string) }

variable "db_password" {
  type      = string
  sensitive = true
}

variable "password_pepper" {
  type      = string
  sensitive = true
}

variable "acm_certificate_arn" {
  type        = string
  description = "ACM certificate ARN for HTTPS."
  default     = ""
}
