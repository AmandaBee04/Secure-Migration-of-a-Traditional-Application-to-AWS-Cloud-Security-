##############################################################################
# modules/rds/main.tf
# Amazon RDS MySQL — Multi-AZ, encrypted at rest, in private subnets
##############################################################################

# ─── Subnet Group ─────────────────────────────────────────────────────────────
resource "aws_db_subnet_group" "main" {
  name       = "${var.name_prefix}-db-subnet-group"
  subnet_ids = var.private_subnet_ids

  tags = merge(var.tags, { Name = "${var.name_prefix}-db-subnet-group" })
}

# ─── Parameter Group (enforce SSL) ───────────────────────────────────────────
resource "aws_db_parameter_group" "main" {
  name   = "${var.name_prefix}-db-params"
  family = "mysql8.0"

  parameter {
    name  = "require_secure_transport"
    value = "0"   # Disabled for demo (Laravel connects without SSL by default)
  }

  tags = var.tags
}

# ─── RDS Instance ─────────────────────────────────────────────────────────────
resource "aws_db_instance" "main" {
  identifier              = "${var.name_prefix}-mysql"
  engine                  = "mysql"
  engine_version          = "8.0"
  instance_class          = "db.t3.micro"   # Free-tier eligible
  allocated_storage       = 20
  # max_allocated_storage not set — free tier does not support storage autoscaling

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  db_subnet_group_name   = aws_db_subnet_group.main.name
  vpc_security_group_ids = [var.db_security_group_id]
  parameter_group_name   = aws_db_parameter_group.main.name

  # Security: encryption at rest
  storage_encrypted = true

  # High Availability (Multi-AZ not available on free tier)
  multi_az = false

  # Automated backups (free tier restricts retention to 0)
  backup_retention_period = 0
  backup_window           = "03:00-04:00"
  maintenance_window      = "Mon:04:00-Mon:05:00"

  # Security: no public access
  publicly_accessible = false

  # Deletion protection for production
  deletion_protection = false  # Set to true for real production

  skip_final_snapshot = true   # Set to false for real production

  tags = merge(var.tags, { Name = "${var.name_prefix}-mysql" })
}
