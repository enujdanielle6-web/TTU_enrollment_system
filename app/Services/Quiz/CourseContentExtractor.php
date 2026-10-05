<?php
namespace App\Services\Quiz;

/**
 * Reads plain text out of course modules and their uploaded materials so a generator
 * can ground questions in it.
 *
 * Supported sources:
 *   - module title + description (always)
 *   - .txt / .md materials
 *   - .docx (Word) and .pptx (PowerPoint) materials, read with PHP's ZipArchive
 *   - .pdf materials only when the smalot/pdfparser Composer package is installed
 * Everything else is reported back as skipped with a reason; nothing is guessed.
 */
class CourseContentExtractor
{
    public const MAX_FILE_BYTES = 15728640;       // 15 MB on disk
    public const MAX_XML_BYTES = 20971520;        // 20 MB uncompressed per document part
    public const MAX_TEXT_CHARS_PER_SOURCE = 200000;

    public const TEXT_EXTENSIONS = ['txt', 'md'];
    public const OFFICE_EXTENSIONS = ['docx', 'pptx'];

    public static function pdfSupported(): bool
    {
        if (class_exists('Smalot\\PdfParser\\Parser')) {
            return true;
        }
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
        return class_exists('Smalot\\PdfParser\\Parser');
    }

    /**
     * Whether a material can be read, judged by extension only (used to label the picker).
     *
     * @return array{supported: bool, reason: string}
     */
    public static function supportFor(string $fileName): array
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, self::TEXT_EXTENSIONS, true)) {
            return ['supported' => true, 'reason' => 'Plain text'];
        }
        if (in_array($ext, self::OFFICE_EXTENSIONS, true)) {
            if (!class_exists('ZipArchive')) {
                return ['supported' => false, 'reason' => 'PHP zip extension is not enabled'];
            }
            return ['supported' => true, 'reason' => $ext === 'docx' ? 'Word document' : 'PowerPoint slides'];
        }
        if ($ext === 'pdf') {
            return self::pdfSupported()
                ? ['supported' => true, 'reason' => 'PDF text layer']
                : ['supported' => false, 'reason' => 'PDF reading needs the smalot/pdfparser package'];
        }
        return ['supported' => false, 'reason' => ($ext === '' ? 'Unknown' : '.' . $ext) . ' files cannot be read'];
    }

    /**
     * @param array $modules   lms_modules rows (id, title, description), already scoped to one course
     * @param array $materials materials to read: ['id','file_name','file_path','module_title','resolved_path']
     * @return array{segments: array<int, array{label: string, text: string}>, skipped: array<int, array{label: string, reason: string}>}
     */
    public function extract(array $modules, array $materials): array
    {
        $segments = [];
        $skipped = [];

        foreach ($modules as $module) {
            $text = trim(($module['title'] ?? '') . ".\n" . ($module['description'] ?? ''));
            if (trim((string)($module['description'] ?? '')) !== '') {
                $segments[] = ['label' => 'Module: ' . $module['title'], 'text' => $text];
            }
        }

        foreach ($materials as $material) {
            $label = ($material['module_title'] ?? 'Module') . ' / ' . $material['file_name'];
            $support = self::supportFor((string)($material['file_path'] ?: $material['file_name']));
            if (!$support['supported']) {
                $skipped[] = ['label' => $label, 'reason' => $support['reason']];
                continue;
            }
            $path = $material['resolved_path'] ?? null;
            if (!$path || !is_file($path)) {
                $skipped[] = ['label' => $label, 'reason' => 'File is missing on the server'];
                continue;
            }
            if (filesize($path) > self::MAX_FILE_BYTES) {
                $skipped[] = ['label' => $label, 'reason' => 'File is larger than 15 MB'];
                continue;
            }
            try {
                $text = $this->readFile($path, strtolower(pathinfo((string)($material['file_path'] ?: $material['file_name']), PATHINFO_EXTENSION)));
            } catch (\Throwable $e) {
                error_log('Quiz content extraction failed for material ' . ($material['id'] ?? '?') . ': ' . $e->getMessage());
                $text = '';
            }
            $text = trim($text);
            if ($text === '') {
                $skipped[] = ['label' => $label, 'reason' => 'No readable text found (scanned or image-only files cannot be read)'];
                continue;
            }
            $segments[] = ['label' => $label, 'text' => mb_substr($text, 0, self::MAX_TEXT_CHARS_PER_SOURCE)];
        }

        return ['segments' => $segments, 'skipped' => $skipped];
    }

    private function readFile(string $path, string $ext): string
    {
        if (in_array($ext, self::TEXT_EXTENSIONS, true)) {
            $text = (string)file_get_contents($path);
            if (str_contains($text, "\0")) {
                return '';
            }
            return QuizQuestionValidator::cleanText($text);
        }
        if ($ext === 'docx') {
            return $this->readOfficeXml($path, ['word/document.xml'], 'p', 't');
        }
        if ($ext === 'pptx') {
            return $this->readOfficeXml($path, null, 'p', 't');
        }
        if ($ext === 'pdf' && self::pdfSupported()) {
            $parserClass = 'Smalot\\PdfParser\\Parser';
            $parser = new $parserClass();
            return QuizQuestionValidator::cleanText($parser->parseFile($path)->getText());
        }
        return '';
    }

    /**
     * Text of a lesson file for the student preview window, split into sections
     * (one per slide for .pptx, one for a .docx or plain-text file).
     *
     * @return array<int, array{label: string, paragraphs: string[]}>
     */
    public function previewSections(string $path, string $ext): array
    {
        $ext = strtolower($ext);
        if (!is_file($path) || filesize($path) > self::MAX_FILE_BYTES) {
            return [];
        }
        if (in_array($ext, self::TEXT_EXTENSIONS, true)) {
            $text = (string)file_get_contents($path);
            if (str_contains($text, "\0")) {
                return [];
            }
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), 'strlen'));
            return $lines ? [['label' => 'Document', 'paragraphs' => $lines]] : [];
        }
        if (!class_exists('ZipArchive')) {
            return [];
        }
        if ($ext === 'docx') {
            $parts = $this->officeParagraphsByPart($path, ['word/document.xml'], 'p', 't');
            $paragraphs = $parts['word/document.xml'] ?? [];
            return $paragraphs ? [['label' => 'Document', 'paragraphs' => $paragraphs]] : [];
        }
        if ($ext === 'pptx') {
            $sections = [];
            $number = 0;
            foreach ($this->officeParagraphsByPart($path, null, 'p', 't') as $paragraphs) {
                $number++;
                $sections[] = ['label' => 'Slide ' . $number, 'paragraphs' => $paragraphs];
            }
            return $sections;
        }
        return [];
    }

    /**
     * Pulls paragraph text out of an Office Open XML package.
     *
     * @param string[]|null $parts exact entry names, or null for every ppt/slides/slideN.xml in slide order
     */
    private function readOfficeXml(string $path, ?array $parts, string $paragraphTag, string $textTag): string
    {
        $paragraphs = array_merge([], ...array_values($this->officeParagraphsByPart($path, $parts, $paragraphTag, $textTag)));
        return QuizQuestionValidator::cleanText(implode("\n", $paragraphs));
    }

    /**
     * Paragraph text of each part of an Office Open XML package, in order.
     *
     * @param string[]|null $parts exact entry names, or null for every ppt/slides/slideN.xml in slide order
     * @return array<string, string[]> part name => non-empty paragraphs
     */
    private function officeParagraphsByPart(string $path, ?array $parts, string $paragraphTag, string $textTag): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return [];
        }
        if ($parts === null) {
            $parts = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string)$zip->getNameIndex($i);
                if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
                    $parts[(int)$m[1]] = $name;
                }
            }
            ksort($parts);
        }

        $result = [];
        foreach ($parts as $part) {
            $stat = $zip->statName($part);
            if ($stat === false || $stat['size'] > self::MAX_XML_BYTES) {
                continue;
            }
            $xml = $zip->getFromName($part);
            if ($xml === false || $xml === '') {
                continue;
            }
            $dom = new \DOMDocument();
            // LIBXML_NONET blocks network access; entity substitution stays off (no LIBXML_NOENT).
            if (!@$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                continue;
            }
            $paragraphs = [];
            foreach ($dom->getElementsByTagNameNS('*', $paragraphTag) as $p) {
                $line = '';
                foreach ($p->getElementsByTagNameNS('*', $textTag) as $t) {
                    $line .= $t->textContent;
                }
                $line = trim($line);
                if ($line !== '') {
                    $paragraphs[] = $line;
                }
            }
            $result[$part] = $paragraphs;
        }
        $zip->close();
        return $result;
    }
}
