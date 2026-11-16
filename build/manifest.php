<?php

declare(strict_types=1);

/**
 * Writes the generated parts of teme-plugin.json: the capabilities, public routes, jobs, owned tables,
 * Tools link and settings. The identity keys (name, code, version, ...) stay as they are in the file.
 * Run `php build/manifest.php` after changing this file or src/Settings/Declaration.php; a test fails
 * if the file is out of date.
 */

require dirname(__DIR__).'/src/Settings/Declaration.php';

use KraftySprouts\AdPlacr\Settings\Declaration;

$path = dirname(__DIR__).'/teme-plugin.json';
$manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

$manifest['capabilities'] = ['content.insert', 'routes.public', 'jobs'];

$manifest['routes'] = [
    [
        'id' => 'track',
        'method' => 'POST',
        'path' => '/{code}/track',
        'handler' => 'KraftySprouts\\AdPlacr\\Stats\\TrackRoute',
        'verify' => ['kind' => 'custom', 'class' => 'KraftySprouts\\AdPlacr\\Stats\\BeaconCheck', 'secret' => Declaration::TRACKING_KEY],
        'maxBody' => 2048,
        'throttle' => 120,
    ],
    [
        'id' => 'script',
        'method' => 'GET',
        'path' => '/{code}/tracking.js',
        'handler' => 'KraftySprouts\\AdPlacr\\Stats\\TrackingScript',
        'cache' => ['query' => [], 'varyAccept' => false, 'lifetime' => 3600],
    ],
];

$manifest['jobs'] = [['key' => 'prune', 'label' => 'Remove old ad statistics', 'tries' => 1]];
$manifest['schedules'] = [['job' => 'prune', 'every' => 'day']];
$manifest['owns'] = ['tables' => ['adplacr_daily', 'adplacr_live']];
$manifest['hooks'] = ['SettingsChanged'];

$manifest['contributes'] = [
    'settings' => Declaration::settings(),
    'admin' => [
        ['surface' => 'tools', 'id' => 'adplacr', 'label' => 'Ad Placr', 'description' => 'Set up the ads Ad Placr places in your articles.', 'icon' => 'chart-line', 'to' => 'settings'],
    ],
];

file_put_contents(
    $path,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
);

echo 'Wrote '.count($manifest['contributes']['settings'])." settings, 2 routes and 1 job to teme-plugin.json\n";
