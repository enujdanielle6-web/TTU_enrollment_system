<?php
$file = 'app/Controllers/Admin/SchedulerController.php';
$content = file_get_contents($file);

// 1. Update shs_sections INSERT
$searchAddSection = <<<'EOD'
        $section_code = trim($_POST['section_code'] ?? '');
        $strand_id = (int)($_POST['strand_id'] ?? 0);
        $grade_level = trim($_POST['grade_level'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $strand_id && $grade_level && $academic_year) {
            try {
                $stmt = $pdo->prepare('INSERT INTO shs_sections (section_code, strand_id, grade_level, academic_year, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $strand_id, $grade_level, $academic_year, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to shs_section_subjects
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM shs_curriculum 
                    WHERE strand_id = ? AND grade_level = ? 
                ');
                $currStmt->execute([$strand_id, $grade_level]);
EOD;

$replaceAddSection = <<<'EOD'
        $section_code = trim($_POST['section_code'] ?? '');
        $strand_id = (int)($_POST['strand_id'] ?? 0);
        $curriculum_id = (int)($_POST['curriculum_id'] ?? 0);
        $grade_level = trim($_POST['grade_level'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $strand_id && $curriculum_id && $grade_level && $academic_year) {
            try {
                $stmt = $pdo->prepare('INSERT INTO shs_sections (section_code, strand_id, curriculum_id, grade_level, academic_year, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $strand_id, $curriculum_id, $grade_level, $academic_year, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to shs_section_subjects
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM shs_curriculum_subjects 
                    WHERE curriculum_id = ? AND grade_level = ? 
                ');
                $currStmt->execute([$curriculum_id, $grade_level]);
EOD;

$content = str_replace($searchAddSection, $replaceAddSection, $content);

// 2. Update SHS Builder Logic
$searchBuilder = <<<'EOD'
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "Senior High School" as category, s.grade_level as year_level, s.strand_id as program_id
            FROM shs_sections s 
            JOIN shs_strands p ON s.strand_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            $response->redirect("/sia/admin/scheduler/shs_sections.php");
            return;
        }

        // Auto-sync missing subjects from SHS Curriculum to Section Subjects
        $syncStmt = $pdo->prepare('
            INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time)
            SELECT ?, c.subject_id, ?, "TBA", "00:00:00", "00:00:00"
            FROM shs_curriculum c
            WHERE c.strand_id = ? AND c.grade_level = ?
              AND NOT EXISTS (
                  SELECT 1 FROM shs_section_subjects ss 
                  WHERE ss.shs_section_id = ? AND ss.subject_id = c.subject_id
              )
        ');
        $syncStmt->execute([$sectionId, $section['capacity'], $section['program_id'], $section['year_level'], $sectionId]);

        // Auto-remove subjects that are no longer in Curriculum
        $delStmt = $pdo->prepare('
            DELETE FROM shs_section_subjects 
            WHERE shs_section_id = ? 
              AND subject_id NOT IN (
                  SELECT subject_id FROM shs_curriculum 
                  WHERE strand_id = ? AND grade_level = ?
              )
        ');
        $delStmt->execute([$sectionId, $section['program_id'], $section['year_level']]);

        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, c.semester
            FROM shs_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN shs_curriculum c ON c.subject_id = ss.subject_id AND c.strand_id = ? AND c.grade_level = ?
            WHERE ss.shs_section_id = ?
            ORDER BY c.semester ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['program_id'], $section['year_level'], $sectionId]);
EOD;

$replaceBuilder = <<<'EOD'
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "Senior High School" as category, s.grade_level as year_level, s.strand_id as program_id, s.curriculum_id
            FROM shs_sections s 
            JOIN shs_strands p ON s.strand_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            $response->redirect("/sia/admin/scheduler/shs_sections.php");
            return;
        }

        if ($section['curriculum_id']) {
            // Auto-sync missing subjects from SHS Curriculum to Section Subjects
            $syncStmt = $pdo->prepare('
                INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time)
                SELECT ?, c.subject_id, ?, "TBA", "00:00:00", "00:00:00"
                FROM shs_curriculum_subjects c
                WHERE c.curriculum_id = ? AND c.grade_level = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM shs_section_subjects ss 
                      WHERE ss.shs_section_id = ? AND ss.subject_id = c.subject_id
                  )
            ');
            $syncStmt->execute([$sectionId, $section['capacity'], $section['curriculum_id'], $section['year_level'], $sectionId]);

            // Auto-remove subjects that are no longer in Curriculum
            $delStmt = $pdo->prepare('
                DELETE FROM shs_section_subjects 
                WHERE shs_section_id = ? 
                  AND subject_id NOT IN (
                      SELECT subject_id FROM shs_curriculum_subjects 
                      WHERE curriculum_id = ? AND grade_level = ?
                  )
            ');
            $delStmt->execute([$sectionId, $section['curriculum_id'], $section['year_level']]);
        }

        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, c.semester
            FROM shs_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN shs_curriculum_subjects c ON c.subject_id = ss.subject_id AND c.curriculum_id = ? AND c.grade_level = ?
            WHERE ss.shs_section_id = ?
            ORDER BY c.semester ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['curriculum_id'], $section['year_level'], $sectionId]);
EOD;

$content = str_replace($searchBuilder, $replaceBuilder, $content);


file_put_contents($file, $content);
echo "SchedulerController updated successfully.\n";
