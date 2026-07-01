# AWS Setup Guide — TC1L_GROUP 6
# CCS6344 Assignment 2

## What you need to do (in order)

---

## Step 1 — Create AWS Free Tier Account

1. Go to https://aws.amazon.com → click "Create a Free Account"
2. Fill in email, password, account name
3. Choose "Personal" account type
4. Enter credit/debit card (won't be charged for free tier services)
5. Verify phone number
6. Choose "Basic Support (Free)"
7. Sign in to the AWS Console

---

## Step 2 — Create an IAM User (don't use root account)

1. Go to **IAM → Users → Create user**
2. Username: `sms-deploy`
3. Check **"Provide user access to the AWS Management Console"**
4. Select **"I want to create an IAM user"**
5. Set a password
6. Attach policy: **AdministratorAccess** (for this assignment only)
7. Click **"Create user"** → download the `.csv` credentials
8. Go to your new user → **Security credentials → Create access key**
9. Choose **"Command Line Interface (CLI)"** → create
10. Save the **Access Key ID** and **Secret Access Key**

---

## Step 3 — Install Tools on Your Computer

### AWS CLI
1. Download: https://aws.amazon.com/cli/
2. Install and run:
   ```bash
   aws configure
   ```
   Enter your Access Key ID, Secret Access Key, region: `ap-southeast-1`, output: `json`

### Terraform
1. Download: https://developer.hashicorp.com/terraform/downloads
2. Install (Windows: extract and add to PATH)
3. Verify: `terraform --version`

### Docker Desktop
1. Download: https://www.docker.com/products/docker-desktop/
2. Install and start Docker Desktop

---

## Step 4 — Create ECR Repositories and Push Docker Images

Run these commands in your terminal from the project root:

```bash
# Get your AWS account ID
AWS_ACCOUNT=$(aws sts get-caller-identity --query Account --output text)
AWS_REGION="ap-southeast-1"

# Log Docker into ECR
aws ecr get-login-password --region $AWS_REGION | \
  docker login --username AWS \
  --password-stdin $AWS_ACCOUNT.dkr.ecr.$AWS_REGION.amazonaws.com

# Build and push BACKEND
docker build -t sms-backend ./studentManagement
docker tag sms-backend:latest $AWS_ACCOUNT.dkr.ecr.$AWS_REGION.amazonaws.com/sms-production-backend:latest
docker push $AWS_ACCOUNT.dkr.ecr.$AWS_REGION.amazonaws.com/sms-production-backend:latest

# Build and push FRONTEND
docker build --build-arg VITE_API_URL="" -t sms-frontend ./6Day-Grind/6Day-Grind
docker tag sms-frontend:latest $AWS_ACCOUNT.dkr.ecr.$AWS_REGION.amazonaws.com/sms-production-frontend:latest
docker push $AWS_ACCOUNT.dkr.ecr.$AWS_REGION.amazonaws.com/sms-production-frontend:latest
```

> Note: ECR repositories are created by Terraform in Step 5. Run `terraform apply` first (without backend_image/frontend_image), then push, then run `terraform apply` again with the image URIs.

---

## Step 5 — Deploy with Terraform

```bash
cd terraform

# Copy and fill in the variables file
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars — add your ECR image URIs

# Set sensitive values as environment variables (don't put in tfvars)
export TF_VAR_db_password="YourStrongPassword123!"
export TF_VAR_password_pepper="RandomPepperString456!"

# Initialise Terraform
terraform init

# Preview what will be created
terraform plan

# Deploy! (takes ~10-15 minutes)
terraform apply
```

After apply finishes, you'll see the **ALB DNS name** as output — that's your app's URL.

---

## Step 6 — Run Database Migrations

The Laravel container runs migrations automatically on startup (via `start.sh`). Check ECS logs in CloudWatch:

1. Go to **CloudWatch → Log groups → /ecs/sms-production/backend**
2. Look for "Migrations complete."

---

## Step 7 — Security Validation (for Part E)

### Port scanning
```bash
# Install nmap if not installed
nmap -sV -p 80,443,3306,22 <your-alb-dns-name>
# Expected: only 80 (redirect) and 443 (HTTPS) open
```

### WAF SQL injection test
```bash
curl -k "https://<your-alb-dns-name>/api/login?id=1' OR '1'='1"
# Expected: 403 Blocked by WAF
```

### CloudTrail logs
1. Go to **CloudTrail → Event history**
2. Filter by: Event source = `ecs.amazonaws.com`
3. Screenshot showing your deployment events

### Encryption confirmation
1. Go to **RDS → your database → Configuration**
2. Screenshot showing "Encryption: Enabled"
3. Go to **S3 → cloudtrail bucket → Properties**
4. Screenshot showing "Default encryption: AES-256"

---

## Step 8 — Tear Down (to avoid charges)

```bash
cd terraform
terraform destroy
```

> ⚠️ Do this AFTER recording your demo video.

---

## Quick Reference — Free Tier Limits
| Service | Free Tier |
|---------|-----------|
| EC2/ECS Fargate | NOT free — costs ~$0.05/hr per task |
| RDS db.t3.micro | 750 hours/month free |
| S3 | 5 GB free |
| CloudTrail | First trail free |
| WAF | $5/month for Web ACL |

> **Cost warning**: ECS Fargate tasks are NOT in free tier. Running 4 tasks (2 backend + 2 frontend) costs ~$0.10-0.15/hour. Tear down after demo!
