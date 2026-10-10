<?php

namespace Tests\Unit\AutoPoster;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/*
 * vendor/ is a symlink to the live tree, so Composer's PSR-4 map resolves
 * Fujiba\AutoPoster\ to /var/www/bagisto/packages/... (LIVE code). Prepend a
 * loader so this worktree's copy is what gets tested.
 */
spl_autoload_register(function (string $class) {
    $prefix = 'Fujiba\\AutoPoster\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__, 3).'/packages/Fujiba/AutoPoster/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
}, true, true);

/**
 * Boots the app with the database pointed at an unroutable, non-existent
 * MySQL server so that NO test in this suite can ever read from or write to
 * the shared production database (the worktree's .env is a symlink to live).
 * pdo_sqlite is not installed on this host, so there is no in-memory DB:
 * any test that accidentally executes a query fails with a connection error
 * instead of touching real data. Query builders are still usable for toSql().
 */
abstract class AutoPosterTestCase extends BaseTestCase
{
    public function createApplication()
    {
        foreach ([
            'APP_ENV'         => 'testing',
            'DB_CONNECTION'   => 'mysql',
            'DB_HOST'         => '127.0.0.254',
            'DB_PORT'         => '1',
            'DB_DATABASE'     => 'autoposter_unit_tests_no_db',
            'DB_USERNAME'     => 'nobody',
            'DB_PASSWORD'     => 'nobody',
            'CACHE_STORE'     => 'array',
            'SESSION_DRIVER'  => 'array',
            'QUEUE_CONNECTION'=> 'sync',
            'LOG_CHANNEL'     => 'null',
        ] as $k => $v) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }

        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        // Belt and braces: refuse to run if anything resolved a real host.
        foreach (config('database.connections') as $name => $c) {
            if (($c['driver'] ?? null) === 'mysql' && ! in_array($c['host'] ?? null, ['127.0.0.254'], true)) {
                config(["database.connections.{$name}.host" => '127.0.0.254', "database.connections.{$name}.port" => '1']);
            }
        }

        return $app;
    }
}
