<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

/**
 * One ad that is Active and has something to show: where it goes, who it is for, and its versions.
 * Built by Ads::fromSettings(); an ad that could not be shown is never built.
 */
final readonly class Ad
{
    /**
     * @param  list<Version>  $versions  at least one
     * @param  list<string>  $devices  desktop, tablet and mobile, at least one
     * @param  list<string>  $types  content types; empty means all
     * @param  array<string, list<string>>  $terms  term ids by taxonomy; empty means all
     * @param  list<string>  $languages  empty means all
     * @param  int|null  $start  Unix time the ad starts, or null
     * @param  int|null  $end  Unix time the ad ends, or null
     */
    public function __construct(
        public int $slot,
        public string $name,
        public string $position,
        public int $paragraph,
        public int $repeatMax,
        public int $headingLevel,
        public int $headingNth,
        public array $versions,
        public string $alignment,
        public array $devices,
        public array $types,
        public array $terms,
        public array $languages,
        public ?int $start,
        public ?int $end,
    ) {}
}
