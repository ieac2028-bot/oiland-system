<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The frontend bundle is not part of the test run, so Vite asset lookups are
     * stubbed out. This keeps feature tests focused on server behaviour instead
     * of requiring `npm run build` to pass.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
