<?php
foreach(glob(__DIR__ . '/../app/Core/*.php') as $f) {
    echo basename($f) . ":\n";
    $c = file_get_contents($f);
    preg_match_all('/public\s+(?:static\s+)?function\s+(\w+)/', $c, $m);
    echo "  " . implode(', ', $m[1]) . "\n";
}
echo "\nMIDDLEWARE:\n";
foreach(glob(__DIR__ . '/../app/Middleware/*.php') as $f) {
    echo basename($f) . ":\n";
    $c = file_get_contents($f);
    preg_match_all('/public\s+(?:static\s+)?function\s+(\w+)/', $c, $m);
    echo "  " . implode(', ', $m[1]) . "\n";
}
