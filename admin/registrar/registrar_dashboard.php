<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['students.view', 'programs.manage']);

$pageTitle = 'Registrar Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';

$stats = [
    'enrolled' => 0,
    'ready_to_enroll' => 0,
    'active_sections' => 0,
    'total_students' => 0
];

try {
    // 1. Enrolled Applications
    $stmtEnrolled = $pdo->query('SELECT COUNT(*) FROM applications WHERE status = "enrolled"');
    $stats['enrolled'] = (int) $stmtEnrolled->fetchColumn();

    // 2. Ready to Enroll (Enrollment Queue)
    $stmtReady = $pdo->query('
        SELECT COUNT(*) FROM applications a 
        INNER JOIN student_assessments sa ON a.id = sa.application_id 
        WHERE a.status = "approved" AND sa.payment_status IN ("partial", "paid")
    ');
    $stats['ready_to_enroll'] = (int) $stmtReady->fetchColumn();

    // 3. Active Sections
    $stmtColSections = $pdo->query('SELECT COUNT(*) FROM college_sections WHERE status = 1');
    $stmtShsSections = $pdo->query('SELECT COUNT(*) FROM shs_sections WHERE status = 1');
    $stats['active_sections'] = (int) $stmtColSections->fetchColumn() + (int) $stmtShsSections->fetchColumn();

    // 4. Total Active Students (Users with student_number)
    $stmtStudents = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "applicant" AND student_number IS NOT NULL');
    $stats['total_students'] = (int) $stmtStudents->fetchColumn();

} catch (PDOException $e) {
    error_log('Registrar dashboard stats failed: ' . $e->getMessage());
}

?>

<?php require_once __DIR__ . '/../components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Registrar Dashboard</h1>
          <p class="text-muted mb-0">Manage student records, enrollment queues, and academic structures.</p>
        </div>
      </div>
    </div>

    <!-- Academic Statistics -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-mortarboard fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['enrolled'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Enrolled</p>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-person-lines-fill fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['ready_to_enroll'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Enrollment Queue</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-diagram-3-fill fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['active_sections'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Active Sections</p>
        </div>
      </div>

      <div class="col-md-3">
        <a href="students.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
              <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-people-fill fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['total_students'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Registered Students</p>
            </div>
        </a>
      </div>
    </div>

    <!-- Quick Navigation Links -->
    <div class="row g-4">
      <div class="col-md-6">
        <div class="island h-100">
          <div class="island-header border-bottom pb-2">
            <i class="bi bi-mortarboard-fill text-primary"></i>
            <h2>College Shortcuts</h2>
          </div>
          <div class="island-body p-4">
            <div class="d-flex flex-column gap-3">
                <a href="college_programs.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-journal-bookmark fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Academic Programs</span>
                    <span class="small text-muted d-block text-body">Manage college courses</span>
                  </div>
                </a>
                <a href="college_curriculum.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-diagram-3 fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Curriculum Directory</span>
                    <span class="small text-muted d-block text-body">Manage subject layouts</span>
                  </div>
                </a>
                <a href="college_sections.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-diagram-3-fill fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Sections & Schedules</span>
                    <span class="small text-muted d-block text-body">Manage block sections</span>
                  </div>
                </a>
                <a href="college_enrollment_queue.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-person-lines-fill fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Enrollment Queue</span>
                    <span class="small text-muted d-block text-body">Process college enrollees</span>
                  </div>
                </a>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="island h-100">
          <div class="island-header border-bottom pb-2">
            <i class="bi bi-building text-primary"></i>
            <h2>Senior High Shortcuts</h2>
          </div>
          <div class="island-body p-4">
            <div class="d-flex flex-column gap-3">
                <a href="shs_strands.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-journal-text fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">SHS Strands</span>
                    <span class="small text-muted d-block text-body">Manage SHS tracks</span>
                  </div>
                </a>
                <a href="shs_curriculum.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-diagram-3 fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Strand Curriculum</span>
                    <span class="small text-muted d-block text-body">Manage grade 11/12 subjects</span>
                  </div>
                </a>
                <a href="shs_sections.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-diagram-3-fill fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Sections & Schedules</span>
                    <span class="small text-muted d-block text-body">Manage class schedules</span>
                  </div>
                </a>
                <a href="shs_enrollment_queue.php" class="btn btn-outline-primary text-start p-3 rounded-12 d-flex align-items-center gap-3">
                  <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2"><i class="bi bi-person-lines-fill fs-5"></i></div>
                  <div>
                    <span class="fw-bold d-block">Enrollment Queue</span>
                    <span class="small text-muted d-block text-body">Process SHS enrollees</span>
                  </div>
                </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

