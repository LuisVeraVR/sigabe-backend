<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @property \App\Domain\Users\Models\User $admin
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}
