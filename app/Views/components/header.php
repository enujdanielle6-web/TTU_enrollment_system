<?php
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
  <link href="/sia/css/main.css?v=<?= filemtime(__DIR__ . '/../../../css/main.css') ?>" rel="stylesheet">
</head>
<body>

