# Registrar Module

**Path**: `admin/registrar/`  
**Required Roles**: `admin`, `superadmin`  
**Controllers**: [`RegistrarController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php), [`SubjectController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/SubjectController.php), [`CollegeController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/CollegeController.php), [`ShsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/ShsController.php)

The Registrar module is the administrative core for managing the academic catalog, curriculum blueprints, master subject registry, and student academic records.

---

## 1. Core Responsibilities
1. **Academic Programs & Strands:**
   - **College Programs:** Manages degrees (BS Information Technology, BS Computer Science, etc.) in `college_programs`.
   - **SHS Strands:** Manages Senior High strands (STEM, ABM, HUMSS, TVL) in `shs_strands`.
2. **Curriculum Lifecycle & Versioning:**
   - Enforces the 3-state lifecycle (`draft` $\rightarrow$ `active` $\rightarrow$ `archived`) across College and SHS curricula (`college_curricula`, `shs_curricula`).
   - Supports subject mapping and reordering (`display_order`) in `draft` mode.
   - **Immutability Enforcement:** Active and archived curricula are strictly locked against structural changes; revisions must be created via **Clone to Draft** ($v+1$).
3. **Master Subject Catalog & Historical Immutability:**
   - Maintains universal subjects in `subjects` table (`subject_code`, `subject_name`, `units`, `subject_type`, `education_level`, `status`).
   - Protects referenced subjects from deletion via MySQL `ON DELETE RESTRICT` foreign key constraints and controller usage detection (`is_locked`).
   - Supports non-destructive subject retirement via `status` toggle (`1 = Active`, `0 = Inactive`).
4. **Enrollment Queues & Masterlist:**
   - Reviews and tracks College and SHS student enrollment queues.
   - Exports student masterlists to CSV via `students_export.php`.

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Controller & Action | Description |
|---|---|---|---|
| `/admin/registrar/registrar_dashboard.php` | GET | `RegistrarController@dashboard` | Master registrar overview and curriculum counts. |
| `/admin/registrar/students.php` | GET | `RegistrarController@students` | Masterlist of all enrolled students. |
| `/admin/registrar/students_export.php` | POST | `RegistrarController@exportStudents` | Exports student roster to CSV file. |
| `/admin/registrar/college_enrollment_queue.php` | GET | `RegistrarController@collegeQueue` | College enrollment queue management. |
| `/admin/registrar/shs_enrollment_queue.php` | GET | `RegistrarController@shsQueue` | SHS enrollment queue management. |
| `/admin/registrar/subjects.php` | GET | `SubjectController@index` | Universal subjects catalog with usage indicators and status controls. |
| `/admin/registrar/subject_process.php` | POST | `SubjectController@process` | Adds, edits (locked if in-use), deletes (blocked if referenced), or toggles subject status. |
| `/admin/registrar/college_programs.php` | GET | `CollegeController@programs` | College degree programs management. |
| `/admin/registrar/college_curriculum.php` | GET/POST | `CollegeController@curriculum` | College curriculum directory, activation, archiving, cloning, and deletion. |
| `/admin/registrar/college_curriculum_builder.php` | GET/POST | `CollegeController@curriculumBuilder` | Interactive subject mapping and display reordering tool. |
| `/admin/registrar/shs_strands.php` | GET | `ShsController@strands` | Senior High School strand management. |
| `/admin/registrar/shs_curriculum.php` | GET/POST | `ShsController@curriculum` | SHS curriculum directory, activation, archiving, cloning, and deletion. |
| `/admin/registrar/shs_curriculum_builder.php` | GET/POST | `ShsController@curriculumBuilder` | SHS subject mapping and display reordering tool. |

---
**Related:**
- [[Curriculum Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[Scheduler]]
- [[Admissions]]
- [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]
