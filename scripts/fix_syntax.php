<?php
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app/Views/'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, "? 'active' : '';)") !== false) {
        $newContent = str_replace("? 'active' : '';)", "? 'active' : '')", $content);
        file_put_contents($file, $newContent);
        echo "Fixed $file\n";
    }
}
