<?php
declare(strict_types=1);

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/app'));
$failed = false;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    exec('php -l ' . escapeshellarg($file->getPathname()), $output, $code);
    if ($code !== 0) {
        $failed = true;
        echo implode(PHP_EOL, $output), PHP_EOL;
    }
    $output = [];
}
exit($failed ? 1 : 0);
