<?php
/**
 * Builds a clean upload folder for InfinityFree:
 *
 *   php scripts/build_infinityfree_package.php
 *
 * Output: dist/infinityfree/htdocs/   -> upload the CONTENTS of this folder into your domain's htdocs/
 *
 * Only runtime files are copied (allow-list). Never copied: .env, .git, docs, *.md, SQL dumps,
 * database/, scripts/, setup_database.php, composer manifests, logs, backups and any user uploads
 * (applicant documents, payment proofs, LMS files) - those are private data.
 *
 * config/config.php is created from config/config.example.php (CHANGE_ME placeholders) unless the
 * output already contains one, so values you filled in are kept across rebuilds.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$out = $root . '/dist/infinityfree/htdocs';

// Folders copied recursively (relative to project root) and paths skipped inside them.
$copyDirs = ['app', 'css', 'images', 'js', 'public', 'vendor'];
$copyFiles = ['.htaccess', 'config/bootstrap.php', 'config/database.php', 'storage/.htaccess', 'uploads/.htaccess', 'uploads/documents/.htaccess'];
$skipPrefixes = [
    'app/uploads/',                              // legacy duplicate upload store (private files)
    'vendor/phpmailer/phpmailer/get_oauth_token.php', // standalone OAuth helper, not used by the app
];
$skipPatterns = ['/\.md$/i', '/(^|\/)\.DS_Store$/', '/(^|\/)Thumbs\.db$/i', '/\.log$/i'];
// Writable folders the application stores uploads/logs in (created empty).
$emptyDirs = [
    'uploads/documents', 'uploads/payments', 'uploads/scholarships',
    'storage/logs', 'storage/uploads/lms/materials', 'storage/uploads/lms/submissions',
];

function rrmdir(string $dir, string $keep): void
{
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $item) {
        $path = str_replace('\\', '/', $item->getPathname());
        if ($path === $keep) {
            continue;
        }
        if ($item->isDir()) {
            @rmdir($path); // fails silently for the folder that still contains $keep
        } else {
            unlink($path);
        }
    }
}

// Preserve a previously filled-in config/config.php across rebuilds.
$configOut = $out . '/config/config.php';
$savedConfig = is_file($configOut) ? file_get_contents($configOut) : null;
if (is_dir($out)) {
    rrmdir($out, $configOut);
}

$copied = 0;
$bytes = 0;
$copy = static function (string $rel) use ($root, $out, &$copied, &$bytes): void {
    $dest = $out . '/' . $rel;
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0755, true);
    }
    copy($root . '/' . $rel, $dest);
    $copied++;
    $bytes += filesize($dest);
};

foreach ($copyDirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isDir()) {
            continue;
        }
        $rel = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($rel, $prefix)) {
                continue 2;
            }
        }
        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $rel)) {
                continue 2;
            }
        }
        $copy($rel);
    }
}
foreach ($copyFiles as $rel) {
    $copy($rel);
}
foreach ($emptyDirs as $dir) {
    if (!is_dir($out . '/' . $dir)) {
        mkdir($out . '/' . $dir, 0755, true);
    }
}

if ($savedConfig !== null) {
    file_put_contents($configOut, $savedConfig);
    $configNote = 'kept your existing config/config.php';
} else {
    copy($root . '/config/config.example.php', $configOut);
    $configNote = 'created config/config.php from the template - FILL IN the CHANGE_ME values before uploading';
}

// Safety net: refuse to finish if anything sensitive slipped in.
$forbidden = [];
$inodes = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
    $inodes++;
    $rel = substr(str_replace('\\', '/', $item->getPathname()), strlen($out) + 1);
    if ($item->isFile() && (preg_match('/(^|\/)\.env|\.sql$|\.md$|(^|\/)setup_database\.php$|(^|\/)\.git(\/|$)/i', $rel)
        || (preg_match('#^(uploads|storage)/#', $rel) && basename($rel) !== '.htaccess'))) {
        $forbidden[] = $rel;
    }
    if ($item->isFile() && $item->getSize() > 10 * 1024 * 1024) {
        $forbidden[] = "$rel (larger than InfinityFree's 10 MB file limit)";
    }
}
if ($forbidden) {
    fwrite(STDERR, "ABORT - sensitive or oversized files found in package:\n  " . implode("\n  ", $forbidden) . "\n");
    exit(1);
}

$hasPlaceholders = str_contains((string) file_get_contents($configOut), 'CHANGE_ME');
printf(
    "Package ready: %s\n  %d files, %.1f MB, %d inodes (InfinityFree free limit is about 30,000)\n  %s\n%s",
    $out,
    $copied,
    $bytes / 1048576,
    $inodes,
    $configNote,
    $hasPlaceholders ? "  WARNING: config/config.php still contains CHANGE_ME placeholders.\n" : ''
);
