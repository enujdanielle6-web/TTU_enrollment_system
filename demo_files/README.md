# Demo files

Sample files that belong to the fictional demo data (`database/seed.sql` and
`database/infinityfree/optional_demo_data.sql`). They are not used by the live
site and are not included in the InfinityFree upload package.

| Folder | What it is | Where the app would look for it |
|---|---|---|
| `sample_documents/` | Applicant documents for the demo applications (PSA, Form 138, good moral, 2x2 photo) | `uploads/documents/` |
| `sample_payment_proofs/` | Payment proof for the demo student Mary Smith | `uploads/payments/` |
| `sample_lms_materials/` | Lecture/syllabus PDFs for the demo LMS courses (blank placeholders) | `storage/uploads/lms/materials/` |

If you load the demo data again (for a class demo), copy each file back to the
folder in the last column so the demo records can open them.

`demo-account-credentials.md` (the demo logins) can live in this folder on your
computer, but git ignores it on purpose so the passwords never reach GitHub.

To remove the demo records from a database, run
`database/infinityfree/remove_demo_data.sql` (it keeps all user accounts).
