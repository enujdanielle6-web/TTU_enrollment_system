<?php

declare(strict_types=1);

// Force browser to ALWAYS fetch the latest version
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Applicant';

$errors = $_SESSION['enroll_errors'] ?? [];
$old = $_SESSION['enroll_old'] ?? [];
unset($_SESSION['enroll_errors'], $_SESSION['enroll_old']);

// Fetch active academic strands (Union of SHS and College)
$activeStrands = [];
try {
    $strStmt = $pdo->query('
        SELECT code, name, \'Senior High School\' as category FROM shs_strands WHERE is_active = 1
        UNION ALL
        SELECT code, name, \'College\' as category FROM college_programs WHERE is_active = 1
        ORDER BY category ASC, code ASC
    ');
    $activeStrands = $strStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Strands fetch failed: ' . $e->getMessage());
}

// Fetch active user details
$user = null;
try {
    $statement = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    
    // Check if application already exists
    $appStatement = $pdo->prepare('SELECT * FROM applications WHERE user_id = :user_id LIMIT 1');
    $appStatement->execute(['user_id' => $userId]);
    $existingApp = $appStatement->fetch();
    
    if ($existingApp) {
        if (!in_array($existingApp['status'], ['pending', 'correction_required'], true)) {
            header('Location: dashboard.php');
            exit;
        }
        // Pre-fill the form with existing database values if no session override exists
        if (empty($old)) {
            $old = $existingApp;
            // Fetch previously selected subjects if any based on academic level
            if ($existingApp['academic_level'] === 'Senior High School') {
                $esStmt = $pdo->prepare('
                    SELECT s.id, s.subject_code as code, s.subject_name as name, s.units 
                    FROM shs_enrollments es
                    INNER JOIN subjects s ON s.id = es.subject_id
                    WHERE es.application_id = :app_id
                ');
            } else {
                $esStmt = $pdo->prepare('
                    SELECT s.id, s.subject_code as code, s.subject_name as name, s.units 
                    FROM college_enrollments es
                    INNER JOIN subjects s ON s.id = es.subject_id
                    WHERE es.application_id = :app_id
                ');
            }
            $esStmt->execute(['app_id' => $existingApp['id']]);
            $old['selected_subjects'] = $esStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $exception) {
    error_log('Database query failed in enroll.php: ' . $exception->getMessage());
    $errors[] = 'Unable to fetch user details at this moment.';
}

// Check global enrollment status
$globalEnrollStatus = getSystemSetting($pdo, 'enrollment_status', 'open');

if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}

if ($globalEnrollStatus === 'closed' && empty($existingApp)) {
    $_SESSION['error_msg'] = 'Enrollment is currently closed. Please check back later.';
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Enrollment Form - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="status-page py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        
        <div class="mb-4">
          <h1 class="h3 mb-2 fw-bold text-dark">Enrollment Application</h1>
          <?php if (!empty($existingApp) && $existingApp['status'] === 'correction_required'): ?>
            <p class="text-danger mb-0 fw-medium"><i class="bi bi-exclamation-triangle-fill me-1"></i> Update required: Please correct your information below and resubmit.</p>
          <?php else: ?>
            <p class="text-muted mb-0">Please fill out all the fields below to submit your school enrollment application.</p>
          <?php endif; ?>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger mb-4 shadow-sm rounded-12">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form action="enroll_process.php" method="post" id="enrollmentForm" novalidate>
          <?= getCsrfInput() ?>
          
          <!-- 1. Personal Information Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-person"></i>
              <h2>Personal Information</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label text-muted small fw-semibold" for="firstName">First Name</label>
                  <input class="form-control bg-light" type="text" id="firstName" value="<?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>
                <div class="col-md-3">
                  <label class="form-label text-muted small fw-semibold" for="middleName">Middle Name</label>
                  <input class="form-control" type="text" id="middleName" name="middle_name" value="<?= htmlspecialchars($old['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional" pattern="^[a-zA-Z\s\-]+$">
                  <div class="invalid-feedback">Please enter a valid middle name.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="lastName">Last Name</label>
                  <input class="form-control bg-light" type="text" id="lastName" value="<?= htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>
                <div class="col-md-2">
                  <label class="form-label text-muted small fw-semibold" for="suffix">Suffix</label>
                  <input class="form-control" type="text" id="suffix" name="suffix" value="<?= htmlspecialchars($old['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Jr." pattern="^[a-zA-Z\.\s]+$">
                  <div class="invalid-feedback">Invalid suffix.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="gender">Sex (Gender)</label>
                  <select class="form-select" id="gender" name="gender" required>
                    <option value="" disabled selected>Select sex</option>
                    <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                  </select>
                  <div class="invalid-feedback">Please select a sex.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="birthDate">Date of Birth</label>
                  <input class="form-control" type="date" id="birthDate" name="birth_date" value="<?= htmlspecialchars($old['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" max="<?= date('Y-m-d') ?>" required>
                  <div class="invalid-feedback">A valid birth date is required (no future dates).</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="placeOfBirth">Place of Birth</label>
                  <input class="form-control" type="text" id="placeOfBirth" name="place_of_birth" value="<?= htmlspecialchars($old['place_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="City/Province">
                  <div class="invalid-feedback">Place of birth is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="civilStatus">Civil Status</label>
                  <select class="form-select" id="civilStatus" name="civil_status" required>
                    <option value="" disabled selected>Select status</option>
                    <option value="Single" <?= ($old['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?= ($old['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Separated" <?= ($old['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                    <option value="Widowed" <?= ($old['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                  </select>
                  <div class="invalid-feedback">Civil status is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="nationality">Nationality</label>
                  <input class="form-control" type="text" id="nationality" name="nationality" value="<?= htmlspecialchars($old['nationality'] ?? 'Filipino', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Nationality is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="religion">Religion</label>
                  <input class="form-control" type="text" id="religion" name="religion" value="<?= htmlspecialchars($old['religion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Contact Information Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-telephone"></i>
              <h2>Contact Information</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="contactNumber">Mobile Number</label>
                  <input class="form-control" type="tel" id="contactNumber" name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. 09123456789" pattern="^09\d{9}$" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 11-digit mobile number starting with 09.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="email">Email Address</label>
                  <input class="form-control bg-light" type="email" id="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="telephoneNumber">Telephone Number</label>
                  <input class="form-control" type="tel" id="telephoneNumber" name="telephone_number" value="<?= htmlspecialchars($old['telephone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional" pattern="^\d{7,10}$" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Please enter a valid telephone number.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Current Address Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-geo-alt"></i>
              <h2>Current Address</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="addressHouseNumber">House Number</label>
                  <input class="form-control" type="text" id="addressHouseNumber" name="address_house_number" value="<?= htmlspecialchars($old['address_house_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">House number is required.</div>
                </div>
                <div class="col-md-8">
                  <label class="form-label text-muted small fw-semibold" for="addressStreet">Street</label>
                  <input class="form-control" type="text" id="addressStreet" name="address_street" value="<?= htmlspecialchars($old['address_street'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Street address is required.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold" for="addressBarangay">Barangay</label>
                  <input class="form-control" type="text" id="addressBarangay" name="address_barangay" value="<?= htmlspecialchars($old['address_barangay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Barangay is required.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold" for="addressCity">Municipality / City</label>
                  <input class="form-control" type="text" id="addressCity" name="address_city" value="<?= htmlspecialchars($old['address_city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">City/Municipality is required.</div>
                </div>
                <div class="col-md-8">
                  <label class="form-label text-muted small fw-semibold" for="addressProvince">Province</label>
                  <input class="form-control" type="text" id="addressProvince" name="address_province" value="<?= htmlspecialchars($old['address_province'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Province is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="addressZip">ZIP Code</label>
                  <input class="form-control" type="text" id="addressZip" name="address_zip" value="<?= htmlspecialchars($old['address_zip'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required pattern="^\d{4}$" maxlength="4" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 4-digit ZIP Code.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Parent / Guardian Information Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-people"></i>
              <h2>Parent / Guardian Information</h2>
            </div>
            <div class="island-body mt-2">
              <h6 class="fw-semibold text-primary mb-3">Father's Information</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="fatherName">Full Name</label>
                  <input class="form-control" type="text" id="fatherName" name="father_name" value="<?= htmlspecialchars($old['father_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="fatherOccupation">Occupation</label>
                  <input class="form-control" type="text" id="fatherOccupation" name="father_occupation" value="<?= htmlspecialchars($old['father_occupation'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="fatherContact">Contact Number</label>
                  <input class="form-control" type="tel" id="fatherContact" name="father_contact" value="<?= htmlspecialchars($old['father_contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional" pattern="^09\d{9}$" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 11-digit mobile number.</div>
                </div>
              </div>

              <h6 class="fw-semibold text-primary mb-3">Mother's Information</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="motherName">Full Name</label>
                  <input class="form-control" type="text" id="motherName" name="mother_name" value="<?= htmlspecialchars($old['mother_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="motherOccupation">Occupation</label>
                  <input class="form-control" type="text" id="motherOccupation" name="mother_occupation" value="<?= htmlspecialchars($old['mother_occupation'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="motherContact">Contact Number</label>
                  <input class="form-control" type="tel" id="motherContact" name="mother_contact" value="<?= htmlspecialchars($old['mother_contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional" pattern="^09\d{9}$" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 11-digit mobile number.</div>
                </div>
              </div>

              <h6 class="fw-semibold text-primary mb-3">Guardian Information</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="guardianName">Full Name</label>
                  <input class="form-control" type="text" id="guardianName" name="guardian_name" value="<?= htmlspecialchars($old['guardian_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="guardianRelationship">Relationship to Applicant</label>
                  <input class="form-control" type="text" id="guardianRelationship" name="guardian_relationship" value="<?= htmlspecialchars($old['guardian_relationship'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="guardianContact">Contact Number</label>
                  <input class="form-control" type="tel" id="guardianContact" name="guardian_contact" value="<?= htmlspecialchars($old['guardian_contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" pattern="^09\d{9}$" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 11-digit mobile number.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Previous School Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-building"></i>
              <h2>Previous School Information</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold" for="lastSchoolAttended">Previous School Name</label>
                  <input class="form-control" type="text" id="lastSchoolAttended" name="last_school_attended" value="<?= htmlspecialchars($old['last_school_attended'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Previous school name is required.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold" for="lastSchoolAddress">School Address</label>
                  <input class="form-control" type="text" id="lastSchoolAddress" name="last_school_address" value="<?= htmlspecialchars($old['last_school_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="City, Province">
                  <div class="invalid-feedback">School address is required.</div>
                </div>
                
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="previousSchoolLevel">Previous School Level</label>
                  <select class="form-select" id="previousSchoolLevel" name="previous_school_level" required>
                    <option value="" disabled selected>Select level</option>
                    <option value="Junior High School" <?= ($old['previous_school_level'] ?? '') === 'Junior High School' ? 'selected' : ''; ?>>Junior High School</option>
                    <option value="Senior High School" <?= ($old['previous_school_level'] ?? '') === 'Senior High School' ? 'selected' : ''; ?>>Senior High School</option>
                    <option value="College" <?= ($old['previous_school_level'] ?? '') === 'College' ? 'selected' : ''; ?>>College</option>
                  </select>
                  <div class="invalid-feedback">Previous school level is required.</div>
                </div>
                <div class="col-md-4" id="strandCourseContainer" style="display: none;">
                  <label class="form-label text-muted small fw-semibold" id="strandCourseLabel" for="previousStrandCourse">Strand / Course</label>
                  <input class="form-control" type="text" id="previousStrandCourse" name="previous_strand_course" value="<?= htmlspecialchars($old['previous_strand_course'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="invalid-feedback">This field is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="previousSchoolStatus">Status</label>
                  <select class="form-select" id="previousSchoolStatus" name="previous_school_status" required>
                    <option value="" disabled selected>Select status</option>
                    <option value="Graduated" <?= ($old['previous_school_status'] ?? '') === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                    <option value="Currently Enrolled" <?= ($old['previous_school_status'] ?? '') === 'Currently Enrolled' ? 'selected' : ''; ?>>Currently Enrolled</option>
                    <option value="Transferee" <?= ($old['previous_school_status'] ?? '') === 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                    <option value="Undergraduate" <?= ($old['previous_school_status'] ?? '') === 'Undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                  </select>
                  <div class="invalid-feedback">Status is required.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold">Academic Year Attended / Graduated</label>
                  <div class="d-flex align-items-center gap-2">
                    <select class="form-select" id="academicYearFrom" name="academic_year_from" required>
                      <option value="" disabled selected>From</option>
                    </select>
                    <span class="text-muted fw-medium">-</span>
                    <select class="form-select" id="academicYearTo" name="academic_year_to" required>
                      <option value="" disabled selected>To</option>
                    </select>
                  </div>
                  <div class="invalid-feedback d-none" id="academicYearError">"To" year cannot be earlier than "From" year.</div>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-semibold" for="lrn">Learner Reference Number (LRN)</label>
                  <input class="form-control" type="text" id="lrn" name="lrn" value="<?= htmlspecialchars($old['lrn'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="12-digit LRN (Optional)" pattern="^\d{12}$" maxlength="12">
                  <div class="invalid-feedback">LRN must be exactly 12 digits.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 6. Enrollment Details Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-mortarboard"></i>
              <h2>Enrollment Details</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label text-muted small fw-semibold" for="schoolYear">School Year</label>
                  <input type="text" class="form-control bg-light" id="schoolYear" name="school_year" value="2026-2027" readonly>
                </div>
                <div class="col-md-3" id="semesterContainer" style="display: none;">
                  <label class="form-label text-muted small fw-semibold" for="semester">Semester</label>
                  <select class="form-select" id="semester" name="semester">
                    <option value="" disabled selected>Select semester</option>
                    <option value="First" <?= ($old['semester'] ?? '') === 'First' ? 'selected' : ''; ?>>First</option>
                    <option value="Second" <?= ($old['semester'] ?? '') === 'Second' ? 'selected' : ''; ?>>Second</option>
                    <option value="Summer" <?= ($old['semester'] ?? '') === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                  </select>
                  <div class="invalid-feedback">Semester is required for College.</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label text-muted small fw-semibold" for="academicLevel">Academic Level</label>
                  <select class="form-select" id="academicLevel" name="academic_level" required>
                    <option value="" disabled selected>Select level</option>
                    <option value="Senior High School" <?= ($old['academic_level'] ?? '') === 'Senior High School' ? 'selected' : ''; ?>>Senior High School</option>
                    <option value="College" <?= ($old['academic_level'] ?? '') === 'College' ? 'selected' : ''; ?>>College</option>
                  </select>
                  <div class="invalid-feedback">Academic level is required.</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label text-muted small fw-semibold" for="gradeLevel">Grade / Year Level</label>
                  <select class="form-select" id="gradeLevel" name="grade_level" required>
                    <option value="" disabled selected>Select grade/year</option>
                  </select>
                  <div class="invalid-feedback">Grade / Year level is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="strand">Strand / Program</label>
                  <select class="form-select" id="strand" name="strand" required>
                    <option value="" disabled selected>Select a program</option>
                  </select>
                  <div class="invalid-feedback">Please select an academic program.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="studentType">Student Type</label>
                  <select class="form-select" id="studentType" name="student_type" required>
                    <option value="" disabled selected>Select student type</option>
                    <option value="Regular" <?= ($old['student_type'] ?? '') === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                    <option value="Irregular" <?= ($old['student_type'] ?? '') === 'Irregular' ? 'selected' : ''; ?>>Irregular</option>
                  </select>
                  <div class="invalid-feedback">Student type is required.</div>
                </div>
                <div class="col-md-4" id="nstpContainer" style="display: none;">
                  <label class="form-label text-muted small fw-semibold" for="nstp">NSTP Choice</label>
                  <select class="form-select" id="nstp" name="nstp">
                    <option value="" disabled selected>Select NSTP</option>
                    <option value="CWTS" <?= ($old['nstp'] ?? '') === 'CWTS' ? 'selected' : ''; ?>>CWTS</option>
                    <option value="ROTC" <?= ($old['nstp'] ?? '') === 'ROTC' ? 'selected' : ''; ?>>ROTC</option>
                    <option value="LTS" <?= ($old['nstp'] ?? '') === 'LTS' ? 'selected' : ''; ?>>LTS</option>
                  </select>
                  <div class="invalid-feedback">NSTP choice is required for First Year College.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Curriculum Subjects Island -->
          <div class="island mb-4" id="curriculumContainer" style="display: none;">
            <div class="island-header">
              <i class="bi bi-journal-text"></i>
              <h2>Curriculum Subjects</h2>
            </div>
            <div class="island-body p-0 mt-2">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light text-muted small text-uppercase">
                    <tr>
                      <th class="ps-4">Subject Code</th>
                      <th>Subject Name</th>
                      <th>Type</th>
                      <th class="text-end pe-4">Units</th>
                    </tr>
                  </thead>
                  <tbody id="curriculumBody">
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">Loading subjects...</td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr>
                      <td colspan="3" class="text-end fw-bold text-dark">Total Units:</td>
                      <td class="text-end pe-4 fw-bold text-dark fs-5" id="curriculumTotal">0</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <!-- Section Selection Island -->
          <div class="island mb-4" id="sectionContainer" style="display: none;">
            <div class="island-header">
              <i class="bi bi-diagram-3"></i>
              <h2>Available Sections</h2>
            </div>
            <div class="island-body mt-2">
              <div id="sectionGrid" class="row g-3">
                <!-- Dynamically populated sections will appear here -->
                <div class="col-12 text-center text-muted py-3">Please select your Program, Year Level, Semester, and Student Type first.</div>
              </div>
              <input type="hidden" name="section_id" id="section_id" required>
              <div class="invalid-feedback d-block mt-2" id="sectionFeedback" style="display: none !important;">Please select a section to proceed.</div>
            </div>
          </div>

          <!-- Irregular Info Island -->
          <div class="island mb-4 border border-warning border-2 rounded-4 text-center overflow-hidden" id="irregularContainer" style="display: none; padding: 0;">
            <div class="bg-warning bg-opacity-10 p-4 border-bottom border-warning border-opacity-25">
              <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2.5rem; line-height: 1;"></i>
              <h2 class="text-warning-emphasis mt-2 mb-0 fw-bold fs-4">Irregular Student Enrollment</h2>
            </div>
            <div class="p-4 bg-white">
              <p class="text-dark mb-2" style="font-size: 1.05rem;">You are classified as an <strong>Irregular Student</strong>. Irregular students cannot complete section and subject scheduling online.</p>
              <p class="text-muted small mb-0">Please select your intended subjects from the curriculum below. You still need to proceed to the Registrar's Office to finalize your enrollment and schedule.</p>
            </div>
          </div>

          <!-- Irregular Curriculum Selection Island -->
          <div class="island mb-4" id="irregularCurriculumContainer" style="display: none;">
            <div class="island-header">
              <i class="bi bi-card-checklist"></i>
              <h2>Curriculum & Subject Selection</h2>
            </div>
            <div class="island-body mt-3">
              <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                  <h6 class="fw-bold text-dark mb-3">Official Curriculum <span class="badge bg-primary ms-2" id="irregProgramBadge"></span></h6>
                  <div class="accordion" id="curriculumAccordion">
                    <div class="text-center py-5 text-muted">
                      <p class="mb-0 small">Please select a Program first...</p>
                    </div>
                  </div>
                </div>
                <div class="col-lg-5">
                  <div class="card border-primary border-opacity-25 shadow-sm sticky-top" style="top: 100px; z-index: 10;">
                    <div class="card-header bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25 py-3">
                      <h6 class="mb-0 fw-bold text-primary-emphasis"><i class="bi bi-bag-check me-2"></i>Selected Subjects</h6>
                    </div>
                    <div class="card-body p-0 bg-light">
                      <ul class="list-group list-group-flush mb-0 p-2" id="selectedSubjectsList" style="max-height: 400px; overflow-y: auto;">
                        <li class="list-group-item bg-transparent px-0 py-3 text-center text-muted border-0 small" id="emptyCartMsg">
                          <i class="bi bi-bag-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                          No subjects selected yet.<br>Click "Add" on a subject from the curriculum.
                        </li>
                      </ul>
                    </div>
                    <div class="card-footer bg-white border-top border-light py-3 d-flex justify-content-between align-items-center">
                      <span class="fw-bold text-dark small">Total Units:</span>
                      <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm" id="cartTotalUnitsBadge">0 Units</span>
                    </div>
                  </div>
                  <div class="invalid-feedback d-block mt-2 text-center" id="irregularFeedback" style="display: none !important;">
                    <i class="bi bi-exclamation-circle me-1"></i> Please select at least one subject.
                  </div>
                </div>
              </div>
            </div>
            <div id="hiddenSubjectsContainer"></div>
          </div>

          <!-- 7. Emergency Contact Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-heart-pulse"></i>
              <h2>Emergency Contact</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="emergencyContactPerson">Contact Person</label>
                  <input class="form-control" type="text" id="emergencyContactPerson" name="emergency_contact_person" value="<?= htmlspecialchars($old['emergency_contact_person'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Contact person is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="emergencyContactRelationship">Relationship</label>
                  <input class="form-control" type="text" id="emergencyContactRelationship" name="emergency_contact_relationship" value="<?= htmlspecialchars($old['emergency_contact_relationship'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                  <div class="invalid-feedback">Relationship is required.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="emergencyContactNumber">Contact Number</label>
                  <input class="form-control" type="tel" id="emergencyContactNumber" name="emergency_contact_number" value="<?= htmlspecialchars($old['emergency_contact_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required pattern="^09\d{9}$" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                  <div class="invalid-feedback">Must be a valid 11-digit mobile number.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 8. Additional Information Island -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-info-circle"></i>
              <h2>Additional Information</h2>
            </div>
            <div class="island-body mt-2">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="specialNeeds">Special Needs (Optional)</label>
                  <textarea class="form-control" id="specialNeeds" name="special_needs" rows="2"><?= htmlspecialchars($old['special_needs'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="medicalConditions">Medical Conditions (Optional)</label>
                  <textarea class="form-control" id="medicalConditions" name="medical_conditions" rows="2"><?= htmlspecialchars($old['medical_conditions'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small fw-semibold" for="allergies">Allergies (Optional)</label>
                  <textarea class="form-control" id="allergies" name="allergies" rows="2"><?= htmlspecialchars($old['allergies'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex flex-wrap gap-3 justify-content-end mt-4 mb-5">
            <a class="btn btn-outline-secondary px-4 py-2" style="border-radius: 12px; font-weight: 600;" href="dashboard.php">
              Cancel
            </a>
            <button class="btn btn-primary px-4 py-2" style="border-radius: 12px; font-weight: 600;" type="submit">
              <i class="bi bi-send-check"></i> <?= (!empty($existingApp) && $existingApp['status'] === 'correction_required') ? 'Resubmit Application' : 'Submit Application' ?>
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</main>

<!-- Schedule Preview Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 bg-primary bg-opacity-10 rounded-top-4">
        <h5 class="modal-title text-primary-emphasis fw-bold" id="scheduleModalLabel">
          <i class="bi bi-calendar3 me-2"></i>Schedule Preview - <span id="modalSectionCode"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-info bg-info bg-opacity-10 border-0 text-info-emphasis d-flex align-items-center mb-4">
          <i class="bi bi-info-circle-fill me-3 fs-4"></i>
          <div>This schedule is read-only. Applicants cannot modify the predefined section schedule.</div>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead class="table-light text-muted small text-uppercase text-center">
              <tr>
                <th>Day</th>
                <th>Time</th>
                <th>Subject</th>
                <th>Room</th>
                <th>Instructor</th>
              </tr>
            </thead>
            <tbody id="scheduleBody">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  $(function () {


    $('#enrollmentForm').on('submit', function (event) {
      var form = this;
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      $(form).addClass('was-validated');
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const academicLevelSelect = document.getElementById('academicLevel');
    const gradeLevelSelect = document.getElementById('gradeLevel');
    const strandSelect = document.getElementById('strand');

    const programs = <?= json_encode($activeStrands) ?>;
    const oldGradeLevel = <?= json_encode($old['grade_level'] ?? '') ?>;
    const oldStrand = <?= json_encode($old['strand'] ?? '') ?>;

    const gradeLevels = {
      'Senior High School': ['Grade 11', 'Grade 12'],
      'College': ['1st Year', '2nd Year', '3rd Year', '4th Year']
    };

    const nstpContainer = document.getElementById('nstpContainer');
    const nstpSelect = document.getElementById('nstp');

    const semesterContainer = document.getElementById('semesterContainer');
    const semesterSelect = document.getElementById('semester');

    function checkVisibility() {
      const level = academicLevelSelect.value;
      const grade = gradeLevelSelect.value;
      const studentTypeContainer = studentTypeSelect.closest('.col-md-4');
      
      // NSTP logic
      if (level === 'College' && grade === '1st Year') {
        nstpContainer.style.display = 'block';
        nstpSelect.setAttribute('required', 'required');
      } else {
        nstpContainer.style.display = 'none';
        nstpSelect.removeAttribute('required');
        nstpSelect.value = ''; // clear value
      }

      // Semester logic
      if (level === 'College') {
        semesterContainer.style.display = 'block';
        semesterSelect.setAttribute('required', 'required');
        if (studentTypeContainer) studentTypeContainer.style.display = 'block';
        studentTypeSelect.setAttribute('required', 'required');
      } else {
        semesterContainer.style.display = 'none';
        semesterSelect.removeAttribute('required');
        semesterSelect.value = '';
        if (studentTypeContainer) studentTypeContainer.style.display = 'none';
        studentTypeSelect.removeAttribute('required');
        studentTypeSelect.value = 'Regular';
      }
    }

    function updateOptions() {
      const level = academicLevelSelect.value;
      
      // Update Grade/Year Level Options
      gradeLevelSelect.innerHTML = '<option value="" disabled selected>Select grade/year</option>';
      if (level && gradeLevels[level]) {
        gradeLevels[level].forEach(grade => {
          const option = document.createElement('option');
          option.value = grade;
          option.textContent = grade;
          if (oldGradeLevel === grade) {
            option.selected = true;
          }
          gradeLevelSelect.appendChild(option);
        });
      }

      // Update Programs Options
      strandSelect.innerHTML = '<option value="" disabled selected>Select a program</option>';
      if (level) {
        const filteredPrograms = programs.filter(p => p.category === level);
        filteredPrograms.forEach(prog => {
           const option = document.createElement('option');
           option.value = prog.code;
           option.textContent = prog.code.toUpperCase();
           if (oldStrand === prog.code) {
             option.selected = true;
           }
           strandSelect.appendChild(option);
        });
      }
      
      checkVisibility();
      updateCurriculum();
    }

    const studentTypeSelect = document.getElementById('studentType');
    const sectionContainer = document.getElementById('sectionContainer');
    const irregularContainer = document.getElementById('irregularContainer');
    const sectionGrid = document.getElementById('sectionGrid');
    const sectionIdInput = document.getElementById('section_id');
    const sectionFeedback = document.getElementById('sectionFeedback');

    function updateCurriculum() {
      const prog = strandSelect.value;
      const yr = gradeLevelSelect.value;
      const sem = semesterSelect.value;
      const level = academicLevelSelect.value;

      const container = document.getElementById('curriculumContainer');
      const tbody = document.getElementById('curriculumBody');
      const ttotal = document.getElementById('curriculumTotal');

      // Only fetch if Program and Year are selected. Semester is only required for College.
      if (!prog || !yr || (level === 'College' && !sem)) {
        container.style.display = 'none';
        return;
      }

      container.style.display = 'block';
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading curriculum...</td></tr>';
      ttotal.textContent = '0';

      fetch(`api_get_curriculum.php?program_code=${encodeURIComponent(prog)}&year_level=${encodeURIComponent(yr)}&semester=${encodeURIComponent(sem)}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.subjects.length > 0) {
            let html = '';
            data.subjects.forEach(sub => {
              html += `<tr>
                <td class="ps-4 fw-bold text-dark">${sub.subject_code}</td>
                <td>${sub.subject_name}</td>
                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">${sub.subject_type || 'Subject'}</span></td>
                <td class="text-end pe-4">${sub.units}</td>
              </tr>`;
            });
            tbody.innerHTML = html;
            ttotal.textContent = data.total_units;
          } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No curriculum subjects found for the selected configuration.</td></tr>';
          }
        })
        .catch(err => {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Failed to load curriculum. Please try again.</td></tr>';
        });
    }

    let selectedIrregularSubjects = <?= json_encode($old['selected_subjects'] ?? []) ?>;
    
    // Ensure numeric types for units
    selectedIrregularSubjects = selectedIrregularSubjects.map(s => ({
        id: parseInt(s.id),
        code: s.code,
        name: s.name,
        units: parseInt(s.units)
    }));

    function updateSectionVisibility() {
      const level = academicLevelSelect.value;
      const type = studentTypeSelect.value;
      const irregularCurriculumContainer = document.getElementById('irregularCurriculumContainer');
      
      if (level === 'College') {
        sectionContainer.style.display = 'block';
        if (type === 'Irregular') {
           irregularContainer.style.display = 'block';
           irregularCurriculumContainer.style.display = 'block';
           sectionIdInput.removeAttribute('required');
           fetchIrregularCurriculum();
        } else {
           irregularContainer.style.display = 'none';
           irregularCurriculumContainer.style.display = 'none';
           sectionIdInput.setAttribute('required', 'required');
        }
        fetchSections();
      } else {
        sectionContainer.style.display = 'none';
        irregularContainer.style.display = 'none';
        irregularCurriculumContainer.style.display = 'none';
        sectionIdInput.removeAttribute('required');
        sectionIdInput.value = '';
      }
    }
    
    // When an irregular student picks a section, auto-load its subjects into the cart
    sectionIdInput.addEventListener('change', function() {
        if (studentTypeSelect.value === 'Irregular' && this.value) {
            if (selectedIrregularSubjects.length > 0) {
                if (!confirm('This will clear your current cart and load the subjects for this section. Proceed?')) {
                    this.value = '';
                    return;
                }
            }
            
            selectedIrregularSubjects = [];
            renderCart();
            
            fetch(`api_get_section_subjects.php?section_id=${this.value}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.subjects) {
                        data.subjects.forEach(sub => {
                            selectedIrregularSubjects.push(sub);
                        });
                        renderCart();
                        alert(`Successfully imported ${data.subjects.length} subjects from the selected section.`);
                    }
                })
                .catch(err => console.error(err));
        }
    });

    function renderCart() {
      const list = document.getElementById('selectedSubjectsList');
      const badge = document.getElementById('cartTotalUnitsBadge');
      const hiddenContainer = document.getElementById('hiddenSubjectsContainer');
      const feedback = document.getElementById('irregularFeedback');
      
      if (selectedIrregularSubjects.length === 0) {
        list.innerHTML = `
          <li class="list-group-item bg-transparent px-0 py-3 text-center text-muted border-0 small" id="emptyCartMsg">
            <i class="bi bi-bag-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
            No subjects selected yet.<br>Click "Add" on a subject from the curriculum.
          </li>`;
        badge.textContent = '0 Units';
        hiddenContainer.innerHTML = '';
      } else {
        feedback.style.setProperty('display', 'none', 'important');
        
        let html = '';
        let hiddenHtml = '';
        let totalUnits = 0;
        
        selectedIrregularSubjects.forEach((sub, index) => {
          totalUnits += sub.units;
          html += `
            <li class="list-group-item bg-white px-3 py-2 rounded-3 border shadow-sm mb-2 d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold text-dark small mb-0">${sub.code} <span class="badge bg-secondary ms-1">${sub.section_code}</span></div>
                <div class="text-muted" style="font-size: 0.7rem;">${sub.name}</div>
                <div class="text-primary mt-1" style="font-size: 0.65rem;">${sub.schedule_text || ''}</div>
              </div>
              <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border">${sub.units} Units</span>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="removeSubject(${index})" title="Remove">
                  <i class="bi bi-x fs-4"></i>
                </button>
              </div>
            </li>`;
          hiddenHtml += `<input type="hidden" name="selected_subjects[${sub.id}]" value="${sub.section_id}">`;
        });
        
        list.innerHTML = html;
        badge.textContent = `${totalUnits} Units`;
        hiddenContainer.innerHTML = hiddenHtml;
      }
      
      // Update Add buttons state
      document.querySelectorAll('.btn-add-subject').forEach(btn => {
        const id = parseInt(btn.getAttribute('data-id'));
        const codeAttr = btn.getAttribute('data-code') || '';
        const nameAttr = btn.getAttribute('data-name') || '';
        const btnCode = codeAttr.toLowerCase().replace(/[^a-z0-9]/g, '');
        const btnName = nameAttr.toLowerCase().trim();
        
        const isSelected = selectedIrregularSubjects.find(s => s.id === id);
        const isEquivalent = selectedIrregularSubjects.find(s => {
            const sCode = (s.code || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            const sName = (s.name || '').toLowerCase().trim();
            return sCode === btnCode || sName === btnName;
        });

        if (isSelected) {
           btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
           btn.classList.add('btn-success', 'text-white');
           btn.innerHTML = '<i class="bi bi-check2"></i> Added';
           btn.disabled = true;
        } else if (isEquivalent) {
           btn.classList.remove('btn-outline-primary', 'btn-success');
           btn.classList.add('btn-outline-secondary');
           btn.innerHTML = '<i class="bi bi-dash-circle"></i> Added Equivalent';
           btn.disabled = true;
        } else {
           btn.classList.add('btn-outline-primary');
           btn.classList.remove('btn-success', 'btn-outline-secondary', 'text-white');
           btn.innerHTML = '<i class="bi bi-plus-lg"></i> Add';
           btn.disabled = false;
        }
      });
    }

    // --- Self-Service Scheduling Logic ---
    let pendingSubject = null;
    
    window.openScheduleModal = function(id, code, name, units) {
      if (selectedIrregularSubjects.find(s => s.id === id)) return;
      
      // Prevent adding duplicate subjects (e.g. if the DB has IT101 and IT-101 both named "Introduction to Computing")
      const safeCode = (code || '').toLowerCase().replace(/[^a-z0-9]/g, '');
      const safeName = (name || '').toLowerCase().trim();
      
      const duplicate = selectedIrregularSubjects.find(s => {
          const sCode = (s.code || '').toLowerCase().replace(/[^a-z0-9]/g, '');
          const sName = (s.name || '').toLowerCase().trim();
          return sCode === safeCode || sName === safeName;
      });
      
      if (duplicate) {
          alert(`You have already selected an equivalent subject: ${duplicate.code} - ${duplicate.name}`);
          return;
      }
      
      pendingSubject = { id, code, name, units: parseInt(units) };
      
      const modalHtml = `
        <div class="modal fade" id="scheduleSelectionModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
              <div class="modal-header bg-primary bg-opacity-10 border-bottom-0">
                <h5 class="modal-title fw-bold text-primary-emphasis"><i class="bi bi-clock-history me-2"></i>Select Schedule for ${code}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body bg-light p-4" id="scheduleModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading schedules...</p></div>
              </div>
              <div class="modal-footer border-top-0 bg-white">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="btnConfirmSchedule" disabled><i class="bi bi-check2-circle me-2"></i>Confirm Schedule</button>
              </div>
            </div>
          </div>
        </div>
      `;
      
      let existingModal = document.getElementById('scheduleSelectionModal');
      if (existingModal) existingModal.remove();
      
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleSelectionModal'));
      scheduleModal.show();
      
      const acadLevel = document.getElementById('academicLevel').value;
      fetch(`api_get_subject_schedules.php?subject_id=${id}&level=${acadLevel}`)
        .then(res => res.json())
        .then(data => {
          const body = document.getElementById('scheduleModalBody');
          const btnConfirm = document.getElementById('btnConfirmSchedule');
          
          if (!data.success || !data.sections || data.sections.length === 0) {
            body.innerHTML = '<div class="alert alert-warning border-0"><i class="bi bi-exclamation-triangle me-2"></i>No active schedules available for this subject.</div>';
            return;
          }
          
          let html = '<div class="list-group shadow-sm">';
          data.sections.forEach(sec => {
            const isFull = sec.is_full;
            const badgeClass = isFull ? 'bg-danger' : 'bg-success';
            const badgeText = isFull ? 'FULL' : `${sec.remaining_slots} slots left`;
            const disabledAttr = isFull ? 'disabled' : '';
            
            let schedTextHtml = '';
            sec.schedules.forEach(s => {
              schedTextHtml += `<div class="small text-muted"><i class="bi bi-calendar-event me-1"></i>${s.day} &middot; <i class="bi bi-clock me-1"></i>${s.start_time} - ${s.end_time} &middot; <i class="bi bi-geo-alt me-1"></i>${s.room}</div>`;
            });
            
            html += `
              <label class="list-group-item list-group-item-action d-flex gap-3 py-3 ${isFull ? 'bg-light opacity-75' : ''}" style="cursor: ${isFull ? 'not-allowed' : 'pointer'}">
                <input class="form-check-input flex-shrink-0 fs-4 schedule-radio" type="radio" name="scheduleRadio" value="${sec.section_id}" ${disabledAttr} data-section-code="${sec.section_code}" data-schedules='${JSON.stringify(sec.schedules)}'>
                <div class="d-flex gap-2 w-100 justify-content-between">
                  <div>
                    <h6 class="mb-1 fw-bold text-dark">${sec.section_code}</h6>
                    ${schedTextHtml}
                  </div>
                  <small class="text-nowrap"><span class="badge ${badgeClass} rounded-pill px-2 py-1">${badgeText}</span></small>
                </div>
              </label>
            `;
          });
          html += '</div>';
          body.innerHTML = html;
          
          document.querySelectorAll('.schedule-radio').forEach(radio => {
            radio.addEventListener('change', () => { btnConfirm.disabled = false; });
          });
          
          btnConfirm.onclick = () => {
            const selected = document.querySelector('.schedule-radio:checked');
            if (!selected) return;
            
            const section_id = parseInt(selected.value);
            const section_code = selected.getAttribute('data-section-code');
            const schedules = JSON.parse(selected.getAttribute('data-schedules'));
            
            // Conflict Detection
            const conflict = checkConflict(schedules);
            if (conflict) {
              alert(`Schedule Conflict! This overlaps with your existing subject: ${conflict}`);
              return;
            }
            
            pendingSubject.section_id = section_id;
            pendingSubject.section_code = section_code;
            pendingSubject.schedules = schedules;
            
            // Generate a readable schedule text for the cart
            let schedText = '';
            schedules.forEach(s => {
               schedText += `${s.day} ${s.start_time}-${s.end_time} (${s.room})<br>`;
            });
            pendingSubject.schedule_text = schedText;
            
            selectedIrregularSubjects.push(pendingSubject);
            renderCart();
            scheduleModal.hide();
          };
        })
        .catch(err => {
          document.getElementById('scheduleModalBody').innerHTML = '<div class="alert alert-danger">Error loading schedules.</div>';
        });
    };
    
    function parseTime(timeStr) {
       // timeStr like "08:00:00" or "13:30:00"
       const parts = timeStr.split(':');
       return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }
    
    function checkConflict(newSchedules) {
       for (const existingSub of selectedIrregularSubjects) {
          if (!existingSub.schedules) continue;
          for (const existSched of existingSub.schedules) {
             for (const newSched of newSchedules) {
                // If they are on the same day
                if (existSched.day === newSched.day) {
                   const eStart = parseTime(existSched.start_time_raw);
                   const eEnd = parseTime(existSched.end_time_raw);
                   const nStart = parseTime(newSched.start_time_raw);
                   const nEnd = parseTime(newSched.end_time_raw);
                   
                   // Overlap condition: start A < end B AND end A > start B
                   if (eStart < nEnd && eEnd > nStart) {
                      return existingSub.code; // Return the conflicting subject code
                   }
                }
             }
          }
       }
       return null;
    }


    window.removeSubject = function(index) {
      selectedIrregularSubjects.splice(index, 1);
      renderCart();
    };

    function fetchIrregularCurriculum() {
      const prog = strandSelect.value;
      const progName = strandSelect.options[strandSelect.selectedIndex]?.text || 'Program';
      
      const accordion = document.getElementById('curriculumAccordion');
      document.getElementById('irregProgramBadge').textContent = progName;
      
      if (!prog) {
        accordion.innerHTML = '<div class="text-center py-5 text-muted"><p class="mb-0 small">Please select a Program first...</p></div>';
        return;
      }
      
      accordion.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><p class="mb-0 small">Loading full curriculum...</p></div>';
      
      fetch(`api_get_full_curriculum.php?program_code=${encodeURIComponent(prog)}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.curriculum && Object.keys(data.curriculum).length > 0) {
            let html = '';
            let accIndex = 0;
            
            for (const [year, semesters] of Object.entries(data.curriculum)) {
              for (const [sem, subjects] of Object.entries(semesters)) {
                accIndex++;
                const headerId = `heading-${accIndex}`;
                const collapseId = `collapse-${accIndex}`;
                
                let subHtml = '';
                subjects.forEach(sub => {
                  subHtml += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                      <div>
                        <div class="fw-bold text-dark small">${sub.subject_code}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${sub.subject_name}</div>
                      </div>
                      <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-muted border">${sub.units} Units</span>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-medium btn-add-subject" data-id="${sub.id}" data-code="${sub.subject_code}" data-name="${sub.subject_name.replace(/"/g, '&quot;')}" onclick="openScheduleModal(${sub.id}, '${sub.subject_code}', '${sub.subject_name.replace(/'/g, "\\'")}', ${sub.units})">
                          <i class="bi bi-plus-lg"></i> Add
                        </button>
                      </div>
                    </div>
                  `;
                });
                
                html += `
                  <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                    <h2 class="accordion-header" id="${headerId}">
                      <button class="accordion-button ${accIndex === 1 ? '' : 'collapsed'} bg-white fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                        <i class="bi bi-folder2-open text-primary me-2"></i> ${year} - ${sem} Semester
                      </button>
                    </h2>
                    <div id="${collapseId}" class="accordion-collapse collapse ${accIndex === 1 ? 'show' : ''}" data-bs-parent="#curriculumAccordion">
                      <div class="accordion-body bg-light p-3">
                        <div class="bg-white rounded-3 p-2 shadow-sm">
                          ${subHtml}
                        </div>
                      </div>
                    </div>
                  </div>
                `;
              }
            }
            accordion.innerHTML = html;
            renderCart();
          } else {
            accordion.innerHTML = '<div class="text-center py-5 text-muted"><p class="mb-0 small">No curriculum found for this program.</p></div>';
          }
        })
        .catch(err => {
          accordion.innerHTML = '<div class="text-center py-5 text-danger"><p class="mb-0 small">Failed to load curriculum.</p></div>';
        });
    }

    function fetchSections() {
      const prog = strandSelect.value;
      const yr = gradeLevelSelect.value;
      const sem = semesterSelect.value;
      const level = academicLevelSelect.value;
      
      if (!prog || !yr || (level === 'College' && !sem)) {
        sectionGrid.innerHTML = '<div class="col-12 text-center text-muted py-3">Please select your Program, Year Level, and Semester (if applicable) first.</div>';
        return;
      }
      
      sectionGrid.innerHTML = '<div class="col-12 text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading sections...</div>';
      
      fetch(`api_get_sections.php?program_code=${encodeURIComponent(prog)}&year_level=${encodeURIComponent(yr)}&semester=${encodeURIComponent(sem)}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.sections && data.sections.length > 0) {
            let html = '';
            data.sections.forEach(sec => {
              const isFull = sec.is_full;
              const cardClass = isFull ? 'border-danger bg-danger bg-opacity-10' : 'border-primary border-opacity-25 shadow-sm';
              const textClass = isFull ? 'text-danger' : 'text-primary';
              const cursor = isFull ? 'not-allowed' : 'pointer';
              const disabled = isFull ? 'disabled' : '';
              
              html += `
                <div class="col-md-6 col-lg-4">
                  <label class="card h-100 ${cardClass} section-card" style="cursor: ${cursor}; transition: all 0.2s;">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title fw-bold ${textClass} mb-0">${sec.section_code}</h5>
                        <input class="form-check-input section-radio" type="radio" name="section_choice" value="${sec.id}" ${disabled}>
                      </div>
                      <div class="small text-muted mb-1"><i class="bi bi-clock me-1"></i> ${sec.schedule_type}</div>
                      <div class="small fw-semibold ${isFull ? 'text-danger' : 'text-success'}">
                        <i class="bi bi-person-bounding-box me-1"></i> ${sec.remaining_slots} slots left
                      </div>
                      <div class="mt-3 text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 view-schedule-btn" data-section="${sec.id}" data-code="${sec.section_code}">
                          <i class="bi bi-eye"></i> View Schedule
                        </button>
                      </div>
                    </div>
                  </label>
                </div>
              `;
            });
            sectionGrid.innerHTML = html;
            
            document.querySelectorAll('.section-radio').forEach(radio => {
              radio.addEventListener('change', function() {
                sectionIdInput.value = this.value;
                sectionFeedback.style.setProperty('display', 'none', 'important');
              });
            });
            
            const savedDataStr = sessionStorage.getItem('enrollmentFormData');
            let savedSectionId = null;
            if (savedDataStr) {
               try { savedSectionId = JSON.parse(savedDataStr).section_id; } catch(e) {}
            }
            if (savedSectionId) {
               const radio = document.querySelector(`.section-radio[value="${savedSectionId}"]`);
               if (radio && !radio.disabled) {
                   radio.checked = true;
                   sectionIdInput.value = savedSectionId;
               }
            } else if (<?= json_encode($old['section_id'] ?? '') ?>) {
               const oldId = <?= json_encode($old['section_id'] ?? '') ?>;
               const radio = document.querySelector(`.section-radio[value="${oldId}"]`);
               if (radio && !radio.disabled) {
                   radio.checked = true;
                   sectionIdInput.value = oldId;
               }
            }

            document.querySelectorAll('.view-schedule-btn').forEach(btn => {
              btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const secId = this.getAttribute('data-section');
                const secCode = this.getAttribute('data-code');
                document.getElementById('modalSectionCode').textContent = secCode;
                const tbody = document.getElementById('scheduleBody');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading schedule...</td></tr>';
                const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
                modal.show();
                
                fetch('api_get_schedule.php?section_id=' + secId)
                  .then(r => r.json())
                  .then(sData => {
                    if (sData.success && sData.schedules && sData.schedules.length > 0) {
                      let trs = '';
                      sData.schedules.forEach(item => {
                        trs += `<tr>
                          <td class="fw-medium">${item.day}</td>
                          <td>${item.start_time_f} - ${item.end_time_f}</td>
                          <td>${item.subject_code} - ${item.subject_name}</td>
                          <td>${item.room}</td>
                          <td class="text-muted">${item.instructor}</td>
                        </tr>`;
                      });
                      tbody.innerHTML = trs;
                    } else {
                      tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No schedule defined for this section yet.</td></tr>';
                    }
                  });
              });
            });
            
          } else {
            sectionGrid.innerHTML = '<div class="col-12 text-center text-muted py-3">No available sections for the selected criteria. Please contact the registrar.</div>';
          }
        })
        .catch(err => {
          sectionGrid.innerHTML = '<div class="col-12 text-center text-danger py-3">Failed to load sections.</div>';
        });
    }

    academicLevelSelect.addEventListener('change', () => { updateOptions(); updateSectionVisibility(); });
    gradeLevelSelect.addEventListener('change', () => { checkVisibility(); updateCurriculum(); updateSectionVisibility(); });
    strandSelect.addEventListener('change', () => { updateCurriculum(); updateSectionVisibility(); });
    semesterSelect.addEventListener('change', () => { updateCurriculum(); updateSectionVisibility(); });
    studentTypeSelect.addEventListener('change', updateSectionVisibility);

    // Initial update if academic level is pre-selected
    if (academicLevelSelect.value) {
      updateOptions();
      updateSectionVisibility();
    }
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Previous School Logic
    const prevSchoolLevel = document.getElementById('previousSchoolLevel');
    const strandCourseContainer = document.getElementById('strandCourseContainer');
    const prevStrandCourse = document.getElementById('previousStrandCourse');
    const strandCourseLabel = document.getElementById('strandCourseLabel');
    
    const yearFrom = document.getElementById('academicYearFrom');
    const yearTo = document.getElementById('academicYearTo');
    const yearError = document.getElementById('academicYearError');

    const currentYear = new Date().getFullYear();
    const oldYearFrom = <?= json_encode($old['academic_year_from'] ?? '') ?>;
    const oldYearTo = <?= json_encode($old['academic_year_to'] ?? '') ?>;

    // Populate Year Dropdowns
    for (let y = currentYear; y >= 1990; y--) {
      [yearFrom, yearTo].forEach(select => {
        const option = document.createElement('option');
        option.value = y;
        option.textContent = y;
        select.appendChild(option);
      });
    }

    if (oldYearFrom) yearFrom.value = oldYearFrom;
    if (oldYearTo) yearTo.value = oldYearTo;

    function checkPrevSchoolLevel() {
      const level = prevSchoolLevel.value;
      if (level === 'Junior High School') {
        strandCourseContainer.style.display = 'none';
        prevStrandCourse.removeAttribute('required');
        prevStrandCourse.value = '';
      } else {
        strandCourseContainer.style.display = 'block';
        prevStrandCourse.setAttribute('required', 'required');
        if (level === 'Senior High School') {
          strandCourseLabel.textContent = 'Strand';
        } else if (level === 'College') {
          strandCourseLabel.textContent = 'Course';
        }
      }
    }

    function validateYears() {
      if (yearFrom.value && yearTo.value && parseInt(yearTo.value) < parseInt(yearFrom.value)) {
        yearTo.setCustomValidity('Invalid');
        yearError.style.display = 'block';
        yearError.classList.remove('d-none');
      } else {
        yearTo.setCustomValidity('');
        yearError.style.display = 'none';
        yearError.classList.add('d-none');
      }
    }

    prevSchoolLevel.addEventListener('change', checkPrevSchoolLevel);
    yearFrom.addEventListener('change', validateYears);
    yearTo.addEventListener('change', validateYears);

    if (prevSchoolLevel.value) {
      checkPrevSchoolLevel();
    }
    validateYears();

    // Custom form validation for sections
    const form = document.getElementById('enrollmentForm');
    form.addEventListener('submit', function (event) {
      let isValid = true;
      if (sectionContainer.style.display === 'block' && !sectionIdInput.value) {
        sectionFeedback.style.setProperty('display', 'block', 'important');
        sectionContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        isValid = false;
      }
      
      const irregularCurriculumContainer = document.getElementById('irregularCurriculumContainer');
      const irregularFeedback = document.getElementById('irregularFeedback');
      if (irregularCurriculumContainer.style.display === 'block' && selectedIrregularSubjects.length === 0) {
        irregularFeedback.style.setProperty('display', 'block', 'important');
        irregularCurriculumContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        isValid = false;
      }
      
      if (!isValid) {
        event.preventDefault();
        event.stopPropagation();
      } else if (form.checkValidity()) {
        // If the form is completely valid and about to submit, clear the saved data
        sessionStorage.removeItem('enrollmentFormData');
      }
    }, false);

    // Auto-save form data to prevent data loss on accidental refresh
    const enrollForm = document.getElementById('enrollmentForm');
    const storageKey = 'enrollmentFormData';
    
    if (enrollForm) {
      const savedData = sessionStorage.getItem(storageKey);
      if (savedData) {
        try {
          const data = JSON.parse(savedData);
          for (const key in data) {
            const elements = enrollForm.elements[key];
            if (!elements) continue;
            
            // Handle node list (like radio buttons)
            const inputList = elements instanceof NodeList || (elements.length && !elements.tagName) ? Array.from(elements) : [elements];
            
            inputList.forEach(input => {
              if (input && !input.readOnly && !input.disabled && input.type !== 'file' && input.type !== 'hidden') {
                if (input.type === 'radio' || input.type === 'checkbox') {
                  if (input.value === data[key]) input.checked = true;
                } else {
                  input.value = data[key];
                  input.dispatchEvent(new Event('change', { bubbles: true }));
                }
              }
            });
          }
        } catch(e) {
          console.error('Failed to restore form data', e);
        }
      }

      const saveFormData = function(e) {
        if (e.target.type === 'file' || e.target.type === 'password' || e.target.type === 'hidden') return;
        
        const formData = new FormData(enrollForm);
        const dataObj = {};
        formData.forEach((value, key) => {
          dataObj[key] = value;
        });
        sessionStorage.setItem(storageKey, JSON.stringify(dataObj));
      };

      enrollForm.addEventListener('input', saveFormData);
      enrollForm.addEventListener('change', saveFormData);
    }

  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
