<?php
namespace App\Services\Quiz;

/**
 * Single source of truth for quiz question rules.
 *
 * Manual entry, CSV import and content generation all produce "draft questions" in the
 * shape below, and every one of them is validated here before it can reach the database:
 *
 *   [
 *     'type'             => 'multiple_choice' | 'true_false' | 'identification' | 'fill_blank',
 *     'question_text'    => string,
 *     'points'           => float,
 *     'choices'          => string[]   (multiple_choice only, 2-6 entries),
 *     'correct_index'    => int        (multiple_choice only, 0-based index into choices),
 *     'correct_tf'       => 'true' | 'false' (true_false only),
 *     'accepted_answers' => string[]   (identification / fill_blank, 1-10 entries),
 *     'case_sensitive'   => bool       (text types only),
 *     'manual_review'    => bool       (text types only),
 *     'source_reference' => string     (optional, shown to faculty only),
 *     'source_excerpt'   => string     (optional, shown to faculty only, never stored),
 *   ]
 */
class QuizQuestionValidator
{
    public const TYPES = [
        'multiple_choice' => 'Multiple Choice',
        'true_false' => 'True / False',
        'identification' => 'Identification',
        'fill_blank' => 'Fill in the Blank',
    ];

    public const TEXT_TYPES = ['identification', 'fill_blank'];

    public const MAX_QUESTION_LENGTH = 2000;
    public const MAX_CHOICE_LENGTH = 500;
    public const MAX_ANSWER_LENGTH = 255;
    public const MIN_CHOICES = 2;
    public const MAX_CHOICES = 6;
    public const MAX_ACCEPTED_ANSWERS = 10;
    public const MIN_POINTS = 0.25;
    public const MAX_POINTS = 100;
    public const BLANK_PATTERN = '/_{3,}/';

    private const TYPE_ALIASES = [
        'multiple_choice' => 'multiple_choice', 'multiple choice' => 'multiple_choice', 'mc' => 'multiple_choice', 'mcq' => 'multiple_choice',
        'true_false' => 'true_false', 'true/false' => 'true_false', 'true false' => 'true_false', 'tf' => 'true_false',
        'identification' => 'identification', 'id' => 'identification',
        'fill_blank' => 'fill_blank', 'fill in the blank' => 'fill_blank', 'fill-in-the-blank' => 'fill_blank',
        'fill_in_the_blank' => 'fill_blank', 'fib' => 'fill_blank',
    ];

    public static function normalizeType(?string $type): ?string
    {
        $key = strtolower(trim((string)$type));
        return self::TYPE_ALIASES[$key] ?? null;
    }

    public static function isTextType(string $type): bool
    {
        return in_array($type, self::TEXT_TYPES, true);
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Validates one draft question.
     *
     * @return array{0: array, 1: string[]} [clean draft, list of human-readable errors]
     */
    public function validate(array $input): array
    {
        $errors = [];

        $type = self::normalizeType($input['type'] ?? null);
        if ($type === null) {
            $errors[] = 'Question type must be one of: ' . implode(', ', array_keys(self::TYPES)) . '.';
        }

        $text = self::cleanText($input['question_text'] ?? '');
        if ($text === '') {
            $errors[] = 'Question text is required.';
        } elseif (mb_strlen($text) > self::MAX_QUESTION_LENGTH) {
            $errors[] = 'Question text must be ' . self::MAX_QUESTION_LENGTH . ' characters or fewer.';
        }

        $rawPoints = trim((string)($input['points'] ?? ''));
        $points = 1.0;
        if ($rawPoints !== '') {
            if (!is_numeric($rawPoints)) {
                $errors[] = 'Points must be a number.';
            } else {
                $points = round((float)$rawPoints, 2);
                if ($points < self::MIN_POINTS || $points > self::MAX_POINTS) {
                    $errors[] = 'Points must be between ' . self::MIN_POINTS . ' and ' . self::MAX_POINTS . '.';
                }
            }
        }

        $clean = [
            'type' => $type ?? (string)($input['type'] ?? ''),
            'question_text' => $text,
            'points' => $points,
            'choices' => [],
            'correct_index' => null,
            'correct_tf' => null,
            'accepted_answers' => [],
            'case_sensitive' => false,
            'manual_review' => false,
            'source_reference' => mb_substr(self::cleanText($input['source_reference'] ?? ''), 0, 500),
            'source_excerpt' => mb_substr(self::cleanText($input['source_excerpt'] ?? ''), 0, 1000),
        ];

        if ($type === 'multiple_choice') {
            $choices = [];
            foreach ((array)($input['choices'] ?? []) as $choice) {
                $choices[] = self::cleanText($choice);
            }
            // Drop trailing empty inputs (e.g. unused choice E/F columns) while keeping positions
            // of the filled ones so a letter-based answer key still lines up.
            while (!empty($choices) && end($choices) === '') {
                array_pop($choices);
            }
            if (in_array('', $choices, true)) {
                $errors[] = 'Choices must be filled in order with no gaps.';
            }
            if (count($choices) < self::MIN_CHOICES || count($choices) > self::MAX_CHOICES) {
                $errors[] = 'Multiple choice needs between ' . self::MIN_CHOICES . ' and ' . self::MAX_CHOICES . ' choices.';
            }
            foreach ($choices as $c) {
                if (mb_strlen($c) > self::MAX_CHOICE_LENGTH) {
                    $errors[] = 'Each choice must be ' . self::MAX_CHOICE_LENGTH . ' characters or fewer.';
                    break;
                }
            }
            $lowered = array_map('mb_strtolower', $choices);
            if (count(array_unique($lowered)) !== count($lowered)) {
                $errors[] = 'Choices must be different from each other.';
            }
            $index = $input['correct_index'] ?? null;
            if ($index === null || $index === '' || !ctype_digit((string)$index) || (int)$index >= count($choices)) {
                $errors[] = 'Mark exactly one existing choice as the correct answer.';
            } else {
                $clean['correct_index'] = (int)$index;
            }
            $clean['choices'] = $choices;
        } elseif ($type === 'true_false') {
            $tf = strtolower(trim((string)($input['correct_tf'] ?? '')));
            if (!in_array($tf, ['true', 'false'], true)) {
                $errors[] = 'True/false answer must be True or False.';
            } else {
                $clean['correct_tf'] = $tf;
            }
        } elseif ($type !== null && self::isTextType($type)) {
            $answers = [];
            $seen = [];
            foreach ((array)($input['accepted_answers'] ?? []) as $answer) {
                $answer = self::cleanText($answer);
                if ($answer === '') {
                    continue;
                }
                if (mb_strlen($answer) > self::MAX_ANSWER_LENGTH) {
                    $errors[] = 'Each accepted answer must be ' . self::MAX_ANSWER_LENGTH . ' characters or fewer.';
                    continue;
                }
                $key = QuizAnswerGrader::normalize($answer, !empty($input['case_sensitive']));
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $answers[] = $answer;
            }
            if (empty($answers)) {
                $errors[] = 'Provide at least one accepted answer.';
            } elseif (count($answers) > self::MAX_ACCEPTED_ANSWERS) {
                $errors[] = 'Use at most ' . self::MAX_ACCEPTED_ANSWERS . ' accepted answers.';
            }
            if ($type === 'fill_blank' && $text !== '') {
                $blanks = preg_match_all(self::BLANK_PATTERN, $text);
                if ($blanks !== 1) {
                    $errors[] = 'Fill-in-the-blank text must contain exactly one blank written as ___ (three or more underscores).';
                }
            }
            $clean['accepted_answers'] = $answers;
            $clean['case_sensitive'] = !empty($input['case_sensitive']);
            $clean['manual_review'] = !empty($input['manual_review']);
        }

        return [$clean, $errors];
    }

    /**
     * Converts a validated draft into the rows LmsQuizService stores.
     *
     * @return array{question: array, choices: array}
     */
    public static function toStorage(array $clean): array
    {
        $question = [
            'question_text' => $clean['question_text'],
            'question_type' => $clean['type'],
            'points' => $clean['points'],
            'case_sensitive' => self::isTextType($clean['type']) && !empty($clean['case_sensitive']) ? 1 : 0,
            'requires_manual_review' => self::isTextType($clean['type']) && !empty($clean['manual_review']) ? 1 : 0,
            'source_reference' => $clean['source_reference'] !== '' ? $clean['source_reference'] : null,
        ];

        $choices = [];
        if ($clean['type'] === 'multiple_choice') {
            foreach ($clean['choices'] as $i => $text) {
                $choices[] = ['choice_text' => $text, 'is_correct' => $i === $clean['correct_index'], 'display_order' => $i + 1];
            }
        } elseif ($clean['type'] === 'true_false') {
            $choices[] = ['choice_text' => 'True', 'is_correct' => $clean['correct_tf'] === 'true', 'display_order' => 1];
            $choices[] = ['choice_text' => 'False', 'is_correct' => $clean['correct_tf'] === 'false', 'display_order' => 2];
        } else {
            foreach ($clean['accepted_answers'] as $i => $text) {
                $choices[] = ['choice_text' => $text, 'is_correct' => true, 'display_order' => $i + 1];
            }
        }

        return ['question' => $question, 'choices' => $choices];
    }

    /**
     * Splits a "|" or newline separated answer list (CSV cell, textarea) into entries.
     */
    public static function splitAnswers(string $value): array
    {
        $parts = preg_split('/\s*(?:\||\r\n|\r|\n)\s*/u', $value) ?: [];
        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /**
     * Trims, removes control characters and collapses runs of spaces/tabs.
     * Line breaks inside question text are kept.
     */
    public static function cleanText($value): string
    {
        $value = (string)$value;
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t\x{00A0}]+/u', ' ', $value) ?? '';
        return trim($value);
    }
}
