<?php

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

/** @var Router $router */

$router->get('/', ['App\Controllers\HomeController', 'index']);
$router->get('/demo_landing.php', ['App\Controllers\HomeController', 'demo']);

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware']], function (Router $router) {
    // Backward Compatibility routes (Strangler Fig Pattern)
    $router->get('/auth/login.php', ['App\Controllers\AuthController', 'showLogin']);
    $router->post('/auth/login_process.php', ['App\Controllers\AuthController', 'login']);
    $router->get('/auth/register.php', ['App\Controllers\AuthController', 'showRegister']);
    $router->post('/auth/register_process.php', ['App\Controllers\AuthController', 'register']);
    $router->get('/auth/logout.php', ['App\Controllers\AuthController', 'logout']);
    
    // LMS Auth
    $router->get('/auth/lms_faculty_login.php', ['App\Controllers\Lms\LmsAuthController', 'showFacultyLogin']);
    $router->get('/auth/lms_student_login.php', ['App\Controllers\Lms\LmsAuthController', 'showStudentLogin']);
    $router->post('/auth/lms_login_process.php', ['App\Controllers\Lms\LmsAuthController', 'loginProcess']);
});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:applicant']], function (Router $router) {
    // Applicant Portal Legacy Routes
    $router->get('/applicant/dashboard.php', ['App\Controllers\ApplicantController', 'dashboard']);
    $router->get('/applicant/application_form.php', ['App\Controllers\ApplicantController', 'applicationForm']);
    $router->post('/applicant/application_process.php', ['App\Controllers\ApplicantController', 'processApplication']);
    $router->get('/applicant/requirements.php', ['App\Controllers\ApplicantController', 'requirements']);
    $router->post('/applicant/upload_document.php', ['App\Controllers\ApplicantController', 'uploadDocument']);
    $router->get('/applicant/assessment.php', ['App\Controllers\ApplicantController', 'assessment']);
    $router->post('/applicant/payment_process.php', ['App\Controllers\ApplicantController', 'processPayment']);
    $router->get('/applicant/print_slip.php', ['App\Controllers\ApplicantController', 'printSlip']);
    $router->get('/applicant/scholarships.php', ['App\Controllers\ApplicantController', 'scholarships']);
    $router->post('/applicant/scholarship_apply.php', ['App\Controllers\ApplicantController', 'applyScholarship']);
    
    $router->get('/applicant/profile.php', ['App\Controllers\ApplicantController', 'profile']);
    $router->post('/applicant/profile_process.php', ['App\Controllers\ApplicantController', 'updateProfile']);
    
    $router->get('/applicant/enroll.php', ['App\Controllers\EnrollController', 'showForm']);
    $router->post('/applicant/enroll_process.php', ['App\Controllers\EnrollController', 'processForm']);
    $router->get('/applicant/status.php', ['App\Controllers\EnrollController', 'status']);
    
    // Document Uploads & Workflow
    $router->get('/applicant/documents.php', ['App\Controllers\DocumentController', 'index']);
    $router->post('/applicant/document_upload.php', ['App\Controllers\DocumentController', 'upload']);
    $router->post('/applicant/document_workflow.php', ['App\Controllers\DocumentController', 'workflow']);
    $router->get('/applicant/document_view.php', ['App\Controllers\DocumentController', 'viewDocument']);

    // Health Information
    $router->get('/applicant/health_info.php', ['App\Controllers\HealthController', 'index']);
    $router->post('/applicant/health_process.php', ['App\Controllers\HealthController', 'process']);
});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']], function (Router $router) {
    // Admin Admissions
    $router->get('/admin/admissions/admissions_dashboard.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'index']);
    $router->get('/admin/admissions/review.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'review']);
    $router->get('/admin/admissions/application_detail.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'detail']);
    $router->post('/admin/admissions/application_process.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'process']);
    $router->post('/admin/admissions/bulk_process.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'bulkProcess']);
    $router->get('/admin/admissions/document_view.php', ['App\Controllers\Admin\Admissions\AdmissionsController', 'viewDocument']);

    // Admin Registrar
    $router->get('/admin/registrar/registrar_dashboard.php', ['App\Controllers\Admin\Registrar\RegistrarController', 'dashboard']);
    $router->get('/admin/registrar/students.php', ['App\Controllers\Admin\Registrar\RegistrarController', 'students']);
    $router->post('/admin/registrar/students_export.php', ['App\Controllers\Admin\Registrar\RegistrarController', 'exportStudents']);
    $router->get('/admin/registrar/college_enrollment_queue.php', ['App\Controllers\Admin\Registrar\RegistrarController', 'collegeQueue']);
    $router->get('/admin/registrar/shs_enrollment_queue.php', ['App\Controllers\Admin\Registrar\RegistrarController', 'shsQueue']);

    $router->get('/admin/registrar/subjects.php', ['App\Controllers\Admin\Registrar\SubjectController', 'index']);
    $router->post('/admin/registrar/subject_process.php', ['App\Controllers\Admin\Registrar\SubjectController', 'process']);

    $router->get('/admin/registrar/college_programs.php', ['App\Controllers\Admin\Registrar\CollegeController', 'programs']);
    $router->post('/admin/registrar/college_program_process.php', ['App\Controllers\Admin\Registrar\CollegeController', 'processProgram']);
    $router->get('/admin/registrar/college_curriculum.php', ['App\Controllers\Admin\Registrar\CollegeController', 'curriculum']);
    $router->post('/admin/registrar/college_curriculum_process.php', ['App\Controllers\Admin\Registrar\CollegeController', 'processCurriculum']);
    $router->get('/admin/registrar/college_curriculum_builder.php', ['App\Controllers\Admin\Registrar\CollegeController', 'curriculumBuilder']);
    $router->post('/admin/registrar/college_curriculum_builder.php', ['App\Controllers\Admin\Registrar\CollegeController', 'curriculumBuilder']); // Handles POST for builder

    $router->get('/admin/registrar/shs_strands.php', ['App\Controllers\Admin\Registrar\ShsController', 'strands']);
    $router->post('/admin/registrar/shs_strand_process.php', ['App\Controllers\Admin\Registrar\ShsController', 'processStrand']);
    $router->get('/admin/registrar/shs_curriculum.php', ['App\Controllers\Admin\Registrar\ShsController', 'curriculum']);
    $router->post('/admin/registrar/shs_curriculum_process.php', ['App\Controllers\Admin\Registrar\ShsController', 'processCurriculum']);
    $router->get('/admin/registrar/shs_curriculum_builder.php', ['App\Controllers\Admin\Registrar\ShsController', 'curriculumBuilder']);
    $router->post('/admin/registrar/shs_curriculum_builder.php', ['App\Controllers\Admin\Registrar\ShsController', 'curriculumBuilder']);

    // Admin Finance
    $router->get('/admin/finance/cashier_dashboard.php', ['App\Controllers\Admin\Finance\FinanceController', 'dashboard']);
    $router->get('/admin/finance/cashier_assessment.php', ['App\Controllers\Admin\Finance\FinanceController', 'assessment']);
    $router->get('/admin/finance/cashier_payments.php', ['App\Controllers\Admin\Finance\FinanceController', 'payments']);
    $router->get('/admin/finance/cashier_receipt.php', ['App\Controllers\Admin\Finance\FinanceController', 'receipt']);
    $router->post('/admin/finance/cashier_process.php', ['App\Controllers\Admin\Finance\FinanceController', 'process']);
    $router->get('/admin/finance/fees.php', ['App\Controllers\Admin\Finance\FeeController', 'index']);
    $router->post('/admin/finance/fee_process.php', ['App\Controllers\Admin\Finance\FeeController', 'process']);

    // Admin Clinic
    $router->get('/admin/clinic/clinic_dashboard.php', ['App\Controllers\Admin\Clinic\ClinicController', 'dashboard']);
    $router->get('/admin/clinic/medical_clearance.php', ['App\Controllers\Admin\Clinic\ClinicController', 'index']);
    $router->get('/admin/clinic/medical_detail.php', ['App\Controllers\Admin\Clinic\ClinicController', 'detail']);
    $router->post('/admin/clinic/medical_process.php', ['App\Controllers\Admin\Clinic\ClinicController', 'process']);

    // Admin Scholarship
    $router->get('/admin/scholarship/scholarship_dashboard.php', ['App\Controllers\Admin\Scholarship\ScholarshipController', 'dashboard']);
    $router->get('/admin/scholarship/scholarships.php', ['App\Controllers\Admin\Scholarship\ScholarshipController', 'index']);
    $router->get('/admin/scholarship/scholarship_review.php', ['App\Controllers\Admin\Scholarship\ScholarshipController', 'review']);
    $router->get('/admin/scholarship/scholarship_detail.php', ['App\Controllers\Admin\Scholarship\ScholarshipController', 'detail']);
    $router->post('/admin/scholarship/scholarship_process.php', ['App\Controllers\Admin\Scholarship\ScholarshipController', 'process']);

    // Admin System
    $router->get('/admin/system/sysadmin_dashboard.php', ['App\Controllers\Admin\System\SystemController', 'dashboard']);
    $router->get('/admin/system/users.php', ['App\Controllers\Admin\System\SystemController', 'users']);
    $router->post('/admin/system/user_process.php', ['App\Controllers\Admin\System\SystemController', 'processUser']);
    $router->get('/admin/system/audit_logs.php', ['App\Controllers\Admin\System\SystemController', 'auditLogs']);
    $router->get('/admin/system/user_activity.php', ['App\Controllers\Admin\System\SystemController', 'userActivity']);
    $router->get('/admin/system/backup.php', ['App\Controllers\Admin\System\SystemController', 'backup']);
    $router->post('/admin/system/backup_process.php', ['App\Controllers\Admin\System\SystemController', 'processBackup']);
    $router->get('/admin/system/settings.php', ['App\Controllers\Admin\System\SystemController', 'settings']);
    $router->post('/admin/system/settings_process.php', ['App\Controllers\Admin\System\SystemController', 'processSettings']);
    
    // Admin LMS Management
    $router->get('/admin/lms/generator', ['App\Controllers\Admin\LmsAdminController', 'courseGenerator']);
    $router->post('/admin/lms/generate', ['App\Controllers\Admin\LmsAdminController', 'generateLmsCourse']);
    
    $router->get('/admin/system/reports.php', ['App\Controllers\Admin\System\ReportController', 'index']);
    $router->post('/admin/system/reports_export.php', ['App\Controllers\Admin\System\ReportController', 'export']);

    // Admin API Routes
    $router->get('/admin/ajax/get_curricula_by_program.php', ['App\Controllers\Api\AdminApiController', 'getCurriculaByProgram']);
    $router->get('/admin/ajax/get_curriculum_subjects_preview.php', ['App\Controllers\Api\AdminApiController', 'getCurriculumSubjectsPreview']);
    $router->get('/admin/ajax/get_enrollment_summary.php', ['App\Controllers\Api\AdminApiController', 'getEnrollmentSummary']);

    // Admin Main Dashboard
    $router->get('/admin/dashboard.php', ['App\Controllers\Admin\System\DashboardController', 'index']);
});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:scheduler']], function (Router $router) {
    // Admin Scheduler
    $router->get('/admin/scheduler/scheduler_dashboard.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'dashboard']);
    
    $router->get('/admin/scheduler/college_sections.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'collegeSections']);
    $router->post('/admin/scheduler/college_sections.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'collegeSections']);
    
    $router->get('/admin/scheduler/shs_sections.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'shsSections']);
    $router->post('/admin/scheduler/shs_sections.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'shsSections']);
    
    $router->get('/admin/scheduler/schedule_builder.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'builder']);
    $router->post('/admin/scheduler/schedule_builder.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'builder']);
    $router->post('/admin/scheduler/schedule_builder_process.php', ['App\Controllers\Admin\Scheduler\SchedulerController', 'process']);
});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:applicant']], function (Router $router) {
    // Applicant API Routes
    $router->get('/applicant/api_get_curriculum.php', ['App\Controllers\Api\ApplicantApiController', 'getCurriculum']);
    $router->get('/applicant/api_get_full_curriculum.php', ['App\Controllers\Api\ApplicantApiController', 'getFullCurriculum']);
    $router->get('/applicant/api_get_schedule.php', ['App\Controllers\Api\ApplicantApiController', 'getSchedule']);
    $router->get('/applicant/api_get_sections.php', ['App\Controllers\Api\ApplicantApiController', 'getSections']);
    $router->get('/applicant/api_get_section_subjects.php', ['App\Controllers\Api\ApplicantApiController', 'getSectionSubjects']);
    $router->get('/applicant/api_get_subject_schedules.php', ['App\Controllers\Api\ApplicantApiController', 'getSubjectSchedules']);

});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\CsrfMiddleware', 'App\Middleware\AuthMiddleware']], function (Router $router) {
    // LMS Student Portal
    $router->get('/lms/student/dashboard.php', ['App\Controllers\Lms\StudentController', 'dashboard']);
    $router->get('/lms/student/course.php', ['App\Controllers\Lms\StudentController', 'course']);
    $router->get('/lms/student/my_courses.php', ['App\Controllers\Lms\StudentController', 'myCourses']);
    
    $router->get('/lms/student/course/{course_id}/assignments', ['App\Controllers\Lms\StudentAssignmentController', 'index']);
    $router->get('/lms/student/course/{course_id}/assignments/{id}', ['App\Controllers\Lms\StudentAssignmentController', 'show']);
    $router->post('/lms/student/course/{course_id}/assignments/{id}/submit', ['App\Controllers\Lms\StudentAssignmentController', 'submit']);

    $router->get('/lms/student/course/{course_id}/quizzes', ['App\Controllers\Lms\StudentQuizController', 'index']);
    $router->get('/lms/student/course/{course_id}/quizzes/{id}', ['App\Controllers\Lms\StudentQuizController', 'show']);
    $router->post('/lms/student/course/{course_id}/quizzes/{id}/start', ['App\Controllers\Lms\StudentQuizController', 'start']);
    $router->get('/lms/student/course/{course_id}/quizzes/{quiz_id}/attempt/{attempt_id}', ['App\Controllers\Lms\StudentQuizController', 'attempt']);
    $router->post('/lms/student/course/{course_id}/quizzes/{quiz_id}/attempt/{attempt_id}/submit', ['App\Controllers\Lms\StudentQuizController', 'submit']);
    $router->get('/lms/student/course/{course_id}/quizzes/{quiz_id}/result/{attempt_id}', ['App\Controllers\Lms\StudentQuizController', 'result']);

    $router->get('/lms/student/course/{course_id}/gradebook', ['App\Controllers\Lms\StudentGradebookController', 'index']);
    $router->get('/lms/student/course/{course_id}/attendance', ['App\Controllers\Lms\StudentAttendanceController', 'index']);
    $router->get('/lms/student/course/{course_id}/announcements', ['App\Controllers\Lms\StudentAnnouncementController', 'index']);
    $router->get('/lms/student/calendar', ['App\Controllers\Lms\StudentCalendarController', 'index']);
    
    $router->get('/lms/student/profile.php', ['App\Controllers\Lms\StudentController', 'profile']);
    $router->get('/lms/student/messages.php', ['App\Controllers\Lms\StudentController', 'messages']);

    // LMS Faculty Portal
    $router->get('/lms/faculty/dashboard.php', ['App\Controllers\Lms\FacultyController', 'dashboard']);
    $router->get('/lms/faculty/course.php', ['App\Controllers\Lms\FacultyController', 'course']);
    $router->post('/lms/faculty/module_create.php', ['App\Controllers\Lms\FacultyController', 'createModule']);
    $router->post('/lms/faculty/material_upload.php', ['App\Controllers\Lms\FacultyController', 'uploadMaterial']);
    
    $router->get('/lms/faculty/profile.php', ['App\Controllers\Lms\FacultyController', 'profile']);
    $router->get('/lms/faculty/messages.php', ['App\Controllers\Lms\FacultyController', 'messages']);
    $router->get('/lms/faculty/calendar', ['App\Controllers\Lms\FacultyCalendarController', 'index']);
    
    $router->get('/lms/faculty/course/{course_id}/assignments', ['App\Controllers\Lms\FacultyAssignmentController', 'index']);
    $router->get('/lms/faculty/course/{course_id}/assignments/create', ['App\Controllers\Lms\FacultyAssignmentController', 'create']);
    $router->post('/lms/faculty/course/{course_id}/assignments/store', ['App\Controllers\Lms\FacultyAssignmentController', 'store']);
    $router->get('/lms/faculty/course/{course_id}/assignments/{id}/edit', ['App\Controllers\Lms\FacultyAssignmentController', 'edit']);
    $router->post('/lms/faculty/course/{course_id}/assignments/{id}/update', ['App\Controllers\Lms\FacultyAssignmentController', 'update']);
    $router->get('/lms/faculty/course/{course_id}/assignments/{id}/submissions', ['App\Controllers\Lms\FacultyAssignmentController', 'submissions']);
    $router->post('/lms/faculty/course/{course_id}/assignments/{id}/grade', ['App\Controllers\Lms\FacultyAssignmentController', 'grade']);

    $router->get('/lms/faculty/course/{course_id}/quizzes', ['App\Controllers\Lms\FacultyQuizController', 'index']);
    $router->get('/lms/faculty/course/{course_id}/quizzes/create', ['App\Controllers\Lms\FacultyQuizController', 'create']);
    $router->post('/lms/faculty/course/{course_id}/quizzes/store', ['App\Controllers\Lms\FacultyQuizController', 'store']);
    $router->get('/lms/faculty/course/{course_id}/quizzes/{id}/edit', ['App\Controllers\Lms\FacultyQuizController', 'edit']);
    $router->post('/lms/faculty/course/{course_id}/quizzes/{id}/update', ['App\Controllers\Lms\FacultyQuizController', 'update']);
    $router->get('/lms/faculty/course/{course_id}/quizzes/{id}/questions', ['App\Controllers\Lms\FacultyQuizController', 'questions']);
    $router->post('/lms/faculty/course/{course_id}/quizzes/{id}/questions/store', ['App\Controllers\Lms\FacultyQuizController', 'storeQuestion']);
    $router->get('/lms/faculty/course/{course_id}/quizzes/{id}/results', ['App\Controllers\Lms\FacultyQuizController', 'results']);

    $router->get('/lms/faculty/course/{course_id}/gradebook', ['App\Controllers\Lms\FacultyGradebookController', 'index']);

    $router->get('/lms/faculty/course/{course_id}/attendance', ['App\Controllers\Lms\FacultyAttendanceController', 'index']);
    $router->get('/lms/faculty/course/{course_id}/attendance/create', ['App\Controllers\Lms\FacultyAttendanceController', 'create']);
    $router->post('/lms/faculty/course/{course_id}/attendance/store', ['App\Controllers\Lms\FacultyAttendanceController', 'store']);
    $router->get('/lms/faculty/course/{course_id}/attendance/{id}/edit', ['App\Controllers\Lms\FacultyAttendanceController', 'edit']);
    $router->post('/lms/faculty/course/{course_id}/attendance/{id}/update', ['App\Controllers\Lms\FacultyAttendanceController', 'update']);

    $router->get('/lms/faculty/course/{course_id}/announcements', ['App\Controllers\Lms\FacultyAnnouncementController', 'index']);
    $router->get('/lms/faculty/course/{course_id}/announcements/create', ['App\Controllers\Lms\FacultyAnnouncementController', 'create']);
    $router->post('/lms/faculty/course/{course_id}/announcements/store', ['App\Controllers\Lms\FacultyAnnouncementController', 'store']);
    $router->get('/lms/faculty/course/{course_id}/announcements/{id}/edit', ['App\Controllers\Lms\FacultyAnnouncementController', 'edit']);
    $router->post('/lms/faculty/course/{course_id}/announcements/{id}/update', ['App\Controllers\Lms\FacultyAnnouncementController', 'update']);

    // LMS Secure File Delivery
    $router->get('/lms/download/material/{id}', ['App\Controllers\Lms\DownloadController', 'downloadMaterial']);
    $router->get('/lms/download/submission/{id}', ['App\Controllers\Lms\DownloadController', 'downloadSubmission']);

});

// Grouped routes with Middleware and Prefix
$router->group(['prefix' => '/api', 'middleware' => 'App\Middleware\TestMiddleware'], function (Router $router) {
    
    // Test basic grouped route
    $router->get('/status', function (Request $request, Response $response) {
        $response->json(['status' => 'success', 'message' => 'API is working']);
    });

    // Test dynamic parameters
    $router->get('/users/{id}', function (Request $request, Response $response, string $id) {
        $response->json([
            'status' => 'success', 
            'user_id' => $id, 
            'message' => "Fetched user $id successfully"
        ]);
    });

});
