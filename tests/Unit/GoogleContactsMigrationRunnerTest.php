<?php

namespace Tests\Unit;

use App\Services\GoogleContactsMigrationRunner;
use Illuminate\Database\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GoogleContactsMigrationRunnerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_date_compatibility_keeps_strict_mode_and_unrelated_settings()
    {
        $mode = GoogleContactsMigrationRunner::ddlSqlMode('STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,NO_ENGINE_SUBSTITUTION');
        $this->assertSame('STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY,NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES', $mode);
        $this->assertSame('ALLOW_INVALID_DATES', GoogleContactsMigrationRunner::ddlSqlMode(''));
        $this->assertSame('ALLOW_INVALID_DATES,STRICT_ALL_TABLES', GoogleContactsMigrationRunner::ddlSqlMode('ALLOW_INVALID_DATES,STRICT_ALL_TABLES'));
    }

    public function test_original_session_mode_is_restored_after_success()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('selectOne')->once()->with('SELECT @@SESSION.sql_mode AS mode', [], false)
            ->andReturn((object) ['mode' => 'STRICT_TRANS_TABLES,NO_ZERO_DATE']);
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', ['STRICT_TRANS_TABLES,ALLOW_INVALID_DATES'])->andReturn(true);
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', ['STRICT_TRANS_TABLES,NO_ZERO_DATE'])->andReturn(true);
        $this->assertSame(7, (new GoogleContactsMigrationRunner)->withLegacyDateMode($connection, function () { return 7; }));
    }

    public function test_original_session_mode_is_restored_when_schema_operation_fails()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('selectOne')->once()->andReturn((object) ['mode' => 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE']);
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', ['STRICT_TRANS_TABLES,ALLOW_INVALID_DATES'])->andReturn(true);
        $connection->shouldReceive('statement')->once()->ordered()->with('SET SESSION sql_mode = ?', ['STRICT_TRANS_TABLES,NO_ZERO_IN_DATE'])->andReturn(true);
        $this->expectExceptionMessage('schema-test-failure');
        (new GoogleContactsMigrationRunner)->withLegacyDateMode($connection, function () { throw new RuntimeException('schema-test-failure'); });
    }

    public function test_other_database_drivers_are_not_sent_mysql_session_commands()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');
        $connection->shouldNotReceive('selectOne');
        $connection->shouldNotReceive('statement');
        $this->assertTrue((new GoogleContactsMigrationRunner)->withLegacyDateMode($connection, function () { return true; }));
    }
}
