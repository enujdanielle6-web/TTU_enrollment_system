<?php
$files = [
    __DIR__ . '/../app/Views/applicant/status.php',
    __DIR__ . '/../app/Views/applicant/enroll.php',
    __DIR__ . '/../app/Views/applicant/documents.php',
    __DIR__ . '/../app/Views/applicant/dashboard.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = str_replace("';)", "')", $content);
        file_put_contents($file, $newContent);
        echo "Fixed " . basename($file) . "\n";
    }
}
