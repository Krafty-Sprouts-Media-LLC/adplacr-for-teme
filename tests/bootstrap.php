<?php

declare(strict_types=1);

/**
 * Loads the teme-dev site's Composer autoloader (Pest, Teme's contracts, Teme's own Tests\TestCase)
 * and maps this plugin's namespace, which Teme's autoloader does not know outside a running site.
 */
$site = getenv('TEME_DEV') ?: dirname(__DIR__, 2).'/teme-dev';

require $site.'/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'KraftySprouts\\AdPlacr\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $file = dirname(__DIR__).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

    if (is_file($file)) {
        require_once $file;
    }
});

require __DIR__.'/helpers.php';
