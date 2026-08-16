# API and AJAX Documentation

The system provides several JSON API endpoints to power dynamic frontend dropdowns and modal contents. These are housed inside `app/Controllers/Api/`.

## Admin Endpoints (`AdminApiController.php`)

### `/admin/ajax/get_curricula_by_program.php`
- **Method:** GET
- **Auth:** Session + Role (`admin`/`registrar`)
- **Params:** `program_id`
- **Returns:** JSON list of curricula for a specific program.
- **Used By:** `admin/registrar/college_sections.php` (for dynamically building section selects).

### `/admin/ajax/get_curriculum_subjects_preview.php`
- **Method:** GET
- **Auth:** Session + Role (`admin`/`registrar`)
- **Params:** `curriculum_id`
- **Returns:** JSON list of mapped subjects and their prerequisites.
- **Used By:** `admin/registrar/college_curriculum.php`

### `/admin/ajax/get_enrollment_summary.php`
- **Method:** GET
- **Auth:** Session + Role (`admin`)
- **Params:** `academic_year`
- **Returns:** JSON containing aggregate counts for dashboards.

## Applicant Endpoints (`ApplicantApiController.php`)

### `/applicant/api_get_schedule.php`
- **Method:** GET
- **Auth:** Session + Role (`applicant`)
- **Params:** `application_id`
- **Returns:** JSON representing the student's current mapped schedule blocks.
- **Used By:** `/applicant/enroll.php` dynamic calendar rendering.

## Error Handling
- Invalid requests return HTTP 400 or 403.
- Responses follow a standard format:
  ```json
  {
    "status": "success", // or error
    "data": { ... },
    "message": ""
  }
  ```
