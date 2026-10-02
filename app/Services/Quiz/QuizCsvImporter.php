<?php
namespace App\Services\Quiz;

/**
 * Parses and validates a quiz question CSV upload.
 *
 * Template columns (header row required, order does not matter, names are case-insensitive):
 *   type, question, choice_a ... choice_f, correct_answer, points, case_sensitive, manual_review
 *
 * correct_answer by type:
 *   multiple_choice  letter of the correct choice (A-F) or its exact text
 *   true_false       True or False (T / F also accepted)
 *   identification   accepted answers separated by |
 *   fill_blank       accepted answers separated by |, question must contain one ___ blank
 */
class QuizCsvImporter
{
    public const MAX_FILE_BYTES = 1048576; // 1 MB
    public const MAX_ROWS = 200;
    public const CHOICE_COLUMNS = ['choice_a', 'choice_b', 'choice_c', 'choice_d', 'choice_e', 'choice_f'];
    public const REQUIRED_COLUMNS = ['type', 'question', 'correct_answer'];
    public const OPTIONAL_COLUMNS = ['points', 'case_sensitive', 'manual_review'];

    private const ALLOWED_MIME_TYPES = ['text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel', 'text/comma-separated-values'];

    private QuizQuestionValidator $validator;

    public function __construct(?QuizQuestionValidator $validator = null)
    {
        $this->validator = $validator ?? new QuizQuestionValidator();
    }

    /**
     * Checks a $_FILES entry before anything reads it.
     *
     * @return string|null error message, or null when the upload is acceptable
     */
    public function checkUpload(?array $file): ?string
    {
        if (!$file || !isset($file['error']) || is_array($file['error'])) {
            return 'Choose a CSV file to upload.';
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Choose a CSV file to upload.';
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'The CSV file is larger than the 1 MB limit.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'The upload did not complete. Please try again.';
        }
        if (!defined('TESTING_ENV') && !is_uploaded_file($file['tmp_name'])) {
            return 'The upload could not be verified. Please try again.';
        }
        if ((int)$file['size'] <= 0) {
            return 'The CSV file is empty.';
        }
        if ((int)$file['size'] > self::MAX_FILE_BYTES) {
            return 'The CSV file is larger than the 1 MB limit.';
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return 'Only .csv files are accepted. In Excel use "Save As" and pick "CSV UTF-8".';
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $file['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime !== '' && !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                return 'The file does not look like a plain-text CSV (detected ' . $mime . ').';
            }
        }
        return null;
    }

    /**
     * Parses CSV text into draft questions.
     *
     * @return array{questions: array, row_errors: array<int, array{row: int, errors: string[]}>, file_errors: string[]}
     *         questions keep their CSV row number in 'csv_row'; rows with errors are still
     *         returned so the professor can fix them on the review screen.
     */
    public function parse(string $content): array
    {
        $result = ['questions' => [], 'row_errors' => [], 'file_errors' => []];

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            // Excel on Windows saves "CSV (Comma delimited)" as Windows-1252.
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        if (str_contains($content, "\0")) {
            $result['file_errors'][] = 'The file contains binary data and is not a CSV.';
            return $result;
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!$header || count(array_filter($header, fn ($h) => trim((string)$h) !== '')) === 0) {
            $result['file_errors'][] = 'The first row must be the header row from the template.';
            fclose($handle);
            return $result;
        }

        $columns = [];
        foreach ($header as $i => $name) {
            $key = strtolower(trim((string)$name));
            $key = str_replace([' ', '-'], '_', $key);
            if ($key !== '') {
                $columns[$key] = $i;
            }
        }
        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($columns));
        if (!empty($missing)) {
            $result['file_errors'][] = 'Missing required column(s): ' . implode(', ', $missing) . '. Download the template to see the expected header.';
            fclose($handle);
            return $result;
        }
        $unknown = array_diff(array_keys($columns), self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS, self::CHOICE_COLUMNS);
        if (!empty($unknown)) {
            $result['file_errors'][] = 'Unknown column(s) ignored: ' . implode(', ', $unknown) . '.';
        }

        $rowNumber = 1;
        $dataRows = 0;
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rowNumber++;
            if ($row === [null] || count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $dataRows++;
            if ($dataRows > self::MAX_ROWS) {
                $result['file_errors'][] = 'Only the first ' . self::MAX_ROWS . ' questions were read. Split larger banks into several files.';
                break;
            }

            $get = fn (string $col) => isset($columns[$col]) ? trim((string)($row[$columns[$col]] ?? '')) : '';

            [$draft, $mappingErrors] = $this->rowToDraft($get);
            [$clean, $errors] = $this->validator->validate($draft);
            $errors = array_values(array_unique(array_merge($mappingErrors, $errors)));
            $clean['csv_row'] = $rowNumber;
            $clean['source_reference'] = 'CSV import, row ' . $rowNumber;
            $result['questions'][] = $clean;
            if (!empty($errors)) {
                $result['row_errors'][] = ['row' => $rowNumber, 'errors' => $errors];
            }
        }
        fclose($handle);

        if ($dataRows === 0) {
            $result['file_errors'][] = 'The file has a header but no question rows.';
        }
        return $result;
    }

    /**
     * Maps one CSV row onto the validator's draft shape.
     *
     * @return array{0: array, 1: string[]}
     */
    private function rowToDraft(callable $get): array
    {
        $errors = [];
        $type = QuizQuestionValidator::normalizeType($get('type'));
        $correct = $get('correct_answer');

        $draft = [
            'type' => $type ?? $get('type'),
            'question_text' => $get('question'),
            'points' => $get('points'),
            'case_sensitive' => self::truthy($get('case_sensitive')),
            'manual_review' => self::truthy($get('manual_review')),
        ];

        foreach (['case_sensitive', 'manual_review'] as $flag) {
            $value = strtolower($get($flag));
            if ($value !== '' && !in_array($value, ['yes', 'no', 'y', 'n', 'true', 'false', '1', '0'], true)) {
                $errors[] = "Column {$flag} must be yes or no.";
            }
        }

        if ($correct === '') {
            $errors[] = 'correct_answer is required.';
        }

        if ($type === 'multiple_choice') {
            $choices = array_map($get, self::CHOICE_COLUMNS);
            $draft['choices'] = $choices;
            if ($correct !== '') {
                $letterIndex = strlen($correct) === 1 ? ord(strtoupper($correct)) - ord('A') : -1;
                if ($letterIndex >= 0 && $letterIndex < count(self::CHOICE_COLUMNS) && $choices[$letterIndex] !== '') {
                    $draft['correct_index'] = $letterIndex;
                } else {
                    $matches = array_keys(array_filter($choices, fn ($c) => $c !== '' && mb_strtolower($c) === mb_strtolower($correct)));
                    if (count($matches) === 1) {
                        $draft['correct_index'] = $matches[0];
                    } else {
                        $errors[] = 'correct_answer "' . $correct . '" must be a choice letter (A-F) with text in that column, or the exact text of one choice.';
                    }
                }
            }
        } elseif ($type === 'true_false') {
            $value = strtolower($correct);
            $map = ['true' => 'true', 't' => 'true', 'false' => 'false', 'f' => 'false'];
            if ($correct !== '' && !isset($map[$value])) {
                $errors[] = 'correct_answer for true_false must be True or False.';
            }
            $draft['correct_tf'] = $map[$value] ?? '';
            foreach (self::CHOICE_COLUMNS as $col) {
                if ($get($col) !== '') {
                    $errors[] = 'true_false rows must leave the choice columns empty.';
                    break;
                }
            }
        } elseif ($type !== null) {
            $draft['accepted_answers'] = QuizQuestionValidator::splitAnswers($correct);
            foreach (self::CHOICE_COLUMNS as $col) {
                if ($get($col) !== '') {
                    $errors[] = $type . ' rows must leave the choice columns empty; put accepted answers in correct_answer separated by |.';
                    break;
                }
            }
        }

        return [$draft, $errors];
    }

    private static function truthy(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['yes', 'y', 'true', '1'], true);
    }

    /**
     * Template served to faculty. One example row per question type.
     */
    public static function templateCsv(): string
    {
        $rows = [
            array_merge(['type', 'question'], self::CHOICE_COLUMNS, ['correct_answer', 'points', 'case_sensitive', 'manual_review']),
            ['multiple_choice', 'Which component executes program instructions?', 'Hard disk', 'CPU', 'Monitor', 'Power supply', '', '', 'B', '1', '', ''],
            ['true_false', 'RAM is volatile memory.', '', '', '', '', '', '', 'True', '1', '', ''],
            ['identification', 'What is the base-2 number system called?', '', '', '', '', '', '', 'Binary|Binary system', '2', 'no', 'no'],
            ['fill_blank', 'The ___ stores data permanently even when the computer is off.', '', '', '', '', '', '', 'hard disk|hard drive|HDD', '2', 'no', 'no'],
        ];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
}
