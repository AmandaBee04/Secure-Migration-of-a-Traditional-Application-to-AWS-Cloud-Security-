# Student Management System (SMS)

**CCS6344 – Database and Cloud Security | Assignment 2**

A secure, cloud-native Student Management System migrated from a monolithic on-premises architecture to AWS, implementing defence-in-depth security across all layers.

---

## Project Structure

```
student-management-system/
├── studentManagement/        # Laravel 10 backend (PHP 8.2)
├── 6Day-Grind/6Day-Grind/    # React 19 + Vite frontend
├── terraform/                # AWS infrastructure as code
│   └── modules/
│       ├── vpc/              # VPC, subnets, NAT Gateways, IPv6
│       ├── security/         # Security groups, IAM roles
│       ├── ecs/              # ECS Fargate, ALB, ECR, TLS
│       ├── rds/              # RDS MySQL 8.0 Multi-AZ
│       ├── waf/              # AWS WAF v2 rules
│       └── monitoring/       # CloudTrail, CloudWatch, S3
└── docker-compose.yml        # Local development environment
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | React 19, Vite, Tailwind CSS, Nginx Alpine |
| Backend | Laravel 10, PHP 8.2-FPM, Nginx, Debian Bookworm |
| Database | MySQL 8.0 (local) / Amazon RDS MySQL 8.0 (AWS) |
| Auth | Custom `RawTokenAuth` middleware (Bearer token via raw SQL) |
| Infrastructure | AWS ECS Fargate, ALB, RDS, WAF v2, CloudTrail, VPC |
| IaC | Terraform (modular) |
| Container Registry | Amazon ECR (AES-256 encrypted, scan-on-push) |

---

## AWS Architecture

```
Internet (IPv4 + IPv6)
    ↓
AWS WAF v2 (SQLi, XSS, Rate Limit 1000 req/5min)
    ↓
Application Load Balancer — dualstack, TLS 1.3, HTTP→HTTPS redirect
    /api/*  → Backend ECS Fargate (Laravel × 2 tasks)
    /*      → Frontend ECS Fargate (React/Nginx × 2 tasks)
                ↓
        RDS MySQL 8.0 — port 3306 SSL only, private subnet
```

All compute runs in **private subnets**. Only the ALB is in public subnets. RDS has no public access. CloudTrail logs all API events to encrypted S3.

---

## Local Development (Docker Compose)

### Prerequisites
- Docker Desktop
- Git

### Setup

```bash
git clone <repo-url>
cd student-management-system

# Copy and configure environment
cp .env.docker .env
# Edit .env — set DB_PASSWORD and PASSWORD_PEPPER

# Start all services (MySQL + Backend + Frontend)
docker compose up --build

# App available at http://localhost
```

### Default Seed Credentials

| Role | ID | Password |
|---|---|---|
| Admin | AD001 | 001 |
| Lecturer | MU001 | 001 |
| Lecturer | MU002 | 002 |
| Student | UC001 | 001 |
| Student | UC002 | 002 |
| Student | UC003 | 003 |

---

## AWS Deployment

### Prerequisites
- AWS CLI configured (`aws configure`)
- Terraform ≥ 1.0
- Docker Desktop

### Step 1 — Create `terraform.tfvars`

```hcl
# terraform/terraform.tfvars  (never commit this file)
db_password     = "YourStrongPassword123!"
password_pepper = "YourPepperSecret"
```

### Step 2 — Deploy Infrastructure (Phase 1: ECR only)

```bash
cd terraform
terraform init
terraform apply -target=module.ecs  # Creates ECR repositories first
```

### Step 3 — Build and Push Docker Images

```powershell
# Authenticate to ECR
$token = aws ecr get-login-password --region ap-southeast-1
docker login --username AWS --password $token 380648615583.dkr.ecr.ap-southeast-1.amazonaws.com

# Backend
docker build -t sms-backend ./studentManagement
docker tag sms-backend:latest 380648615583.dkr.ecr.ap-southeast-1.amazonaws.com/sms-backend:latest
docker push 380648615583.dkr.ecr.ap-southeast-1.amazonaws.com/sms-backend:latest

# Frontend
docker build -t sms-frontend ./6Day-Grind/6Day-Grind
docker tag sms-frontend:latest 380648615583.dkr.ecr.ap-southeast-1.amazonaws.com/sms-frontend:latest
docker push 380648615583.dkr.ecr.ap-southeast-1.amazonaws.com/sms-frontend:latest
```

### Step 4 — Deploy Full Infrastructure

```bash
terraform apply  # Deploys ECS services, RDS, WAF, CloudTrail
```

### Step 5 — Verify Deployment

```bash
# Get ALB DNS
terraform output alb_dns_name

# Force new ECS deployment (after image updates)
aws ecs update-service --cluster sms-production-cluster \
    --service sms-production-backend-svc --force-new-deployment \
    --region ap-southeast-1

aws ecs update-service --cluster sms-production-cluster \
    --service sms-production-frontend-svc --force-new-deployment \
    --region ap-southeast-1
```

### Teardown

```bash
terraform destroy  # Destroys all AWS resources
```

---

## Security Features

| Feature | Implementation |
|---|---|
| Network isolation | VPC private subnets; RDS not publicly accessible |
| WAF protection | AWS WAF v2 — SQLi, XSS, rate limiting (1000 req/5min) |
| TLS enforcement | ALB TLS 1.3 policy; RDS `require_secure_transport=ON` |
| Encryption at rest | RDS AES-256; S3 AES-256; ECR AES-256 |
| IAM least privilege | Separate execution + task roles; no wildcard permissions |
| Audit logging | CloudTrail multi-region trail → encrypted S3 |
| Monitoring | CloudWatch Container Insights; CPU/RDS alarms |
| Vulnerability scanning | ECR scan-on-push for all container images |
| IPv6 | VPC dualstack; ALB `ip_address_type = dualstack` |
| Password security | bcrypt (cost=8) + pepper; no plaintext secrets in code |

---

## Authentication

This project uses a **custom `RawTokenAuth` middleware** instead of Laravel Sanctum, due to PHP-FPM SIGSEGV crashes caused by Eloquent model booting on ECS Fargate (PHP 8.2 / Debian Bookworm).

All database operations use `DB::table()` raw query builder — no Eloquent models are used at runtime.

Bearer token format: `{token_id}|{40-char-random}`, validated via SHA-256 hash comparison against the `personal_access_tokens` table.

Three roles supported: **admin**, **lecturer**, **student** — each stored in separate tables.

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/login` | — | Login (returns Bearer token) |
| POST | `/api/logout` | Bearer | Logout |
| GET | `/api/profile` | Bearer | Get current user profile |
| GET | `/api/students` | Admin | List all students |
| POST | `/api/students` | Admin | Add student |
| PUT | `/api/students/{id}` | Admin | Update student |
| DELETE | `/api/students/{id}` | Admin | Delete student |
| GET | `/api/lecturers` | Admin | List all lecturers |
| POST | `/api/lecturers` | Admin | Add lecturer |
| GET | `/api/subjects` | Admin | List all subjects |
| POST | `/api/subjects` | Admin | Add subject (1-to-1 with lecturer) |
| GET | `/api/student-subjects` | Lecturer | List students in lecturer's subject |
| GET | `/api/results` | Lecturer | View results for lecturer's subject |
| POST | `/api/results` | Lecturer | Add/update student grade |
| GET | `/api/my-results` | Student | View own results |
| GET | `/api/my-semesters` | Student | List available semesters |
