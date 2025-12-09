#!/bin/bash
# Script untuk generate email request IAM policy

USER_NAME="idmobstic"
ACCOUNT_ID="040681451912"
S3_BUCKET="app038-terraform-state"
DYNAMODB_TABLE="terraform-state-lock"
AWS_REGION="us-west-2"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=========================================="
echo "Generate IAM Policy Request Email"
echo "=========================================="
echo ""

# Read user input
read -p "Masukkan email AWS admin: " ADMIN_EMAIL
read -p "Masukkan nama Anda: " YOUR_NAME

echo ""
echo -e "${YELLOW}Generating email template...${NC}"
echo ""

# Generate email
cat << EOF > /tmp/iam-policy-request-email.txt
Subject: Request IAM Policy untuk Terraform Backend Setup - User: ${USER_NAME}

Halo AWS Admin,

Saya memerlukan IAM policy untuk user \`${USER_NAME}\` (Account: ${ACCOUNT_ID}) 
untuk melakukan setup Terraform Backend (S3 + DynamoDB).

**Request Details:**
- User: ${USER_NAME}
- Account: ${ACCOUNT_ID}
- Purpose: Setup Terraform Backend untuk project app038
- Resources needed:
  - S3 bucket: ${S3_BUCKET}
  - DynamoDB table: ${DYNAMODB_TABLE}
  - Region: ${AWS_REGION}

**IAM Policy Required:**

Silakan buat IAM policy dengan nama: \`TerraformBackendPolicy\`

Policy JSON:
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
                "arn:aws:s3:::${S3_BUCKET}",
                "arn:aws:s3:::${S3_BUCKET}/*"
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
                "arn:aws:dynamodb:${AWS_REGION}:${ACCOUNT_ID}:table/${DYNAMODB_TABLE}"
            ]
        }
    ]
}

**Steps untuk Admin:**
1. Login ke AWS Console → IAM → Policies → Create policy
2. Pilih JSON tab
3. Paste policy JSON di atas
4. Review policy
5. Name: TerraformBackendPolicy
6. Description: Policy for Terraform backend S3 and DynamoDB access
7. Create policy
8. Attach policy ke user: ${USER_NAME}

**Alternative (jika Admin ingin membuat resources manual):**
Jika Admin lebih suka membuat S3 bucket dan DynamoDB table secara manual, 
silahkan lihat instruksi di: terraform/IAM_POLICY_REQUIRED.md (section: "Alternative: Manual Setup oleh Admin")

**Verification:**
Setelah policy di-attach, saya akan verify dengan:
- aws sts get-caller-identity
- ./scripts/check-terraform-backend.sh

Terima kasih!

Best regards,
${YOUR_NAME}
EOF

echo -e "${GREEN}✅ Email template generated!${NC}"
echo ""
echo "File saved to: /tmp/iam-policy-request-email.txt"
echo ""
echo "Next steps:"
echo "1. Review email: cat /tmp/iam-policy-request-email.txt"
echo "2. Copy email content"
echo "3. Send to: ${ADMIN_EMAIL}"
echo ""
echo "Or open in default editor:"
echo "  - macOS: open -a TextEdit /tmp/iam-policy-request-email.txt"
echo "  - Linux: xdg-open /tmp/iam-policy-request-email.txt"
echo ""
