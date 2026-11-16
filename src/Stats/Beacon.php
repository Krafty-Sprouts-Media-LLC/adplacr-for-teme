<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use KraftySprouts\AdPlacr\Settings\Declaration;

/**
 * Reads the body a visitor's browser sends when an ad is seen or clicked. Anything that is not exactly
 * an impression or a click for a real slot and version letter is refused, so the table cannot grow.
 */
final class Beacon
{
    /**
     * @return array{event: string, slot: int, version: string}|null null when the body is not a beacon
     */
    public static function parse(string $body): ?array
    {
        $data = json_decode($body, true);

        if (! is_array($data) || ! is_string($data['event'] ?? null) || ! is_int($data['ad'] ?? null) || ! is_string($data['version'] ?? null)) {
            return null;
        }

        $slot = $data['ad'];

        if (! in_array($data['event'], ['impression', 'click'], true) || $slot < 1 || $slot > Declaration::SLOTS) {
            return null;
        }

        // A version id is its slot number and a letter, so it must agree with the slot.
        if (! in_array($data['version'], [$slot.'a', $slot.'b'], true)) {
            return null;
        }

        return ['event' => $data['event'], 'slot' => $slot, 'version' => $data['version']];
    }
}
