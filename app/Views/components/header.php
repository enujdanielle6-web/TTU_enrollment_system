<?php
// Prevent browser caching of pages to ensure latest data is always shown
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

$pageTitle = $pageTitle ?? 'Triple T University';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

  <link rel="stylesheet" href="/sia/public/vendor/fonts/fonts.css">
  <link href="/sia/public/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="/sia/public/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/sia/css/main.css?v=<?= esc(filemtime(__DIR__ . '/../../../css/main.css')) ?>" rel="stylesheet">
</head>
<body>

