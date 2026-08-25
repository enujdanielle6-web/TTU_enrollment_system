# Scheduler Admin Relationship Map

This document traces the code execution chain, database models, and conflict detection logic for the Timetable and Section Scheduling subsystem.

---

## 1. Scheduler Dashboard (`/admin/scheduler/scheduler_dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scheduler/scheduler_dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scheduler/scheduler_dashboard.php)
- **Controller:** [`app/Controllers/Admin/Scheduler/SchedulerController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php) (`dashboard()`)
- **Route:** `GET /admin/scheduler/scheduler_dashboard.php`
- **Authorized Roles:** `scheduler`, `admin`, `superadmin`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:scheduler,admin,superadmin`

### Database Tracing Chain
```text
GET /admin/scheduler/scheduler_dashboard.php
    ↓
SchedulerController@dashboard
    ↓
1. SELECT COUNT(*) as total_college_sections FROM college_sections WHERE status = 1
2. SELECT COUNT(*) as total_shs_sections FROM shs_sections WHERE status = 1
3. SELECT COUNT(*) as total_offerings FROM college_section_subjects
4. SELECT sec.*, prog.name as program_name,
          (SELECT COUNT(*) FROM college_enrollments WHERE college_section_id = sec.id) as enrolled_count
   FROM college_sections sec 
   JOIN college_programs prog ON sec.program_id = prog.id
   LIMIT 10
    ↓
Renders section capacity utilization bars and scheduled room counts
```

---

## 2. College & SHS Section Managers (`/admin/scheduler/college_sections.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scheduler/college_sections.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scheduler/college_sections.php)
- **Controller:** `SchedulerController@collegeSections`, `SchedulerController@saveSection`
- **Routes:** `GET /admin/scheduler/college_sections.php`, `POST /admin/scheduler/section_save.php`

### Interactive AJAX Dropdown Traced
When creating a new section in the modal, selecting a Program triggers:
- `GET /admin/ajax/get_curricula_by_program.php?program_id={id}` $\rightarrow$ `AdminApiController@getCurriculaByProgram`.

### Section Creation Chain
```text
POST /admin/scheduler/section_save.php (section_code, program_id, curriculum_id, year_level, capacity, schedule_type, adviser)
    ↓
SchedulerController@saveSection
    ↓
Validation: Unique section_code check
    ↓
Database Operation:
    ├── 1. INSERT INTO college_sections (section_code, program_id, curriculum_id, year_level, capacity, schedule_type, adviser, status)
    │      VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    └── 2. Auto-import subjects from college_curriculum_subjects into college_section_subjects
    ↓
Redirect: /admin/scheduler/college_sections.php
```

---

## 3. Interactive Schedule Matrix Builder (`/admin/scheduler/schedule_builder.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scheduler/schedule_builder.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scheduler/schedule_builder.php)
- **Controller:** `SchedulerController@scheduleBuilder`, `SchedulerController@saveSchedule`
- **Routes:** `GET /admin/scheduler/schedule_builder.php?section_id={id}`, `POST /admin/scheduler/schedule_save.php`

### Conflict Detection Engine
```mermaid
flowchart TD
    Admin[Scheduler Staff] -->|Assigns Day, Start Time, End Time, Room, Instructor| Form[Schedule Form]
    Form --> Submit[POST /admin/scheduler/schedule_save.php]
    Submit --> Controller[SchedulerController@saveSchedule]
    Controller --> RoomCheck{Room Available?}
    RoomCheck -->|Conflict: Same Room & Time| Error1[Reject: Room Already Occupied]
    RoomCheck -->|Pass| InstCheck{Instructor Available?}
    InstCheck -->|Conflict: Same Instructor & Time| Error2[Reject: Instructor Double-Booked]
    InstCheck -->|Pass| DBUpdate[UPDATE college_section_subjects SET day=?, start_time=?, end_time=?, room=?, instructor=?]
    DBUpdate --> Success[Redirect with Success Toast]
```

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[07 - Registrar Admin Relationship Map]]
- [[04 - Applicant Portal Relationship Map]]
