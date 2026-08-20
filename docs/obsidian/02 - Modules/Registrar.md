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
2. **Curriculum Builder & Versioning:**
   - Visual drag-and-drop / tabular builder for versioned curricula (`college_curricula`, `shs_curricula`).
   - Maps required subjects per year level and semester in `college_curriculum_subjects` / `shs_curriculum_subjects`.
   - **Immutability Rule:** Once students are enrolled in a curriculum version, it is locked against destructive edits to maintain transcript integrity.
3. **Master Subject Catalog:**
   - Maintains universal subjects in `subjects` table (`subject_code`, `subject_name`, `units`, `lecture_hours`, `lab_hours`).
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
| `/admin/registrar/subjects.php` | GET | `SubjectController@index` | Universal subjects catalog table. |
| `/admin/registrar/subject_process.php` | POST | `SubjectController@process` | Adds, edits, or deletes subjects. |
| `/admin/registrar/college_programs.php` | GET | `CollegeController@programs` | College degree programs management. |
| `/admin/registrar/college_curriculum.php` | GET | `CollegeController@curriculum` | College curriculum versions index. |
| `/admin/registrar/college_curriculum_builder.php` | GET/POST | `CollegeController@curriculumBuilder` | Interactive curriculum subject mapping tool. |
| `/admin/registrar/shs_strands.php` | GET | `ShsController@strands` | Senior High School strand management. |
| `/admin/registrar/shs_curriculum.php` | GET | `ShsController@curriculum` | SHS curriculum versions index. |
| `/admin/registrar/shs_curriculum_builder.php` | GET/POST | `ShsController@curriculumBuilder` | SHS curriculum subject mapping tool. |

---
**Related:**
- [[Curriculum Architecture]]
- [[Scheduler]]
- [[Admissions]]
