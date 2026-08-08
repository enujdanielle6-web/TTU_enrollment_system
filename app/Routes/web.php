<?php

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

/** @var Router $router */

$router->get('/', ['App\Controllers\HomeController', 'index']);

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware']], function (Router $router) {
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

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\AuthMiddleware']], function (Router $router) {
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

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\AuthMiddleware', 'App\Middleware\RoleMiddleware:admin']], function (Router $router) {
    // Admin Admissions
    $router->get('/admin/admissions/admissions_dashboard.php', ['App\Controllers\Admin\AdmissionsController', 'index']);
    $router->get('/admin/admissions/review.php', ['App\Controllers\Admin\AdmissionsController', 'review']);
    $router->get('/admin/admissions/application_detail.php', ['App\Controllers\Admin\AdmissionsController', 'detail']);
    $router->post('/admin/admissions/application_process.php', ['App\Controllers\Admin\AdmissionsController', 'process']);
    $router->post('/admin/admissions/bulk_process.php', ['App\Controllers\Admin\AdmissionsController', 'bulkProcess']);
    $router->get('/admin/admissions/document_view.php', ['App\Controllers\Admin\AdmissionsController', 'viewDocument']);

    // Admin Registrar
    $router->get('/admin/registrar/registrar_dashboard.php', ['App\Controllers\Admin\RegistrarController', 'dashboard']);
    $router->get('/admin/registrar/students.php', ['App\Controllers\Admin\RegistrarController', 'students']);
    $router->post('/admin/registrar/students_export.php', ['App\Controllers\Admin\RegistrarController', 'exportStudents']);
    $router->get('/admin/registrar/college_enrollment_queue.php', ['App\Controllers\Admin\RegistrarController', 'collegeQueue']);
    $router->get('/admin/registrar/shs_enrollment_queue.php', ['App\Controllers\Admin\RegistrarController', 'shsQueue']);

    $router->get('/admin/registrar/subjects.php', ['App\Controllers\Admin\SubjectController', 'index']);
    $router->post('/admin/registrar/subject_process.php', ['App\Controllers\Admin\SubjectController', 'process']);

    $router->get('/admin/registrar/college_programs.php', ['App\Controllers\Admin\CollegeController', 'programs']);
    $router->post('/admin/registrar/college_program_process.php', ['App\Controllers\Admin\CollegeController', 'processProgram']);
    $router->get('/admin/registrar/college_curriculum.php', ['App\Controllers\Admin\CollegeController', 'curriculum']);
    $router->post('/admin/registrar/college_curriculum_process.php', ['App\Controllers\Admin\CollegeController', 'processCurriculum']);
    $router->get('/admin/registrar/college_curriculum_builder.php', ['App\Controllers\Admin\CollegeController', 'curriculumBuilder']);
    $router->post('/admin/registrar/college_curriculum_builder.php', ['App\Controllers\Admin\CollegeController', 'curriculumBuilder']); // Handles POST for builder
    $router->get('/admin/registrar/college_sections.php', ['App\Controllers\Admin\CollegeController', 'sections']);
    $router->post('/admin/registrar/college_sections.php', ['App\Controllers\Admin\CollegeController', 'sections']); // Handles POST for section creation

    $router->get('/admin/registrar/shs_strands.php', ['App\Controllers\Admin\ShsController', 'strands']);
    $router->post('/admin/registrar/shs_strand_process.php', ['App\Controllers\Admin\ShsController', 'processStrand']);
    $router->get('/admin/registrar/shs_curriculum.php', ['App\Controllers\Admin\ShsController', 'curriculum']);
    $router->post('/admin/registrar/shs_curriculum_process.php', ['App\Controllers\Admin\ShsController', 'processCurriculum']);
    $router->get('/admin/registrar/shs_curriculum_builder.php', ['App\Controllers\Admin\ShsController', 'curriculumBuilder']);
    $router->post('/admin/registrar/shs_curriculum_builder.php', ['App\Controllers\Admin\ShsController', 'curriculumBuilder']);
    $router->get('/admin/registrar/shs_sections.php', ['App\Controllers\Admin\ShsController', 'sections']);
    $router->post('/admin/registrar/shs_sections.php', ['App\Controllers\Admin\ShsController', 'sections']);

    $router->get('/admin/registrar/schedule_builder.php', ['App\Controllers\Admin\ScheduleController', 'builder']);
    $router->post('/admin/registrar/schedule_builder.php', ['App\Controllers\Admin\ScheduleController', 'builder']);
    $router->post('/admin/registrar/schedule_builder_process.php', ['App\Controllers\Admin\ScheduleController', 'process']);

    // Admin Finance
    $router->get('/admin/finance/cashier_dashboard.php', ['App\Controllers\Admin\FinanceController', 'dashboard']);
    $router->get('/admin/finance/cashier_assessment.php', ['App\Controllers\Admin\FinanceController', 'assessment']);
    $router->get('/admin/finance/cashier_payments.php', ['App\Controllers\Admin\FinanceController', 'payments']);
    $router->get('/admin/finance/cashier_receipt.php', ['App\Controllers\Admin\FinanceController', 'receipt']);
    $router->post('/admin/finance/cashier_process.php', ['App\Controllers\Admin\FinanceController', 'process']);
    $router->get('/admin/finance/fees.php', ['App\Controllers\Admin\FeeController', 'index']);
    $router->post('/admin/finance/fee_process.php', ['App\Controllers\Admin\FeeController', 'process']);

    // Admin Clinic
    $router->get('/admin/clinic/clinic_dashboard.php', ['App\Controllers\Admin\ClinicController', 'dashboard']);
    $router->get('/admin/clinic/medical_clearance.php', ['App\Controllers\Admin\ClinicController', 'index']);
    $router->get('/admin/clinic/medical_detail.php', ['App\Controllers\Admin\ClinicController', 'detail']);
    $router->post('/admin/clinic/medical_process.php', ['App\Controllers\Admin\ClinicController', 'process']);

    // Admin Scholarship
    $router->get('/admin/scholarship/scholarship_dashboard.php', ['App\Controllers\Admin\ScholarshipController', 'dashboard']);
    $router->get('/admin/scholarship/scholarships.php', ['App\Controllers\Admin\ScholarshipController', 'index']);
    $router->get('/admin/scholarship/scholarship_review.php', ['App\Controllers\Admin\ScholarshipController', 'review']);
    $router->get('/admin/scholarship/scholarship_detail.php', ['App\Controllers\Admin\ScholarshipController', 'detail']);
    $router->post('/admin/scholarship/scholarship_process.php', ['App\Controllers\Admin\ScholarshipController', 'process']);

    // Admin System
    $router->get('/admin/system/sysadmin_dashboard.php', ['App\Controllers\Admin\SystemController', 'dashboard']);
    $router->get('/admin/system/users.php', ['App\Controllers\Admin\SystemController', 'users']);
    $router->post('/admin/system/user_process.php', ['App\Controllers\Admin\SystemController', 'processUser']);
    $router->get('/admin/system/audit_logs.php', ['App\Controllers\Admin\SystemController', 'auditLogs']);
    $router->get('/admin/system/user_activity.php', ['App\Controllers\Admin\SystemController', 'userActivity']);
    $router->get('/admin/system/backup.php', ['App\Controllers\Admin\SystemController', 'backup']);
    $router->post('/admin/system/backup_process.php', ['App\Controllers\Admin\SystemController', 'processBackup']);
    $router->get('/admin/system/settings.php', ['App\Controllers\Admin\SystemController', 'settings']);
    $router->post('/admin/system/settings_process.php', ['App\Controllers\Admin\SystemController', 'processSettings']);
    
    $router->get('/admin/system/reports.php', ['App\Controllers\Admin\ReportController', 'index']);
    $router->post('/admin/system/reports_export.php', ['App\Controllers\Admin\ReportController', 'export']);

    // Admin API Routes
    $router->get('/admin/ajax/get_curricula_by_program.php', ['App\Controllers\Api\AdminApiController', 'getCurriculaByProgram']);
    $router->get('/admin/ajax/get_curriculum_subjects_preview.php', ['App\Controllers\Api\AdminApiController', 'getCurriculumSubjectsPreview']);
    $router->get('/admin/ajax/get_enrollment_summary.php', ['App\Controllers\Api\AdminApiController', 'getEnrollmentSummary']);

    // Admin Main Dashboard
    $router->get('/admin/dashboard.php', ['App\Controllers\Admin\DashboardController', 'index']);
});

$router->group(['middleware' => ['App\Middleware\SessionSecurityMiddleware', 'App\Middleware\AuthMiddleware']], function (Router $router) {
    // Applicant API Routes
    $router->get('/applicant/api_get_curriculum.php', ['App\Controllers\Api\ApplicantApiController', 'getCurriculum']);
    $router->get('/applicant/api_get_full_curriculum.php', ['App\Controllers\Api\ApplicantApiController', 'getFullCurriculum']);
    $router->get('/applicant/api_get_schedule.php', ['App\Controllers\Api\ApplicantApiController', 'getSchedule']);
    $router->get('/applicant/api_get_sections.php', ['App\Controllers\Api\ApplicantApiController', 'getSections']);
    $router->get('/applicant/api_get_section_subjects.php', ['App\Controllers\Api\ApplicantApiController', 'getSectionSubjects']);
    $router->get('/applicant/api_get_subject_schedules.php', ['App\Controllers\Api\ApplicantApiController', 'getSubjectSchedules']);

    // LMS Student Portal
    $router->get('/lms/student/dashboard.php', ['App\Controllers\Lms\StudentController', 'dashboard']);
    $router->get('/lms/student/course.php', ['App\Controllers\Lms\StudentController', 'course']);
    $router->get('/lms/student/my_courses.php', ['App\Controllers\Lms\StudentController', 'myCourses']);

    // LMS Faculty Portal
    $router->get('/lms/faculty/dashboard.php', ['App\Controllers\Lms\FacultyController', 'dashboard']);
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
