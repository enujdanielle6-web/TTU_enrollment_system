<?php
require 'config/database.php';
// Set CC101 (Intro to Computing) instructor to 'Prof. Smith'
$stmt = $pdo->query("SELECT id FROM subjects WHERE subject_code = 'CC101' LIMIT 1");
$subjectId = $stmt->fetchColumn();

if ($subjectId) {
    // Find the section subject mapping
    $stmt = $pdo->prepare("UPDATE college_section_subjects SET instructor = 'Prof. Smith' WHERE subject_id = ? LIMIT 1");
    $stmt->execute([$subjectId]);
    echo "Updated instructor for CC101 to Prof. Smith!\n";
} else {
    echo "Subject CC101 not found.\n";
}
