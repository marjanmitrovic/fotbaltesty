<?php

declare(strict_types=1);

use App\Bootstrap;

$appRoot = __DIR__ . '/fotbaltesty_app';

require $appRoot . '/vendor/autoload.php';

Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
