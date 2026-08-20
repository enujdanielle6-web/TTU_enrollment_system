# API & AJAX Endpoint Documentation

This document catalogs the active JSON/AJAX API endpoints powering interactive UI components, dynamic dropdowns, timetable previews, and background workflows across the system.

---

## 1. Standard Response Envelope
All API endpoints return standard JSON responses:

```json
{
  "status": "success",
  "data": { ... },
  "message": "Operation completed successfully."
}
```
Or on error:
```json
{
  "status": "error",
  "message": "Detailed error message.",
  "errors": [ ... ]
}
```

---

## 2. Admin & Registrar API Endpoints (`AdminApiController.php`)

### 2.1 Get Curricula by Program
- **Route:** `GET /admin/ajax/get_curricula_by_program.php`
- **Controller:** `App\Controllers\Api\AdminApiController@getCurriculaByProgram`
- **Auth:** Session required (`admin`, `superadmin`, `registrar`)
- **Query Parameters:** `program_id` (int, required)
- **Response Format:**
  ```json
  {
    "status": "success",
    "data": [
      { "id": 1, "curriculum_name": "BSIT 2024 Revised", "year_level": "1st Year", "is_active": 1 }
    ]
  }
  ```
- **Frontend Caller:** Dynamic program select dropdown in Section Builder (`college_sections.php`).

### 2.2 Get Curriculum Subjects Preview
- **Route:** `GET /admin/ajax/get_curriculum_subjects_preview.php`
- **Controller:** `App\Controllers\Api\AdminApiController@getCurriculumSubjectsPreview`
- **Auth:** Session required (`admin`, `superadmin`)
- **Query Parameters:** `curriculum_id` (int, required)
- **Response Format:**
  ```json
  {
    "status": "success",
    "data": [
      { "id": 12, "subject_code": "IT101", "subject_name": "Introduction to Computing", "units": 3, "year_level": 1, "semester": "First" }
    ]
  }
  ```
- **Frontend Caller:** Interactive subject curriculum viewer modal (`college_curriculum.php`).

### 2.3 Get Enrollment Summary
- **Route:** `GET /admin/ajax/get_enrollment_summary.php`
- **Controller:** `App\Controllers\Api\AdminApiController@getEnrollmentSummary`
- **Auth:** Session required (`admin`, `superadmin`)
- **Query Parameters:** `academic_year` (string, optional)
- **Response Format:**
  ```json
  {
    "status": "success",
    "data": {
      "total_applications": 450,
      "enrolled_college": 320,
      "enrolled_shs": 130,
      "pending_admissions": 15
    }
  }
  ```
- **Frontend Caller:** Admin dashboard KPI charts (`dashboard.php`).

---

## 3. Applicant Portal API Endpoints (`ApplicantApiController.php`)

### 3.1 Get Curriculum
- **Route:** `GET /applicant/api_get_curriculum.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getCurriculum`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `program_id` (int, required)
- **Purpose:** Fetches available curricula for applicant's selected program.

### 3.2 Get Full Curriculum Catalog
- **Route:** `GET /applicant/api_get_full_curriculum.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getFullCurriculum`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `curriculum_id` (int, required)
- **Purpose:** Retrieves all subject requirements grouped by semester for prospectus view.

### 3.3 Get Student Timetable Schedule
- **Route:** `GET /applicant/api_get_schedule.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getSchedule`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `application_id` (int, optional)
- **Response Format:**
  ```json
  {
    "status": "success",
    "data": [
      { "subject_code": "IT101", "subject_name": "Intro to Computing", "day": "Monday", "start_time": "08:00:00", "end_time": "10:00:00", "room": "Lab 3" }
    ]
  }
  ```
- **Frontend Caller:** Dynamic calendar scheduler grid in `/applicant/enroll.php`.

### 3.4 Get Sections by Curriculum & Year
- **Route:** `GET /applicant/api_get_sections.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getSections`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `curriculum_id` (int), `year_level` (string)
- **Purpose:** Loads available class sections with open seat slots.

### 3.5 Get Section Subjects
- **Route:** `GET /applicant/api_get_section_subjects.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getSectionSubjects`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `section_id` (int, required)
- **Purpose:** Fetches timetable blocks and subject listings for a specific class section.

### 3.6 Get Subject Schedules
- **Route:** `GET /applicant/api_get_subject_schedules.php`
- **Controller:** `App\Controllers\Api\ApplicantApiController@getSubjectSchedules`
- **Auth:** Session required (`applicant`)
- **Query Parameters:** `subject_id` (int, required)
- **Purpose:** Lists all section timeslots where a specific subject is being offered.

---

## 4. General Core API Routes

### 4.1 System Status Ping
- **Route:** `GET /api/status`
- **Auth:** Public / TestMiddleware
- **Response:**
  ```json
  { "status": "success", "message": "API is working" }
  ```

### 4.2 User Lookup Test Endpoint
- **Route:** `GET /api/users/{id}`
- **Auth:** Public / TestMiddleware
- **Parameters:** `id` (URL parameter)
- **Response:**
  ```json
  { "status": "success", "user_id": "5", "message": "Fetched user 5 successfully" }
  ```

---
**Related:**
- [[System Architecture]]
- [[Applicant Portal]]
- [[Scheduler]]
