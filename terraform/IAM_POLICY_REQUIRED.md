# IAM Policy Required untuk Terraform Backend Setup

## Status

❌ **Permission Issue Detected**

User `idmobstic` tidak memiliki permission yang diperlukan untuk membuat S3 bucket dan DynamoDB table.

## Required IAM Permissions

Untuk melakukan Step 2: Setup Terraform Backend, user memerlukan IAM policy berikut:

### Option 1: Minimal Permissions (Recommended)

Attach policy berikut ke user `idmobstic`:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "TerraformBackendS3",
            "Effect": "Allow",
            "Action": [
                "s3:CreateBucket",
                "s3:GetBucketVersioning",
                "s3:PutBucketVersioning",
                "s3:GetBucketEncryption",
                "s3:PutBucketEncryption",
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::app038-terraform-state",
                "arn:aws:s3:::app038-terraform-state/*"
            ]
        },
        {
            "Sid": "TerraformBackendDynamoDB",
            "Effect": "Allow",
            "Action": [
                "dynamodb:CreateTable",
                "dynamodb:DescribeTable",
                "dynamodb:PutItem",
                "dynamodb:GetItem",
                "dynamodb:DeleteItem"
            ],
            "Resource": [
                "arn:aws:dynamodb:us-west-2:040681451912:table/terraform-state-lock"
            ]
        }
    ]
}
```

### Option 2: Using AWS Managed Policies

Jika admin ingin memberikan permission yang lebih luas:

1. **AmazonS3FullAccess** - untuk S3 operations
2. **AmazonDynamoDBFullAccess** - untuk DynamoDB operations

**⚠️ Warning:** Managed policies memberikan permission yang lebih luas dari yang diperlukan.

## Instructions untuk AWS Admin

### Step 1: Create IAM Policy

1. Login ke AWS Console sebagai admin
2. Navigate ke **IAM** → **Policies** → **Create policy**
3. Pilih **JSON** tab
4. Paste policy dari Option 1 di atas
5. Click **Next**
6. Name: `TerraformBackendPolicy`
7. Description: `Policy for Terraform backend S3 and DynamoDB access`
8. Click **Create policy**

### Step 2: Attach Policy ke User

1. Navigate ke **IAM** → **Users** → `idmobstic`
2. Click **Add permissions** → **Attach policies directly**
3. Search dan select `TerraformBackendPolicy`
4. Click **Add permissions**

### Step 3: Verify Permissions

Setelah policy di-attach, user dapat verify dengan:

```bash
aws sts get-caller-identity
aws iam list-attached-user-policies --user-name idmobstic
```

## Alternative: Manual Setup oleh Admin

Jika admin ingin membuat resources secara manual:

### Create S3 Bucket (Admin)

```bash
aws s3 mb s3://app038-terraform-state --region us-west-2

# Enable versioning
aws s3api put-bucket-versioning \
  --bucket app038-terraform-state \
  --versioning-configuration Status=Enabled \
  --region us-west-2

# Enable encryption
aws s3api put-bucket-encryption \
  --bucket app038-terraform-state \
  --server-side-encryption-configuration '{
    "Rules": [{
      "ApplyServerSideEncryptionByDefault": {
        "SSEAlgorithm": "AES256"
      }
    }]
  }' \
  --region us-west-2
```

### Create DynamoDB Table (Admin)

```bash
aws dynamodb create-table \
  --table-name terraform-state-lock \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --region us-west-2

# Wait for table to be active
aws dynamodb wait table-exists \
  --table-name terraform-state-lock \
  --region us-west-2
```

### Grant Access ke User (Admin)

Setelah resources dibuat, admin perlu memberikan permission untuk user mengakses resources yang sudah ada:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "TerraformBackendS3Access",
            "Effect": "Allow",
            "Action": [
                "s3:GetBucketVersioning",
                "s3:PutBucketVersioning",
                "s3:GetBucketEncryption",
                "s3:PutBucketEncryption",
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::app038-terraform-state",
                "arn:aws:s3:::app038-terraform-state/*"
            ]
        },
        {
            "Sid": "TerraformBackendDynamoDBAccess",
            "Effect": "Allow",
            "Action": [
                "dynamodb:DescribeTable",
                "dynamodb:PutItem",
                "dynamodb:GetItem",
                "dynamodb:DeleteItem"
            ],
            "Resource": [
                "arn:aws:dynamodb:us-west-2:040681451912:table/terraform-state-lock"
            ]
        }
    ]
}
```

## Next Steps

Setelah permission diberikan atau resources dibuat oleh admin:

1. Re-run setup script:
   ```bash
   ./scripts/deploy-k8s.sh
   # atau
   bash /tmp/setup-terraform-backend.sh
   ```

2. Verify setup:
   ```bash
   aws s3 ls s3://app038-terraform-state
   aws dynamodb describe-table --table-name terraform-state-lock --region us-west-2
   ```

3. Continue dengan Step 3: Configure Terraform

## Current Status

- ❌ S3 Bucket: **Not Created** (Permission denied)
- ❌ DynamoDB Table: **Not Created** (Permission denied)
- ✅ AWS Credentials: **Configured**
- ✅ AWS Account: **040681451912**
- ✅ AWS User: **idmobstic**

## Contact

Jika Anda adalah AWS admin dan perlu bantuan setup, silakan:
1. Review policy di atas
2. Attach policy ke user `idmobstic`
3. Atau create resources secara manual dan berikan access
