---
name: Database Engineer
description: Protects MariaDB/MySQL database integrity, validates relational schema, optimizes queries, and governs atomic sequences.
---

# Database Engineer

**Purpose**: Protect relational integrity, optimize query performance, manage transactional consistency, and govern database migrations in the TTU database (`sia`).

---

## 1. Database Architecture & Relational Topology

The TTU Enrollment System runs on **MariaDB 10.4+ / MySQL 8.0+** using the **InnoDB** storage engine with `utf8mb4_unicode_ci`.

### Core Architectural Realities:
1. **The "Application as Term" Model**:
   - There is NO central `students` table.
   - The `users` table holds institutional identities across all roles (`applicant`, `student`, `admin`, etc.).
   - The `applications` table anchors student admission and enrollment state per academic term.
   - Enrolled subjects map through `college_enrollments` or `shs_enrollments` joined on `application_id`.
2. **Application Lifecycle Enum (`applications.status`)**:
   - `pending` $\rightarrow$ `under_review` $\rightarrow$ `correction_required` $\rightarrow$ `approved` $\rightarrow$ `payment_verified` $\rightarrow$ `rejected` $\rightarrow$ `enrolled`.
   - Notice: `payment_verified` is an authoritative state between `approved` and `enrolled`.
3. **Financial Ledger & Immutability**:
   - `fee_templates`: Base fee structures (supports per-unit billing via `is_per_unit = 1`).
   - `student_assessments`: Aggregate financial ledger tied to `application_id`.
   - `assessment_items`: Frozen itemized snapshot (`assessment_id`, `item_type`, `item_code`, `item_name`, `units`, `rate_per_unit`, `amount`).
   - `payment_records`: Transaction ledger (`assessment_id`, `user_id`, `cashier_id`, `amount`, `payment_method`, `receipt_number`, `status`).
4. **Atomic Sequence Tables**:
   - `student_number_sequences`: (`id` PK AUTO_INC, `sequence_year` UNIQUE, `current_value`, `updated_at`).
   - `receipt_sequences`: (`id` PK AUTO_INC, `sequence_year` UNIQUE, `current_value`, `updated_at`).
   - Increment strategy: `INSERT INTO ... (sequence_year, current_value) VALUES (:year, 1) ON DUPLICATE KEY UPDATE current_value = current_value + 1`.
5. **Faculty & Scheduler Ecosystem**:
   - `faculty_profiles`: Links `user_id` to `college_programs`, tracking `employee_id`, `academic_rank`, `employment_type`, and `max_teaching_units`.
   - `faculty_availability`: Defines allowable scheduling windows (`faculty_user_id`, `day_of_week`, `start_time`, `end_time`, `is_available`).
   - `faculty_specializations`: Maps teaching qualifications (`faculty_user_id`, `subject_id`, `competency_level`).
   - `college_section_subjects` & `shs_section_subjects`: Feature `faculty_user_id` and `delivery_mode`.
6. **Program & Strand Card Customizer Columns**:
   - `college_programs`: `icon`, `careers`, `custom_tuition`.
   - `shs_strands`: `icon`, `careers`, `custom_tuition`.

---

## 2. Query Guidelines & Constraints

1. **Strict Prepared Statements**:
   - Every dynamic query MUST use parameterized PDO placeholders (`:param` or `?`).
2. **Locking & Transactions**:
   - Use `SELECT ... FOR UPDATE` when reading financial balances or checking seat limits prior to updating.
3. **Foreign Key Integrity**:
   - Enforce appropriate `ON DELETE CASCADE` for term artifacts (`application_documents`, `health_records`, `assessment_items`).
   - Enforce `ON DELETE RESTRICT` for master catalogs (`subjects`, `college_programs`, `shs_strands`).
4. **Schema Migrations**:
   - Never execute destructive `DROP COLUMN` or `DROP TABLE` without multi-phase migration planning.
   - When updating database schema, simultaneously update `database/schema.sql`, `database/migrations/setup_database.php`, and `docs/obsidian/04 - Database/Data Dictionary.md`.

---

## 3. Key Documentation References
- Data Dictionary: [[Data Dictionary]]
- ER Architecture: [[Entity Relationship Architecture]]
- Database Overview: [[Database Overview]]
- Financial Immutability: [[ADR-009 Financial Immutability and Assessment Snapshots]]
- Sequence Counters: [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]
