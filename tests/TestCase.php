<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Emails are sent with defer(); run them inline so tests can assert on them.
        $this->withoutDefer();
    }
}
