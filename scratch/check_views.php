<?php
$views = glob(__DIR__ . '/../app/Views/*.php');
$subViews = glob(__DIR__ . '/../app/Views/*/*.php');
$subSubViews = glob(__DIR__ . '/../app/Views/*/*/*.php');
$allViews = array_merge($views, $subViews, $subSubViews);

$viewMap = [];
$rootPath = realpath(__DIR__ . '/..');

foreach ($allViews as $v) {
    $rel = str_replace($rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR, '', realpath($v));
    $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
    $content = file_get_contents($v);
    
    // check layout includes
    $includes = [];
    if (strpos($content, 'admin_navbar') !== false) $includes[] = 'admin_navbar';
    if (strpos($content, 'sidebar') !== false) $includes[] = 'sidebar';
    if (strpos($content, 'applicant_navbar') !== false) $includes[] = 'applicant_navbar';
    if (strpos($content, 'layout_header') !== false) $includes[] = 'layout_header';
    if (strpos($content, 'header.php') !== false) $includes[] = 'header';
    if (strpos($content, 'footer.php') !== false) $includes[] = 'footer';
    
    $viewMap[$rel] = $includes;
}

echo "Total Views: " . count($viewMap) . "\n";
foreach (array_slice($viewMap, 0, 15) as $path => $inc) {
    echo "$path => " . implode(', ', $inc) . "\n";
}
