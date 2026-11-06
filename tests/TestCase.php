<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** The only database tests are ever allowed to touch. */
    public const TEST_DATABASE = 'fsmart_test';

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: RefreshDatabase wipes the database, so never run against
        // anything but the dedicated test database.
        $db = config('database.connections.mysql.database');
        if (config('database.default') === 'mysql' && $db !== self::TEST_DATABASE) {
            throw new RuntimeException("Refusing to run tests against database [$db]; expected [" . self::TEST_DATABASE . '].');
        }
    }
}
