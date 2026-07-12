<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;
use RuntimeException;

final class Bootstrap
{
    public static function boot(): Configurator
    {
        $root = dirname(__DIR__);
        self::loadEnv($root . '/.env');

        $configurator = new Configurator();
        $configurator->setDebugMode(filter_var(self::env('APP_DEBUG', '0'), FILTER_VALIDATE_BOOL));
        $configurator->enableTracy($root . '/log');
        $configurator->setTempDirectory($root . '/temp');
        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addDynamicParameters([
            'databaseDsn' => self::requiredEnv('DB_DSN'),
            'databaseUser' => self::requiredEnv('DB_USER'),
            'databasePassword' => self::requiredEnv('DB_PASSWORD'),
        ]);
        $configurator->addConfig($root . '/config/common.neon');

        return $configurator;
    }

    private static function env(string $name, ?string $default = null): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return ($value === false || $value === null || $value === '')
            ? $default
            : (string) $value;
    }

    private static function requiredEnv(string $name): string
    {
        return self::env($name)
            ?? throw new RuntimeException(sprintf('Missing required environment variable "%s".', $name));
    }

    private static function loadEnv(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
