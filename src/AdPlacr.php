<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr;

use Teme\Contracts\Hooks\Hooks;
use Teme\Contracts\Insertions\ArticleFacts;
use Teme\Contracts\Insertions\InsertionContributor;
use Teme\Contracts\Plugins\Plugin;

/**
 * The Ad Placr plugin: places the ads an administrator set up inside published articles.
 * Scaffold only for now; each behaviour arrives test-first (see DECISIONS.md D-06).
 */
final class AdPlacr implements InsertionContributor, Plugin
{
    public function register(Hooks $hooks): void {}

    public function precedence(): string
    {
        return 'plugin';
    }

    public function insertions(ArticleFacts $article): array
    {
        return [];
    }
}
