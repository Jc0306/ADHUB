<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api/config.php';

class ConfigTest extends TestCase
{
    public function testDbConnection(): void
    {
        $pdo = db();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testRequireMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        require_method('POST'); // should not throw
        $this->assertTrue(true);
    }

    public function testSessionStarts(): void
    {
        start_session();
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testFindProject(): void
    {
        // Just verify function exists
        $this->assertTrue(function_exists('find_project'));
    }

    public function testFindRequest(): void
    {
        $this->assertTrue(function_exists('find_request'));
    }
}