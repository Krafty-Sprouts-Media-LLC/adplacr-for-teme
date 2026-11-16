<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use Teme\Contracts\Routes\InboundVerifier;
use Teme\Contracts\Routes\PublicRequest;

/**
 * Accepts a beacon only from the plugin's own script: a page of another site cannot add a custom header
 * to a request without the browser asking this site's permission first.
 */
final class BeaconCheck implements InboundVerifier
{
    public function verify(PublicRequest $request, string $secret): bool
    {
        return ($request->headers['x-adplacr'] ?? '') === '1';
    }
}
