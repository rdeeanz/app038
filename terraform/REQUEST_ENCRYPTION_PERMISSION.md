# 🔐 Request Permission untuk S3 Bucket Encryption

## Status

❌ **Permission Issue:** User tidak memiliki permission untuk enable encryption pada S3 bucket.

**Required Permission:**
- `s3:PutEncryptionConfiguration` - untuk enable encryption
- `s3:GetEncryptionConfiguration` - untuk verify encryption status

## Request Details

**User:** idmobstic  
**Account:** 040681451912  
**S3 Bucket:** app038-terraform-state  
**Region:** us-west-2  
**Encryption Type:** AES256 (Server-Side Encryption)

## IAM Policy Addition Required

Tambahkan permission berikut ke existing IAM policy untuk user `idmobstic`:

### Option 1: Update Existing Policy

Tambahkan actions berikut ke statement S3 di policy yang sudah ada:

```json
{
    "Sid": "TerraformBackendS3",
    "Effect": "Allow",
    "Action": [
        "s3:CreateBucket",
        "s3:GetBucketVersioning",
        "s3:PutBucketVersioning",
        "s3:GetBucketEncryption",      // ← ADD THIS
        "s3:PutBucketEncryption",     // ← ADD THIS
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
    ],
    "Resource": [
        "arn:aws:s3:::app038-terraform-state",
        "arn:aws:s3:::app038-terraform-state/*"
    ]
}
```

### Option 2: Standalone Policy Addition

Jika ingin membuat policy terpisah:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "S3BucketEncryption",
            "Effect": "Allow",
            "Action": [
                "s3:GetBucketEncryption",
                "s3:PutBucketEncryption"
            ],
            "Resource": [
                "arn:aws:s3:::app038-terraform-state"
            ]
        }
    ]
}
```

## Email Template untuk Request

**Subject:** Request Additional Permission - S3 Bucket Encryption (app038-terraform-state)

**Body:**

```
Halo AWS Admin,

Saya memerlukan permission tambahan untuk enable encryption pada S3 bucket 
yang sudah dibuat untuk Terraform Backend.

**Request Details:**
- User: idmobstic
- Account: 040681451912
- S3 Bucket: app038-terraform-state
- Region: us-west-2
- Purpose: Enable server-side encryption (AES256) untuk security compliance

**Required Permissions:**
- s3:PutEncryptionConfiguration
- s3:GetEncryptionConfiguration

**Action Required:**
Tambahkan 2 actions di atas ke existing IAM policy untuk user idmobstic,
atau update policy dengan JSON di bawah ini:

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
}

**After Permission Granted:**
Saya akan enable encryption dengan command:
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

Terima kasih!

Best regards,
[Your Name]
```

## Alternative: Admin Enable Encryption Manual

Jika admin ingin enable encryption secara manual:

```bash
# Enable encryption dengan AES256
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

# Verify encryption
aws s3api get-bucket-encryption \
  --bucket app038-terraform-state \
  --region us-west-2
```

## Verification After Permission Granted

Setelah permission diberikan, verify dengan:

```bash
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

# Verify encryption is enabled
aws s3api get-bucket-encryption \
  --bucket app038-terraform-state \
  --region us-west-2

# Expected output:
# {
#     "ServerSideEncryptionConfiguration": {
#         "Rules": [
#             {
#                 "ApplyServerSideEncryptionByDefault": {
#                     "SSEAlgorithm": "AES256"
#                 },
#                 "BucketKeyEnabled": false
#             }
#         ]
#     }
# }
```

## Current Status

- ✅ S3 Bucket: Created
- ✅ Versioning: Enabled
- ❌ Encryption: **Not Enabled** (Permission required)
- ✅ DynamoDB Table: Active

## Security Best Practices

Enabling encryption pada S3 bucket adalah **best practice** untuk:
1. **Compliance:** Memenuhi requirement security compliance (SOC 2, ISO 27001, dll)
2. **Data Protection:** Melindungi Terraform state file yang mungkin berisi sensitive data
3. **Defense in Depth:** Layer tambahan untuk security

**Note:** Meskipun encryption belum enabled, S3 bucket tetap aman karena:
- Access dibatasi melalui IAM policies
- Versioning sudah enabled untuk backup
- Bucket tidak publicly accessible

Namun, encryption tetap **highly recommended** untuk production environment.

## Related Documentation

- `terraform/IAM_POLICY_REQUIRED.md` - Original IAM policy requirements
- `terraform/REQUEST_IAM_POLICY.md` - Template untuk request IAM policy
- `DEPLOY_K8S_INTERACTIVE.md` - Full deployment guide
