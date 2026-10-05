# Demo files

Fictional sample files for the sample records in `database/seed.sql`
(`database/infinityfree/optional_demo_data.sql` / `reset_to_sample_data.sql`):
one enrolled student (John Doe, 2026-000001) in BSIT 1-A, and one LMS class
(CC101 Introduction to Computing, taught by Alan Turing). Every file is marked
"SAMPLE ONLY". None of them are in the InfinityFree upload package, so copy
them to the server yourself (FileZilla) after loading the sample data.

| File | What it is | Copy it to (inside htdocs) |
|---|---|---|
| `applicant/john_doe_psa_birth_certificate.pdf` | PSA birth certificate | `uploads/documents/` |
| `applicant/john_doe_form_138.pdf` | Form 138 report card | `uploads/documents/` |
| `applicant/john_doe_good_moral_certificate.pdf` | Good moral certificate | `uploads/documents/` |
| `applicant/john_doe_2x2_picture.jpg` | 2x2 ID picture | `uploads/documents/` |
| `applicant/john_doe_payment_proof.jpg` | GCash payment proof (PHP 7,500) | `uploads/payments/` |
| `faculty/cc101_syllabus.pdf` | CC101 course syllabus | `storage/uploads/lms/materials/` |
| `faculty/cc101_lesson1_what_is_a_computer.pptx` | Lesson 1 slides | `storage/uploads/lms/materials/` |
| `faculty/cc101_quiz1_questions.csv` | Quiz questions to try the faculty "Import CSV" button | not uploaded; use it in the LMS |
| `student/john_doe_assignment1_number_systems.pdf` | An answer sheet to try submitting Assignment 1 as John Doe | not uploaded; use it in the LMS |
| `registrar/bsit_2026_curriculum.pdf` | Printable copy of the BSIT 2026 curriculum already in the system | not uploaded; reference only |

`demo-account-credentials.md` (the demo logins) can live in this folder on your
computer, but git ignores it on purpose so the passwords never reach GitHub.

Database scripts (in `database/infinityfree/`):
- `reset_to_sample_data.sql`: for a database that already has the demo accounts;
  removes all other records and adds the one sample student and class above.
- `remove_demo_data.sql`: removes all records except accounts and school setup,
  with no sample student or class.
