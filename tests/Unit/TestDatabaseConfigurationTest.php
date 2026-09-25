<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestDatabaseConfigurationTest extends TestCase
{
    public function test_the_test_suite_uses_an_in_memory_sqlite_database(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}
