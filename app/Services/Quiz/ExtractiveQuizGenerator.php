<?php
namespace App\Services\Quiz;

/**
 * Built-in, offline question generator. It does not use AI.
 *
 * It looks for definitional statements in the selected course content, for example
 *   "The central processing unit (CPU) is the component that executes instructions."
 *   "Cache: a small, fast memory close to the processor."
 *   "Registers are small storage locations inside the CPU."
 * and turns each statement into at most one question. The answer key is the term or
 * description taken verbatim from that statement, so every question can be traced back
 * to a quoted excerpt. When the content has fewer usable statements than requested, it
 * returns fewer questions and says so instead of padding.
 */
class ExtractiveQuizGenerator implements QuizGeneratorInterface
{
    private const MAX_TERM_WORDS = 6;

    private const SUBJECT_STOPWORDS = [
        'it', 'this', 'that', 'there', 'these', 'those', 'they', 'he', 'she', 'we', 'you', 'i', 'which', 'what', 'who',
        'each', 'every', 'all', 'one', 'some', 'here', 'today', 'its', 'our', 'their', 'his', 'her', 'my', 'your', 'such',
        'another', 'other', 'many', 'most', 'both', 'none', 'no', 'why', 'how', 'when', 'where', 'while', 'if', 'because',
        'so', 'also', 'then', 'now', 'however', 'therefore', 'thus', 'in', 'on', 'at', 'for', 'with', 'by', 'from', 'of',
        'to', 'as', 'note', 'example', 'examples', 'figure', 'table', 'chapter', 'module', 'lesson', 'section', 'objective',
        'objectives', 'reference', 'references', 'source', 'page', 'week', 'topic', 'topics', 'summary', 'answer',
        'question', 'activity', 'exercise', 'quiz', 'exam', 'deadline', 'due', 'instructor', 'professor', 'step',
        'lecture', 'unit', 'part', 'slide', 'session', 'day', 'date', 'name', 'title', 'goal', 'goals',
    ];

    private const ARE_DEFINITION_STOPWORDS = [
        'not', 'also', 'very', 'often', 'usually', 'used', 'able', 'important', 'more', 'less', 'still', 'now', 'being',
        'all', 'both', 'called', 'known', 'available', 'required', 'here', 'there', 'just', 'too', 'so', 'only',
    ];

    public function id(): string
    {
        return 'extractive';
    }

    public function label(): string
    {
        return 'Built-in statement extractor (no AI)';
    }

    public function description(): string
    {
        return 'Finds definitional statements in the selected content ("X is a ...", "Term: description") and turns each one into a question whose answer is quoted from that statement.';
    }

    public function generate(array $segments, array $counts, float $points): array
    {
        $facts = $this->extractFacts($segments);
        shuffle($facts);

        $notices = [];
        $questions = [];
        $used = [];

        if (empty($facts)) {
            return [
                'questions' => [],
                'notices' => ['No definitional statements were found in the selected content, so no questions were generated. This generator needs sentences such as "X is a ..." or lines such as "Term: description".'],
            ];
        }

        // Fill-in-the-blank is the most restrictive (needs a full sentence), so it picks first.
        foreach (['fill_blank', 'multiple_choice', 'identification', 'true_false'] as $type) {
            $wanted = max(0, (int)($counts[$type] ?? 0));
            $made = 0;
            foreach ($facts as $i => $fact) {
                if ($made >= $wanted) {
                    break;
                }
                if (isset($used[$i])) {
                    continue;
                }
                $question = $this->buildQuestion($type, $fact, $facts, $points);
                if ($question === null) {
                    continue;
                }
                $used[$i] = true;
                $questions[] = $question;
                $made++;
            }
            if ($made < $wanted) {
                $notices[] = sprintf(
                    'Requested %d %s question(s) but only %d could be grounded in the selected content.',
                    $wanted,
                    QuizQuestionValidator::typeLabel($type),
                    $made
                );
            }
        }

        if (($counts['true_false'] ?? 0) > 0) {
            $notices[] = 'False true/false statements pair a description with a different term from the same content. Check that each one is actually false.';
        }

        return ['questions' => $questions, 'notices' => $notices];
    }

    /**
     * @return array<int, array{term: string, answers: string[], definition: string, sentence: string, kind: string, label: string}>
     */
    public function extractFacts(array $segments): array
    {
        $facts = [];
        $seenTerms = [];

        foreach ($segments as $segment) {
            foreach ($this->splitUnits((string)$segment['text']) as $unit) {
                $fact = $this->matchCopula($unit) ?? $this->matchLabelled($unit);
                if ($fact === null) {
                    continue;
                }
                $key = QuizAnswerGrader::normalize($fact['term']);
                if ($key === '' || isset($seenTerms[$key])) {
                    continue;
                }
                // The definition must not give the answer away.
                foreach ($fact['answers'] as $answer) {
                    if (mb_stripos($fact['definition'], $answer) !== false) {
                        continue 2;
                    }
                }
                $seenTerms[$key] = true;
                $fact['label'] = (string)$segment['label'];
                $facts[] = $fact;
            }
        }
        return $facts;
    }

    private function splitUnits(string $text): array
    {
        $units = [];
        foreach (preg_split('/\n+/u', $text) ?: [] as $line) {
            $line = trim(preg_replace('/^\s*(?:[-*\x{2022}\x{25AA}\x{25CF}]|\d+[.)])\s+/u', '', $line) ?? '');
            if ($line === '') {
                continue;
            }
            foreach (preg_split('/(?<=[.!?])\s+(?=[\p{Lu}0-9"\x{201C}(])/u', $line) ?: [] as $sentence) {
                $sentence = trim($sentence);
                $length = mb_strlen($sentence);
                if ($length >= 15 && $length <= 400) {
                    $units[] = $sentence;
                }
            }
        }
        return $units;
    }

    /** "X is a ...", "X are ...", "X refers to ...", "Y is called X" */
    private function matchCopula(string $sentence): ?array
    {
        $pattern = '/^(?:(?:The|An|A)\s+)?(?<term>[\p{L}0-9][\p{L}0-9\-\/&\'. ]{0,58}?(?:\s*\([\p{L}0-9\-\/ ]{1,20}\))?)\s+'
            . '(?<verb>is defined as|refers to|is known as|is called|are called|means|is|are)\s+(?<def>[^?]{3,300}?)[.!]?$/u';
        if (!preg_match($pattern, $sentence, $m)) {
            return null;
        }
        $subject = trim($m['term']);
        $verb = $m['verb'];
        $predicate = trim($m['def']);

        if (in_array($verb, ['is called', 'are called', 'is known as'], true)) {
            // "A program in execution is called a process." -> term "process"
            $term = trim(preg_replace('/^(?:the|an|a)\s+/iu', '', $predicate) ?? '');
            $definition = $subject;
            $kind = 'named';
        } else {
            $term = $subject;
            $definition = $predicate;
            $kind = 'copula';
            $firstWord = mb_strtolower(strtok($definition, ' ') ?: '');
            if ($verb === 'is' && !preg_match('/^(?:a|an|the|one)\s/iu', $definition)) {
                return null;
            }
            if ($verb === 'are' && in_array($firstWord, self::ARE_DEFINITION_STOPWORDS, true)) {
                return null;
            }
        }

        if (!$this->isUsableTerm($term) || mb_strlen($definition) < 12) {
            return null;
        }

        return [
            'term' => self::displayTerm($term),
            'answers' => self::answerVariants($term),
            'definition' => $definition,
            'sentence' => $sentence,
            'kind' => $kind,
        ];
    }

    /** "Term: description" or "Term - description" (glossary / slide style) */
    private function matchLabelled(string $line): ?array
    {
        if (!preg_match('/^(?<term>[\p{L}0-9][^:\x{2013}\x{2014}]{0,58}?)\s*(?::|\s[-\x{2013}\x{2014}])\s+(?<def>.{12,300}?)[.!]?$/u', $line, $m)) {
            return null;
        }
        $term = trim($m['term']);
        $definition = trim($m['def']);
        // Headings such as "Lecture 3: Computer Organization" are not definitions.
        if (!$this->isUsableTerm($term) || preg_match('/[.!?,;]/u', $term) || count(preg_split('/\s+/u', $definition) ?: []) < 4) {
            return null;
        }
        return [
            'term' => self::displayTerm($term),
            'answers' => self::answerVariants($term),
            'definition' => $definition,
            'sentence' => $line,
            'kind' => 'labelled',
        ];
    }

    private function isUsableTerm(string $term): bool
    {
        $term = trim($term);
        $words = preg_split('/\s+/u', trim(preg_replace('/\([^)]*\)/u', '', $term) ?? '')) ?: [];
        if ($term === '' || count($words) === 0 || count($words) > self::MAX_TERM_WORDS) {
            return false;
        }
        if (in_array(mb_strtolower($words[0]), self::SUBJECT_STOPWORDS, true) || preg_match('/\d$/u', $term)) {
            return false;
        }
        return mb_strlen($term) >= 2 && preg_match('/\p{L}/u', $term) === 1;
    }

    private static function displayTerm(string $term): string
    {
        $term = trim($term);
        return mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
    }

    /** "central processing unit (CPU)" -> ["central processing unit", "CPU"] */
    private static function answerVariants(string $term): array
    {
        $term = trim($term);
        $variants = [];
        if (preg_match('/^(?<long>.+?)\s*\((?<short>[^)]+)\)$/u', $term, $m)) {
            $variants[] = trim($m['long']);
            $variants[] = trim($m['short']);
        } else {
            $variants[] = $term;
        }
        return array_values(array_unique(array_filter($variants, fn ($v) => $v !== '')));
    }

    private function buildQuestion(string $type, array $fact, array $facts, float $points): ?array
    {
        $base = [
            'type' => $type,
            'points' => $points,
            'source_reference' => 'Generated from ' . $fact['label'],
            'source_excerpt' => $fact['sentence'],
        ];
        $description = rtrim($fact['definition'], " .");
        $description = mb_strtoupper(mb_substr($description, 0, 1)) . mb_substr($description, 1);

        switch ($type) {
            case 'fill_blank':
                if ($fact['kind'] !== 'copula') {
                    return null;
                }
                $sentence = $fact['sentence'];
                $subject = preg_quote(preg_replace('/^(?:the|an|a)\s+/iu', '', $fact['term']) ?? '', '/');
                if (preg_match_all('/' . $subject . '/iu', $sentence) !== 1) {
                    return null;
                }
                $text = preg_replace('/' . $subject . '/iu', '___', $sentence, 1);
                return $base + ['question_text' => $text, 'accepted_answers' => $fact['answers']];

            case 'identification':
                return $base + [
                    'question_text' => 'Identify the term described here: "' . $description . '."',
                    'accepted_answers' => $fact['answers'],
                ];

            case 'multiple_choice':
                $distractors = $this->pickOtherTerms($fact, $facts, 3);
                if (count($distractors) < 2) {
                    return null;
                }
                $choices = array_merge([$fact['term']], $distractors);
                shuffle($choices);
                return $base + [
                    'question_text' => 'Which term matches this description: "' . $description . '"?',
                    'choices' => $choices,
                    'correct_index' => array_search($fact['term'], $choices, true),
                ];

            case 'true_false':
                $makeFalse = random_int(0, 1) === 1;
                $term = $fact['term'];
                if ($makeFalse) {
                    $other = $this->pickOtherTerms($fact, $facts, 1);
                    if (empty($other)) {
                        $makeFalse = false;
                    } else {
                        $term = $other[0];
                    }
                }
                return $base + [
                    'question_text' => '"' . $description . '" describes ' . $term . '.',
                    'correct_tf' => $makeFalse ? 'false' : 'true',
                ];
        }
        return null;
    }

    /** Other extracted terms, preferring the same source, that do not appear in this fact's definition. */
    private function pickOtherTerms(array $fact, array $facts, int $limit): array
    {
        $same = [];
        $other = [];
        $self = QuizAnswerGrader::normalize($fact['term']);
        foreach ($facts as $candidate) {
            $key = QuizAnswerGrader::normalize($candidate['term']);
            if ($key === $self || mb_stripos($fact['definition'], $candidate['term']) !== false) {
                continue;
            }
            if ($candidate['label'] === $fact['label']) {
                $same[$key] = $candidate['term'];
            } else {
                $other[$key] = $candidate['term'];
            }
        }
        $pool = array_values($same + $other);
        return array_slice($pool, 0, $limit);
    }
}
