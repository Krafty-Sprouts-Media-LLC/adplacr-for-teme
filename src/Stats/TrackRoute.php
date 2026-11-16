<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * POST /adplacr/track: adds one impression or click to today's count. It answers 204 and counts nothing
 * unless the ad version is on the list of countable ones, which is empty while statistics are off.
 * Teme builds a route handler with no arguments, so it cannot read the settings itself (FINDINGS F-16);
 * LiveAds keeps that list in the plugin's own table.
 */
final class TrackRoute implements PublicRoute
{
    public function handle(PublicRequest $request): PublicResponse
    {
        $beacon = Beacon::parse($request->rawBody);

        if ($beacon === null) {
            return new PublicResponse(400, 'text/plain; charset=UTF-8', 'Not a beacon.');
        }

        if (LiveAds::counts($beacon['slot'], $beacon['version'])) {
            Counter::record($beacon['event'], $beacon['slot'], $beacon['version'], gmdate('Y-m-d'));
        }

        return new PublicResponse(204, 'text/plain; charset=UTF-8', '');
    }
}
