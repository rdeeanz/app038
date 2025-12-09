#!/bin/bash
# Script untuk check status Terraform Backend setup

S3_BUCKET="app038-terraform-state"
DYNAMODB_TABLE="terraform-state-lock"
AWS_REGION="us-west-2"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "=========================================="
echo "Terraform Backend Status Check"
echo "=========================================="
echo ""

# Check AWS credentials
echo -e "${YELLOW}Checking AWS credentials...${NC}"
if aws sts get-caller-identity &> /dev/null; then
    IDENTITY=$(aws sts get-caller-identity)
    echo -e "${GREEN}✅ AWS credentials valid${NC}"
    echo "   Account: $(echo $IDENTITY | jq -r '.Account')"
    echo "   User: $(echo $IDENTITY | jq -r '.Arn')"
else
    echo -e "${RED}❌ AWS credentials invalid${NC}"
    exit 1
fi

echo ""

# Check S3 Bucket
echo -e "${YELLOW}Checking S3 bucket: ${S3_BUCKET}...${NC}"
S3_CHECK=$(aws s3 ls "s3://${S3_BUCKET}" 2>&1)
if echo "$S3_CHECK" | grep -q 'NoSuchBucket'; then
    echo -e "${RED}❌ S3 bucket does not exist${NC}"
    echo "   Action required: Create bucket or request permission"
elif echo "$S3_CHECK" | grep -qi 'AccessDenied\|Unauthorized'; then
    echo -e "${RED}❌ Permission denied${NC}"
    echo "   User does not have permission to access S3 bucket"
    echo "   See: terraform/IAM_POLICY_REQUIRED.md"
elif [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ S3 bucket exists${NC}"
else
    echo -e "${RED}❌ Error checking S3 bucket${NC}"
fi

echo ""

# Check DynamoDB Table
echo -e "${YELLOW}Checking DynamoDB table: ${DYNAMODB_TABLE}...${NC}"
DDB_CHECK=$(aws dynamodb describe-table --table-name "${DYNAMODB_TABLE}" --region "${AWS_REGION}" 2>&1)
if echo "$DDB_CHECK" | grep -qi 'ResourceNotFoundException'; then
    echo -e "${RED}❌ DynamoDB table does not exist${NC}"
    echo "   Action required: Create table or request permission"
elif echo "$DDB_CHECK" | grep -qi 'AccessDenied\|Unauthorized'; then
    echo -e "${RED}❌ Permission denied${NC}"
    echo "   User does not have permission to access DynamoDB table"
    echo "   See: terraform/IAM_POLICY_REQUIRED.md"
elif [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ DynamoDB table exists${NC}"
else
    echo -e "${RED}❌ Error checking DynamoDB table${NC}"
fi

echo ""
echo "=========================================="
echo "Summary"
echo "=========================================="
echo ""
echo "For detailed IAM policy requirements, see:"
echo "  terraform/IAM_POLICY_REQUIRED.md"
echo ""
