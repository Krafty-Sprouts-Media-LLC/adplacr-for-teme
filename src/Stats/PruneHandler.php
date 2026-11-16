<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use KraftySprouts\AdPlacr\AdPlacr;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Jobs\JobHandler;
use Teme\Contracts\Settings\SettingsReader;

/**
 * The daily job that removes statistics older than the retention window the administrator chose.
 */
final class PruneHandler implements JobHandler
{
    public function __construct(private readonly SettingsReader $settings) {}

    public function handle(JobContext $context): void
    {
        $days = $this->settings->group(AdPlacr::GROUP)['retention_days'] ?? '90';
        $days = is_numeric($days) ? max(7, min(3650, (int) $days)) : 90;

        Counter::pruneBefore(gmdate('Y-m-d', time() - $days * 86400));
    }
}
