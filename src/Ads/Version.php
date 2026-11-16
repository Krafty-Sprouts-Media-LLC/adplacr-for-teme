<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

/**
 * One version of an ad: its code, optional mobile code and how often it is chosen.
 * The id is the slot number and a letter, such as `1a`, and never changes, so statistics stay attached.
 */
final readonly class Version
{
    public function __construct(
        public string $id,
        public string $code,
        public string $mobileCode,
        public int $weight,
    ) {}
}
