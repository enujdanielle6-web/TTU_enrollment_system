<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['lms_logged_in']) || $_SESSION['lms_role'] !== 'student') {
    header("Location: ../../auth/lms_student_login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Fetch user's enrolled subjects
$enrolled_courses = [];
try {
    global $pdo;
    if (!isset($pdo)) {
        $pdo = (new Database())->getConnection();
    }
    
    $stmt = $pdo->prepare("
        SELECT s.id as subject_id, s.subject_code as code, s.subject_name as name, cs.section_code as section_name, s.units
        FROM college_enrollments ce
        JOIN applications a ON ce.application_id = a.id
        JOIN subjects s ON ce.subject_id = s.id
        LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
        WHERE a.user_id = :uid AND a.status = 'enrolled'
    ");
    $stmt->execute(['uid' => $_SESSION['lms_user_id']]);
    $enrolled_courses = $stmt->fetchAll();
} catch (Exception $e) {
    // Graceful fallback
}

$pageTitle = 'Dashboard - TTU LMS';
require_once __DIR__ . '/layout_header.php';
?>

<div class="container-fluid py-4">

    <div class="row g-4">
        <!-- Left Column: Main Content -->
        <div class="col-lg-8">
            <!-- Next Upcoming Event -->
            <div class="lms-card p-4 mb-4 bg-primary bg-opacity-10 border-0 shadow-sm position-relative overflow-hidden">
        <div class="position-absolute" style="top: -50px; right: -50px; width: 150px; height: 150px; background: var(--lms-primary-light); border-radius: 50%; opacity: 0.3; filter: blur(30px);"></div>
        <div class="d-flex justify-content-between align-items-center mb-3 position-relative z-1">
            <span class="text-primary fw-bold small text-uppercase"><i class="bi bi-calendar-event me-2"></i>Next Upcoming Event</span>
            <a href="#" class="text-primary small fw-semibold text-decoration-none">View All</a>
        </div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
            <div class="d-flex align-items-center gap-4">
                <div class="bg-white rounded-4 p-3 text-center shadow-sm" style="min-width: 80px;">
                    <div class="text-primary fw-bold text-uppercase small mb-1">Jul</div>
                    <div class="fs-2 fw-bold text-dark lh-1">22</div>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-1 h4">Monthly Live Call</h3>
                    <div class="text-muted small d-flex gap-3">
                        <span><i class="bi bi-clock me-1"></i> 2:30 PM - 4:00 PM</span>
                        <span><i class="bi bi-people me-1"></i> 1 attending</span>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm">View Details</button>
        </div>
    </div>

    <!-- Quick Actions -->
    <h4 class="fw-bold mb-3 h5 text-dark mt-2">Quick Actions</h4>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                    <i class="bi bi-play-circle fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Resume Course</h6>
                    <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Pick up where you left off</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0">
                <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                    <i class="bi bi-file-earmark-text fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">View Assignments</h6>
                    <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Up to date</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0">
                <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                    <i class="bi bi-people fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Join Community</h6>
                    <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Connect & learn together</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0">
                <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                    <i class="bi bi-download fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Download Cert</h6>
                    <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Get achievement</p>
                </div>
            </div>
        </div>
    </div>

            <div class="d-flex justify-content-between align-items-end mb-3 mt-2">
                <h3 class="h5 fw-bold mb-0">My Courses</h3>
                <span class="text-muted small"><?= count($enrolled_courses) ?> Enrolled</span>
            </div>
            
            <?php if (empty($enrolled_courses)): ?>
                <!-- Learning Journey -->
                <div class="lms-card p-0 position-relative overflow-hidden border-0 shadow-sm">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-4 bg-light p-4 d-flex justify-content-center align-items-center border-end" style="min-height: 250px;">
                            <i class="bi bi-geo-alt-fill text-primary opacity-75" style="font-size: 6rem;"></i>
                        </div>
                        <div class="col-md-8 p-5">
                            <h3 class="fw-bold text-dark mb-3">Your journey is waiting to begin!</h3>
                            <p class="text-muted mb-4 fs-6 lh-lg" style="max-width: 500px;">
                                Enroll in a course or follow a learning route to track your progress here. Once Phase 2 is complete, your assigned modules will populate this area.
                            </p>
                            <button class="btn btn-primary px-4 py-2 rounded-pill shadow-sm fw-bold">Explore Courses &rarr;</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="col-md-6">
                            <a href="course.php?id=<?= $course['subject_id'] ?>" class="text-decoration-none text-dark d-block h-100">
                                <div class="lms-card h-100 transition-all shadow-sm-hover overflow-hidden border">
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 160px;">
                                        <i class="bi bi-image text-muted opacity-25" style="font-size: 3rem;"></i>
                                    </div>
                                    <div class="p-4 text-center">
                                        <h4 class="h5 fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($course['name']) ?>">
                                            <?= htmlspecialchars($course['name']) ?>
                                        </h4>
                                        <div class="text-muted small fw-semibold">
                                            <?= htmlspecialchars($course['code']) ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column (Sidebar Widgets) -->
        <div class="col-lg-4">
            
            <!-- Streak Widget -->
            <div class="lms-card mb-4 bg-primary bg-opacity-10 border-0">
                <div class="d-flex align-items-center gap-3 p-3">
                    <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-fire fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0">3 Day Streak!</h4>
                        <p class="text-muted small mb-0">Keep it up, you're doing great!</p>
                    </div>
                </div>
            </div>


            <!-- Upcoming Deadlines Widget -->
            <div class="lms-card mb-4">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <h4 class="lms-card-title mb-0 h5 fw-bold">Upcoming Deadlines</h4>
                    <a href="#" class="text-muted small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                </div>
                <div class="p-3 pt-0">
                    <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom">
                        <div class="bg-danger bg-opacity-10 text-danger rounded p-2 text-center" style="min-width: 60px;">
                            <div class="small fw-bold text-uppercase">Aug</div>
                            <div class="fs-5 fw-bold">15</div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Midterm Exam</div>
                            <div class="text-muted small">Introduction to Computing</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded p-2 text-center" style="min-width: 60px;">
                            <div class="small fw-bold text-uppercase">Aug</div>
                            <div class="fs-5 fw-bold">22</div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Project Proposal</div>
                            <div class="text-muted small">Programming 1</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Widget -->
            <div class="lms-card mb-4">
                <div class="lms-card-header border-0 pb-3 d-flex justify-content-between align-items-center">
                    <h4 class="lms-card-title mb-0 h5 fw-bold"><i class="bi bi-bell-fill me-2 text-primary"></i> Notifications</h4>
                    <a href="#" class="text-muted small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                </div>
                <div class="p-3 pt-0">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="border-start border-4 border-primary ps-3">
                            <div class="d-flex justify-content-between mb-1">
                                <div class="fw-bold text-dark">System Maintenance</div>
                                <div class="text-muted small">2 hrs ago</div>
                            </div>
                            <div class="text-muted small">LMS will be down from 2 AM - 4 AM.</div>
                        </div>
                    </div>
                    <div>
                        <div class="border-start border-4 border-primary ps-3">
                            <div class="d-flex justify-content-between mb-1">
                                <div class="fw-bold text-dark">New Material Uploaded</div>
                                <div class="text-muted small">5 hrs ago</div>
                            </div>
                            <div class="text-muted small">Prof. Smith uploaded Chapter 4 slides.</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
