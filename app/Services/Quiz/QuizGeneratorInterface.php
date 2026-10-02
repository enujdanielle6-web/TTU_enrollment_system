<?php
namespace App\Services\Quiz;

interface QuizGeneratorInterface
{
    /** Short id stored in settings and shown in the UI, e.g. "extractive". */
    public function id(): string;

    /** Human-readable name shown to faculty. */
    public function label(): string;

    /** One sentence telling faculty how questions are produced. */
    public function description(): string;

    /**
     * @param array $segments  [['label' => string, 'text' => string], ...] from CourseContentExtractor
     * @param array $counts    question type => number requested
     * @param float $points    points per generated question
     * @return array{questions: array, notices: string[]} draft questions in QuizQuestionValidator shape;
     *         each one carries source_reference and source_excerpt pointing at the content it came from
     */
    public function generate(array $segments, array $counts, float $points): array;
}
