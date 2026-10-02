<?php
/**
 * Verification suite for the mixed-type quiz builder (Phase 11).
 *
 * Covers: question validation, text-answer grading policy, CSV import (valid rows,
 * row errors, malformed files), content extraction (.txt/.docx/.pptx, unsupported
 * types), grounded generation, draft review/save through the faculty controller,
 * course ownership checks, mixed-type student attempts, manual review and the
 * gradebook rule that provisional scores are excluded.
 *
 * Needs a live database with database/migrations/lms_phase11_quiz_builder_schema.sql
 * applied and the seed data from database/seed.sql (faculty user 9 owns LMS course 1,
 * student user 11 is enrolled in it). Every record it creates is removed at the end.
 *
 * Usage: php scripts/test_quiz_builder.php
 */
define('TESTING_ENV', true);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Quiz\CourseContentExtractor;
use App\Services\Quiz\ExtractiveQuizGenerator;
use App\Services\Quiz\QuizAnswerGrader;
use App\Services\Quiz\QuizCsvImporter;
use App\Services\Quiz\QuizQuestionValidator;

const FACULTY_ID = 9;        // Ada, owns LMS course 1 in seed.sql
const OTHER_FACULTY_ID = 8;  // Alan, owns LMS course 2
const COURSE_ID = 1;
const STUDENT_ID = 11;       // John, enrolled in course 1

// Child mode: run one forbidden request in a separate process, because the
// controller ends the request with exit() on 403/404.
if (($argv[1] ?? '') === '--child') {
    session_start();
    $_SESSION['csrf_token'] = 't';
    $_SESSION['user_id'] = (int)$argv[3];
    $ctrl = new \App\Controllers\Lms\FacultyQuizController();
    $req = new \App\Core\Request();
    $res = new \App\Core\Response();
    switch ($argv[2]) {
        case 'foreign_review':
            $ctrl->reviewDraft($req, $res, (string)COURSE_ID, $argv[4]);
            break;
        case 'quiz_course_mismatch':
            $ctrl->generateForm($req, $res, '2', $argv[4]);
            break;
        case 'foreign_attempt':
            $ctrl->reviewAttempt($req, $res, (string)COURSE_ID, $argv[4], $argv[5]);
            break;
    }
    echo "\nREACHED_END";
    exit(0);
}

session_start();
$passed = 0;
$failed = 0;
function check(string $label, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✔ {$label}\n";
    } else {
        $failed++;
        echo "  ✘ {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    }
}

function post(\App\Core\Request $req, array $body): void
{
    $_POST = $body + ['csrf_token' => 't'];
    $prop = (new ReflectionClass($req))->getProperty('data');
    $prop->setAccessible(true);
    $prop->setValue($req, $_POST);
}

function makeOfficeFile(string $path, array $entries): void
{
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $xml) {
        $zip->addFromString($name, $xml);
    }
    $zip->close();
}

echo "===============================================\n";
echo "      TTU LMS QUIZ BUILDER VERIFICATION SUITE  \n";
echo "===============================================\n";

// ---------------------------------------------------------------------------
echo "\n[1] Question validation\n";
$v = new QuizQuestionValidator();
[$c, $e] = $v->validate(['type' => 'mc', 'question_text' => 'Pick one', 'choices' => ['A', 'B', '', ''], 'correct_index' => '1', 'points' => '2']);
check('multiple choice alias accepted, trailing empty choices dropped', !$e && $c['type'] === 'multiple_choice' && count($c['choices']) === 2);
[, $e] = $v->validate(['type' => 'multiple_choice', 'question_text' => 'Pick', 'choices' => ['A', 'B'], 'correct_index' => '5']);
check('multiple choice without a valid correct choice rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'multiple_choice', 'question_text' => 'Pick', 'choices' => ['A', '', 'C'], 'correct_index' => '0']);
check('multiple choice with a gap between choices rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'multiple_choice', 'question_text' => 'Pick', 'choices' => ['Same', 'same'], 'correct_index' => '0']);
check('duplicate choices rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'fill_blank', 'question_text' => 'No blank here', 'accepted_answers' => ['x']]);
check('fill in the blank without ___ rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'fill_blank', 'question_text' => '___ and ___', 'accepted_answers' => ['x']]);
check('fill in the blank with two blanks rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'identification', 'question_text' => 'Name it', 'accepted_answers' => []]);
check('identification without accepted answers rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'true_false', 'question_text' => 'Sky is blue', 'correct_tf' => 'maybe']);
check('true/false needs True or False', (bool)$e);
[, $e] = $v->validate(['type' => 'essay', 'question_text' => 'x']);
check('unknown type rejected', (bool)$e);
[, $e] = $v->validate(['type' => 'true_false', 'question_text' => 'x', 'correct_tf' => 'true', 'points' => '0']);
check('zero points rejected', (bool)$e);

// ---------------------------------------------------------------------------
echo "\n[2] Text answer grading policy\n";
$g = new QuizAnswerGrader();
$textQ = fn (array $answers, array $extra = []) => $extra + [
    'id' => 1, 'question_type' => 'identification', 'points' => 2, 'case_sensitive' => 0, 'requires_manual_review' => 0,
    'choices' => array_map(fn ($a) => ['id' => 0, 'choice_text' => $a, 'is_correct' => 1], $answers),
];
$r = $g->grade($textQ(['Central Processing Unit', 'CPU']), '  cpu. ');
check('case, spacing and trailing punctuation ignored', $r['is_correct'] && $r['points'] == 2.0);
$r = $g->grade($textQ(['3']), '3.00');
check('numbers compared by value', $r['is_correct']);
$r = $g->grade($textQ(['photosynthesis']), 'photosynthesys');
check('near miss goes to review with 0 provisional points', !$r['is_correct'] && $r['needs_review'] && $r['points'] == 0.0);
$r = $g->grade($textQ(['photosynthesis']), 'respiration');
check('clearly wrong answer is incorrect without review', !$r['is_correct'] && !$r['needs_review']);
$r = $g->grade($textQ(['photosynthesis'], ['requires_manual_review' => 1]), 'respiration');
check('manual review question sends non-matching answers to review', $r['needs_review']);
$r = $g->grade($textQ(['photosynthesis'], ['requires_manual_review' => 1]), 'Photosynthesis');
check('manual review question still auto-credits exact matches', $r['is_correct'] && !$r['needs_review']);
$r = $g->grade($textQ(['NaCl'], ['case_sensitive' => 1]), 'nacl');
check('case-sensitive mismatch is flagged for review, not credited', !$r['is_correct'] && $r['needs_review']);
$r = $g->grade($textQ(['NaCl'], ['case_sensitive' => 1]), 'NaCl');
check('case-sensitive exact match credited', $r['is_correct']);
$r = $g->grade($textQ(['x']), '   ');
check('blank answer scores 0 without review', !$r['is_correct'] && !$r['needs_review'] && $r['answer_text'] === null);
$mcQ = ['id' => 5, 'question_type' => 'multiple_choice', 'points' => 1, 'choices' => [['id' => 50, 'is_correct' => 0], ['id' => 51, 'is_correct' => 1]]];
check('choice id from another question treated as unanswered', $g->grade($mcQ, '999')['choice_id'] === null);
check('array payload for a choice question treated as unanswered', $g->grade($mcQ, ['51'])['choice_id'] === null);
check('correct choice credited', $g->grade($mcQ, '51')['points'] == 1.0);

// ---------------------------------------------------------------------------
echo "\n[3] CSV import parsing\n";
$importer = new QuizCsvImporter();
$template = $importer->parse(QuizCsvImporter::templateCsv());
check('downloadable template parses with no errors', count($template['questions']) === 4 && !$template['row_errors'] && !$template['file_errors']);
check('template covers all four types', array_column($template['questions'], 'type') === ['multiple_choice', 'true_false', 'identification', 'fill_blank']);

$csv = "type,question,choice_a,choice_b,choice_c,choice_d,correct_answer,points\n"
    . "multiple_choice,Capital of France?,Paris,Rome,Berlin,,A,1\n"
    . "multiple_choice,Bad key,Paris,Rome,,,D,1\n"
    . "true_false,Water boils at 100C at sea level,,,,,yes,1\n"
    . "identification,Chemical symbol for gold,,,,,Au|AU,abc\n"
    . "fill_blank,No blank in this one,,,,,x,1\n"
    . ",,,,,,,\n"
    . "essay,Explain,,,,,x,1\n"
    . "multiple_choice,\"Quoted, with comma\",\"Yes, really\",No,,,\"Yes, really\",2\n";
$p = $importer->parse($csv);
$rowsWithErrors = array_column($p['row_errors'], 'row');
check('valid rows parse', count($p['questions']) === 7);
check('row-specific errors reported with CSV line numbers', $rowsWithErrors === [3, 4, 5, 6, 8], json_encode($rowsWithErrors));
check('blank lines skipped', !in_array(7, array_column($p['questions'], 'csv_row'), true));
check('correct answer by exact choice text and quoted commas', $p['questions'][6]['correct_index'] === 0 && $p['questions'][6]['choices'][0] === 'Yes, really');
check('invalid points reported on its row', str_contains(implode(' ', $p['row_errors'][2]['errors']), 'Points'));

$p = $importer->parse("question,correct_answer\nWhat?,x\n");
check('missing required column rejected', (bool)$p['file_errors'] && !$p['questions']);
$p = $importer->parse("type,question,correct_answer\n");
check('header-only file rejected', (bool)$p['file_errors']);
$p = $importer->parse("\xEF\xBB\xBFtype;question;correct_answer\ntrue_false;Semicolons work;True\n");
check('UTF-8 BOM and semicolon delimiter handled', count($p['questions']) === 1 && !$p['row_errors']);
$p = $importer->parse("type,question,correct_answer\nidentification,Caf\xE9 is French for?,coffee\n");
check('Windows-1252 text converted to UTF-8', ($p['questions'][0]['question_text'] ?? '') === 'Café is French for?');
$p = $importer->parse("type,question,correct_answer\n\0\0binary");
check('binary content rejected', (bool)$p['file_errors']);

$tmp = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($tmp, 'type,question,correct_answer');
check('non-.csv extension rejected', $importer->checkUpload(['name' => 'q.php', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 10]) !== null);
check('oversized upload rejected', $importer->checkUpload(['name' => 'q.csv', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 2000000]) !== null);
check('missing upload rejected', $importer->checkUpload(['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]) !== null);
$png = tempnam(sys_get_temp_dir(), 'png');
file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
check('image renamed to .csv rejected by content type', $importer->checkUpload(['name' => 'q.csv', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => filesize($png)]) !== null);
check('plain CSV upload accepted', $importer->checkUpload(['name' => 'q.csv', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 10]) === null);
@unlink($png);

// ---------------------------------------------------------------------------
echo "\n[4] Content extraction and grounded generation\n";
$lectureText = "The central processing unit (CPU) is the component that executes program instructions.\n"
    . "Random access memory (RAM) is a volatile memory that holds data while the computer runs.\n"
    . "A program in execution is called a process.\n"
    . "Cache: a small, fast memory located close to the processor.\n"
    . "The operating system is the software that manages hardware resources for programs.\n"
    . "A hard disk drive is a non-volatile storage device that uses spinning magnetic platters.\n"
    . "Lecture 3: Computer Organization\n"
    . "It is important to review these notes every week.";
$materialsDir = __DIR__ . '/../storage/uploads/lms/materials/';
if (!is_dir($materialsDir)) {
    mkdir($materialsDir, 0755, true);
}
$stamp = bin2hex(random_bytes(4));
$docxName = "test_quiz_builder_{$stamp}.docx";
$pptxName = "test_quiz_builder_{$stamp}.pptx";
$jpgName = "test_quiz_builder_{$stamp}.jpg";
$wordXml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>';
foreach (explode("\n", $lectureText) as $line) {
    $wordXml .= '<w:p><w:r><w:t>' . htmlspecialchars($line, ENT_XML1) . '</w:t></w:r></w:p>';
}
$wordXml .= '</w:body></w:document>';
makeOfficeFile($materialsDir . $docxName, ['word/document.xml' => $wordXml]);
$slide = fn ($text) => '<?xml version="1.0" encoding="UTF-8"?><p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><p:cSld><p:spTree><p:sp><p:txBody><a:p><a:r><a:t>' . htmlspecialchars($text, ENT_XML1) . '</a:t></a:r></a:p></p:txBody></p:sp></p:spTree></p:cSld></p:sld>';
makeOfficeFile($materialsDir . $pptxName, [
    'ppt/slides/slide2.xml' => $slide('Registers are small storage locations inside the processor core.'),
    'ppt/slides/slide1.xml' => $slide('A compiler is a program that translates source code into machine code.'),
]);
file_put_contents($materialsDir . $jpgName, 'not really an image');

$extractor = new CourseContentExtractor();
$content = $extractor->extract(
    [['id' => 1, 'title' => 'Module A', 'description' => 'An algorithm is a finite sequence of steps that solves a problem.']],
    [
        ['id' => 1, 'file_name' => 'Notes.docx', 'file_path' => 'materials/' . $docxName, 'module_title' => 'Module A', 'resolved_path' => $materialsDir . $docxName],
        ['id' => 2, 'file_name' => 'Slides.pptx', 'file_path' => 'materials/' . $pptxName, 'module_title' => 'Module A', 'resolved_path' => $materialsDir . $pptxName],
        ['id' => 3, 'file_name' => 'Photo.jpg', 'file_path' => 'materials/' . $jpgName, 'module_title' => 'Module A', 'resolved_path' => $materialsDir . $jpgName],
        ['id' => 4, 'file_name' => 'Lecture.pdf', 'file_path' => 'materials/missing.pdf', 'module_title' => 'Module A', 'resolved_path' => null],
    ]
);
$allText = implode("\n", array_column($content['segments'], 'text'));
check('module description, .docx and .pptx read', count($content['segments']) === 3 && str_contains($allText, 'central processing unit') && str_contains($allText, 'Registers'));
check('pptx slides read in slide order', strpos($allText, 'compiler') < strpos($allText, 'Registers'));
check('unsupported and unreadable files reported as skipped', count($content['skipped']) === 2, json_encode($content['skipped']));

$gen = new ExtractiveQuizGenerator();
$result = $gen->generate($content['segments'], ['multiple_choice' => 2, 'true_false' => 2, 'identification' => 2, 'fill_blank' => 2], 1.5);
$allGrounded = true;
$allValid = true;
foreach ($result['questions'] as $q) {
    [$clean, $errs] = $v->validate($q);
    $allValid = $allValid && !$errs;
    $allGrounded = $allGrounded && $q['source_excerpt'] !== '' && str_contains($allText, $q['source_excerpt']);
    $keys = $q['accepted_answers'] ?? (isset($q['correct_index']) ? [$q['choices'][$q['correct_index']]] : []);
    foreach ($keys as $key) {
        $allGrounded = $allGrounded && mb_stripos($q['source_excerpt'], preg_replace('/\s*\(.*\)$/', '', $key)) !== false;
    }
}
check('generated mixed-type draft', count($result['questions']) === 8, count($result['questions']) . ' generated');
check('every generated question validates', $allValid);
check('every question and answer key is traceable to a quoted excerpt', $allGrounded);
check('no question built from headings or filler sentences', !preg_match('/Lecture 3|important to review/', json_encode($result['questions'])));
$few = $gen->generate([['label' => 'x', 'text' => 'Binary representations, CPU microarchitecture, and memory hierarchies.']], ['multiple_choice' => 5], 1);
check('content without definitions yields no questions and an explanation', !$few['questions'] && str_contains($few['notices'][0], 'No definitional statements'));
$short = $gen->generate($content['segments'], ['identification' => 40], 1);
check('requesting more than the content supports returns fewer and says so', count($short['questions']) < 40 && str_contains(implode(' ', $short['notices']), 'only'));

// ---------------------------------------------------------------------------
echo "\n[5] Faculty workflow against the database\n";
$pdo = \App\Core\Database::getConnection();
$cols = $pdo->query("SHOW COLUMNS FROM lms_quiz_answers LIKE 'needs_review'")->rowCount()
    + $pdo->query("SHOW COLUMNS FROM lms_questions LIKE 'requires_manual_review'")->rowCount();
check('phase 11 migration applied', $cols === 2);
if ($cols !== 2) {
    echo "\nApply database/migrations/lms_phase11_quiz_builder_schema.sql first.\n";
    exit(1);
}

$_SESSION['csrf_token'] = 't';
$_SESSION['user_id'] = FACULTY_ID;
$quizService = new \App\Services\LmsQuizService();
$lmsService = new \App\Services\LmsService();
$ctrl = new \App\Controllers\Lms\FacultyQuizController();
$req = new \App\Core\Request();
$res = new \App\Core\Response();

$quizId = $quizService->createQuiz(['lms_course_id' => COURSE_ID, 'title' => 'Quiz builder test ' . $stamp, 'max_attempts' => 3, 'status' => 'draft']);
$moduleId = $lmsService->createModule(COURSE_ID, 'Quiz builder test module ' . $stamp, 99);
$pdo->prepare("UPDATE lms_modules SET description = :d WHERE id = :id")->execute(['d' => 'An algorithm is a finite sequence of steps that solves a problem.', 'id' => $moduleId]);
$materialIds = [];
foreach ([[$docxName, 'Notes.docx'], [$pptxName, 'Slides.pptx'], [$jpgName, 'Photo.jpg']] as [$file, $label]) {
    $materialIds[] = $lmsService->createMaterial(['lms_module_id' => $moduleId, 'title' => $label, 'file_path' => 'materials/' . $file, 'file_size' => 100]);
}

$csvFile = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($csvFile, "type,question,choice_a,choice_b,choice_c,correct_answer,points,case_sensitive,manual_review\n"
    . "multiple_choice,Which device executes instructions?,CPU,Monitor,Keyboard,A,1,,\n"
    . "true_false,RAM keeps data after power off.,,,,False,1,,\n"
    . "identification,What is the chemical symbol for sodium chloride?,,,,NaCl,2,yes,no\n"
    . "fill_blank,The ___ manages hardware resources.,,,,operating system|OS,2,no,yes\n"
    . "multiple_choice,Broken row,Only one,,,A,1,,\n");
$_FILES['questions_csv'] = ['name' => 'questions.csv', 'type' => 'text/csv', 'tmp_name' => $csvFile, 'error' => UPLOAD_ERR_OK, 'size' => filesize($csvFile)];
post($req, []);
$ctrl->importCsv($req, $res, (string)COURSE_ID, (string)$quizId);
$draft = $_SESSION['quiz_builder_drafts'][$quizId] ?? null;
check('CSV upload creates a draft and redirects to review', $draft && str_ends_with($ctrl->redirectUrl, '/questions/review'));
check('draft keeps the broken row with its error for editing', count($draft['questions']) === 5 && isset($draft['errors'][4]));
check('nothing saved before review', count($quizService->getQuestions($quizId)) === 0);

ob_start();
$html = $ctrl->reviewDraft($req, $res, (string)COURSE_ID, (string)$quizId);
ob_end_clean();
check('review page renders the draft with row error', str_contains($html, 'CSV row 6') && str_contains($html, 'Multiple choice needs between'));

// Professor removes the broken row and edits question 1 before saving.
$posted = [];
foreach (array_slice($draft['questions'], 0, 4) as $q) {
    $posted[] = array_merge($q, ['accepted_answers' => implode("\n", $q['accepted_answers'])]);
}
$posted[0]['question_text'] = 'Which device executes program instructions?';
$posted[1]['accepted_answers'] = '';
$bad = $posted;
$bad[2]['accepted_answers'] = '';
post($req, ['questions' => array_map(fn ($q) => array_merge($q, ['accepted_answers' => is_array($q['accepted_answers']) ? '' : $q['accepted_answers']]), $bad)]);
$ctrl->saveDraft($req, $res, (string)COURSE_ID, (string)$quizId);
check('saving with an invalid question saves nothing and keeps the draft', count($quizService->getQuestions($quizId)) === 0 && isset($_SESSION['quiz_builder_drafts'][$quizId]['errors'][2]));

post($req, ['questions' => $posted, 'publish' => '1']);
$ctrl->saveDraft($req, $res, (string)COURSE_ID, (string)$quizId);
$saved = $quizService->getQuestions($quizId, true);
check('corrected draft saved, removed row dropped', count($saved) === 4);
check('professor edit persisted', $saved[0]['question_text'] === 'Which device executes program instructions?');
check('text settings persisted', (int)$saved[2]['case_sensitive'] === 1 && (int)$saved[3]['requires_manual_review'] === 1);
check('accepted answers stored as correct choices', array_column($saved[3]['choices'], 'choice_text') === ['operating system', 'OS']);
check('quiz published on request', $quizService->getQuiz($quizId)['status'] === 'published');
check('draft cleared after save', !isset($_SESSION['quiz_builder_drafts'][$quizId]));

// Generation from this course's module and materials, plus a material from another course.
$foreignMaterial = $pdo->query("SELECT m.id FROM lms_materials m JOIN lms_modules d ON d.id = m.lms_module_id WHERE d.lms_course_id <> " . COURSE_ID . " LIMIT 1")->fetchColumn();
post($req, ['modules' => [$moduleId], 'materials' => array_merge($materialIds, [$foreignMaterial ?: 0]), 'counts' => ['multiple_choice' => 2, 'true_false' => 1, 'identification' => 2, 'fill_blank' => 2], 'points' => '1']);
$ctrl->generate($req, $res, (string)COURSE_ID, (string)$quizId);
$draft = $_SESSION['quiz_builder_drafts'][$quizId] ?? null;
check('generation produces a draft for review', $draft && $draft['source'] === 'generator' && count($draft['questions']) === 7);
check('generated draft reports the skipped image file', str_contains(implode(' ', $draft['notices']), 'Photo.jpg'));
$foreignLabel = $foreignMaterial ? $pdo->query("SELECT file_name FROM lms_materials WHERE id = " . (int)$foreignMaterial)->fetchColumn() : null;
check('material from another course ignored', !$foreignLabel || !str_contains(json_encode($draft), (string)$foreignLabel));

ob_start();
$ctrl->generateForm($req, $res, (string)COURSE_ID, (string)$quizId);
ob_end_clean();

post($req, []);
$ctrl->discardDraft($req, $res, (string)COURSE_ID, (string)$quizId);
check('discarding a draft adds nothing', count($quizService->getQuestions($quizId)) === 4 && !isset($_SESSION['quiz_builder_drafts'][$quizId]));

post($req, ['counts' => ['multiple_choice' => 0], 'modules' => [$moduleId]]);
$ctrl->generate($req, $res, (string)COURSE_ID, (string)$quizId);
check('zero requested questions rejected', ($_SESSION['quiz_builder_flash']['type'] ?? '') === 'danger');
post($req, ['counts' => ['multiple_choice' => 3], 'modules' => [999999]]);
$ctrl->generate($req, $res, (string)COURSE_ID, (string)$quizId);
check('selection outside the course rejected', str_contains($_SESSION['quiz_builder_flash']['message'] ?? '', 'Select at least one'));

post($req, ['question_type' => 'fill_blank', 'question_text' => 'Water is made of hydrogen and ___.', 'points' => '1', 'accepted_answers' => "oxygen\nO"]);
$ctrl->storeQuestion($req, $res, (string)COURSE_ID, (string)$quizId);
post($req, ['question_type' => 'multiple_choice', 'question_text' => 'No correct', 'points' => '1', 'mc_choice_1' => 'A', 'mc_choice_2' => 'B']);
$ctrl->storeQuestion($req, $res, (string)COURSE_ID, (string)$quizId);
check('manual add validates on the server', count($quizService->getQuestions($quizId)) === 5 && ($_SESSION['quiz_builder_flash']['type'] ?? '') === 'danger');

// Ownership and scoping, each in its own process because forbidden requests exit().
$_SESSION['quiz_builder_drafts'][$quizId] = ['course_id' => COURSE_ID, 'questions' => []];
$run = fn (string ...$args) => shell_exec(PHP_BINARY . ' ' . escapeshellarg(__FILE__) . ' --child ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1');
$out = $run('foreign_review', (string)OTHER_FACULTY_ID, (string)$quizId);
check('faculty who does not own the course is refused', str_contains($out, '403') && !str_contains($out, 'REACHED_END'), trim((string)$out));
$out = $run('quiz_course_mismatch', (string)OTHER_FACULTY_ID, (string)$quizId);
check('quiz opened under a different course is refused', str_contains($out, '404') && !str_contains($out, 'REACHED_END'), trim((string)$out));

// ---------------------------------------------------------------------------
echo "\n[6] Mixed-type student attempt, review and gradebook\n";
$questions = $quizService->getQuestions($quizId, true);
$byType = [];
foreach ($questions as $q) {
    $byType[$q['question_type']][] = $q;
}
$mc = $byType['multiple_choice'][0];
$tf = $byType['true_false'][0];
$ident = $byType['identification'][0];   // NaCl, case-sensitive
$fill = $byType['fill_blank'][0];         // operating system | OS, manual review
$fill2 = $byType['fill_blank'][1];        // oxygen | O
$correctChoice = fn ($q) => (int)array_values(array_filter($q['choices'], fn ($c) => $c['is_correct']))[0]['id'];
$wrongChoice = fn ($q) => (int)array_values(array_filter($q['choices'], fn ($c) => !$c['is_correct']))[0]['id'];

$attemptId = $quizService->startAttempt($quizId, STUDENT_ID);
check('student attempt started', $attemptId > 0);
$ok = $quizService->submitAttempt($attemptId, [
    $mc['id'] => (string)$correctChoice($mc),          // +1
    $tf['id'] => (string)$wrongChoice($tf),            // 0
    $ident['id'] => 'NaCl',                            // +2 exact, case-sensitive
    $fill['id'] => 'operating sytem',                  // near miss -> review
    $fill2['id'] => 'Oxygen.',                         // +1 normalized
]);
$attempt = $quizService->getAttempt($attemptId);
check('mixed attempt submitted', $ok);
check('attempt with a flagged answer is held for review', $attempt['status'] === 'submitted' && (float)$attempt['score'] === 4.0, $attempt['status'] . ' ' . $attempt['score']);
check('second submission of the same attempt refused', !$quizService->submitAttempt($attemptId, []));
$answers = [];
foreach ($quizService->getAttemptDetails($attemptId) as $a) {
    $answers[(int)$a['lms_question_id']] = $a;
}
check('typed answers stored verbatim', $answers[$fill['id']]['answer_text'] === 'operating sytem' && (int)$answers[$fill['id']]['needs_review'] === 1);
check('review queue counts the flagged answer', ($quizService->countAnswersNeedingReview($quizId)[$attemptId] ?? 0) === 1);

$gradebook = (new \App\Services\LmsGradebookService())->getStudentPersonalGradebook(COURSE_ID, STUDENT_ID);
check('provisional score excluded from the gradebook', array_key_exists($quizId, $gradebook['my_grades']['quizzes']) && $gradebook['my_grades']['quizzes'][$quizId] === null);

ob_start();
$html = $ctrl->reviewAttempt($req, $res, (string)COURSE_ID, (string)$quizId, (string)$attemptId);
ob_end_clean();
check('faculty review page lists the flagged answer', str_contains($html, 'operating sytem') && str_contains($html, 'Needs review'));

post($req, ['awarded' => [$fill['id'] => '5']]);
$ctrl->saveAttemptReview($req, $res, (string)COURSE_ID, (string)$quizId, (string)$attemptId);
check('points above the question maximum rejected', $quizService->getAttempt($attemptId)['status'] === 'submitted' && ($_SESSION['quiz_builder_flash']['type'] ?? '') === 'danger');

post($req, ['awarded' => [$fill['id'] => '2', $mc['id'] => '0']]);
$ctrl->saveAttemptReview($req, $res, (string)COURSE_ID, (string)$quizId, (string)$attemptId);
$attempt = $quizService->getAttempt($attemptId);
$mcAfter = $quizService->getAttemptDetails($attemptId);
$mcAnswer = array_values(array_filter($mcAfter, fn ($a) => (int)$a['lms_question_id'] === (int)$mc['id']))[0];
check('review credits the answer and finalizes the attempt', $attempt['status'] === 'graded' && (float)$attempt['score'] === 6.0, $attempt['status'] . ' ' . $attempt['score']);
check('choice question points cannot be overridden through review', (float)$mcAnswer['points_awarded'] === 1.0);
$gradebook = (new \App\Services\LmsGradebookService())->getStudentPersonalGradebook(COURSE_ID, STUDENT_ID);
$score = $gradebook['my_grades']['quizzes'][$quizId] ?? null;
check('graded score now counts in the gradebook', (float)$score === 6.0, var_export($score, true));

// Blank and unanswered attempt
$attempt2 = $quizService->startAttempt($quizId, STUDENT_ID);
$quizService->submitAttempt($attempt2, [$ident['id'] => '   ', $mc['id'] => '999999']);
$attempt2Row = $quizService->getAttempt($attempt2);
check('blank and tampered answers score 0 and need no review', $attempt2Row['status'] === 'graded' && (float)$attempt2Row['score'] === 0.0);

$out = $run('foreign_attempt', (string)OTHER_FACULTY_ID, (string)$quizId, (string)$attemptId);
check('another faculty member cannot open the attempt review', str_contains($out, '403'), trim((string)$out));

// ---------------------------------------------------------------------------
echo "\n[7] Cleanup\n";
$pdo->prepare("DELETE FROM lms_quizzes WHERE id = :id")->execute(['id' => $quizId]);
$pdo->prepare("DELETE FROM lms_modules WHERE id = :id")->execute(['id' => $moduleId]);
foreach ([$docxName, $pptxName, $jpgName] as $file) {
    @unlink($materialsDir . $file);
}
@unlink($tmp);
@unlink($csvFile);
check('test quiz, attempts, module and files removed', !$quizService->getQuiz($quizId));

echo "\n===============================================\n";
echo " {$passed} passed, {$failed} failed\n";
echo "===============================================\n";
exit($failed === 0 ? 0 : 1);
