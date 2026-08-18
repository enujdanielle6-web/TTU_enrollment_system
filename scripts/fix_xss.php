<?php

$dir = realpath(__DIR__ . '/../app/Views');

if (!$dir) {
    die("Views directory not found.\n");
}

$directory = new RecursiveDirectoryIterator($dir);
$iterator = new RecursiveIteratorIterator($directory);
$regex = '/<\?=\s*(.+?)\s*\?>/s';

$safeFunctions = [
    'esc', 'htmlspecialchars', 'count', 'empty', 'isset', 
    'number_format', 'date', 'json_encode', 'getCsrfInput', 
    'csrf_token', 'strtoupper', 'strtolower', 'ucfirst', 
    'asset', 'url', 'getSystemSetting', 'formatDisplayDate',
    'getStrandLabel', 'getApplicationStatusBadgeClass',
    'formatApplicationStatus'
];

$updatedFiles = 0;

foreach ($iterator as $info) {
    if ($info->isFile() && $info->getExtension() === 'php') {
        $content = file_get_contents($info->getPathname());
        
        $newContent = preg_replace_callback($regex, function($matches) use ($safeFunctions) {
            $expr = trim($matches[1]);
            
            // Check if it starts with a safe function
            foreach ($safeFunctions as $func) {
                if (preg_match('/^' . $func . '\s*\(/i', $expr)) {
                    return $matches[0]; // Leave as is
                }
            }
            
            // Check if already contains esc or htmlspecialchars anywhere
            if (stripos($expr, 'esc(') !== false || stripos($expr, 'htmlspecialchars(') !== false) {
                return $matches[0];
            }
            
            // Wrap in esc()
            return '<?= esc(' . $expr . ') ?>';
            
        }, $content);
        
        if ($newContent !== $content) {
            file_put_contents($info->getPathname(), $newContent);
            echo "Updated: " . $info->getFilename() . "\n";
            $updatedFiles++;
        }
    }
}

echo "Done. Updated $updatedFiles files.\n";
