<?php
$dir = __DIR__ . '/../app/Views/';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$hasError = false;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $output = shell_exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1');
        if (strpos($output, 'Errors parsing') !== false || strpos($output, 'Parse error') !== false) {
            echo "Error in: " . $file->getPathname() . "\n";
            $hasError = true;
        }
    }
}

if (!$hasError) {
    echo "No syntax errors found in app/Views/.\n";
}
