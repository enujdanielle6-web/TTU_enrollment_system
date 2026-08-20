<?php
$files = [
    __DIR__ . '/../app/Views/admin/registrar/college_queue.php',
    __DIR__ . '/../app/Views/admin/registrar/shs_queue.php',
    __DIR__ . '/../app/Views/admin/registrar/students.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = str_replace("';)", "')", $content);
        file_put_contents($file, $newContent);
        echo "Fixed " . basename($file) . "\n";
    }
}
