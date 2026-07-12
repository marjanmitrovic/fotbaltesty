<?php

declare(strict_types=1);

use App\Bootstrap;

$appRoot = __DIR__ . '/fotbaltesty_app';
$autoload = $appRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Missing fotbaltesty_app/vendor/autoload.php');
}

require $autoload;

Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
