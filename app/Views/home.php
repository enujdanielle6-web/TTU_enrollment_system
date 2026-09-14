<?php
$pageTitle = 'Online Enrollment System';

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main>
  <section id="hero" class="hero-section text-center text-white">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4 hero-badge">
            <span class="badge-pulse"></span>
            <span class="fw-semibold text-uppercase" style="font-size: 0.78rem; letter-spacing: 1px;">Triple T University • Admissions Open 2026</span>
          </div>
          <h1 class="hero-title text-white">
            Empowering Minds.<br>
            <span class="hero-title-gradient">Transforming Futures.</span>
          </h1>
          <p class="hero-text mx-auto">
            Start your school application online with a clear, guided enrollment experience built for students and families.
          </p>
          <div class="hero-actions justify-content-center mt-4">
            <a class="btn btn-primary btn-lg rounded-pill px-4 py-3 fw-bold d-inline-flex align-items-center gap-2 hero-btn-primary shadow-lg" href="/sia/auth/register.php">
              <i class="bi bi-pencil-square"></i>
              <span>Enroll Now</span>
              <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 fw-bold d-inline-flex align-items-center gap-2 hero-btn-secondary" href="#courses">
              <i class="bi bi-compass"></i>
              <span>Explore Programs</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FLOATING KPI STATS STRIP -->
  <div class="container hero-stats-strip">
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-lg-3">
        <div class="hero-stat-card d-flex align-items-center gap-3">
          <div class="hero-stat-icon text-primary bg-primary bg-opacity-10">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">12+</h4>
            <span class="text-muted small">Academic Programs</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="hero-stat-card d-flex align-items-center gap-3">
          <div class="hero-stat-icon text-success bg-success bg-opacity-10">
            <i class="bi bi-lightning-charge-fill"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">100%</h4>
            <span class="text-muted small">Online Processing</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="hero-stat-card d-flex align-items-center gap-3">
          <div class="hero-stat-icon text-warning bg-warning bg-opacity-10">
            <i class="bi bi-patch-check-fill"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">Level IV</h4>
            <span class="text-muted small">CHED & DepEd Accredited</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="hero-stat-card d-flex align-items-center gap-3">
          <div class="hero-stat-icon text-info bg-info bg-opacity-10">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">24/7</h4>
            <span class="text-muted small">Portal & LMS Access</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- LMS PORTALS SECTION -->
  <section id="lms-portals" class="py-5" style="background-color: #f8fafc;">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Digital Learning</span>
        <h2 class="fw-bold text-dark mb-2">Learning Management System</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">Access your courses, interactive modules, real-time grades, and digital assignments anytime.</p>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-lg-5 col-md-6">
          <div class="card h-100 lms-card p-4">
            <div class="card-body d-flex flex-column text-center">
              <div class="lms-icon-box text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%); box-shadow: 0 10px 24px rgba(13, 110, 253, 0.28);">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 align-self-center mb-2 fw-semibold small">Student Gateway</span>
              <h3 class="h4 fw-bold mb-2 text-dark">Student Portal</h3>
              <p class="text-muted mb-3 small">View enrolled subjects, submit assignments, take online exams, and track your academic standing.</p>
              <div class="d-flex flex-wrap justify-content-center gap-1 mb-4">
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-journal-text me-1 text-primary"></i> Course Modules</span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-award me-1 text-warning"></i> Grades & Progress</span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-chat-dots me-1 text-success"></i> Academic Notices</span>
              </div>
              <a href="/sia/auth/lms_student_login.php" class="btn btn-primary rounded-pill py-2 px-4 fw-bold mt-auto w-100 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                <span>Student Login</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
        <div class="col-lg-5 col-md-6">
          <div class="card h-100 lms-card p-4">
            <div class="card-body d-flex flex-column text-center">
              <div class="lms-icon-box text-white" style="background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%); box-shadow: 0 10px 24px rgba(2, 132, 199, 0.28);">
                <i class="bi bi-person-video3"></i>
              </div>
              <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1 align-self-center mb-2 fw-semibold small">Faculty Gateway</span>
              <h3 class="h4 fw-bold mb-2 text-dark">Faculty Portal</h3>
              <p class="text-muted mb-3 small">Manage class rosters, publish lessons, submit student grade sheets, and conduct virtual classes.</p>
              <div class="d-flex flex-wrap justify-content-center gap-1 mb-4">
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-people me-1 text-info"></i> Class Rosters</span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-cloud-arrow-up me-1 text-primary"></i> Module Uploads</span>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1" style="font-size: 0.72rem;"><i class="bi bi-clipboard2-data me-1 text-warning"></i> Grading Sheets</span>
              </div>
              <a href="/sia/auth/lms_faculty_login.php" class="btn btn-outline-primary rounded-pill py-2 px-4 fw-bold mt-auto w-100 d-flex align-items-center justify-content-center gap-2">
                <span>Faculty Login</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CLEAN MODERN FEATURES -->
  <section class="modern-features py-5">
      <div class="container py-4">
          <div class="text-center mb-5">
              <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Why Choose TTU</span>
              <h2 class="fw-bold text-dark">Everything You Need</h2>
              <p class="text-muted mx-auto" style="max-width: 540px;">A fully integrated, technology-driven academic environment built for student success.</p>
          </div>
          <div class="row g-4">
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-laptop"></i>
                      </div>
                      <h4 class="fw-bold text-dark mb-2">Smart LMS</h4>
                      <p class="text-muted mb-0">Access courses, track your progress, and interact with faculty through our seamless online portal.</p>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-rocket-takeoff"></i>
                      </div>
                      <h4 class="fw-bold text-dark mb-2">Fast Enrollment</h4>
                      <p class="text-muted mb-0">Skip the lines. Our digital enrollment process makes registering for classes quick, guided, and convenient.</p>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-globe-americas"></i>
                      </div>
                      <h4 class="fw-bold text-dark mb-2">Connected Community</h4>
                      <p class="text-muted mb-0">Join student organizations, attend academic seminars, and engage with a vibrant campus community.</p>
                  </div>
              </div>
          </div>
      </div>
  </section>

  <!-- CAMPUS LIFE SECTION -->
  <section id="about" class="py-5 bg-white">
    <div class="container py-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 fade-in-up">
          <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Campus Experience</span>
          <h2 class="fw-bold mb-3 text-dark">Discover Campus Life at Triple T</h2>
          <p class="text-muted fs-5 mb-4">
            Experience a vibrant community where academic excellence meets personal growth. Our modern facilities and expansive grounds provide the perfect environment for your university journey.
          </p>
          <div class="row g-3 text-muted">
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                <span class="fw-medium text-dark">Modern Digital Library</span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                <span class="fw-medium text-dark">Advanced Tech Laboratories</span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                <span class="fw-medium text-dark">Expansive Sports Complex</span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-primary fs-5"></i>
                <span class="fw-medium text-dark">Collaborative Student Hubs</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 fade-in-up" style="animation-delay: 0.2s;">
          <div class="position-relative">
            <img src="/sia/images/TTU_OUTSIDE.png" alt="TTU Campus Outside" class="img-fluid rounded-4 shadow-lg w-100" style="border: 1px solid rgba(226, 232, 240, 0.8);">
            <div class="position-absolute bottom-0 start-0 m-3 px-3 py-2 rounded-pill bg-dark bg-opacity-75 backdrop-blur text-white small d-inline-flex align-items-center gap-2 shadow" style="backdrop-filter: blur(8px);">
              <i class="bi bi-geo-alt-fill text-danger"></i>
              <span>Sahur City Campus • 123 Tung Tung Avenue</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="admission-process" class="admission-process-section">
    <div class="container">
      <div class="section-heading text-center">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Step-by-Step Guide</span>
        <h2 class="fw-bold text-dark">Admission Process</h2>
        <p class="text-muted mx-auto" style="max-width: 580px;">Follow these simple steps to complete your online enrollment and secure your place at Triple T University.</p>
      </div>

      <div class="admission-timeline">
        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-person-plus-fill"></i>
              </div>
              <span class="admission-step-badge">Step 01</span>
              <h3 class="fw-bold text-dark fs-5">Create Account</h3>
              <p>Register with your personal email to initiate your applicant account.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-box-arrow-in-right"></i>
              </div>
              <span class="admission-step-badge">Step 02</span>
              <h3 class="fw-bold text-dark fs-5">Login</h3>
              <p>Access your applicant dashboard using your registered secure credentials.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-ui-checks"></i>
              </div>
              <span class="admission-step-badge">Step 03</span>
              <h3 class="fw-bold text-dark fs-5">Fill Information</h3>
              <p>Provide accurate student, parent/guardian, and academic background details.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-send-check-fill"></i>
              </div>
              <span class="admission-step-badge">Step 04</span>
              <h3 class="fw-bold text-dark fs-5">Submit Form</h3>
              <p>Review all filled information carefully and submit for initial evaluation.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-heart-pulse-fill"></i>
              </div>
              <span class="admission-step-badge">Step 05</span>
              <h3 class="fw-bold text-dark fs-5">Health Form</h3>
              <p>Submit your health clearance details to ensure institutional clinic readiness.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-award-fill"></i>
              </div>
              <span class="admission-step-badge">Step 06</span>
              <h3 class="fw-bold text-dark fs-5">Scholarship</h3>
              <p>Apply for institutional grants or merit scholarships if eligible (optional).</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <span class="admission-step-badge">Step 07</span>
              <h3 class="fw-bold text-dark fs-5">Admissions Review</h3>
              <p>Admissions personnel evaluate your academic records and clearance submissions.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-building-check"></i>
              </div>
              <span class="admission-step-badge">Step 08</span>
              <h3 class="fw-bold text-dark fs-5">Campus Visit</h3>
              <p>Visit the university campus on your advised date for physical validation.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-file-earmark-check-fill"></i>
              </div>
              <span class="admission-step-badge">Step 09</span>
              <h3 class="fw-bold text-dark fs-5">Verify Documents</h3>
              <p>Present original credentials and valid certificates for Registrar validation.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-credit-card-2-front-fill"></i>
              </div>
              <span class="admission-step-badge">Step 10</span>
              <h3 class="fw-bold text-dark fs-5">Pay Assessment</h3>
              <p>Settle your official tuition and laboratory fees via the Cashier counter.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body">
              <div class="admission-icon">
                <i class="bi bi-patch-check-fill"></i>
              </div>
              <span class="admission-step-badge">Step 11</span>
              <h3 class="fw-bold text-dark fs-5">Official Enrollment</h3>
              <p>Receive your official TTU Student ID number and finalized Certificate of Registration.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="requirements" class="requirements-section py-5">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Document Checklist</span>
        <h2 class="fw-bold text-dark mb-2">Enrollment Requirements</h2>
        <p class="text-muted mx-auto" style="max-width: 580px;">Prepare the following authentic credentials and documentary requirements before proceeding with your registration.</p>
      </div>

      <ul class="nav nav-pills justify-content-center gap-2 mb-5" id="reqsTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active rounded-pill px-4 py-2" id="shs-req-tab" data-bs-toggle="tab" data-bs-target="#shs-req" type="button" role="tab" aria-controls="shs-req" aria-selected="true">
            <i class="bi bi-mortarboard me-1"></i> Senior High School
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4 py-2" id="college-req-tab" data-bs-toggle="tab" data-bs-target="#college-req" type="button" role="tab" aria-controls="college-req" aria-selected="false">
            <i class="bi bi-bank me-1"></i> College Degree
          </button>
        </li>
      </ul>

      <div class="tab-content" id="reqsTabContent">
        <!-- SHS Requirements Tab -->
        <div class="tab-pane fade show active" id="shs-req" role="tabpanel" aria-labelledby="shs-req-tab">
          <div class="row g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-person"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">PSA Birth Certificate</h3>
                  <p class="text-muted small mb-0">Submit a clear original or certified copy issued by the Philippine Statistics Authority.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-card-checklist"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Form 138 (Report Card)</h3>
                  <p class="text-muted small mb-0">Provide your official Grade 10 final report card signed by the School Principal.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-shield-check"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Good Moral Certificate</h3>
                  <p class="text-muted small mb-0">Include a certificate of good moral character from your junior high school.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-person-bounding-box"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">2x2 ID Pictures</h3>
                  <p class="text-muted small mb-0">Prepare recent photographs with a clean white background and formal attire.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-person-vcard"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Parent / Guardian Valid ID</h3>
                  <p class="text-muted small mb-0">Submit a photocopy of a government-issued ID of your parent or legal guardian.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-text"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">NCAE / ESC Certificate</h3>
                  <p class="text-muted small mb-0">Photocopy of NCAE results or ESC / Voucher Certificate if applicable.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- College Requirements Tab -->
        <div class="tab-pane fade" id="college-req" role="tabpanel" aria-labelledby="college-req-tab">
          <div class="row g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-person"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">PSA Birth Certificate</h3>
                  <p class="text-muted small mb-0">Submit a clear original or certified copy issued by the civil registrar.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-card-checklist"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Form 138 / Official TOR</h3>
                  <p class="text-muted small mb-0">Grade 12 Senior High School report card or official Transcript of Records for transferees.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-shield-check"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Good Moral Certificate</h3>
                  <p class="text-muted small mb-0">Official certification of good conduct issued by your previous school or university.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-person-bounding-box"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">2x2 ID Pictures</h3>
                  <p class="text-muted small mb-0">Recent colored photographs with white background and formal attire.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-text"></i></div>
                  <h3 class="fw-bold fs-5 text-dark">Honorable Dismissal</h3>
                  <p class="text-muted small mb-0">Required for transfer students along with course descriptions for crediting.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="requirements-note mt-5 d-flex align-items-center gap-3 p-3 rounded-4 shadow-sm" style="background: rgba(13, 110, 253, 0.06); border: 1px solid rgba(13, 110, 253, 0.2);">
        <i class="bi bi-info-circle-fill text-primary fs-4"></i>
        <span class="text-dark small fw-medium">All original copies must be presented during the scheduled on-site document validation with the Registrar.</span>
      </div>
    </div>
  </section>

  <section id="courses" class="programs-section py-5">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Academic Offerings</span>
        <h2 class="fw-bold text-dark mb-2">Programs & Strands Offered</h2>
        <p class="text-muted mx-auto" style="max-width: 580px;">Discover future-ready Senior High School tracks and accredited College degree programs tailored for leadership and career success.</p>
      </div>

      <?php
      $getLandingProgramIcon = function (string $code): string {
          $code = strtoupper(trim($code));
          $iconMap = [
              'STEM' => 'bi-calculator',
              'ABM' => 'bi-briefcase',
              'HUMSS' => 'bi-chat-square-quote',
              'TVL' => 'bi-tools',
              'TVL-ICT' => 'bi-laptop',
              'GAS' => 'bi-journal-bookmark',
              'ICT' => 'bi-cpu',
              'ARTS' => 'bi-palette',
              'SPORTS' => 'bi-trophy',
              'BSIT' => 'bi-pc-display',
              'BSCS' => 'bi-laptop',
              'BSIS' => 'bi-diagram-3',
              'BSHM' => 'bi-cup-hot',
              'BSA' => 'bi-calculator-fill',
              'BSBA' => 'bi-bar-chart-line',
              'BSED' => 'bi-book-half',
              'BEED' => 'bi-pencil-square',
              'BSN' => 'bi-heart-pulse',
              'BSC' => 'bi-shield-check',
              'BSCE' => 'bi-building',
              'BSEE' => 'bi-lightning-charge',
              'BSME' => 'bi-gear-wide-connected',
          ];
          if (isset($iconMap[$code])) {
              return $iconMap[$code];
          }
          foreach ($iconMap as $key => $icon) {
              if (str_contains($code, $key)) {
                  return $icon;
              }
          }
          return 'bi-mortarboard';
      };

      $getLandingProgramCareers = function (string $code, string $type = 'College'): string {
          $code = strtoupper(trim($code));
          $careerMap = [
              'STEM' => 'Engineer, Programmer, Architect',
              'ABM' => 'Accountant, Entrepreneur, Manager',
              'HUMSS' => 'Lawyer, Teacher, Psychologist',
              'TVL' => 'Technician, Chef, IT Support',
              'TVL-ICT' => 'Technician, Web Developer, IT Support',
              'GAS' => 'Educator, Administrator, Various',
              'BSIT' => 'Software Engineer, IT Analyst, System Admin',
              'BSCS' => 'Data Scientist, Systems Architect, AI Researcher',
              'BSIS' => 'Systems Analyst, ERP Consultant, IT Manager',
              'BSHM' => 'Hotel Manager, F&B Director, Event Coordinator',
              'BSA' => 'CPA, Financial Advisor, Auditor',
              'BSBA' => 'Corporate Manager, HR Director, Marketer',
              'BSED' => 'High School Teacher, Educator, Principal',
              'BEED' => 'Elementary Educator, Academic Specialist',
              'BSN' => 'Registered Nurse, Clinical Specialist',
          ];
          if (isset($careerMap[$code])) {
              return $careerMap[$code];
          }
          foreach ($careerMap as $key => $careers) {
              if (str_contains($code, $key)) {
                  return $careers;
              }
          }
          return $type === 'College' ? 'Industry Specialist, Professional Practitioner' : 'Higher Education, Career Readiness';
      };

      $formatLandingProgramTuition = function (array $item, string $type = 'College'): string {
          if (!empty($item['total_amount']) && floatval($item['total_amount']) > 0) {
              if (!empty($item['is_per_unit'])) {
                  $perUnit = floatval($item['tuition_fee'] ?? 0);
                  $totalEst = floatval($item['total_amount']);
                  return '₱' . number_format($perUnit, 0) . ' / unit (Est. ₱' . number_format($totalEst, 0) . ' / sem)';
              }
              return '₱' . number_format((float)$item['total_amount'], 0) . ' / sem';
          }
          return $type === 'College' ? '₱25,000 - ₱30,000 / sem' : '₱15,000 - ₱20,000 / sem';
      };
      ?>

      <ul class="nav nav-pills justify-content-center gap-2 mb-5" id="programsTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active rounded-pill px-4 py-2" id="shs-tab" data-bs-toggle="tab" data-bs-target="#shs" type="button" role="tab" aria-controls="shs" aria-selected="true">
            <i class="bi bi-mortarboard me-1"></i> Senior High School
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4 py-2" id="college-tab" data-bs-toggle="tab" data-bs-target="#college" type="button" role="tab" aria-controls="college" aria-selected="false">
            <i class="bi bi-bank me-1"></i> College Degrees
          </button>
        </li>
      </ul>

      <div class="tab-content" id="programsTabContent">
        <!-- Senior High School Tab -->
        <div class="tab-pane fade show active" id="shs" role="tabpanel" aria-labelledby="shs-tab">
          <div class="row g-4">
            <?php if (!empty($shsStrands)): ?>
              <?php foreach ($shsStrands as $strand): ?>
                <?php 
                  $strandCode = $strand['code'] ?? '';
                  $icon = !empty($strand['icon']) ? $strand['icon'] : $getLandingProgramIcon($strandCode);
                  $careers = !empty($strand['careers']) ? $strand['careers'] : $getLandingProgramCareers($strandCode, 'SHS');
                  $tuition = !empty($strand['custom_tuition']) ? $strand['custom_tuition'] : $formatLandingProgramTuition($strand, 'SHS');
                  $desc = !empty($strand['description']) ? $strand['description'] : ($strand['name'] ?? '');
                ?>
                <div class="col-lg-4 col-md-6">
                  <div class="card program-card h-100 fade-in-up p-4 d-flex flex-column">
                    <div class="card-body p-0 d-flex flex-column h-100">
                      <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="program-icon mb-0">
                          <i class="bi <?= esc($icon) ?>"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-semibold small">SHS Strand</span>
                      </div>
                      <h3 class="fw-bold fs-4 text-dark mb-2"><?= esc($strandCode) ?></h3>
                      <p class="text-muted small mb-4" style="line-height: 1.6; min-height: 48px;"><?= esc($desc) ?></p>
                      
                      <div class="tuition-badge-box mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="text-muted small"><i class="bi bi-cash-stack me-1 text-success"></i> Est. Tuition:</span>
                          <span class="text-dark fw-bold small"><?= esc($tuition) ?></span>
                        </div>
                      </div>

                      <div class="mb-4">
                        <span class="d-block text-muted small fw-semibold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Career Pathways:</span>
                        <div class="d-flex flex-wrap">
                          <?php foreach (array_map('trim', explode(',', $careers)) as $career): ?>
                            <?php if (!empty($career)): ?>
                              <span class="career-chip"><?= esc($career) ?></span>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <a href="/sia/applicant/enroll.php?level=shs&strand=<?= urlencode($strandCode) ?>" class="btn btn-primary rounded-pill w-100 fw-bold py-2 mt-auto d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                        <span>Apply for <?= esc($strandCode) ?></span>
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 text-center text-muted py-4">
                <p>No Senior High School strands are currently available.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- College Tab -->
        <div class="tab-pane fade" id="college" role="tabpanel" aria-labelledby="college-tab">
          <div class="row g-4">
            <?php if (!empty($collegePrograms)): ?>
              <?php foreach ($collegePrograms as $program): ?>
                <?php 
                  $programCode = $program['code'] ?? '';
                  $icon = !empty($program['icon']) ? $program['icon'] : $getLandingProgramIcon($programCode);
                  $careers = !empty($program['careers']) ? $program['careers'] : $getLandingProgramCareers($programCode, 'College');
                  $tuition = !empty($program['custom_tuition']) ? $program['custom_tuition'] : $formatLandingProgramTuition($program, 'College');
                  $desc = !empty($program['description']) ? $program['description'] : ($program['name'] ?? '');
                ?>
                <div class="col-lg-4 col-md-6">
                  <div class="card program-card h-100 fade-in-up p-4 d-flex flex-column">
                    <div class="card-body p-0 d-flex flex-column h-100">
                      <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="program-icon mb-0">
                          <i class="bi <?= esc($icon) ?>"></i>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1 fw-semibold small">Undergraduate Degree</span>
                      </div>
                      <h3 class="fw-bold fs-4 text-dark mb-2"><?= esc($programCode) ?></h3>
                      <p class="text-muted small mb-4" style="line-height: 1.6; min-height: 48px;"><?= esc($desc) ?></p>
                      
                      <div class="tuition-badge-box mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="text-muted small"><i class="bi bi-cash-stack me-1 text-success"></i> Est. Tuition:</span>
                          <span class="text-dark fw-bold small"><?= esc($tuition) ?></span>
                        </div>
                      </div>

                      <div class="mb-4">
                        <span class="d-block text-muted small fw-semibold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Career Pathways:</span>
                        <div class="d-flex flex-wrap">
                          <?php foreach (array_map('trim', explode(',', $careers)) as $career): ?>
                            <?php if (!empty($career)): ?>
                              <span class="career-chip"><?= esc($career) ?></span>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <a href="/sia/applicant/enroll.php?level=college&program=<?= urlencode($programCode) ?>" class="btn btn-primary rounded-pill w-100 fw-bold py-2 mt-auto d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                        <span>Apply for <?= esc($programCode) ?></span>
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12 text-center text-muted py-4">
                <p>No College degree programs are currently available.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SCHOLARSHIPS SECTION -->
  <section id="scholarships" class="scholarships-section py-5">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">
          <i class="bi bi-award-fill me-1"></i> Financial Grants & Aid
        </span>
        <h2 class="fw-bold text-dark mb-2">Available Scholarships</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">
          Triple T University is committed to accessible education. Explore institutional merit honors, varsity sports grants, and government funding opportunities.
        </p>
      </div>

      <div class="row g-4 justify-content-center">
        <?php if (!empty($activeScholarships)): ?>
          <?php foreach ($activeScholarships as $scholarship): ?>
            <?php
              $category = $scholarship['category'] ?? 'School-Based';
              $categoryClass = strtolower(str_replace([' ', '-'], ['', '-'], $category));
              $categoryBadgeClass = match($category) {
                  'Government' => 'bg-success bg-opacity-10 text-success border-success',
                  'Private' => 'bg-info bg-opacity-10 text-info border-info',
                  default => 'bg-primary bg-opacity-10 text-primary border-primary',
              };
              $slots = !empty($scholarship['slots']) ? (int)$scholarship['slots'] : null;
              $tuitionType = $scholarship['tuition_coverage_type'] ?? 'percentage';
              $tuitionVal = floatval($scholarship['tuition_coverage_value'] ?? 0);
              $tuitionText = $tuitionType === 'percentage' 
                  ? number_format($tuitionVal, 0) . '% Tuition Waiver' 
                  : '₱' . number_format($tuitionVal, 2) . ' / sem Grant';

              $stipend = floatval($scholarship['stipend_amount'] ?? 0);
              $book = floatval($scholarship['book_allowance'] ?? 0);
              $minGwa = !empty($scholarship['min_gwa']) ? $scholarship['min_gwa'] : null;
              $incomeReq = !empty($scholarship['income_requirement']) ? floatval($scholarship['income_requirement']) : null;
              $yearLevel = !empty($scholarship['year_level']) ? $scholarship['year_level'] : 'All Years';
              
              // Requirements list
              $rawReqs = $scholarship['requirements'] ?? '';
              $reqList = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawReqs)));
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card scholarship-card <?= esc($categoryClass) ?> h-100 fade-in-up p-4 d-flex flex-column">
                <div class="card-body p-0 d-flex flex-column h-100">
                  <!-- Header: Category & Slots -->
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge rounded-pill px-3 py-1 border <?= $categoryBadgeClass ?> fw-semibold" style="font-size: 0.75rem;">
                      <i class="bi bi-tag-fill me-1"></i><?= esc($category) ?>
                    </span>
                    <?php if ($slots !== null): ?>
                      <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                        <i class="bi bi-people-fill me-1 text-primary"></i><?= esc($slots) ?> Slots
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Code & Title -->
                  <div class="mb-2">
                    <span class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;"><?= esc($scholarship['code']) ?></span>
                    <h3 class="fw-bold fs-5 text-dark mt-1 mb-1"><?= esc($scholarship['name']) ?></h3>
                    <?php if (!empty($scholarship['provider'])): ?>
                      <div class="small text-muted mb-2">
                        <i class="bi bi-building me-1 text-secondary"></i><?= esc($scholarship['provider']) ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <p class="text-muted small mb-3" style="line-height: 1.6; min-height: 44px;">
                    <?= esc($scholarship['description'] ?? 'Comprehensive financial assistance for qualifying applicants.') ?>
                  </p>

                  <!-- Coverage & Benefit Pills -->
                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="benefit-pill coverage">
                      <i class="bi bi-patch-check-fill"></i> <?= esc($tuitionText) ?>
                    </span>
                    <?php if ($stipend > 0): ?>
                      <span class="benefit-pill stipend">
                        <i class="bi bi-cash-coin"></i> ₱<?= number_format($stipend, 0) ?> Stipend
                      </span>
                    <?php endif; ?>
                    <?php if ($book > 0): ?>
                      <span class="benefit-pill book">
                        <i class="bi bi-book-half"></i> ₱<?= number_format($book, 0) ?> Books
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Eligibility Details Box -->
                  <div class="eligibility-box mb-3">
                    <span class="d-block text-dark fw-bold small mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px; text-transform: uppercase;">
                      <i class="bi bi-person-check-fill text-primary me-1"></i> Key Eligibility
                    </span>
                    <div class="d-flex flex-wrap gap-2 small text-muted">
                      <?php if ($minGwa): ?>
                        <span><strong>GWA:</strong> &le; <?= esc($minGwa) ?></span>
                        <span class="text-black-50">&bull;</span>
                      <?php endif; ?>
                      <span><strong>Level:</strong> <?= esc($yearLevel) ?></span>
                      <?php if ($incomeReq): ?>
                        <span class="text-black-50">&bull;</span>
                        <span><strong>Max Income:</strong> &le; ₱<?= number_format($incomeReq, 0) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Requirements Preview -->
                  <?php if (!empty($reqList)): ?>
                    <div class="mb-4">
                      <span class="d-block text-muted small fw-semibold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Documentary Checklist:</span>
                      <?php foreach (array_slice($reqList, 0, 3) as $reqItem): ?>
                        <div class="scholarship-req-item"><?= esc(ltrim($reqItem, "1234567890.- \t")) ?></div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Action Button -->
                  <?php if (!empty($_SESSION['logged_in']) && ($_SESSION['user_role'] ?? '') === 'applicant'): ?>
                    <a href="/sia/applicant/scholarships.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2 mt-auto d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                      <span>Apply in Portal</span>
                      <i class="bi bi-arrow-right"></i>
                    </a>
                  <?php else: ?>
                    <a href="/sia/auth/login.php?redirect=/sia/applicant/scholarships.php" class="btn btn-primary rounded-pill w-100 fw-bold py-2 mt-auto d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                      <span>Apply Now</span>
                      <i class="bi bi-arrow-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
            <p>No scholarship programs are currently open for new applications.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="text-center mt-5">
        <p class="text-muted small mb-2">
          <i class="bi bi-info-circle text-primary me-1"></i>
          Scholarship applications open every academic term. Approved enrollees can apply directly through the applicant portal dashboard.
        </p>
      </div>
    </div>
  </section>

  <!-- FAQ SECTION -->
  <section id="faq" class="faq-section py-5">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Help & Support</span>
        <h2 class="fw-bold text-dark mb-2">Frequently Asked Questions</h2>
        <p class="text-muted mx-auto" style="max-width: 580px;">Common questions and clear answers about online enrollment and academic admissions.</p>
      </div>

      <div class="accordion faq-accordion shadow-sm rounded-4 overflow-hidden" id="faqAccordion">
        <div class="accordion-item border-0 border-bottom">
          <h3 class="accordion-header" id="faqHeadingOne">
            <button class="accordion-button fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
              How do I apply for enrollment?
            </button>
          </h3>
          <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Simply click the <strong>Enroll Now</strong> button or register for a free account. Complete the online enrollment form with your academic details, submit your health clearance, and upload required documents.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 border-bottom">
          <h3 class="accordion-header" id="faqHeadingTwo">
            <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
              Is the enrollment process fully online?
            </button>
          </h3>
          <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              The initial application, profile creation, and document submission can be completed 100% online. Physical verification of original records is conducted on campus according to your advised schedule.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 border-bottom">
          <h3 class="accordion-header" id="faqHeadingThree">
            <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
              What documents are required?
            </button>
          </h3>
          <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Essential requirements include your PSA Birth Certificate, Form 138 (Report Card) or Official Transcript of Records, Certificate of Good Moral Character, 2x2 ID pictures, and a valid Guardian ID.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 border-bottom">
          <h3 class="accordion-header" id="faqHeadingFour">
            <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
              Can I edit my application after submission?
            </button>
          </h3>
          <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Submitted applications are locked during Admissions review to maintain data integrity. If adjustments are needed, you may visit the Admissions Office or request assistance through the portal support.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0 border-bottom">
          <h3 class="accordion-header" id="faqHeadingFive">
            <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFive" aria-expanded="false" aria-controls="faqCollapseFive">
              How do I check my application status?
            </button>
          </h3>
          <div id="faqCollapseFive" class="accordion-collapse collapse" aria-labelledby="faqHeadingFive" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              Log in to your applicant dashboard to view real-time status badges (Pending, Approved, Medical Cleared, or Enrolled) and track updates instantly.
            </div>
          </div>
        </div>

        <div class="accordion-item border-0">
          <h3 class="accordion-header" id="faqHeadingSix">
            <button class="accordion-button collapsed fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseSix" aria-expanded="false" aria-controls="faqCollapseSix">
              When should I visit the school?
            </button>
          </h3>
          <div id="faqCollapseSix" class="accordion-collapse collapse" aria-labelledby="faqHeadingSix" data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted">
              You will receive an automated advisory notification in your portal dashboard once your application has passed preliminary review, instructing you when to present physical credentials.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT SECTION -->
  <section id="contact" class="contact-section py-5" style="background-color: #f8fafc;">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Get In Touch</span>
        <h2 class="fw-bold text-dark mb-2">Contact Admissions</h2>
        <p class="text-muted mx-auto" style="max-width: 580px;">Have questions about admission requirements or program availability? Our admissions officers are ready to assist you.</p>
      </div>

      <div class="row g-4 align-items-stretch">
        <div class="col-lg-5">
          <div class="contact-info-panel p-4 rounded-4 shadow-sm bg-white border h-100">
            <div class="d-flex align-items-start gap-3 mb-4">
              <div class="contact-icon rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex-shrink: 0;">
                <i class="bi bi-geo-alt-fill fs-5"></i>
              </div>
              <div>
                <h4 class="fw-bold text-dark fs-6 mb-1">Campus Address</h4>
                <p class="text-muted small mb-0">123 Tung Tung Avenue, Sahur City, Philippines</p>
              </div>
            </div>

            <div class="d-flex align-items-start gap-3 mb-4">
              <div class="contact-icon rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex-shrink: 0;">
                <i class="bi bi-telephone-fill fs-5"></i>
              </div>
              <div>
                <h4 class="fw-bold text-dark fs-6 mb-1">Telephone Hotlines</h4>
                <p class="text-muted small mb-0">(02) 8123-4567 • 0912-3456-789</p>
              </div>
            </div>

            <div class="d-flex align-items-start gap-3 mb-4">
              <div class="contact-icon rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex-shrink: 0;">
                <i class="bi bi-envelope-fill fs-5"></i>
              </div>
              <div>
                <h4 class="fw-bold text-dark fs-6 mb-1">Email Inquiries</h4>
                <p class="text-muted small mb-0">admissions@ttu.edu.ph</p>
              </div>
            </div>

            <div class="d-flex align-items-start gap-3 mb-4">
              <div class="contact-icon rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; flex-shrink: 0;">
                <i class="bi bi-clock-fill fs-5"></i>
              </div>
              <div>
                <h4 class="fw-bold text-dark fs-6 mb-1">Admissions Office Hours</h4>
                <p class="text-muted small mb-0">Monday to Friday: 8:00 AM - 5:00 PM</p>
              </div>
            </div>

            <div class="map-container mt-4 rounded-4 overflow-hidden shadow-sm" style="height: 220px; border: 1px solid rgba(226, 232, 240, 0.8);">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2733.471547765584!2d121.01362955409941!3d14.328123255462542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d7003ea37227%3A0x7a7aa8c20812ec54!2sBahay%20ni%20tung%20sahur!5e0!3m2!1sen!2sph!4v1786858331902!5m2!1sen!2sph" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card contact-form-card fade-in-up p-4 rounded-4 shadow-sm bg-white border h-100">
            <div class="card-body p-0">
              <h3 class="fw-bold fs-5 text-dark mb-3">Send Us a Direct Message</h3>
              <p class="text-muted small mb-4">Fill out the inquiry form below and an admissions counselor will get back to you within 24 hours.</p>
              <form action="#" method="post">
                <?= getCsrfInput() ?>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark" for="contactFullName">Full Name</label>
                    <input class="form-control rounded-3 py-2" type="text" id="contactFullName" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark" for="contactEmail">Email Address</label>
                    <input class="form-control rounded-3 py-2" type="email" id="contactEmail" name="email" placeholder="e.g. juan@example.com" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark" for="contactSubject">Subject</label>
                    <input class="form-control rounded-3 py-2" type="text" id="contactSubject" name="subject" placeholder="e.g. Inquiry regarding BSIT enrollment" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label small fw-semibold text-dark" for="contactMessage">Your Message</label>
                    <textarea class="form-control rounded-3" id="contactMessage" name="message" rows="5" placeholder="Write your questions or notes here..." required></textarea>
                  </div>

                  <div class="col-12 mt-4">
                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-sm" type="submit" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                      <i class="bi bi-send-fill"></i>
                      <span>Send Message</span>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- UNIVERSITY FOOTER -->
  <footer class="university-footer">
    <div class="container">
      <div class="row g-4 mb-5">
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 48px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(13, 110, 253, 0.4));">
            <div>
              <h4 class="fw-bold text-white mb-0" style="font-size: 1.2rem; letter-spacing: -0.2px;">Triple T University</h4>
              <span class="text-white-50 small fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.68rem;">Center of Academic Excellence</span>
            </div>
          </div>
          <p class="text-muted small mb-4" style="line-height: 1.7;">
            Empowering minds and transforming futures through accessible, technology-driven education and global industry standards.
          </p>
          <div class="footer-badge d-flex align-items-center gap-3">
            <i class="bi bi-shield-check text-primary fs-3"></i>
            <div>
              <span class="d-block text-white fw-semibold small">Fully Accredited Institution</span>
              <span class="d-block text-muted" style="font-size: 0.72rem;">Recognized by CHED & DepEd Philippines</span>
            </div>
          </div>
        </div>

        <div class="col-lg-2 col-md-6">
          <h5>Quick Links</h5>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
            <li><a href="#hero"><i class="bi bi-chevron-right me-1 small"></i> Home</a></li>
            <li><a href="#about"><i class="bi bi-chevron-right me-1 small"></i> About Campus</a></li>
            <li><a href="#admission-process"><i class="bi bi-chevron-right me-1 small"></i> Admissions Flow</a></li>
            <li><a href="#requirements"><i class="bi bi-chevron-right me-1 small"></i> Requirements</a></li>
            <li><a href="#courses"><i class="bi bi-chevron-right me-1 small"></i> Academic Programs</a></li>
            <li><a href="#scholarships"><i class="bi bi-chevron-right me-1 small"></i> Scholarships</a></li>
            <li><a href="#faq"><i class="bi bi-chevron-right me-1 small"></i> FAQs</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6">
          <h5>Portals & Services</h5>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
            <li><a href="/sia/auth/lms_student_login.php"><i class="bi bi-mortarboard me-1 text-primary"></i> Student LMS Portal</a></li>
            <li><a href="/sia/auth/lms_faculty_login.php"><i class="bi bi-person-video3 me-1 text-info"></i> Faculty LMS Portal</a></li>
            <li><a href="/sia/auth/login.php"><i class="bi bi-box-arrow-in-right me-1 text-success"></i> Applicant Portal Login</a></li>
            <li><a href="/sia/auth/register.php"><i class="bi bi-pencil-square me-1 text-warning"></i> Online Application Form</a></li>
            <li><a href="/sia/applicant/scholarships.php"><i class="bi bi-award me-1 text-primary"></i> Scholarship Services</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6">
          <h5>Campus Location</h5>
          <p class="small text-muted mb-2"><i class="bi bi-geo-alt-fill text-danger me-2"></i> 123 Tung Tung Avenue, Sahur City, Philippines</p>
          <p class="small text-muted mb-2"><i class="bi bi-telephone-fill text-success me-2"></i> (02) 8123-4567 / 0912-3456-789</p>
          <p class="small text-muted mb-4"><i class="bi bi-envelope-fill text-info me-2"></i> admissions@ttu.edu.ph</p>
          <div class="d-flex align-items-center gap-2">
            <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.2);" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.2);" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.2);" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.2);" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
          </div>
        </div>
      </div>

      <div class="pt-4 mt-4 border-top border-secondary border-opacity-25 d-flex flex-wrap align-items-center justify-content-between gap-3 small">
        <p class="mb-0 text-muted">&copy; <?= date('Y'); ?> Triple T University. All rights reserved.</p>
        <div class="d-flex gap-3">
          <a href="#" class="text-muted text-decoration-none">Privacy Policy</a>
          <span class="text-secondary">&bull;</span>
          <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
          <span class="text-secondary">&bull;</span>
          <a href="#" class="text-muted text-decoration-none">Student Handbook</a>
        </div>
      </div>
    </div>
  </footer>
</main>

<style>
.fade-in-up { animation-play-state: paused; }
.fade-in-up.is-visible { animation-play-state: running; }
body > footer.bg-white { display: none !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if(entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.fade-in-up').forEach(function(el) {
        observer.observe(el);
    });
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
