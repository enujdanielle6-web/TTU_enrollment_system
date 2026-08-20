# Scheduler Module

**Path**: `admin/scheduler/`  
**Required Roles**: `scheduler`, `admin`, `superadmin`  
**Controller**: [`SchedulerController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php)

The Scheduler module translates curriculum subjects into timetabled, scheduled class sections with assigned faculty instructors and physical/virtual classrooms.

---

## 1. Core Responsibilities
1. **Section Creation:** Creates structured class blocks in `college_sections` and `shs_sections` (e.g. `BSIT 1-A`, `STEM 11-1`).
2. **Subject Scheduling & Matrix:** Attaches curriculum subjects to sections via `college_section_subjects` and `shs_section_subjects` with designated days (M/T/W/Th/F/S), start/end times, and room numbers.
3. **Faculty Assignment:** Assigns faculty instructors to class sections. (The section's faculty adviser dynamically determines instructor assignments in the LMS).
4. **Capacity Controls:** Sets maximum enrollment limits per section to prevent classroom overcrowding.

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Action | Description |
|---|---|---|---|
| `/admin/scheduler/scheduler_dashboard.php` | GET | `dashboard` | Timetable metrics, section counts, room utilization stats. |
| `/admin/scheduler/college_sections.php` | GET/POST | `collegeSections` | Manages college section list, advisers, and year levels. |
| `/admin/scheduler/shs_sections.php` | GET/POST | `shsSections` | Manages senior high school section list and strand links. |
| `/admin/scheduler/schedule_builder.php` | GET/POST | `builder` | Visual timetable schedule builder for mapping timeslots and rooms. |
| `/admin/scheduler/schedule_builder_process.php` | POST | `process` | Saves section subject schedule blocks into the database. |

---
**Related:**
- [[Registrar]]
- [[Curriculum Architecture]]
- [[LMS]]
