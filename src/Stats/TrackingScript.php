<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Stats;

use Teme\Contracts\Routes\PublicRequest;
use Teme\Contracts\Routes\PublicResponse;
use Teme\Contracts\Routes\PublicRoute;

/**
 * GET /adplacr/tracking.js: the small script that reports impressions and clicks, served from the site's
 * own address so the page's content security policy already allows it.
 */
final class TrackingScript implements PublicRoute
{
    public function handle(PublicRequest $request): PublicResponse
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2).'/resources/tracking.js');

        return new PublicResponse(200, 'text/javascript; charset=UTF-8', $script);
    }
}
