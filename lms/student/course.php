<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['lms_logged_in']) || $_SESSION['lms_role'] !== 'student') {
    header("Location: ../../auth/lms_student_login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    header("Location: dashboard.php");
    exit;
}

// 1. Verify Enrollment & Fetch Subject Data
try {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = (new Database())->getConnection();
    }
    
    // Strict check: Student must be enrolled in this subject_id
    $stmt = $pdo->prepare("
        SELECT s.id, s.subject_code, s.subject_name, s.units, cs.section_code 
        FROM college_enrollments ce
        JOIN applications a ON ce.application_id = a.id
        JOIN subjects s ON ce.subject_id = s.id
        LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
        WHERE a.user_id = :uid AND a.status = 'enrolled' AND s.id = :sid
        LIMIT 1
    ");
    $stmt->execute(['uid' => $_SESSION['lms_user_id'], 'sid' => $subject_id]);
    $course = $stmt->fetch();

    if (!$course) {
        // Not enrolled or doesn't exist
        header("Location: dashboard.php");
        exit;
    }

    // 2. Fetch LMS Course Metadata (Welcome message, instructor info)
    $lms_stmt = $pdo->prepare("
        SELECT lc.welcome_message, lc.thumbnail_path, u.first_name, u.last_name, u.email
        FROM lms_courses lc
        LEFT JOIN users u ON lc.teacher_id = u.id
        WHERE lc.subject_id = :sid
        LIMIT 1
    ");
    $lms_stmt->execute(['sid' => $subject_id]);
    $lms_course = $lms_stmt->fetch();

    $instructor_name = $lms_course && $lms_course['first_name'] ? $lms_course['first_name'] . ' ' . $lms_course['last_name'] : 'Instructor TBA';
    $instructor_email = $lms_course && $lms_course['email'] ? $lms_course['email'] : 'N/A';
    $welcome_message = $lms_course && $lms_course['welcome_message'] ? $lms_course['welcome_message'] : 'Welcome to ' . htmlspecialchars($course['subject_name']) . '! Your instructor will post materials soon.';
    $thumbnail_path = $lms_course && $lms_course['thumbnail_path'] ? $lms_course['thumbnail_path'] : '../../images/default_course.jpg';

    // 3. Fetch Modules (Just the structure for now)
    $mod_stmt = $pdo->prepare("
        SELECT m.id, m.title, m.description, m.sequence_order
        FROM lms_modules m
        JOIN lms_courses c ON m.lms_course_id = c.id
        WHERE c.subject_id = :sid AND m.is_published = 1
        ORDER BY m.sequence_order ASC
    ");
    $mod_stmt->execute(['sid' => $subject_id]);
    $modules = $mod_stmt->fetchAll();

} catch (Exception $e) {
    error_log("LMS Course Error: " . $e->getMessage());
    header("Location: dashboard.php");
    exit;
}

$pageTitle = $course['subject_code'] . ' - TTU LMS';
$current_page = 'my_courses.php'; // Highlight "My Courses" in sidebar
require_once __DIR__ . '/layout_header.php';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="my_courses.php" class="text-decoration-none">My Courses</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($course['subject_code']) ?></li>
        </ol>
    </nav>

    <!-- Course Banner -->
    <div class="lms-banner mb-4 text-white p-5 rounded-4 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--lms-primary) 0%, #0a58ca 100%);">
        <div class="position-absolute" style="top: -50px; right: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%; filter: blur(20px);"></div>
        <div class="position-relative z-1 row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white text-primary mb-2 px-3 py-2 rounded-pill fw-bold shadow-sm">
                    <?= htmlspecialchars($course['section_code'] ?? 'Global Section') ?>
                </span>
                <h1 class="display-5 fw-bold mb-2 text-white"><?= htmlspecialchars($course['subject_name']) ?></h1>
                <p class="fs-5 mb-0 opacity-75 fw-semibold"><?= htmlspecialchars($course['subject_code']) ?> &bull; <?= (int)$course['units'] ?> Units</p>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0 d-none d-md-block">
                 <div class="bg-white text-dark p-3 rounded-4 shadow-sm d-inline-block text-start">
                     <p class="small text-muted fw-bold text-uppercase mb-1">Instructor</p>
                     <div class="d-flex align-items-center gap-3">
                         <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                             <?= substr($instructor_name, 0, 1) ?>
                         </div>
                         <div>
                             <h6 class="mb-0 fw-bold"><?= htmlspecialchars($instructor_name) ?></h6>
                             <small class="text-muted"><?= htmlspecialchars($instructor_email) ?></small>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content (Overview + Modules) -->
        <div class="col-lg-8">
            <!-- Welcome Overview -->
            <div class="lms-card p-4 mb-4 border-0 shadow-sm">
                <h4 class="h5 fw-bold mb-3">Course Overview</h4>
                <div class="text-secondary lh-lg">
                    <?= nl2br(htmlspecialchars($welcome_message)) ?>
                </div>
            </div>

            <!-- Modules List -->
            <h4 class="h5 fw-bold mb-3 mt-5">Learning Modules</h4>
            
            <?php if (empty($modules)): ?>
                <div class="lms-card p-5 text-center border-0 shadow-sm bg-light">
                    <i class="bi bi-box-seam text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-dark">No Modules Yet</h5>
                    <p class="text-muted mb-0">Your instructor hasn't published any learning modules for this course yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="accordion" id="modulesAccordion">
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden lms-card">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> bg-white fw-bold text-dark p-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMod<?= $module['id'] ?>">
                                    <div class="d-flex align-items-center gap-3 w-100">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; flex-shrink: 0;">
                                            <?= $index + 1 ?>
                                        </div>
                                        <?= htmlspecialchars($module['title']) ?>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseMod<?= $module['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#modulesAccordion">
                                <div class="accordion-body p-4 pt-0 text-secondary bg-white">
                                    <p class="mb-3"><?= nl2br(htmlspecialchars($module['description'] ?? 'No description provided.')) ?></p>
                                    
                                    <!-- Placeholder for Lessons -->
                                    <div class="list-group list-group-flush border-top pt-2">
                                        <div class="list-group-item px-0 py-3 d-flex align-items-center gap-3 border-bottom-0 text-muted">
                                            <i class="bi bi-journal-text fs-5"></i>
                                            <div>
                                                <span class="d-block fw-semibold">Lessons will appear here</span>
                                                <small>Coming in the next module implementation</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Widgets (Course Resources) -->
        <div class="col-lg-4">
            <div class="lms-card p-4 mb-4 border-0 shadow-sm">
                <h4 class="h6 fw-bold mb-3 text-uppercase text-muted"><i class="bi bi-folder-fill me-2 text-primary"></i> Course Resources</h4>
                <div class="text-center py-4 bg-light rounded-3">
                    <p class="text-muted small mb-0">No resources attached.</p>
                </div>
            </div>

            <div class="lms-card p-4 border-0 shadow-sm">
                <h4 class="h6 fw-bold mb-3 text-uppercase text-muted"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Your Progress</h4>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold fs-3 text-dark">0%</span>
                    <span class="text-muted small">0 of 0 lessons</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
