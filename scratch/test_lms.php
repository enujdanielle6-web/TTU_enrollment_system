<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsService.php';

$lmsService = new \App\Services\LmsService();
$course = $lmsService->getCourseDetails(13);

header('Content-Type: application/json');
echo json_encode($course, JSON_PRETTY_PRINT);
