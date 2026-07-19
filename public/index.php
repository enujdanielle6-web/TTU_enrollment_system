<?php
$pageTitle = 'Online Enrollment System';

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main>
  <section id="hero" class="hero-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="hero-eyebrow">Triple T University</span>
          <h1 class="hero-title">Empowering Minds. Transforming Futures.</h1>
          <p class="hero-text">
            Start your school application online with a clear, guided enrollment experience built for students and families.
          </p>
          <div class="hero-actions">
            <a class="btn btn-primary btn-lg" href="../applicant/enroll.php">
              <i class="bi bi-pencil-square"></i>
              Enroll Now
            </a>
            <a class="btn btn-outline-primary btn-lg" href="#about">
              <i class="bi bi-info-circle"></i>
              Learn More
            </a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="school-illustration-placeholder" aria-label="School illustration placeholder">
            <div class="illustration-sun"></div>
            <div class="illustration-building">
              <i class="bi bi-building"></i>
            </div>
            <div class="illustration-card illustration-card-primary">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="illustration-card illustration-card-secondary">
              <i class="bi bi-journal-check"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="about">
    <div class="container">
      <h2>About</h2>
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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
          <div class="card admission-card">
            <div class="card-body">
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

      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-file-earmark-person"></i>
              </div>
              <h3>Birth Certificate</h3>
              <p>Submit a clear copy issued by the civil registrar.</p>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-card-checklist"></i>
              </div>
              <h3>Form 138 (Report Card)</h3>
              <p>Provide your latest completed school report card.</p>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <h3>Good Moral Certificate</h3>
              <p>Include a certificate from your previous school.</p>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-person-bounding-box"></i>
              </div>
              <h3>2x2 ID Picture</h3>
              <p>Prepare a recent photo with a clean background.</p>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <h3>Certificate of Enrollment</h3>
              <p>Required only for transferee applicants.</p>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6">
          <div class="card requirement-card">
            <div class="card-body">
              <div class="requirement-icon">
                <i class="bi bi-person-vcard"></i>
              </div>
              <h3>Parent/Guardian ID</h3>
              <p>Submit a valid ID of your parent or guardian.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="requirements-note">
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
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-calculator"></i>
                  </div>
                  <h3>STEM</h3>
                  <p>Science, Technology, Engineering, and Mathematics for future innovators.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-briefcase"></i>
                  </div>
                  <h3>ABM</h3>
                  <p>Accountancy, Business, and Management for aspiring business leaders.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-chat-square-quote"></i>
                  </div>
                  <h3>HUMSS</h3>
                  <p>Humanities and Social Sciences for communication and public service paths.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-tools"></i>
                  </div>
                  <h3>TVL</h3>
                  <p>Technical Vocational Livelihood for practical career-ready training.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-journal-bookmark"></i>
                  </div>
                  <h3>GAS</h3>
                  <p>General Academic Strand for flexible preparation across college programs.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- College Tab -->
        <div class="tab-pane fade" id="college" role="tabpanel" aria-labelledby="college-tab">
          <div class="row g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-pc-display"></i>
                  </div>
                  <h3>BSIT</h3>
                  <p>Bachelor of Science in Information Technology for future software developers and IT professionals.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-laptop"></i>
                  </div>
                  <h3>BSCS</h3>
                  <p>Bachelor of Science in Computer Science focusing on computing theory and advanced software systems.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-calculator-fill"></i>
                  </div>
                  <h3>BSA</h3>
                  <p>Bachelor of Science in Accountancy for future certified public accountants and financial experts.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-book-half"></i>
                  </div>
                  <h3>BSED</h3>
                  <p>Bachelor of Secondary Education for aspiring high school teachers and educators.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card program-card h-100">
                <div class="card-body">
                  <div class="program-icon">
                    <i class="bi bi-bar-chart-line"></i>
                  </div>
                  <h3>BSBA</h3>
                  <p>Bachelor of Science in Business Administration for future corporate leaders and entrepreneurs.</p>
                </div>
              </div>
            </div>
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
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2794.4765924235903!2d-122.62732272379954!3d45.54073737107526!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x5495a1d116b8b961%3A0x8ab2d7d1d7acf0e9!2sTung%20Tung%20Tung%20Sahur%20death%20house!5e0!3m2!1sen!2sph!4v1782695956338!5m2!1sen!2sph" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card contact-form-card">
            <div class="card-body">
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

<?php require_once __DIR__ . '/../components/footer.php'; ?>
