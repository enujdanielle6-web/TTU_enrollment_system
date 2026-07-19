<?php

/**
 * Script to seed official Philippine curriculum subjects and mappings.
 * Run this via CLI: php seed_official_curriculum.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

echo "Starting curriculum seeding...\n";

try {
    $pdo->beginTransaction();

    // 1. CLEAR EXISTING DATA (To avoid duplicates if run multiple times)
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE curriculum');
    $pdo->exec('TRUNCATE TABLE subjects');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "Cleared old curriculum and subject data.\n";

    // 2. DEFINE SUBJECTS
    // We will define a massive list of realistic subjects.
    // Format: [Code, Name, Units, Description]
    $allSubjects = [
        // SHS CORE (Common)
        ['SHS-CORE-1', 'Oral Communication in Context', 3, 'Core Subject'],
        ['SHS-CORE-2', 'Reading and Writing Skills', 3, 'Core Subject'],
        ['SHS-CORE-3', 'Komunikasyon at Pananaliksik sa Wika at Kulturang Filipino', 3, 'Core Subject'],
        ['SHS-CORE-4', 'Pagbasa at Pagsusuri ng Iba\'t-Ibang Teksto', 3, 'Core Subject'],
        ['SHS-CORE-5', '21st Century Literature from the Philippines and the World', 3, 'Core Subject'],
        ['SHS-CORE-6', 'Contemporary Philippine Arts from the Regions', 3, 'Core Subject'],
        ['SHS-CORE-7', 'Media and Information Literacy', 3, 'Core Subject'],
        ['SHS-CORE-8', 'General Mathematics', 3, 'Core Subject'],
        ['SHS-CORE-9', 'Statistics and Probability', 3, 'Core Subject'],
        ['SHS-CORE-10', 'Earth and Life Science', 3, 'Core Subject'],
        ['SHS-CORE-11', 'Physical Science', 3, 'Core Subject'],
        ['SHS-CORE-12', 'Personal Development', 3, 'Core Subject'],
        ['SHS-CORE-13', 'Understanding Culture, Society and Politics', 3, 'Core Subject'],
        ['SHS-CORE-14', 'Introduction to the Philosophy of the Human Person', 3, 'Core Subject'],
        ['SHS-CORE-15', 'Physical Education and Health', 3, 'Core Subject'],

        // SHS APPLIED (Common)
        ['SHS-APP-1', 'English for Academic and Professional Purposes', 3, 'Applied Subject'],
        ['SHS-APP-2', 'Practical Research 1', 3, 'Applied Subject'],
        ['SHS-APP-3', 'Practical Research 2', 3, 'Applied Subject'],
        ['SHS-APP-4', 'Empowerment Technologies', 3, 'Applied Subject'],
        ['SHS-APP-5', 'Entrepreneurship', 3, 'Applied Subject'],
        ['SHS-APP-6', 'Filipino sa Piling Larang', 3, 'Applied Subject'],

        // SHS SPECIALIZED - STEM
        ['STEM-1', 'Pre-Calculus', 3, 'Specialized Subject'],
        ['STEM-2', 'Basic Calculus', 3, 'Specialized Subject'],
        ['STEM-3', 'General Biology 1', 3, 'Specialized Subject'],
        ['STEM-4', 'General Biology 2', 3, 'Specialized Subject'],
        ['STEM-5', 'General Physics 1', 3, 'Specialized Subject'],
        ['STEM-6', 'General Physics 2', 3, 'Specialized Subject'],
        ['STEM-7', 'General Chemistry 1', 3, 'Specialized Subject'],
        ['STEM-8', 'General Chemistry 2', 3, 'Specialized Subject'],
        ['STEM-9', 'Work Immersion/Research/Career Advocacy/Culminating Activity', 3, 'Specialized Subject'],

        // SHS SPECIALIZED - ABM
        ['ABM-1', 'Applied Economics', 3, 'Specialized Subject'],
        ['ABM-2', 'Business Ethics and Social Responsibility', 3, 'Specialized Subject'],
        ['ABM-3', 'Fundamentals of Accountancy, Business and Management 1', 3, 'Specialized Subject'],
        ['ABM-4', 'Fundamentals of Accountancy, Business and Management 2', 3, 'Specialized Subject'],
        ['ABM-5', 'Business Math', 3, 'Specialized Subject'],
        ['ABM-6', 'Business Finance', 3, 'Specialized Subject'],
        ['ABM-7', 'Organization and Management', 3, 'Specialized Subject'],
        ['ABM-8', 'Principles of Marketing', 3, 'Specialized Subject'],
        ['ABM-9', 'Work Immersion/Research/Career Advocacy/Culminating Activity', 3, 'Specialized Subject'],

        // SHS SPECIALIZED - HUMSS
        ['HUMSS-1', 'Creative Writing', 3, 'Specialized Subject'],
        ['HUMSS-2', 'Introduction to World Religions and Belief Systems', 3, 'Specialized Subject'],
        ['HUMSS-3', 'Creative Nonfiction', 3, 'Specialized Subject'],
        ['HUMSS-4', 'Trends, Networks, and Critical Thinking in the 21st Century', 3, 'Specialized Subject'],
        ['HUMSS-5', 'Philippine Politics and Governance', 3, 'Specialized Subject'],
        ['HUMSS-6', 'Community Engagement, Solidarity, and Citizenship', 3, 'Specialized Subject'],
        ['HUMSS-7', 'Disciplines and Ideas in the Social Sciences', 3, 'Specialized Subject'],
        ['HUMSS-8', 'Disciplines and Ideas in the Applied Social Sciences', 3, 'Specialized Subject'],
        ['HUMSS-9', 'Work Immersion/Research/Career Advocacy/Culminating Activity', 3, 'Specialized Subject'],

        // SHS SPECIALIZED - TVL
        ['TVL-1', 'Computer Systems Servicing NC II (Part 1)', 3, 'Specialized Subject'],
        ['TVL-2', 'Computer Systems Servicing NC II (Part 2)', 3, 'Specialized Subject'],
        ['TVL-3', 'Computer Systems Servicing NC II (Part 3)', 3, 'Specialized Subject'],
        ['TVL-4', 'Computer Systems Servicing NC II (Part 4)', 3, 'Specialized Subject'],

        // SHS SPECIALIZED - GAS
        ['GAS-1', 'Humanities 1', 3, 'Specialized Subject'],
        ['GAS-2', 'Humanities 2', 3, 'Specialized Subject'],
        ['GAS-3', 'Social Science 1', 3, 'Specialized Subject'],
        ['GAS-4', 'Applied Economics', 3, 'Specialized Subject'],
        ['GAS-5', 'Organization and Management', 3, 'Specialized Subject'],
        ['GAS-6', 'Disaster Readiness and Risk Reduction', 3, 'Specialized Subject'],


        // COLLEGE GENERAL EDUCATION
        ['GE-1', 'Understanding the Self', 3, 'GE Course'],
        ['GE-2', 'Readings in Philippine History', 3, 'GE Course'],
        ['GE-3', 'The Contemporary World', 3, 'GE Course'],
        ['GE-4', 'Mathematics in the Modern World', 3, 'GE Course'],
        ['GE-5', 'Purposive Communication', 3, 'GE Course'],
        ['GE-6', 'Art Appreciation', 3, 'GE Course'],
        ['GE-7', 'Science, Technology, and Society', 3, 'GE Course'],
        ['GE-8', 'Ethics', 3, 'GE Course'],
        ['GE-9', 'The Life and Works of Rizal', 3, 'GE Course'],
        ['PE-1', 'Physical Education 1', 2, 'GE Course'],
        ['PE-2', 'Physical Education 2', 2, 'GE Course'],
        ['PE-3', 'Physical Education 3', 2, 'GE Course'],
        ['PE-4', 'Physical Education 4', 2, 'GE Course'],
        ['NSTP-1', 'National Service Training Program 1', 3, 'GE Course'],
        ['NSTP-2', 'National Service Training Program 2', 3, 'GE Course'],

        // COLLEGE BSIT/BSCS COMMON
        ['IT-101', 'Introduction to Computing', 3, 'IT/CS Core'],
        ['IT-102', 'Computer Programming 1', 3, 'IT/CS Core'],
        ['IT-103', 'Computer Programming 2', 3, 'IT/CS Core'],
        ['IT-201', 'Data Structures and Algorithms', 3, 'IT/CS Core'],
        ['IT-202', 'Information Management', 3, 'IT/CS Core'],
        
        // BSIT SPECIFIC
        ['IT-301', 'Networking 1', 3, 'IT Professional'],
        ['IT-302', 'Networking 2', 3, 'IT Professional'],
        ['IT-303', 'Web Systems and Technologies', 3, 'IT Professional'],
        ['IT-304', 'Systems Integration and Architecture', 3, 'IT Professional'],
        ['IT-401', 'IT Capstone Project 1', 3, 'IT Professional'],
        ['IT-402', 'IT Capstone Project 2', 3, 'IT Professional'],
        ['IT-403', 'Practicum / OJT', 6, 'IT Professional'],

        // BSCS SPECIFIC
        ['CS-301', 'Discrete Structures 1', 3, 'CS Professional'],
        ['CS-302', 'Discrete Structures 2', 3, 'CS Professional'],
        ['CS-303', 'Object-Oriented Programming', 3, 'CS Professional'],
        ['CS-304', 'Algorithms and Complexity', 3, 'CS Professional'],
        ['CS-305', 'Automata Theory and Formal Languages', 3, 'CS Professional'],
        ['CS-306', 'Artificial Intelligence', 3, 'CS Professional'],
        ['CS-401', 'Software Engineering 1', 3, 'CS Professional'],
        ['CS-402', 'Software Engineering 2', 3, 'CS Professional'],
        ['CS-403', 'CS Thesis 1', 3, 'CS Professional'],
        ['CS-404', 'CS Thesis 2', 3, 'CS Professional'],

        // COLLEGE BSA (Accountancy)
        ['BSA-101', 'Financial Accounting and Reporting', 6, 'BSA Core'],
        ['BSA-102', 'Conceptual Framework and Accounting Standards', 3, 'BSA Core'],
        ['BSA-201', 'Intermediate Accounting 1', 3, 'BSA Core'],
        ['BSA-202', 'Intermediate Accounting 2', 3, 'BSA Core'],
        ['BSA-203', 'Cost Accounting and Control', 3, 'BSA Core'],
        ['BSA-301', 'Intermediate Accounting 3', 3, 'BSA Professional'],
        ['BSA-302', 'Income Taxation', 3, 'BSA Professional'],
        ['BSA-303', 'Auditing and Assurance Principles', 3, 'BSA Professional'],
        ['BSA-401', 'Auditing and Assurance: Concepts and Applications', 6, 'BSA Professional'],
        ['BSA-402', 'Accounting Information System', 3, 'BSA Professional'],

        // COLLEGE BSED (Education)
        ['BSED-101', 'The Child and Adolescent Learners', 3, 'BSED Core'],
        ['BSED-102', 'The Teaching Profession', 3, 'BSED Core'],
        ['BSED-201', 'Facilitating Learner-Centered Teaching', 3, 'BSED Core'],
        ['BSED-202', 'Assessment of Learning 1', 3, 'BSED Core'],
        ['BSED-301', 'Assessment of Learning 2', 3, 'BSED Core'],
        ['BSED-302', 'Building and Enhancing New Literacies', 3, 'BSED Professional'],
        ['BSED-401', 'Field Study 1', 3, 'BSED Professional'],
        ['BSED-402', 'Field Study 2', 3, 'BSED Professional'],
        ['BSED-403', 'Teaching Internship', 6, 'BSED Professional'],

        // COLLEGE BSBA (Business Admin)
        ['BSBA-101', 'Basic Microeconomics', 3, 'BSBA Core'],
        ['BSBA-102', 'Law on Obligations and Contracts', 3, 'BSBA Core'],
        ['BSBA-201', 'Good Governance and Social Responsibility', 3, 'BSBA Core'],
        ['BSBA-202', 'Human Resource Management', 3, 'BSBA Professional'],
        ['BSBA-301', 'Financial Management', 3, 'BSBA Professional'],
        ['BSBA-302', 'Marketing Management', 3, 'BSBA Professional'],
        ['BSBA-401', 'Operations Management', 3, 'BSBA Professional'],
        ['BSBA-402', 'Strategic Management', 3, 'BSBA Professional'],
        ['BSBA-403', 'Business Practicum', 6, 'BSBA Professional']
    ];

    $stmtInsertSub = $pdo->prepare('INSERT INTO subjects (subject_code, subject_name, units, description, status) VALUES (?, ?, ?, ?, 1)');
    $subjectIdMap = []; // code => id

    foreach ($allSubjects as $sub) {
        $stmtInsertSub->execute($sub);
        $subjectIdMap[$sub[0]] = $pdo->lastInsertId();
    }
    echo "Inserted " . count($allSubjects) . " subjects.\n";

    // 3. FETCH ACADEMIC PROGRAMS
    $stmtProgs = $pdo->query('SELECT id, code, category FROM academic_programs WHERE is_active = 1');
    $programs = $stmtProgs->fetchAll(PDO::FETCH_ASSOC);

    $stmtInsertCurr = $pdo->prepare('INSERT INTO curriculum (program_id, year_level, semester, subject_id) VALUES (?, ?, ?, ?)');
    $currCount = 0;

    foreach ($programs as $prog) {
        $pId = $prog['id'];
        $code = strtoupper($prog['code']);
        
        if ($prog['category'] === 'Senior High School') {
            // GRADE 11 & 12
            // Since sections.php disables semester for SHS, we will use semester = NULL (or empty string)
            
            // GRADE 11 SUBJECTS
            $g11Codes = [
                'SHS-CORE-1', 'SHS-CORE-2', 'SHS-CORE-3', 'SHS-CORE-8', 'SHS-CORE-9', 'SHS-CORE-12', 'SHS-CORE-15',
                'SHS-APP-1', 'SHS-APP-2', 'SHS-APP-6'
            ];
            // GRADE 12 SUBJECTS
            $g12Codes = [
                'SHS-CORE-4', 'SHS-CORE-5', 'SHS-CORE-6', 'SHS-CORE-7', 'SHS-CORE-10', 'SHS-CORE-11', 'SHS-CORE-13', 'SHS-CORE-14',
                'SHS-APP-3', 'SHS-APP-4', 'SHS-APP-5'
            ];

            // Add Specialized Subjects
            if ($code === 'STEM') {
                array_push($g11Codes, 'STEM-1', 'STEM-2', 'STEM-3', 'STEM-5');
                array_push($g12Codes, 'STEM-4', 'STEM-6', 'STEM-7', 'STEM-8', 'STEM-9');
            } elseif ($code === 'ABM') {
                array_push($g11Codes, 'ABM-1', 'ABM-3', 'ABM-5', 'ABM-7');
                array_push($g12Codes, 'ABM-2', 'ABM-4', 'ABM-6', 'ABM-8', 'ABM-9');
            } elseif ($code === 'HUMSS') {
                array_push($g11Codes, 'HUMSS-1', 'HUMSS-2', 'HUMSS-3', 'HUMSS-5');
                array_push($g12Codes, 'HUMSS-4', 'HUMSS-6', 'HUMSS-7', 'HUMSS-8', 'HUMSS-9');
            } elseif ($code === 'TVL') {
                array_push($g11Codes, 'TVL-1', 'TVL-2');
                array_push($g12Codes, 'TVL-3', 'TVL-4');
            } elseif ($code === 'GAS') {
                array_push($g11Codes, 'GAS-1', 'GAS-3', 'GAS-5');
                array_push($g12Codes, 'GAS-2', 'GAS-4', 'GAS-6');
            }

            foreach ($g11Codes as $c) {
                if (isset($subjectIdMap[$c])) {
                    $stmtInsertCurr->execute([$pId, 'Grade 11', null, $subjectIdMap[$c]]);
                    $currCount++;
                }
            }
            foreach ($g12Codes as $c) {
                if (isset($subjectIdMap[$c])) {
                    $stmtInsertCurr->execute([$pId, 'Grade 12', null, $subjectIdMap[$c]]);
                    $currCount++;
                }
            }

        } elseif ($prog['category'] === 'College') {
            // 1st Year to 4th Year (First / Second Semesters)
            
            // 1ST YEAR - FIRST SEMESTER
            $y1s1 = ['GE-1', 'GE-4', 'GE-5', 'PE-1', 'NSTP-1'];
            // 1ST YEAR - SECOND SEMESTER
            $y1s2 = ['GE-2', 'GE-6', 'GE-7', 'PE-2', 'NSTP-2'];
            // 2ND YEAR - FIRST SEMESTER
            $y2s1 = ['GE-3', 'GE-8', 'PE-3'];
            // 2ND YEAR - SECOND SEMESTER
            $y2s2 = ['GE-9', 'PE-4'];
            
            // 3RD YEAR - FIRST SEMESTER
            $y3s1 = [];
            // 3RD YEAR - SECOND SEMESTER
            $y3s2 = [];
            
            // 4TH YEAR - FIRST SEMESTER
            $y4s1 = [];
            // 4TH YEAR - SECOND SEMESTER
            $y4s2 = [];

            if ($code === 'BSIT' || $code === 'BSCS') {
                array_push($y1s1, 'IT-101', 'IT-102');
                array_push($y1s2, 'IT-103', 'IT-201');
                
                if ($code === 'BSIT') {
                    array_push($y2s1, 'IT-202', 'IT-301');
                    array_push($y2s2, 'IT-302', 'IT-303');
                    array_push($y3s1, 'IT-304');
                    array_push($y4s1, 'IT-401');
                    array_push($y4s2, 'IT-402', 'IT-403');
                } else { // BSCS
                    array_push($y2s1, 'CS-301', 'CS-303');
                    array_push($y2s2, 'CS-302', 'CS-304');
                    array_push($y3s1, 'CS-305');
                    array_push($y3s2, 'CS-306');
                    array_push($y4s1, 'CS-401', 'CS-403');
                    array_push($y4s2, 'CS-402', 'CS-404');
                }
            } elseif ($code === 'BSA') {
                array_push($y1s1, 'BSA-101');
                array_push($y1s2, 'BSA-102', 'BSA-201');
                array_push($y2s1, 'BSA-202', 'BSA-203');
                array_push($y2s2, 'BSA-301');
                array_push($y3s1, 'BSA-302', 'BSA-303');
                array_push($y4s1, 'BSA-401');
                array_push($y4s2, 'BSA-402');
            } elseif ($code === 'BSED') {
                array_push($y1s1, 'BSED-101', 'BSED-102');
                array_push($y1s2, 'BSED-201', 'BSED-202');
                array_push($y2s1, 'BSED-301');
                array_push($y2s2, 'BSED-302');
                array_push($y3s1, 'BSED-401');
                array_push($y3s2, 'BSED-402');
                array_push($y4s2, 'BSED-403'); // Internship
            } elseif ($code === 'BSBA') {
                array_push($y1s1, 'BSBA-101');
                array_push($y1s2, 'BSBA-102');
                array_push($y2s1, 'BSBA-201');
                array_push($y2s2, 'BSBA-202');
                array_push($y3s1, 'BSBA-301');
                array_push($y3s2, 'BSBA-302');
                array_push($y4s1, 'BSBA-401');
                array_push($y4s2, 'BSBA-402', 'BSBA-403');
            }

            $collegeMappings = [
                ['1st Year', 'First', $y1s1],
                ['1st Year', 'Second', $y1s2],
                ['2nd Year', 'First', $y2s1],
                ['2nd Year', 'Second', $y2s2],
                ['3rd Year', 'First', $y3s1],
                ['3rd Year', 'Second', $y3s2],
                ['4th Year', 'First', $y4s1],
                ['4th Year', 'Second', $y4s2]
            ];

            foreach ($collegeMappings as $map) {
                $y = $map[0];
                $s = $map[1];
                $subjects = $map[2];
                foreach ($subjects as $c) {
                    if (isset($subjectIdMap[$c])) {
                        $stmtInsertCurr->execute([$pId, $y, $s, $subjectIdMap[$c]]);
                        $currCount++;
                    }
                }
            }
        }
    }

    echo "Inserted $currCount curriculum mappings.\n";
    $pdo->commit();
    echo "SUCCESS: Database seeded!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
