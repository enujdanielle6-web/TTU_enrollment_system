<?php
$dir = __DIR__ . '/../app/Views/';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, ';) ?>') !== false) {
            $newContent = str_replace(';) ?>', ') ?>', $content);
            file_put_contents($file->getPathname(), $newContent);
            echo "Fixed " . $file->getPathname() . "\n";
        }
    }
}
