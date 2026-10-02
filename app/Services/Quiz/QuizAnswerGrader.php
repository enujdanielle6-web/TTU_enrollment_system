<?php
namespace App\Services\Quiz;

/**
 * Grades one student response against one question (pure logic, no database access).
 *
 * Text answer policy (identification and fill_blank):
 *   1. Blank answer                         -> 0 points, incorrect, no review.
 *   2. Matches any accepted answer after
 *      normalization (see normalize())      -> full points, correct.
 *      Numbers are compared by value, so "3", "3.0" and "3.00" match.
 *   3. Question marked "manual review"      -> 0 points for now, sent to faculty review.
 *   4. Near miss (small typo, see isNearMiss) -> 0 points for now, sent to faculty review.
 *   5. Anything else                        -> 0 points, incorrect, no review
 *                                              (faculty can still override it on the review page).
 *
 * Multiple choice and true/false only accept a choice id that belongs to the question;
 * anything else is treated as unanswered.
 */
class QuizAnswerGrader
{
    public const MAX_RESPONSE_LENGTH = 1000;

    /**
     * @param array $question lms_questions row with a 'choices' list of lms_question_choices rows
     * @param mixed $response raw value posted for this question
     * @return array{choice_id: ?int, answer_text: ?string, is_correct: bool, points: float, needs_review: bool}
     */
    public function grade(array $question, $response): array
    {
        $points = (float)$question['points'];
        $result = ['choice_id' => null, 'answer_text' => null, 'is_correct' => false, 'points' => 0.0, 'needs_review' => false];

        if (QuizQuestionValidator::isTextType((string)$question['question_type'])) {
            $answer = is_string($response) ? QuizQuestionValidator::cleanText($response) : '';
            $answer = mb_substr($answer, 0, self::MAX_RESPONSE_LENGTH);
            if ($answer === '') {
                return $result;
            }
            $result['answer_text'] = $answer;

            $caseSensitive = !empty($question['case_sensitive']);
            $accepted = array_map(fn ($c) => (string)$c['choice_text'], array_filter($question['choices'], fn ($c) => !empty($c['is_correct'])));

            if ($this->matchesAny($answer, $accepted, $caseSensitive)) {
                $result['is_correct'] = true;
                $result['points'] = $points;
            } elseif (!empty($question['requires_manual_review']) || $this->isNearMiss($answer, $accepted, $caseSensitive)) {
                $result['needs_review'] = true;
            }
            return $result;
        }

        if ($response === null || $response === '' || is_array($response) || !ctype_digit((string)$response)) {
            return $result;
        }
        foreach ($question['choices'] as $choice) {
            if ((int)$choice['id'] === (int)$response) {
                $result['choice_id'] = (int)$choice['id'];
                if (!empty($choice['is_correct'])) {
                    $result['is_correct'] = true;
                    $result['points'] = $points;
                }
                break;
            }
        }
        return $result;
    }

    /**
     * Normalization applied to both the student answer and every accepted answer:
     * trim, collapse internal whitespace, strip surrounding quotes and trailing
     * sentence punctuation, and (unless the question is case-sensitive) lowercase.
     */
    public static function normalize(string $value, bool $caseSensitive = false): string
    {
        $value = QuizQuestionValidator::cleanText($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value, " \"'`\u{201C}\u{201D}\u{2018}\u{2019}");
        $value = rtrim($value, " .,;:!?");
        if (!$caseSensitive) {
            $value = mb_strtolower($value, 'UTF-8');
        }
        return $value;
    }

    public function matchesAny(string $answer, array $accepted, bool $caseSensitive): bool
    {
        $given = self::normalize($answer, $caseSensitive);
        if ($given === '') {
            return false;
        }
        foreach ($accepted as $candidate) {
            $expected = self::normalize($candidate, $caseSensitive);
            if ($given === $expected) {
                return true;
            }
            if (is_numeric($given) && is_numeric($expected) && abs((float)$given - (float)$expected) < 1e-9) {
                return true;
            }
        }
        return false;
    }

    /**
     * A near miss is within one edit of an accepted answer of 4-7 characters, or within
     * two edits of one of 8+ characters. Letter case is ignored here even for
     * case-sensitive questions, so a wrong-case answer is reviewed instead of rejected.
     */
    public function isNearMiss(string $answer, array $accepted, bool $caseSensitive): bool
    {
        $given = self::normalize($answer, false);
        foreach ($accepted as $candidate) {
            $expected = self::normalize($candidate, false);
            $length = mb_strlen($expected);
            if ($length < 4 || $length > 255 || mb_strlen($given) > 255) {
                if ($caseSensitive && $given === $expected) {
                    return true;
                }
                continue;
            }
            $allowed = $length >= 8 ? 2 : 1;
            if (self::editDistance($given, $expected) <= $allowed) {
                return true;
            }
        }
        return false;
    }

    /**
     * Multibyte-safe Levenshtein distance (PHP's levenshtein() counts bytes).
     */
    public static function editDistance(string $a, string $b): int
    {
        $a = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $previous = range(0, count($b));
        foreach ($a as $i => $charA) {
            $current = [$i + 1];
            foreach ($b as $j => $charB) {
                $current[$j + 1] = min(
                    $previous[$j + 1] + 1,
                    $current[$j] + 1,
                    $previous[$j] + ($charA === $charB ? 0 : 1)
                );
            }
            $previous = $current;
        }
        return $previous[count($b)];
    }
}
