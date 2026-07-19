<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$pageTitle = 'Document Requirements - Triple T University';
$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Applicant';
$application = null;
$documents = [];

try {
    $statement = $pdo->prepare(
        'SELECT id, reference_number, document_submission_method, status 
         FROM applications 
         WHERE user_id = :user_id 
         ORDER BY created_at DESC 
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $application = $statement->fetch() ?: null;

    if ($application) {
        $docStmt = $pdo->prepare('SELECT id, document_name, status, file_path, feedback, created_at FROM application_documents WHERE application_id = :app_id');
        $docStmt->execute(['app_id' => $application['id']]);
        $rows = $docStmt->fetchAll();
        foreach ($rows as $row) {
            $documents[$row['document_name']] = $row;
        }
    }
} catch (PDOException $exception) {
    error_log('Documents fetch failed: ' . $exception->getMessage());
}

$requiredDocs = [
    'PSA Birth Certificate' => 'Clear scanned copy of PSA Birth Certificate',
    'Form 138' => 'Report Card / Form 138 from previous school year',
    'Good Moral Certificate' => 'Certificate of Good Moral Character',
    '2x2 Picture' => 'Recent 2x2 ID picture with white background'
];

$method = $application['document_submission_method'] ?? 'online';
$appStatus = $application['status'] ?? 'pending';
$isLocked = in_array($appStatus, ['approved', 'rejected', 'enrolled'], true);

$successMsg = $_SESSION['doc_success'] ?? '';
$errorMsg = $_SESSION['doc_error'] ?? '';
unset($_SESSION['doc_success'], $_SESSION['doc_error']);

require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-9 col-lg-10">
        
        <div class="island island-hero mb-4 d-flex align-items-center justify-content-between">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Document Requirements</h1>
            <p class="text-muted mb-0">Manage and submit your required academic documents.</p>
          </div>
        </div>

        <?php if ($successMsg): ?>
          <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
          <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($application === null): ?>
          <div class="island text-center py-5">
            <i class="bi bi-file-earmark-x text-muted mb-3" style="font-size: 3rem;"></i>
            <h2 class="h4 mb-2 text-dark fw-bold">No Application Found</h2>
            <p class="text-muted mb-4">You must submit an enrollment application before uploading documents.</p>
            <a class="btn btn-primary px-4 py-2 rounded-pill" href="enroll.php"><i class="bi bi-pencil-square me-2"></i> Start Enrollment</a>
          </div>
        <?php else: ?>

          <!-- Submission Workflow Configuration -->
          <div class="island mb-4">
            <div class="island-header">
              <i class="bi bi-gear-fill"></i>
              <h2>Submission Method</h2>
            </div>
            <div class="island-body mt-2">
              <form action="document_workflow.php" method="POST" class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <?= getCsrfInput() ?>
                <div>
                  <p class="mb-2 text-dark small fw-medium">How would you like to submit your documents?</p>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="submission_method" id="methodOnline" value="online" <?= $method === 'online' ? 'checked' : ''; ?> <?= $isLocked ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="methodOnline">Online Upload</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="submission_method" id="methodOnCampus" value="on_campus" <?= $method === 'on_campus' ? 'checked' : ''; ?> <?= $isLocked ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="methodOnCampus">On-Campus Submission</label>
                  </div>
                </div>
                <?php if (!$isLocked): ?>
                  <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">Save Preference</button>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <?php if ($method === 'on_campus'): ?>
            <div class="island text-center py-5">
              <div class="bg-primary-light text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-building fs-1"></i>
              </div>
              <h3 class="h5 fw-bold">On-Campus Verification Selected</h3>
              <p class="text-muted mb-0">Please bring the original copies of all required documents to the admissions office during your scheduled physical verification.</p>
            </div>
          <?php else: ?>
            
            <!-- Online Upload List -->
            <div class="row g-4">
              <?php foreach ($requiredDocs as $docName => $docDesc): ?>
                <?php 
                  $hasDoc = isset($documents[$docName]);
                  $docStatus = $hasDoc ? $documents[$docName]['status'] : null;
                  $docId = $hasDoc ? $documents[$docName]['id'] : null;
                ?>
                <div class="col-12">
                  <div class="island border <?= $hasDoc ? 'border-success' : 'border-light' ?>">
                    <div class="island-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                      
                      <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                          <h3 class="h6 fw-bold mb-0 text-dark"><?= htmlspecialchars($docName, ENT_QUOTES, 'UTF-8'); ?></h3>
                          <?php if ($hasDoc): ?>
                            <?php 
                              $badgeClass = match($docStatus) {
                                'verified' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark'
                              };
                            ?>
                            <span class="badge <?= $badgeClass ?> small px-2 py-1"><?= ucfirst(htmlspecialchars($docStatus, ENT_QUOTES, 'UTF-8')); ?></span>
                          <?php else: ?>
                            <span class="badge bg-secondary small px-2 py-1">Missing</span>
                          <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($docDesc, ENT_QUOTES, 'UTF-8'); ?></p>
                         <?php if ($hasDoc && !empty($documents[$docName]['feedback'])): ?>
                            <div class="alert alert-warning border-0 p-2 mt-2 mb-0 small rounded-3 d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                              <i class="bi bi-chat-left-text-fill text-warning"></i>
                              <span><strong>Admin Feedback:</strong> <?= htmlspecialchars($documents[$docName]['feedback'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                         <?php elseif ($hasDoc && $docStatus === 'rejected'): ?>
                            <p class="text-danger small mt-1 mb-0"><i class="bi bi-exclamation-circle"></i> This document was rejected. Please upload a clearer copy.</p>
                         <?php endif; ?>
                      </div>

                      <div class="text-md-end" style="min-width: 250px;">
                        <?php if ($hasDoc && $docStatus !== 'rejected'): ?>
                          <a href="document_view.php?id=<?= $docId ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-eye"></i> View File
                          </a>
                        <?php elseif (!$isLocked): ?>
                          <form action="document_upload.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                            <?= getCsrfInput() ?>
                            <input type="hidden" name="document_name" value="<?= htmlspecialchars($docName, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 flex-shrink-0">Upload</button>
                          </form>
                          <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">PDF, JPG, PNG up to 5MB</small>
                        <?php else: ?>
                          <span class="text-muted small"><i class="bi bi-lock-fill"></i> Locked</span>
                        <?php endif; ?>
                      </div>

                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
