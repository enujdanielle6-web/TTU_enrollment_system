-- Online Enrollment System
-- Optional development seed data for database "sia".
--
-- Import after schema.sql:
--   mysql -u root sia < database/seed.sql
--
-- Default login credentials (password for all accounts: password123)
--   Admin:     admin@ttu.edu.ph
--   Applicant: applicant@example.com

USE sia;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
INSERT INTO users (first_name, last_name, email, password, role)
VALUES
  (
    'System',
    'Administrator',
    'admin@ttu.edu.ph',
    '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.',
    'superadmin'
  ),
  (
    'Juan',
    'Dela Cruz',
    'applicant@example.com',
    '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.',
    'applicant'
  )
ON DUPLICATE KEY UPDATE
  first_name = VALUES(first_name),
  last_name = VALUES(last_name),
  password = VALUES(password),
  role = VALUES(role);

INSERT INTO users (first_name, last_name, email, password, role, department, permissions)
VALUES
  ('Registrar', 'Staff', 'registrar@ttu.edu.ph', '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.', 'admin', 'Registrar', '["programs.manage","sections.manage","enrollment.approve","enrollment.finalize"]'),
  ('Cashier', 'Staff', 'cashier@ttu.edu.ph', '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.', 'cashier', 'Accounting', '["payments.view","payments.process"]'),
  ('Admissions', 'Staff', 'admissions@ttu.edu.ph', '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.', 'admissions', 'Admissions', '["applications.view","applications.approve"]'),
  ('Scholarship', 'Staff', 'scholarship@ttu.edu.ph', '$2y$10$ZvVWTBoYatlfPgGf2rnk8uQ9mM5UvsKiryNfBsQGlLf2eAV9gKJr.', 'scholarship', 'Student Affairs', '["scholarships.manage"]')
ON DUPLICATE KEY UPDATE
  first_name = VALUES(first_name),
  last_name = VALUES(last_name),
  password = VALUES(password),
  role = VALUES(role),
  department = VALUES(department),
  permissions = VALUES(permissions);

-- ---------------------------------------------------------------------------
-- system_settings
-- ---------------------------------------------------------------------------
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES
  ('active_school_year', '2026-2027', 'Current active school year for enrollment.'),
  ('enrollment_status', 'open', 'Global enrollment portal status (open/closed).'),
  ('college_cost_per_unit', '500.00', 'Tuition cost per unit for college level students.')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  description = VALUES(description);

-- ---------------------------------------------------------------------------
-- academic_programs (Strands & Courses)
-- ---------------------------------------------------------------------------
INSERT INTO academic_programs (code, name, category, is_active)
VALUES
  ('STEM', 'Science, Technology, Engineering, and Mathematics', 'Senior High School', 1),
  ('ABM', 'Accountancy, Business, and Management', 'Senior High School', 1),
  ('HUMSS', 'Humanities and Social Sciences', 'Senior High School', 1),
  ('TVL', 'Technical-Vocational-Livelihood', 'Senior High School', 1),
  ('GAS', 'General Academic Strand', 'Senior High School', 1),
  ('BSIT', 'Bachelor of Science in Information Technology', 'College', 1),
  ('BSCS', 'Bachelor of Science in Computer Science', 'College', 1),
  ('BSA', 'Bachelor of Science in Accountancy', 'College', 1),
  ('BSED', 'Bachelor of Secondary Education', 'College', 1),
  ('BSBA', 'Bachelor of Science in Business Administration', 'College', 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  category = VALUES(category),
  is_active = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- applications
-- ---------------------------------------------------------------------------
INSERT INTO applications (user_id, reference_number, academic_level, status)
SELECT
  u.id,
  'SIA-2026-000001',
  'Senior High School',
  'pending'
FROM users u
WHERE u.email = 'applicant@example.com'
  AND NOT EXISTS (
    SELECT 1
    FROM applications a
    WHERE a.reference_number = 'SIA-2026-000001'
  )
LIMIT 1;

-- ---------------------------------------------------------------------------
-- activity_logs
-- ---------------------------------------------------------------------------
INSERT INTO activity_logs (user_id, icon, title, description)
SELECT u.id, 'bi-shield-check', 'Portal Access Granted', 'Your account was successfully registered inside the Triple T University portal.'
FROM users u WHERE u.email = 'applicant@example.com'
ON DUPLICATE KEY UPDATE user_id = user_id;

INSERT INTO activity_logs (user_id, icon, title, description)
SELECT u.id, 'bi-file-earmark-check', 'Application Submitted', 'You successfully completed the online enrollment application. Ref: SIA-2026-000001'
FROM users u WHERE u.email = 'applicant@example.com'
ON DUPLICATE KEY UPDATE user_id = user_id;

-- ---------------------------------------------------------------------------
-- announcements
-- ---------------------------------------------------------------------------
INSERT INTO announcements (badge_label, badge_color, title, content, is_active)
VALUES
  ('Important', 'danger', 'Enrollment Ongoing', 'The online enrollment for Academic Year 2026-2027 is now officially open.', 1),
  ('Notice', 'info', 'Document Submission', 'Please prepare your original PSA Birth Certificate and Report Card for verification.', 1)
ON DUPLICATE KEY UPDATE
  badge_label = VALUES(badge_label),
  badge_color = VALUES(badge_color),
  content = VALUES(content),
  is_active = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- fee_templates
-- ---------------------------------------------------------------------------
INSERT INTO fee_templates (name, academic_level, grade_level, strand, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount)
VALUES
  ('Grade 11 STEM Fee Template', 'Senior High School', 'Grade 11', 'STEM', 20000.00, 5000.00, 1000.00, 3000.00, 1500.00, 30500.00),
  ('Grade 11 ABM Fee Template', 'Senior High School', 'Grade 11', 'ABM', 20000.00, 5000.00, 1000.00, 1000.00, 1500.00, 28500.00),
  ('Grade 12 STEM Fee Template', 'Senior High School', 'Grade 12', 'STEM', 22000.00, 5000.00, 1000.00, 4000.00, 1500.00, 33500.00),
  ('Grade 12 ABM Fee Template', 'Senior High School', 'Grade 12', 'ABM', 22000.00, 5000.00, 1000.00, 1000.00, 1500.00, 30500.00)
ON DUPLICATE KEY UPDATE
  tuition_fee = VALUES(tuition_fee),
  miscellaneous_fee = VALUES(miscellaneous_fee),
  registration_fee = VALUES(registration_fee),
  laboratory_fee = VALUES(laboratory_fee),
  other_fees = VALUES(other_fees),
  total_amount = VALUES(total_amount);

-- ---------------------------------------------------------------------------
-- scholarships
-- ---------------------------------------------------------------------------
INSERT INTO scholarships (name, discount_type, discount_value, description, is_active)
VALUES
  ('Academic Excellence - 100%', 'percentage', 100.00, 'Full tuition discount for students with highest honors.', 1),
  ('Academic Excellence - 75%', 'percentage', 75.00, '75% tuition discount for students with high honors.', 1),
  ('Academic Excellence - 50%', 'percentage', 50.00, '50% tuition discount for students with honors.', 1),
  ('Academic Excellence - 25%', 'percentage', 25.00, '25% tuition discount for exemplary students.', 1)
ON DUPLICATE KEY UPDATE
  discount_type = VALUES(discount_type),
  discount_value = VALUES(discount_value),
  description = VALUES(description),
  is_active = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- subjects
-- ---------------------------------------------------------------------------
INSERT INTO subjects (subject_code, subject_name, units, description, status)
VALUES
  -- General College Subjects
  ('GE101', 'Understanding the Self', 3, 'Fundamental course on self-discovery.', 1),
  ('GE102', 'Readings in Philippine History', 3, 'Philippine history from primary sources.', 1),
  ('GE103', 'The Contemporary World', 3, 'Introduction to globalization and modern society.', 1),
  ('GE104', 'Mathematics in the Modern World', 3, 'Applications of mathematics in daily life.', 1),
  ('PE101', 'Physical Fitness', 2, 'Basic physical education and wellness.', 1),
  ('PE102', 'Rhythmic Activities', 2, 'Dance and basic rhythmic movements.', 1),
  ('PE103', 'Individual/Dual Sports', 2, 'Individual and dual sports physical education.', 1),
  ('PE104', 'Team Sports', 2, 'Team sports physical education.', 1),
  ('NSTP1', 'National Service Training Program 1', 3, 'Civic consciousness and defense preparedness.', 1),
  ('NSTP2', 'National Service Training Program 2', 3, 'Community immersion and service.', 1),
  
  -- IT / CS Core Subjects
  ('IT101', 'Introduction to Computing', 3, 'Foundations of computer systems and information technology.', 1),
  ('IT102', 'Computer Programming 1', 3, 'Introduction to programming concepts and logical formulation.', 1),
  ('IT103', 'Computer Programming 2', 3, 'Intermediate programming with object-oriented concepts.', 1),
  ('IT104', 'Data Structures and Algorithms', 3, 'Study of core data structures and programming algorithms.', 1),
  ('CS101', 'Introduction to Computer Science', 3, 'Theoretical and mathematical foundations of computing.', 1),
  ('CS102', 'Discrete Mathematics', 3, 'Mathematical structures relevant to computer science.', 1),
  ('CS103', 'Object-Oriented Programming', 3, 'OOP principles and paradigms.', 1),
  
  -- Accountancy / Business
  ('ACC101', 'Financial Accounting', 3, 'Introduction to basic financial accounting concepts.', 1),
  ('ACC102', 'Managerial Accounting', 3, 'Accounting systems for management decisions.', 1),
  ('ACC103', 'Cost Accounting', 3, 'Cost accumulation methods and analysis.', 1),
  ('BUS101', 'Principles of Management', 3, 'Foundational principles of administrative management.', 1),
  ('BUS102', 'Marketing Management', 3, 'Principles of product planning and customer relation.', 1),
  
  -- Education
  ('ED101', 'Child and Adolescent Development', 3, 'Human development stages and theories of learning.', 1),
  ('ED102', 'Facilitating Learner-Centered Teaching', 3, 'Modern pedagogical practices and models.', 1),

  -- Senior High School Core Subjects
  ('SHS_ENG1', 'Oral Communication in Context', 3, 'High school oral communications course.', 1),
  ('SHS_MATH1', 'General Mathematics', 3, 'General mathematical operations and systems.', 1),
  ('SHS_SCI1', 'Earth and Life Science', 3, 'Foundations of geology and biology.', 1),
  ('SHS_FIL1', 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 3, 'Study of Filipino culture and language research.', 1),
  ('SHS_STEM1', 'Pre-Calculus', 3, 'Introductory calculus concepts for STEM.', 1),
  ('SHS_STEM2', 'Basic Calculus', 3, 'Differential and integral calculus for STEM.', 1),
  ('SHS_ABM1', 'Fundamentals of Accountancy, Business, and Management 1', 3, 'High school business accounting basics.', 1),
  ('SHS_ABM2', 'Fundamentals of Accountancy, Business, and Management 2', 3, 'Advanced high school business accounting basics.', 1)
ON DUPLICATE KEY UPDATE
  subject_name = VALUES(subject_name),
  units = VALUES(units),
  description = VALUES(description),
  status = VALUES(status);

-- ---------------------------------------------------------------------------
-- curriculum mappings
-- ---------------------------------------------------------------------------

-- Curriculum map for BSIT, 1st Year, First Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'First', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSIT' AND s.subject_code IN ('IT101', 'IT102', 'GE101', 'PE101', 'NSTP1')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for BSIT, 1st Year, Second Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'Second', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSIT' AND s.subject_code IN ('IT103', 'GE102', 'GE104', 'PE102', 'NSTP2')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for BSCS, 1st Year, First Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'First', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSCS' AND s.subject_code IN ('CS101', 'CS102', 'GE101', 'PE101', 'NSTP1')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for BSCS, 1st Year, Second Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'Second', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSCS' AND s.subject_code IN ('CS103', 'GE102', 'GE104', 'PE102', 'NSTP2')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for BSA, 1st Year, First Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'First', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSA' AND s.subject_code IN ('ACC101', 'BUS101', 'GE101', 'PE101', 'NSTP1')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for BSA, 1st Year, Second Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, '1st Year', 'Second', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'BSA' AND s.subject_code IN ('ACC102', 'GE102', 'GE104', 'PE102', 'NSTP2')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for STEM, Grade 11, First Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, 'Grade 11', 'First', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'STEM' AND s.subject_code IN ('SHS_ENG1', 'SHS_MATH1', 'SHS_SCI1', 'SHS_FIL1', 'SHS_STEM1')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for STEM, Grade 11, Second Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, 'Grade 11', 'Second', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'STEM' AND s.subject_code IN ('SHS_STEM2')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for ABM, Grade 11, First Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, 'Grade 11', 'First', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'ABM' AND s.subject_code IN ('SHS_ENG1', 'SHS_MATH1', 'SHS_SCI1', 'SHS_FIL1', 'SHS_ABM1')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- Curriculum map for ABM, Grade 11, Second Semester
INSERT INTO curriculum (program_id, year_level, semester, subject_id)
SELECT p.id, 'Grade 11', 'Second', s.id
FROM academic_programs p, subjects s
WHERE p.code = 'ABM' AND s.subject_code IN ('SHS_ABM2')
ON DUPLICATE KEY UPDATE program_id = program_id;

-- ---------------------------------------------------------------------------
-- sections
-- ---------------------------------------------------------------------------
INSERT INTO sections (section_code, program_id, year_level, semester, schedule_type, capacity, adviser, status)
SELECT 'STEM-11A', p.id, 'Grade 11', NULL, 'Morning', 40, 'Mr. Smith', 1 FROM academic_programs p WHERE p.code = 'STEM'
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

INSERT INTO sections (section_code, program_id, year_level, semester, schedule_type, capacity, adviser, status)
SELECT 'ABM-11A', p.id, 'Grade 11', NULL, 'Morning', 40, 'Ms. Johnson', 1 FROM academic_programs p WHERE p.code = 'ABM'
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

INSERT INTO sections (section_code, program_id, year_level, semester, schedule_type, capacity, adviser, status)
SELECT 'BSIT-1A', p.id, '1st Year', 'First', 'Morning', 40, 'Dr. Alan', 1 FROM academic_programs p WHERE p.code = 'BSIT'
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

INSERT INTO sections (section_code, program_id, year_level, semester, schedule_type, capacity, adviser, status)
SELECT 'BSCS-1A', p.id, '1st Year', 'First', 'Afternoon', 40, 'Dr. Turing', 1 FROM academic_programs p WHERE p.code = 'BSCS'
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity);

-- ---------------------------------------------------------------------------
-- fee_templates
-- ---------------------------------------------------------------------------
INSERT INTO fee_templates (name, academic_level, grade_level, strand, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount) VALUES
('STEM - Grade 11 Fees', 'Senior High School', 'Grade 11', 'STEM', 15000, 2500, 500, 1500, 1000, 20500),
('STEM - Grade 12 Fees', 'Senior High School', 'Grade 12', 'STEM', 15000, 2500, 500, 1500, 1000, 20500),
('ABM - Grade 11 Fees', 'Senior High School', 'Grade 11', 'ABM', 15000, 2500, 500, 0, 1000, 19000),
('ABM - Grade 12 Fees', 'Senior High School', 'Grade 12', 'ABM', 15000, 2500, 500, 0, 1000, 19000),
('HUMSS - Grade 11 Fees', 'Senior High School', 'Grade 11', 'HUMSS', 15000, 2500, 500, 0, 1000, 19000),
('HUMSS - Grade 12 Fees', 'Senior High School', 'Grade 12', 'HUMSS', 15000, 2500, 500, 0, 1000, 19000),
('TVL - Grade 11 Fees', 'Senior High School', 'Grade 11', 'TVL', 15000, 2500, 500, 1500, 1000, 20500),
('TVL - Grade 12 Fees', 'Senior High School', 'Grade 12', 'TVL', 15000, 2500, 500, 1500, 1000, 20500),
('GAS - Grade 11 Fees', 'Senior High School', 'Grade 11', 'GAS', 15000, 2500, 500, 0, 1000, 19000),
('GAS - Grade 12 Fees', 'Senior High School', 'Grade 12', 'GAS', 15000, 2500, 500, 0, 1000, 19000),
('BSIT - 1st Year Fees', 'College', '1st Year', 'BSIT', 20000, 3500, 1000, 3000, 1500, 29000),
('BSIT - 2nd Year Fees', 'College', '2nd Year', 'BSIT', 20000, 3500, 1000, 3000, 1500, 29000),
('BSIT - 3rd Year Fees', 'College', '3rd Year', 'BSIT', 20000, 3500, 1000, 3000, 1500, 29000),
('BSIT - 4th Year Fees', 'College', '4th Year', 'BSIT', 20000, 3500, 1000, 3000, 1500, 29000),
('BSCS - 1st Year Fees', 'College', '1st Year', 'BSCS', 20000, 3500, 1000, 3000, 1500, 29000),
('BSCS - 2nd Year Fees', 'College', '2nd Year', 'BSCS', 20000, 3500, 1000, 3000, 1500, 29000),
('BSCS - 3rd Year Fees', 'College', '3rd Year', 'BSCS', 20000, 3500, 1000, 3000, 1500, 29000),
('BSCS - 4th Year Fees', 'College', '4th Year', 'BSCS', 20000, 3500, 1000, 3000, 1500, 29000),
('BSA - 1st Year Fees', 'College', '1st Year', 'BSA', 20000, 3500, 1000, 0, 1500, 26000),
('BSA - 2nd Year Fees', 'College', '2nd Year', 'BSA', 20000, 3500, 1000, 0, 1500, 26000),
('BSA - 3rd Year Fees', 'College', '3rd Year', 'BSA', 20000, 3500, 1000, 0, 1500, 26000),
('BSA - 4th Year Fees', 'College', '4th Year', 'BSA', 20000, 3500, 1000, 0, 1500, 26000),
('BSED - 1st Year Fees', 'College', '1st Year', 'BSED', 20000, 3500, 1000, 0, 1500, 26000),
('BSED - 2nd Year Fees', 'College', '2nd Year', 'BSED', 20000, 3500, 1000, 0, 1500, 26000),
('BSED - 3rd Year Fees', 'College', '3rd Year', 'BSED', 20000, 3500, 1000, 0, 1500, 26000),
('BSED - 4th Year Fees', 'College', '4th Year', 'BSED', 20000, 3500, 1000, 0, 1500, 26000),
('BSBA - 1st Year Fees', 'College', '1st Year', 'BSBA', 20000, 3500, 1000, 0, 1500, 26000),
('BSBA - 2nd Year Fees', 'College', '2nd Year', 'BSBA', 20000, 3500, 1000, 0, 1500, 26000),
('BSBA - 3rd Year Fees', 'College', '3rd Year', 'BSBA', 20000, 3500, 1000, 0, 1500, 26000),
('BSBA - 4th Year Fees', 'College', '4th Year', 'BSBA', 20000, 3500, 1000, 0, 1500, 26000)
ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount);
