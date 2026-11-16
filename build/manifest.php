<?php

declare(strict_types=1);

/**
 * Writes the generated part of teme-plugin.json: the settings. Run `php build/manifest.php`
 * after changing src/Settings/Declaration.php; a test fails if the file is out of date.
 */

require dirname(__DIR__).'/src/Settings/Declaration.php';

use KraftySprouts\AdPlacr\Settings\Declaration;

$path = dirname(__DIR__).'/teme-plugin.json';
$manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

$manifest['contributes']['settings'] = Declaration::settings();

file_put_contents(
    $path,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
);

echo 'Wrote '.count($manifest['contributes']['settings'])." settings to teme-plugin.json\n";
