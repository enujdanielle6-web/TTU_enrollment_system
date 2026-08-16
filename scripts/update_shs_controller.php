<?php
$file = 'app/Controllers/Admin/ShsController.php';
$content = file_get_contents($file);

// Replace curriculum()
$curriculumNew = <<<'EOD'
    public function curriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $pageTitle = 'SHS Curriculum - Admin Portal';

        $curriculaData = [];
        try {
            $stmt = $pdo->query('
                SELECT 
                    c.id as curriculum_id,
                    c.strand_id,
                    c.curriculum_name,
                    c.version,
                    c.effective_academic_year,
                    c.status,
                    p.code as strand_code, 
                    p.name as strand_name,
                    COUNT(cs.id) as total_subjects,
                    COALESCE(SUM(s.units), 0) as total_units
                FROM shs_curricula c
                INNER JOIN shs_strands p ON c.strand_id = p.id
                LEFT JOIN shs_curriculum_subjects cs ON cs.curriculum_id = c.id
                LEFT JOIN subjects s ON cs.subject_id = s.id
                GROUP BY c.id, c.strand_id, c.curriculum_name, c.version, c.effective_academic_year, c.status, p.code, p.name
                ORDER BY p.code ASC, c.version DESC
            ');
            $curriculaData = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Failed to fetch shs_curricula: ' . $e->getMessage());
        }

        $activeStrands = [];
        try {
            $activeStrands = $pdo->query('SELECT id, code, name FROM shs_strands WHERE is_active = 1 ORDER BY code ASC')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/registrar/shs_curriculum', get_defined_vars());
    }
EOD;

$content = preg_replace('/public function curriculum\(.*?\n\s+return \$this->render\(\'admin\/registrar\/shs_curriculum\', get_defined_vars\(\)\);\n\s+}/s', $curriculumNew, $content);

// Replace curriculumBuilder()
$builderNew = <<<'EOD'
    public function curriculumBuilder(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        $curriculumId = (int)($_GET['curriculum_id'] ?? 0);
        if ($curriculumId <= 0) {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT c.*, p.code as strand_code, p.name as strand_name 
                FROM shs_curricula c 
                INNER JOIN shs_strands p ON c.strand_id = p.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$curriculumId]);
            $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching curriculum: " . $e->getMessage());
        }

        if (!$curriculum) {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        $pageTitle = htmlspecialchars($curriculum['strand_code'] . ' v' . $curriculum['version']) . ' - SHS Curriculum Builder';

        $subjects = [
            'Grade 11' => [ 'First' => [], 'Second' => [] ],
            'Grade 12' => [ 'First' => [], 'Second' => [] ]
        ];

        $totalUnits = 0;
        $totalSubjects = 0;
        $lectureUnits = 0;
        $labUnits = 0;

        try {
            $stmt = $pdo->prepare("
                SELECT cs.id as mapping_id, cs.grade_level, cs.semester,
                       s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
                FROM shs_curriculum_subjects cs
                INNER JOIN subjects s ON cs.subject_id = s.id
                WHERE cs.curriculum_id = ?
                ORDER BY cs.grade_level ASC, cs.semester ASC, s.subject_code ASC
            ");
            $stmt->execute([$curriculumId]);
            $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subjectsRaw as $row) {
                $gl = $row['grade_level'];
                $sem = $row['semester'];
                
                if (!isset($subjects[$gl])) $subjects[$gl] = [];
                if (!isset($subjects[$gl][$sem])) $subjects[$gl][$sem] = [];
                
                $subjects[$gl][$sem][] = $row;
                $totalUnits += (int)$row['units'];
                $totalSubjects++;
                
                if (stripos((string)$row['subject_type'], 'lab') !== false) {
                    $labUnits += (int)$row['units'];
                } else {
                    $lectureUnits += (int)$row['units'];
                }
            }
        } catch (PDOException $e) {
            die("Error fetching subjects: " . $e->getMessage());
        }

        $globalSubjects = [];
        try {
            $gstmt = $pdo->query("SELECT id, subject_code, subject_name, units, subject_type FROM subjects WHERE status = 1 AND education_level IN ('SHS', 'Both') ORDER BY subject_code ASC");
            $globalSubjects = $gstmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/registrar/shs_curriculum_builder', get_defined_vars());
    }
EOD;

$content = preg_replace('/public function curriculumBuilder\(.*?\n\s+return \$this->render\(\'admin\/registrar\/shs_curriculum_builder\', get_defined_vars\(\)\);\n\s+}/s', $builderNew, $content);

// Replace processCurriculum()
$processNew = <<<'EOD'
    public function processCurriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'create_curriculum') {
            $strandId = (int)($_POST['strand_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $year = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($strandId <= 0 || $name === '' || $version === '') {
                $_SESSION['error_msg'] = 'Strand, Curriculum Name, and Version are required.';
                $response->redirect("/sia/admin/registrar/shs_curriculum.php");
                return;
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO shs_curricula (strand_id, curriculum_name, version, effective_academic_year, description, status) VALUES (?, ?, ?, ?, ?, "active")');
                $stmt->execute([$strandId, $name, $version, $year, $desc]);
                $_SESSION['success_msg'] = 'Curriculum created successfully.';
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Error creating curriculum: ' . $e->getMessage();
            }
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        if ($action === 'add') {
            $curriculumId = (int) ($_POST['curriculum_id'] ?? 0);
            $gradeLevel = trim($_POST['grade_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $subjectIds = $_POST['subject_ids'] ?? [];

            if ($curriculumId <= 0 || $gradeLevel === '' || $semester === '') {
                $_SESSION['error_msg'] = 'Curriculum, Grade Level, and Semester are required.';
                $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$curriculumId");
                return;
            }

            if (!is_array($subjectIds)) {
                $subjectIds = [$subjectIds];
            }

            $added = 0;
            $duplicates = 0;

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO shs_curriculum_subjects (curriculum_id, grade_level, semester, subject_id) VALUES (:curriculum, :gl, :sem, :subject)');
                
                foreach ($subjectIds as $subId) {
                    $subId = (int) $subId;
                    if ($subId <= 0) continue;

                    try {
                        $stmt->execute(['curriculum' => $curriculumId, 'gl' => $gradeLevel, 'sem' => $semester, 'subject' => $subId]);
                        $added++;
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $duplicates++;
                        } else {
                            throw $e;
                        }
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_msg'] = 'An error occurred while compiling curriculum: ' . $e->getMessage();
            }

            if ($added > 0) {
                $_SESSION['success_msg'] = "$added subject(s) added successfully." . ($duplicates > 0 ? " ($duplicates duplicates ignored)" : "");
            } else if ($duplicates > 0) {
                $_SESSION['error_msg'] = 'All selected subjects are already assigned to this semester.';
            } else if (empty($_SESSION['error_msg'])) {
                $_SESSION['error_msg'] = 'No subjects were selected or failed to add.';
            }
            
            $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$curriculumId");
            return;

        } elseif ($action === 'delete_subject') {
            $curriculumId = (int) ($_POST['curriculum_id'] ?? 0);
            $mappingId = (int) ($_POST['mapping_id'] ?? 0);

            if ($mappingId <= 0) {
                $_SESSION['error_msg'] = 'Invalid curriculum mapping ID.';
            } else {
                try {
                    $stmt = $pdo->prepare('DELETE FROM shs_curriculum_subjects WHERE id = :id');
                    $stmt->execute(['id' => $mappingId]);
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['success_msg'] = 'Subject successfully removed from the curriculum.';
                    } else {
                        $_SESSION['error_msg'] = 'Failed to remove subject or it was already removed.';
                    }
                } catch (PDOException $e) {
                    $_SESSION['error_msg'] = 'Error removing subject: ' . $e->getMessage();
                }
            }
            $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$curriculumId");
            return;
        }

        $_SESSION['error_msg'] = 'Invalid action.';
        $response->redirect("/sia/admin/registrar/shs_curriculum.php");
        return;
    }
EOD;

$content = preg_replace('/public function processCurriculum\(.*?\n\s+return;\n\s+}\n}/s', $processNew . "\n}", $content);

file_put_contents($file, $content);
echo "ShsController updated successfully.\n";
