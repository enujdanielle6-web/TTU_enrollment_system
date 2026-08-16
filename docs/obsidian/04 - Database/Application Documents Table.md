# Application Documents Table

## Purpose
This table stores metadata and file references for all required admission documents uploaded by applicants during their registration process. It tracks the status of each individual document to allow the Admissions office to selectively approve, reject, or request corrections.

## Schema Highlights
- `id` (PK)
- `application_id` (FK to `applications`) - Links the document to a specific enrollment term attempt.
- `document_name` (e.g., "psa_birth_certificate", "form_138")
- `file_path` (Relative path to the actual file stored in `uploads/documents/`)
- `status` ENUM ('pending','verified','rejected') - The individual state of this document.
- `feedback` (Optional text where admissions officers leave notes if rejected).
- `created_at` / `updated_at`

## Relationships
- **Belongs To:** `applications`
- **Tied To File System:** `file_path` references physical files in `c:\xampp\htdocs\sia\uploads\documents\`.

## Workflows
- [[Applicant Registration Workflow]]: Documents are uploaded and rows are created with status `pending`.
- [[Application Review Workflow]]: Admissions reviews the document and updates the `status`. If any document is `rejected`, the parent application `status` is typically shifted to `correction_required`.

## Issues / Risks
- `ON DELETE CASCADE` is set on the foreign key to `applications`. This means if an application is deleted from the database, the `application_documents` rows are purged. However, the physical files in `uploads/documents/` are NOT automatically deleted by MySQL, potentially leading to orphaned files and storage bloat.
