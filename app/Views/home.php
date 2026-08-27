<?php
$pageTitle = 'Online Enrollment System';

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main>
  <section id="hero" class="hero-section text-center text-white">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <span class="hero-eyebrow" style="color: #60a5fa;">Triple T University</span>
          <h1 class="hero-title text-white">Empowering Minds. Transforming Futures.</h1>
          <p class="hero-text text-white opacity-75 mx-auto" style="max-width: 600px;">
            Start your school application online with a clear, guided enrollment experience built for students and families.
          </p>
          <div class="hero-actions justify-content-center mt-4">
            <a class="btn btn-primary btn-lg" href="/sia/auth/register.php">
              <i class="bi bi-pencil-square"></i>
              Enroll Now
            </a>
            <a class="btn btn-outline-light btn-lg" href="#lms-portals">
              <i class="bi bi-info-circle"></i>
              Learn More
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="lms-portals" class="py-5 bg-light">
    <div class="container py-4">
      <div class="section-heading text-center mb-5">
        <h2>Learning Management System</h2>
        <p class="text-muted">Access your online courses, assignments, and grades.</p>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-5">
          <div class="card h-100 shadow-sm border-0 text-center p-4 hover-lift">
            <div class="card-body">
              <div class="display-4 text-primary mb-3"><i class="bi bi-mortarboard"></i></div>
              <h3 class="h4 mb-3">Student Portal</h3>
              <p class="text-muted mb-4">View enrolled subjects, submit assignments, and check your academic progress.</p>
              <a href="/sia/auth/lms_student_login.php" class="btn btn-primary w-100">Student Login</a>
            </div>
          </div>
        </div>
        <div class="col-md-5">
          <div class="card h-100 shadow-sm border-0 text-center p-4 hover-lift">
            <div class="card-body">
              <div class="display-4 text-primary mb-3"><i class="bi bi-person-video3"></i></div>
              <h3 class="h4 mb-3">Faculty Portal</h3>
              <p class="text-muted mb-4">Manage your classes, grade students, and upload course modules.</p>
              <a href="/sia/auth/lms_faculty_login.php" class="btn btn-outline-primary w-100">Faculty Login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CLEAN MODERN CARDS -->
  <section class="modern-features">
      <div class="container">
          <div class="text-center mb-5">
              <h2 class="fw-bold">Everything You Need</h2>
              <p class="text-muted">A fully integrated academic experience.</p>
          </div>
          <div class="row g-4">
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-laptop"></i>
                      </div>
                      <h4 class="fw-bold">Smart LMS</h4>
                      <p class="text-muted mb-0">Access courses, track your progress, and interact with faculty through our seamless online portal.</p>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-rocket"></i>
                      </div>
                      <h4 class="fw-bold">Fast Enrollment</h4>
                      <p class="text-muted mb-0">Skip the lines. Our 10-step digital enrollment process makes registering for classes a breeze.</p>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="feature-card">
                      <div class="feature-icon">
                          <i class="bi bi-globe"></i>
                      </div>
                      <h4 class="fw-bold">Connected Community</h4>
                      <p class="text-muted mb-0">Join clubs, attend virtual seminars, and stay engaged with a university that never sleeps.</p>
                  </div>
              </div>
          </div>
      </div>
  </section>

  <!-- CAMPUS LIFE SECTION -->
  <section class="py-5 bg-white">
    <div class="container py-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 fade-in-up">
          <h2 class="fw-bold mb-4">Discover Campus Life</h2>
          <p class="text-muted fs-5 mb-4">
            Experience a vibrant community where academic excellence meets personal growth. Our modern facilities and expansive grounds provide the perfect environment for your journey.
          </p>
          <ul class="list-unstyled text-muted mb-0">
            <li class="mb-3"><i class="bi bi-check-circle-fill text-info me-2"></i> Modern Library & Study Centers</li>
            <li class="mb-3"><i class="bi bi-check-circle-fill text-info me-2"></i> State-of-the-Art Laboratories</li>
            <li class="mb-3"><i class="bi bi-check-circle-fill text-info me-2"></i> Expansive Sports Complex</li>
            <li><i class="bi bi-check-circle-fill text-info me-2"></i> Collaborative Student Hubs</li>
          </ul>
        </div>
        <div class="col-lg-6 fade-in-up" style="animation-delay: 0.2s;">
          <img src="/sia/images/TTU_OUTSIDE.png" alt="TTU Campus Outside" class="img-fluid rounded-4 shadow-lg" style="border: 1px solid var(--color-border);">
        </div>
      </div>
    </div>
  </section>

  <section id="admission-process" class="admission-process-section">
    <div class="container">
      <div class="section-heading text-center">
        <h2>Admission Process</h2>
        <p>Follow these simple steps to complete your online enrollment.</p>
      </div>

      <div class="admission-timeline">
        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-person-plus"></i>
              </div>
              <span class="admission-step-number">Step 1</span>
              <h3>Create Account</h3>
              <p>Register with your basic information to begin the enrollment process.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-box-arrow-in-right"></i>
              </div>
              <span class="admission-step-number">Step 2</span>
              <h3>Login</h3>
              <p>Access your applicant account using your registered credentials.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-ui-checks"></i>
              </div>
              <span class="admission-step-number">Step 3</span>
              <h3>Complete Enrollment Form</h3>
              <p>Fill out the required student, guardian, and academic details.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-send-check"></i>
              </div>
              <span class="admission-step-number">Step 4</span>
              <h3>Submit Application</h3>
              <p>Review your information and send your application for processing.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-heart-pulse"></i>
              </div>
              <span class="admission-step-number">Step 5</span>
              <h3>Submit Health Information</h3>
              <p>Fill out the medical clearance form so the clinic can evaluate your health status.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-award"></i>
              </div>
              <span class="admission-step-number">Step 6</span>
              <h3>Apply for Scholarship (Optional)</h3>
              <p>Eligible students can apply for financial assistance or academic scholarships.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <span class="admission-step-number">Step 7</span>
              <h3>Wait for Admin Review</h3>
              <p>The admissions team checks your submitted application and health details.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-building"></i>
              </div>
              <span class="admission-step-number">Step 8</span>
              <h3>Visit the School</h3>
              <p>Proceed to the campus on the advised schedule for next steps.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-file-earmark-check"></i>
              </div>
              <span class="admission-step-number">Step 9</span>
              <h3>Verify Original Documents</h3>
              <p>Present original documents for validation by school personnel.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-credit-card"></i>
              </div>
              <span class="admission-step-number">Step 10</span>
              <h3>Pay Enrollment Fees</h3>
              <p>Settle the required fees through the approved payment process.</p>
            </div>
          </div>
        </div>

        <div class="admission-step">
          <div class="card admission-card fade-in-up">
            <div class="card-body fade-in-up">
              <div class="admission-icon">
                <i class="bi bi-patch-check"></i>
              </div>
              <span class="admission-step-number">Step 11</span>
              <h3>Enrollment Completed</h3>
              <p>Receive confirmation that your enrollment has been finalized.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="requirements" class="requirements-section">
    <div class="container">
      <div class="section-heading text-center">
        <h2>Requirements</h2>
        <p>Prepare the following documents before enrollment.</p>
      </div>

      <ul class="nav nav-pills justify-content-center mb-4" id="reqsTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active rounded-pill px-4" id="shs-req-tab" data-bs-toggle="tab" data-bs-target="#shs-req" type="button" role="tab" aria-controls="shs-req" aria-selected="true">Senior High School</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4" id="college-req-tab" data-bs-toggle="tab" data-bs-target="#college-req" type="button" role="tab" aria-controls="college-req" aria-selected="false">College</button>
        </li>
      </ul>

      <div class="tab-content" id="reqsTabContent">
        <!-- SHS Requirements Tab -->
        <div class="tab-pane fade show active" id="shs-req" role="tabpanel" aria-labelledby="shs-req-tab">
          <div class="row g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-person"></i></div>
                  <h3>Birth Certificate</h3>
                  <p>Submit a clear copy issued by the civil registrar.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-card-checklist"></i></div>
                  <h3>Form 138 (Report Card)</h3>
                  <p>Provide your latest Grade 10 report card.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-shield-check"></i></div>
                  <h3>Good Moral Certificate</h3>
                  <p>Include a certificate from your previous school.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-person-bounding-box"></i></div>
                  <h3>2x2 ID Picture</h3>
                  <p>Prepare a recent photo with a clean background.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-person-vcard"></i></div>
                  <h3>Parent/Guardian ID</h3>
                  <p>Submit a valid ID of your parent or guardian.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-text"></i></div>
                  <h3>NCAE Results</h3>
                  <p>Photocopy of NCAE results (if available).</p>
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
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-person"></i></div>
                  <h3>Birth Certificate</h3>
                  <p>Submit a clear copy issued by the civil registrar.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-card-checklist"></i></div>
                  <h3>Form 138 / Transcript of Records</h3>
                  <p>Provide your Grade 12 report card or official TOR for transferees.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-shield-check"></i></div>
                  <h3>Good Moral Certificate</h3>
                  <p>Include a certificate from your senior high school.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-person-bounding-box"></i></div>
                  <h3>2x2 ID Picture</h3>
                  <p>Prepare a recent photo with a clean background.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card requirement-card fade-in-up">
                <div class="card-body fade-in-up">
                  <div class="requirement-icon"><i class="bi bi-file-earmark-text"></i></div>
                  <h3>Certificate of Enrollment</h3>
                  <p>Required only for transferee applicants.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="requirements-note mt-4">
        <i class="bi bi-info-circle"></i>
        <span>All original documents must be presented during on-site verification.</span>
      </div>
    </div>
  </section>

  <section id="courses" class="programs-section">
    <div class="container">
      <div class="section-heading text-center">
        <h2>Programs Offered</h2>
        <p>Choose the right program for your future.</p>
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

      <ul class="nav nav-pills justify-content-center mb-4" id="programsTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active rounded-pill px-4" id="shs-tab" data-bs-toggle="tab" data-bs-target="#shs" type="button" role="tab" aria-controls="shs" aria-selected="true">Senior High School</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4" id="college-tab" data-bs-toggle="tab" data-bs-target="#college" type="button" role="tab" aria-controls="college" aria-selected="false">College</button>
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
                  <div class="card program-card h-100 fade-in-up">
                    <div class="card-body fade-in-up">
                      <div class="program-icon">
                        <i class="bi <?= esc($icon) ?>"></i>
                      </div>
                      <h3><?= esc($strandCode) ?></h3>
                      <p><?= esc($desc) ?></p>
                      <p class="text-muted small mb-1"><i class="bi bi-cash-coin me-1"></i> Tuition: <?= esc($tuition) ?></p>
                      <p class="text-muted small"><i class="bi bi-briefcase-fill me-1"></i> Careers: <?= esc($careers) ?></p>
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
                  <div class="card program-card h-100 fade-in-up">
                    <div class="card-body fade-in-up">
                      <div class="program-icon">
                        <i class="bi <?= esc($icon) ?>"></i>
                      </div>
                      <h3><?= esc($programCode) ?></h3>
                      <p><?= esc($desc) ?></p>
                      <p class="text-muted small mb-1"><i class="bi bi-cash-coin me-1"></i> Tuition: <?= esc($tuition) ?></p>
                      <p class="text-muted small"><i class="bi bi-briefcase-fill me-1"></i> Careers: <?= esc($careers) ?></p>
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

  <section id="faq" class="faq-section">
    <div class="container">
      <div class="section-heading text-center">
        <h2>Frequently Asked Questions</h2>
        <p>Common questions about online enrollment.</p>
      </div>

      <div class="accordion faq-accordion" id="faqAccordion">
        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
              How do I apply for enrollment?
            </button>
          </h3>
          <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              Create an account, log in, complete the enrollment form, and submit your application online.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingTwo">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
              Is the enrollment process fully online?
            </button>
          </h3>
          <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              The application can be submitted online, but original documents must be verified on-site.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingThree">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
              What documents are required?
            </button>
          </h3>
          <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              Prepare your birth certificate, report card, good moral certificate, ID picture, and required guardian documents.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingFour">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
              Can I edit my application after submission?
            </button>
          </h3>
          <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              Submitted applications may require admin assistance before changes can be made.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingFive">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFive" aria-expanded="false" aria-controls="faqCollapseFive">
              How do I check my application status?
            </button>
          </h3>
          <div id="faqCollapseFive" class="accordion-collapse collapse" aria-labelledby="faqHeadingFive" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              Log in to your applicant account and use your application reference number to track updates.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h3 class="accordion-header" id="faqHeadingSix">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseSix" aria-expanded="false" aria-controls="faqCollapseSix">
              When should I visit the school?
            </button>
          </h3>
          <div id="faqCollapseSix" class="accordion-collapse collapse" aria-labelledby="faqHeadingSix" data-bs-parent="#faqAccordion">
            <div class="accordion-body">
              Visit the school after your application has been reviewed and you receive instructions for verification.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="contact" class="contact-section">
    <div class="container">
      <div class="section-heading text-center">
        <h2>Contact Us</h2>
        <p>We’re here to help you with your enrollment.</p>
      </div>

      <div class="row g-4 align-items-stretch">
        <div class="col-lg-5">
          <div class="contact-info-panel">
            <div class="contact-info-item">
              <div class="contact-icon">
                <i class="bi bi-geo-alt"></i>
              </div>
              <div>
                <h3>School Address</h3>
                <p>123 Tung Tung Avenue, Sahur City, Philippines</p>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-icon">
                <i class="bi bi-telephone"></i>
              </div>
              <div>
                <h3>Phone Number</h3>
                <p>0912-3456-789</p>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-icon">
                <i class="bi bi-envelope"></i>
              </div>
              <div>
                <h3>Email Address</h3>
                <p>admissions@ttu.edu.ph</p>
              </div>
            </div>

            <div class="contact-info-item">
              <div class="contact-icon">
                <i class="bi bi-clock"></i>
              </div>
              <div>
                <h3>Office Hours</h3>
                <p>Monday to Friday, 8:00 AM - 5:00 PM</p>
              </div>
            </div>

            <div class="map-container mt-4" style="border-radius: 12px; overflow: hidden; height: 250px;">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2733.471547765584!2d121.01362955409941!3d14.328123255462542!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d7003ea37227%3A0x7a7aa8c20812ec54!2sBahay%20ni%20tung%20sahur!5e0!3m2!1sen!2sph!4v1786858331902!5m2!1sen!2sph" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card contact-form-card fade-in-up">
            <div class="card-body fade-in-up">
              <form action="#" method="post">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="contactFullName">Full Name</label>
                    <input class="form-control" type="text" id="contactFullName" name="full_name" placeholder="Enter your full name">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label" for="contactEmail">Email</label>
                    <input class="form-control" type="email" id="contactEmail" name="email" placeholder="Enter your email">
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="contactSubject">Subject</label>
                    <input class="form-control" type="text" id="contactSubject" name="subject" placeholder="How can we help?">
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="contactMessage">Message</label>
                    <textarea class="form-control" id="contactMessage" name="message" rows="6" placeholder="Write your message"></textarea>
                  </div>

                  <div class="col-12">
                    <button class="btn btn-primary contact-submit" type="submit">
                      <i class="bi bi-send"></i>
                      Submit
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
</main>

<style>
.fade-in-up { animation-play-state: paused; }
.fade-in-up.is-visible { animation-play-state: running; }
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
