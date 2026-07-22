<?php
require_once __DIR__ . '/config/database.php';

$shsData = [
    'career_opportunities' => 'Great foundation for college degrees. Opportunities in internships and junior roles.',
    'possible_professions' => 'Depending on track: Junior Developer, Accounting Clerk, Junior Designer.',
    'industry_information' => 'The SHS track prepares students for higher education and immediate technical employment.'
];

$collegeData = [
    'career_opportunities' => 'High-demand career paths across technology, business, and healthcare.',
    'possible_professions' => 'Software Engineer, CPA, Registered Nurse, Project Manager.',
    'industry_information' => 'Rapidly growing industries with continuous demand for skilled professionals globally.'
];

try {
    $pdo->prepare('UPDATE shs_strands SET career_opportunities = ?, possible_professions = ?, industry_information = ?')
        ->execute([$shsData['career_opportunities'], $shsData['possible_professions'], $shsData['industry_information']]);

    $pdo->prepare('UPDATE college_programs SET career_opportunities = ?, possible_professions = ?, industry_information = ?')
        ->execute([$collegeData['career_opportunities'], $collegeData['possible_professions'], $collegeData['industry_information']]);
    
    echo "Seeding successful.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
