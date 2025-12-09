# 📧 Template Request IAM Policy untuk AWS Admin

## Cara Request IAM Policy ke AWS Admin

### Option 1: Email Request (Recommended)

Copy template email berikut dan kirim ke AWS admin:

---

**Subject:** Request IAM Policy untuk Terraform Backend Setup - User: idmobstic

**Body:**

```
Halo AWS Admin,

Saya memerlukan IAM policy untuk user `idmobstic` (Account: 040681451912) 
untuk melakukan setup Terraform Backend (S3 + DynamoDB).

**Request Details:**
- User: idmobstic
- Account: 040681451912
- Purpose: Setup Terraform Backend untuk project app038
- Resources needed:
  - S3 bucket: app038-terraform-state
  - DynamoDB table: terraform-state-lock
  - Region: us-west-2

**IAM Policy Required:**

Silakan buat IAM policy dengan nama: `TerraformBackendPolicy`

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

**Steps untuk Admin:**
1. Login ke AWS Console → IAM → Policies → Create policy
2. Pilih JSON tab
3. Paste policy JSON di atas
4. Review policy
5. Name: TerraformBackendPolicy
6. Description: Policy for Terraform backend S3 and DynamoDB access
7. Create policy
8. Attach policy ke user: idmobstic

**Alternative (jika Admin ingin membuat resources manual):**
Jika Admin lebih suka membuat S3 bucket dan DynamoDB table secara manual, 
silahkan lihat instruksi di: terraform/IAM_POLICY_REQUIRED.md (section: "Alternative: Manual Setup oleh Admin")

**Verification:**
Setelah policy di-attach, saya akan verify dengan:
- aws sts get-caller-identity
- ./scripts/check-terraform-backend.sh

Terima kasih!

Best regards,
[Your Name]
```

---

### Option 2: Jira/Ticket System

Jika perusahaan menggunakan ticketing system (Jira, ServiceNow, dll):

**Title:** Request IAM Policy - Terraform Backend Setup

**Description:**

```
**Request Type:** IAM Policy Request
**Priority:** Medium
**User:** idmobstic
**Account:** 040681451912

**Purpose:**
Setup Terraform Backend untuk project app038 deployment ke Kubernetes.

**Required Permissions:**
1. S3 bucket operations (app038-terraform-state)
2. DynamoDB table operations (terraform-state-lock)

**Policy Details:**
See attached file: terraform/IAM_POLICY_REQUIRED.md

**Steps:**
1. Create IAM policy: TerraformBackendPolicy
2. Attach policy to user: idmobstic
3. Verify access

**Attachments:**
- terraform/IAM_POLICY_REQUIRED.md
- terraform/REQUEST_IAM_POLICY.md (this file)
```

---

### Option 3: Slack/Teams Message

**Message Template:**

```
Hi @aws-admin-team 👋

I need IAM policy for user `idmobstic` to setup Terraform Backend.

**Quick Summary:**
- User: idmobstic
- Need: S3 + DynamoDB permissions
- Purpose: Terraform state management

**Policy JSON:** (see attached file or link to IAM_POLICY_REQUIRED.md)

**AWS Console Steps:**
1. IAM → Policies → Create policy
2. JSON tab → paste policy
3. Name: TerraformBackendPolicy
4. Attach to user: idmobstic

Full details: terraform/IAM_POLICY_REQUIRED.md

Thanks! 🙏
```

---

### Option 4: Direct AWS Console Request (jika ada akses)

Jika Anda memiliki akses ke AWS Console (meskipun tidak bisa create policy):

1. **Login ke AWS Console**
2. **Navigate ke IAM → Policies**
3. **Screenshot atau copy policy JSON** dari `terraform/IAM_POLICY_REQUIRED.md`
4. **Create support ticket** atau **contact admin** dengan policy JSON

---

## 📋 Checklist Sebelum Request

Sebelum mengirim request, pastikan:

- [ ] ✅ Sudah membaca `terraform/IAM_POLICY_REQUIRED.md`
- [ ] ✅ Memahami resources yang diperlukan (S3 bucket + DynamoDB table)
- [ ] ✅ Memiliki informasi user yang benar (idmobstic)
- [ ] ✅ Memiliki account ID yang benar (040681451912)
- [ ] ✅ Sudah prepare policy JSON yang akan di-request

---

## 🔍 Informasi yang Perlu Disiapkan

Sebelum request, siapkan informasi berikut:

1. **User Details:**
   - Username: `idmobstic`
   - Account ID: `040681451912`
   - ARN: `arn:aws:iam::040681451912:user/idmobstic`

2. **Resources yang Diperlukan:**
   - S3 Bucket: `app038-terraform-state`
   - DynamoDB Table: `terraform-state-lock`
   - Region: `us-west-2`

3. **Policy JSON:**
   - Copy dari `terraform/IAM_POLICY_REQUIRED.md` section "Option 1: Minimal Permissions"

4. **Business Justification:**
   - Project: app038
   - Purpose: Kubernetes deployment dengan Terraform
   - Impact: Required untuk infrastructure provisioning

---

## 📝 Follow-up Actions

Setelah request dikirim:

1. **Track request** (jika menggunakan ticketing system)
2. **Follow up** setelah 1-2 hari jika belum ada response
3. **Verify** setelah policy di-attach:
   ```bash
   aws sts get-caller-identity
   ./scripts/check-terraform-backend.sh
   ```

---

## 🆘 Jika Request Ditolak

Jika admin menolak request, alternatif:

1. **Request minimal permissions** (hanya untuk resources yang sudah ada)
2. **Request admin membuat resources manual** (lihat `IAM_POLICY_REQUIRED.md` section "Alternative")
3. **Use different approach:**
   - Local Terraform state (tidak recommended untuk production)
   - Shared Terraform state dengan user lain yang sudah punya access

---

## 📚 Related Documentation

- `terraform/IAM_POLICY_REQUIRED.md` - Detailed IAM policy requirements
- `DEPLOY_K8S_INTERACTIVE.md` - Full deployment guide
- `scripts/check-terraform-backend.sh` - Status verification script

---

## 💡 Tips untuk Request yang Berhasil

1. **Be Specific:** Jelaskan dengan jelas apa yang diperlukan dan untuk apa
2. **Provide Context:** Sertakan business justification
3. **Include Details:** Sertakan semua informasi yang diperlukan (user, account, resources)
4. **Be Patient:** IAM policy requests biasanya memerlukan approval process
5. **Follow Up:** Jangan ragu untuk follow up jika belum ada response

---

**Good luck dengan request Anda! 🚀**
