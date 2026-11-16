<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr;

use DateTimeZone;
use KraftySprouts\AdPlacr\Ads\Placer;
use KraftySprouts\AdPlacr\Stats\LiveAds;
use KraftySprouts\AdPlacr\Stats\PruneHandler;
use Teme\Contracts\Settings\SettingsChanged;
use Teme\Contracts\Hooks\Hooks;
use Teme\Contracts\Insertions\ArticleFacts;
use Teme\Contracts\Insertions\InsertionContributor;
use Teme\Contracts\Jobs\PluginHasJobs;
use Teme\Contracts\Plugins\Plugin;
use Teme\Contracts\Settings\SettingsReader;
use Throwable;

/**
 * The Ad Placr plugin: places the ads an administrator set up in its settings inside published articles.
 * Teme asks it for insertions once per article page; the work is done by Placer.
 */
final class AdPlacr implements InsertionContributor, Plugin, PluginHasJobs
{
    /** The plugin's settings group: `plugin.` and the plugin's code. */
    public const string GROUP = 'plugin.adplacr';

    public function __construct(private readonly SettingsReader $settings) {}

    public function register(Hooks $hooks): void
    {
        // Saved settings decide which ad versions the tracking route may count (see Stats\LiveAds).
        $hooks->on(SettingsChanged::class, function (SettingsChanged $saved): void {
            if ($saved->group === self::GROUP) {
                LiveAds::sync($this->settings->group(self::GROUP));
            }
        });
    }

    public function jobs(): array
    {
        return ['prune' => new PruneHandler($this->settings)];
    }

    public function precedence(): string
    {
        return 'plugin';
    }

    public function insertions(ArticleFacts $article): array
    {
        try {
            return Placer::insertionsFor($this->settings->group(self::GROUP), $this->siteZone(), $article, time());
        } catch (Throwable) {
            // An ad is never worth a broken article: if anything goes wrong, the article is drawn without ads.
            return [];
        }
    }

    private function siteZone(): DateTimeZone
    {
        try {
            return new DateTimeZone($this->settings->string('general', 'timezone'));
        } catch (Throwable) {
            return new DateTimeZone('UTC');
        }
    }
}
