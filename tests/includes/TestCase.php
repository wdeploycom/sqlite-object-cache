<?php

namespace SQLiteObjectCache\Tests;

// Check if WordPress test framework is available, fallback to PHPUnit
if ( class_exists( 'WP_UnitTestCase' ) ) {
    abstract class BaseTestCase extends \WP_UnitTestCase {}
} else {
    abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {}
}

/**
 * Base test case for SQLite Object Cache tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * SQLite test database path
     */
    protected string $test_db_path;

    /**
     * Backup of global state
     */
    protected array $globals_backup = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary SQLite database for testing
        $this->test_db_path = sys_get_temp_dir() . '/sqlite_object_cache_test_' . uniqid() . '.sqlite';
        
        // Backup globals that might be modified during tests
        $this->globals_backup = [
            'wp_object_cache' => $GLOBALS['wp_object_cache'] ?? null,
        ];
        
        // If using mock WordPress (non WP_UnitTestCase), initialize it
        if ( ! class_exists( 'WP_UnitTestCase' ) && class_exists( 'SQLiteObjectCache\Tests\MockWordPress' ) ) {
            MockWordPress::reset();
        }
    }

    protected function tearDown(): void
    {
        // Clean up test database
        if (file_exists($this->test_db_path)) {
            unlink($this->test_db_path);
        }
        
        // Clean up any related SQLite files
        foreach (glob($this->test_db_path . '*') as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Restore globals (only when not using WP_UnitTestCase which handles this)
        if ( ! class_exists( 'WP_UnitTestCase' ) ) {
            foreach ($this->globals_backup as $key => $value) {
                if ($value === null) {
                    unset($GLOBALS[$key]);
                } else {
                    $GLOBALS[$key] = $value;
                }
            }
        }
        
        parent::tearDown();
    }

    /**
     * Create a test SQLite Object Cache instance
     */
    protected function createCacheInstance(): \SQLite_Object_Cache
    {
        return new \SQLite_Object_Cache(__FILE__, '1.5.6');
    }

    /**
     * Assert that SQLite extension is available
     */
    protected function assertSQLiteAvailable(): void
    {
        $this->assertTrue(
            class_exists('SQLite3') && extension_loaded('sqlite3'),
            'SQLite3 extension must be available for tests'
        );
    }

    /**
     * Create a temporary directory for testing
     */
    protected function createTempDir(): string
    {
        $temp_dir = sys_get_temp_dir() . '/sqlite_object_cache_test_' . uniqid();
        if (!mkdir($temp_dir, 0755, true)) {
            $this->fail("Could not create temporary directory: $temp_dir");
        }
        return $temp_dir;
    }

    /**
     * Compatibility method for assertStringContains (available in newer PHPUnit versions)
     */
    public function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString($needle, $haystack, $message);
        } else {
            $this->assertContains($needle, $haystack, $message);
        }
    }
}