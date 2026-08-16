CREATE OR REPLACE VIEW student_academic_records_view AS
SELECT
    u.id AS user_id,
    u.student_number,
    u.first_name,
    u.last_name,
    a.id AS application_id,
    a.academic_level,
    a.grade_level,
    a.school_year,
    a.semester,
    a.strand,
    s.id AS subject_id,
    s.subject_code,
    s.subject_name,
    s.units,
    'College' AS enrollment_type,
    ce.created_at AS enrolled_at
FROM college_enrollments ce
JOIN applications a ON ce.application_id = a.id
JOIN users u ON a.user_id = u.id
JOIN subjects s ON ce.subject_id = s.id

UNION ALL

SELECT
    u.id AS user_id,
    u.student_number,
    u.first_name,
    u.last_name,
    a.id AS application_id,
    a.academic_level,
    a.grade_level,
    a.school_year,
    a.semester,
    a.strand,
    s.id AS subject_id,
    s.subject_code,
    s.subject_name,
    s.units,
    'SHS' AS enrollment_type,
    se.created_at AS enrolled_at
FROM shs_enrollments se
JOIN applications a ON se.application_id = a.id
JOIN users u ON a.user_id = u.id
JOIN subjects s ON se.subject_id = s.id;
