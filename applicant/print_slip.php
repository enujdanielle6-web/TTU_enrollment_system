<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];

try {
    // 1. Fetch Application & User data
    $stmt = $pdo->prepare('
        SELECT a.*, u.first_name, u.last_name, u.email, u.student_number 
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.user_id = :user_id AND a.status IN ("approved", "enrolled")
        ORDER BY a.created_at DESC 
        LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $app = $stmt->fetch();

    if (!$app) {
        showErrorPage('Application Not Found', 'No active application found or your application is not yet approved.', 404);
    }
    
    $appId = (int)$app['id'];

    // 2. Fetch Documents (Requirements)
    $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id');
    $docStmt->execute(['app_id' => $appId]);
    $documents = $docStmt->fetchAll();

    // 3. Fetch Assessment
    $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE application_id = :app_id LIMIT 1');
    $assStmt->execute(['app_id' => $appId]);
    $assessment = $assStmt->fetch();

    // 4. Fetch Enrolled Subjects & Schedules
    if (($app['academic_level'] ?? '') === 'Senior High School') {
        $esStmt = $pdo->prepare('
            SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.shs_section_id as section_id, sec.section_code
            FROM shs_enrollments es
            INNER JOIN subjects s ON s.id = es.subject_id
            LEFT JOIN shs_sections sec ON sec.id = es.shs_section_id
            WHERE es.application_id = :app_id
            ORDER BY s.subject_code ASC
        ');
    } else {
        $esStmt = $pdo->prepare('
            SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.college_section_id as section_id, sec.section_code
            FROM college_enrollments es
            INNER JOIN subjects s ON s.id = es.subject_id
            LEFT JOIN college_sections sec ON sec.id = es.college_section_id
            WHERE es.application_id = :app_id
            ORDER BY s.subject_code ASC
        ');
    }
    $esStmt->execute(['app_id' => $appId]);
    $enrolledSubjects = $esStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Attach schedules using a single query
    $sectionIds = array_unique(array_filter(array_map(function($sub) use ($app) {
        return $sub['section_id'] ?: ($app['section_id'] ?? null);
    }, $enrolledSubjects)));

    $allSchedules = [];
    if (!empty($sectionIds)) {
        $in = str_repeat('?,', count($sectionIds) - 1) . '?';
        if (($app['academic_level'] ?? '') === 'Senior High School') {
            $schedStmt = $pdo->prepare("SELECT shs_section_id as section_id, subject_id, day, start_time, end_time, room, instructor FROM shs_section_subjects WHERE shs_section_id IN ($in)");
        } else {
            $schedStmt = $pdo->prepare("SELECT college_section_id as section_id, subject_id, day, start_time, end_time, room, instructor FROM college_section_subjects WHERE college_section_id IN ($in)");
        }
        $schedStmt->execute(array_values($sectionIds));
        $allSchedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($enrolledSubjects as &$sub) {
        $sub['schedules'] = [];
        $targetSecId = $sub['section_id'] ?: ($app['section_id'] ?? null);
        foreach ($allSchedules as $sc) {
            if ($sc['section_id'] == $targetSecId && $sc['subject_id'] == $sub['subject_id']) {
                $sub['schedules'][] = $sc;
            }
        }
    }

} catch (PDOException $e) {
    error_log("Enrollment summary error: " . $e->getMessage());
    showErrorPage('Database Error', 'A database error occurred while fetching your enrollment summary.');
}

$pageTitle = 'Enrollment Summary - ' . htmlspecialchars((string)($app['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8');
require_once __DIR__ . '/../components/header.php';
?>
<style>
@media print {
    .no-print, .no-print * {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    body {
        background-color: #fff !important;
        margin: 0;
        padding: 0;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: transparent !important;
        border-bottom: 2px solid #000 !important;
        color: #000 !important;
        padding: 0 0 10px 0 !important;
        margin-bottom: 15px !important;
    }
    .island {
        box-shadow: none !important;
        border: none !important;
    }
    main {
        padding: 0 !important;
    }
    .col-lg-4, .col-lg-8 {
        width: 100% !important;
    }
}

.table-schedule th, .table-schedule td {
    vertical-align: middle;
}
.timetable {
    width: 100%;
    border-collapse: collapse;
}
.timetable th, .timetable td {
    border: 1px solid #dee2e6;
    padding: 8px;
    text-align: center;
}
.timetable th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.time-col {
    width: 80px;
    font-size: 0.85rem;
    color: #6c757d;
}
.schedule-block {
    background-color: #e9ecef;
    border-radius: 4px;
    padding: 4px;
    font-size: 0.8rem;
    margin-bottom: 4px;
    border-left: 3px solid #0d6efd;
}
</style>

<div class="no-print">
<?php require_once __DIR__ . '/components/navbar.php'; ?>
</div>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Enrollment Summary</h1>
        <p class="text-muted mb-0">Review your final schedule and assessment before printing.</p>
      </div>
      <div>
        <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm rounded-pill fw-medium">
          <i class="bi bi-printer me-2"></i> Print Summary
        </button>
      </div>
    </div>

    <!-- Print Header -->
    <div class="print-only d-none text-center mb-4 pb-3 border-bottom border-dark border-2">
      <h1 class="h4 fw-bold text-uppercase mb-1">Triple T University</h1>
      <h2 class="h5 fw-bold text-uppercase mb-1">Official Enrollment Summary</h2>
      <p class="mb-0 text-muted">Academic Year <?= htmlspecialchars((string)($app['school_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="row g-4">
      
      <!-- Left Column: Student Info & Requirements -->
      <div class="col-lg-4">
        
        <!-- Student Information -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge text-primary me-2"></i> Student Information</h5>
          </div>
          <div class="card-body p-4">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" style="width: 40%;">Name</td>
                  <td class="fw-bold"><?= htmlspecialchars(($app['last_name'] ?? '') . ', ' . ($app['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                  <td class="text-muted">Applicant ID</td>
                  <td class="fw-bold"><?= htmlspecialchars((string)($app['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php if (!empty($app['student_number'])): ?>
                <tr>
                  <td class="text-muted">Student No.</td>
                  <td class="fw-bold text-primary"><?= htmlspecialchars($app['student_number'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                  <td class="text-muted">Program</td>
                  <td class="fw-bold"><?= htmlspecialchars(getStrandLabel((string)($app['strand'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                  <td class="text-muted">Level</td>
                  <td class="fw-bold"><?= htmlspecialchars((string)($app['academic_level'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                  <td class="text-muted">Year/Grade</td>
                  <td class="fw-bold"><?= htmlspecialchars((string)($app['grade_level'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                  <td class="text-muted">Status</td>
                  <td><span class="badge <?= getApplicationStatusBadgeClass((string)($app['status'] ?? '')) ?>"><?= formatApplicationStatus((string)($app['status'] ?? '')) ?></span></td>
                </tr>
                <tr>
                  <td class="text-muted">Student Type</td>
                  <td>
                    <?php if (($app['student_type'] ?? 'Regular') === 'Irregular'): ?>
                      <span class="badge bg-warning text-dark">Irregular</span>
                    <?php else: ?>
                      <span class="badge bg-success">Regular</span>
                    <?php endif; ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Requirements -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-check text-primary me-2"></i> Requirements</h5>
          </div>
          <div class="card-body p-4">
            <?php if (empty($documents)): ?>
              <p class="text-muted small mb-0">No documents submitted.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($documents as $doc): ?>
                  <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent border-0 py-1">
                    <span class="small fw-medium"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type'] ?? '')), ENT_QUOTES) ?></span>
                    <?php if ($doc['status'] === 'verified'): ?>
                      <i class="bi bi-check-circle-fill text-success" title="Verified"></i>
                    <?php elseif ($doc['status'] === 'rejected'): ?>
                      <i class="bi bi-x-circle-fill text-danger" title="Rejected"></i>
                    <?php else: ?>
                      <i class="bi bi-hourglass-split text-warning" title="Pending"></i>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tuition / Assessment -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-receipt text-primary me-2"></i> Assessment</h5>
          </div>
          <div class="card-body p-4">
            <?php if (!$assessment): ?>
              <div class="alert alert-warning small mb-0">Assessment pending.</div>
            <?php else: 
                $breakdown = json_decode($assessment['breakdown'] ?? '{}', true) ?: [];
                $tuition = $breakdown['tuition'] ?? 0;
                $misc = $breakdown['miscellaneous'] ?? 0;
                $lab = $breakdown['laboratory'] ?? 0;
                $other = $breakdown['other'] ?? 0;
                $discount = $breakdown['scholarship_discount'] ?? 0;
            ?>
              <table class="table table-sm table-borderless mb-2">
                <tbody>
                  <tr>
                    <td class="text-muted">Tuition Fee</td>
                    <td class="text-end fw-medium">₱<?= number_format((float)$tuition, 2) ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">Miscellaneous</td>
                    <td class="text-end fw-medium">₱<?= number_format((float)$misc, 2) ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">Laboratory</td>
                    <td class="text-end fw-medium">₱<?= number_format((float)$lab, 2) ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">Other Fees</td>
                    <td class="text-end fw-medium">₱<?= number_format((float)$other, 2) ?></td>
                  </tr>
                  <?php if ($discount > 0): ?>
                  <tr>
                    <td class="text-success">Scholarship Discount</td>
                    <td class="text-end fw-bold text-success">-₱<?= number_format((float)$discount, 2) ?></td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
              <hr class="my-2 border-secondary">
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="fw-bold text-dark text-uppercase">Total Amount</span>
                <span class="fw-bold text-primary fs-5">₱<?= number_format((float)($assessment['total_amount'] ?? 0), 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Right Column: Subjects & Schedule -->
      <div class="col-lg-8">
        
        <!-- Enrolled Subjects Table -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Enrolled Subjects</h5>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle table-schedule">
                <thead class="table-light">
                  <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Units</th>
                    <th>Instructor</th>
                    <th>Room</th>
                    <th>Schedule</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $totalUnits = 0;
                  if (empty($enrolledSubjects)): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-3">No subjects enrolled.</td>
                    </tr>
                  <?php else:
                    foreach ($enrolledSubjects as $sub): 
                      $totalUnits += (int)($sub['units'] ?? 0);
                      $hasSchedule = !empty($sub['schedules']);
                  ?>
                    <tr>
                      <td class="fw-bold text-nowrap"><?= htmlspecialchars((string)($sub['subject_code'] ?? '')) ?></td>
                      <td><?= htmlspecialchars((string)($sub['subject_name'] ?? '')) ?>
                        <?php if (($app['student_type'] ?? 'Regular') === 'Irregular'): ?>
                           <?php if (($sub['subject_type'] ?? '') === 'Irregular'): ?>
                             <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Irregular</span>
                           <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-center fw-medium"><?= (int)($sub['units'] ?? 0) ?></td>
                      
                      <?php if (!$hasSchedule): ?>
                        <td colspan="3" class="text-muted fst-italic text-center">Schedule Not Assigned</td>
                      <?php else: ?>
                        <td>
                          <?php 
                          $instructors = array_unique(array_filter(array_column($sub['schedules'], 'instructor')));
                          echo !empty($instructors) ? htmlspecialchars(implode(', ', $instructors)) : '<span class="text-muted">TBA</span>';
                          ?>
                        </td>
                        <td>
                          <?php 
                          $rooms = array_unique(array_filter(array_column($sub['schedules'], 'room')));
                          echo !empty($rooms) ? htmlspecialchars(implode(', ', $rooms)) : '<span class="text-muted">TBA</span>';
                          ?>
                        </td>
                        <td class="text-nowrap small">
                          <?php 
                          foreach ($sub['schedules'] as $sc) {
                              if (empty($sc['day']) || $sc['day'] === 'TBA') {
                                  echo 'TBA<br>';
                              } else {
                                  if (!empty($sc['start_time']) && !empty($sc['end_time'])) {
                                      $st = date('h:i A', strtotime($sc['start_time']));
                                      $et = date('h:i A', strtotime($sc['end_time']));
                                      echo "{$sc['day']} {$st}-{$et}<br>";
                                  } else {
                                      echo "{$sc['day']} TBA<br>";
                                  }
                              }
                          }
                          ?>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="2" class="text-end fw-bold">Total Units:</td>
                    <td class="text-center fw-bold fs-6"><?= $totalUnits ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Weekly Schedule Timetable -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-week text-primary me-2"></i> Weekly Schedule</h5>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive">
              <table class="timetable">
                <thead>
                  <tr>
                    <th class="time-col">Time</th>
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($days as $day): ?>
                      <th><?= $day ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $startHour = 7;
                  $endHour = 20;
                  
                  // Group schedules by day and block
                  $grid = [];
                  foreach ($days as $d) { $grid[$d] = []; }

                  foreach ($enrolledSubjects as $sub) {
                      foreach ($sub['schedules'] as $sc) {
                          if (!empty($sc['day']) && $sc['day'] !== 'TBA' && in_array($sc['day'], $days) && !empty($sc['start_time']) && !empty($sc['end_time'])) {
                              $grid[$sc['day']][] = [
                                  'code' => $sub['subject_code'],
                                  'name' => $sub['subject_name'],
                                  'room' => $sc['room'] ?: 'TBA',
                                  'start' => strtotime($sc['start_time']),
                                  'end' => strtotime($sc['end_time'])
                              ];
                          }
                      }
                  }
                  
                  // Sort blocks by start time
                  foreach ($days as $d) {
                      usort($grid[$d], function($a, $b) { return $a['start'] <=> $b['start']; });
                  }

                  // Render blocks per hour (simplified visualization)
                  for ($h = $startHour; $h <= $endHour; $h++) {
                      $hourLabel = date('h:i A', strtotime(sprintf('%02d:00:00', $h)));
                      echo '<tr>';
                      echo "<td class='time-col fw-medium'>{$hourLabel}</td>";
                      
                      foreach ($days as $d) {
                          echo '<td style="vertical-align: top;">';
                          foreach ($grid[$d] as $block) {
                              $blockStartHour = (int)date('H', $block['start']);
                              // If block falls in this hour
                              if ($blockStartHour === $h) {
                                  $st = date('h:i A', $block['start']);
                                  $et = date('h:i A', $block['end']);
                                  echo "<div class='schedule-block shadow-sm'>";
                                  echo "<div class='fw-bold text-dark'>" . htmlspecialchars((string)$block['code']) . "</div>";
                                  echo "<div class='text-truncate' title='" . htmlspecialchars((string)$block['name']) . "'>" . htmlspecialchars((string)$block['name']) . "</div>";
                                  echo "<div class='text-muted' style='font-size:0.7rem;'><i class='bi bi-geo-alt-fill me-1'></i>" . htmlspecialchars((string)$block['room']) . "</div>";
                                  echo "<div class='text-primary mt-1' style='font-size:0.7rem;'><i class='bi bi-clock-fill me-1'></i>{$st} - {$et}</div>";
                                  echo "</div>";
                              }
                          }
                          echo '</td>';
                      }
                      echo '</tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
    
  </div>
</main>

<div class="no-print">
<?php require_once __DIR__ . '/../components/footer.php'; ?>
</div>
