<?php
$f = 'c:/xampp/htdocs/sia/app/Controllers/Api/ApplicantApiController.php';
$c = file_get_contents($f);
$c = preg_replace('/require_once __DIR__ . \'\/\.\.\/config\/database\.php\';\s*/', '', $c);
$c = preg_replace('/requireApplicantLogin\(\);\s*/', '', $c);
$c = preg_replace('/header\(\'Content-Type: application\/json\'\);\s*/', '', $c);
file_put_contents($f, $c);
echo "Cleaned up ApplicantApiController.php";
