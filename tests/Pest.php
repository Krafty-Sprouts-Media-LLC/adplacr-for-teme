<?php

declare(strict_types=1);

use Tests\TestCase;

/*
 * Unit tests are plain PHP and need no Teme. Feature tests boot the teme-dev site through
 * Teme's own TestCase, as the handbook's example test does.
 */
pest()->extend(TestCase::class)->in('Feature');
