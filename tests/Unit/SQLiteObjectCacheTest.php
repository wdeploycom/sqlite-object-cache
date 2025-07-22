<?php

namespace SQLiteObjectCache\Tests\Unit;

use SQLiteObjectCache\Tests\TestCase;

/**
 * Tests for SQLite_Object_Cache main class
 */
class SQLiteObjectCacheTest extends TestCase
{
    private \SQLite_Object_Cache $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSQLiteAvailable();
        $this->plugin = new \SQLite_Object_Cache(__FILE__, '1.5.6');
    }

    public function testConstructorInitializesProperties(): void
    {
        $this->assertEquals('1.5.6', $this->plugin->_version);
        $this->assertEquals('sqlite_object_cache', $this->plugin->_token);
        $this->assertNotEmpty($this->plugin->dir);
        $this->assertNotEmpty($this->plugin->assets_dir);
        $this->assertNotEmpty($this->plugin->dropinfilesource);
        $this->assertNotEmpty($this->plugin->dropinfiledest);
    }

    public function testHasSqliteReturnsTrueWhenAvailable(): void
    {
        $result = $this->plugin->has_sqlite();
        $this->assertTrue($result);
    }

    public function testSqliteGetVersionReturnsVersion(): void
    {
        $version = $this->plugin->sqlite_get_version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', $version);
    }

    public function testMinimumSqliteVersionCheck(): void
    {
        $current_version = $this->plugin->sqlite_get_version();
        $minimum_version = $this->plugin->minimum_sqlite_version;
        
        $this->assertGreaterThanOrEqual(
            0,
            version_compare($current_version, $minimum_version),
            "Current SQLite version ($current_version) should meet minimum requirement ($minimum_version)"
        );
    }

    public function testApcuExtensionDetection(): void
    {
        $has_apcu = $this->plugin->apcu_extension_is_enabled();
        $this->assertIsBool($has_apcu);
        
        if (function_exists('apcu_enabled')) {
            $this->assertEquals(apcu_enabled(), $has_apcu);
        } else {
            $this->assertFalse($has_apcu);
        }
    }

    public function testApcuActivationStatus(): void
    {
        $is_activated = $this->plugin->apcu_is_activated();
        $this->assertIsBool($is_activated);
        
        // APCu should only be activated if both the constant is set and extension is enabled
        if (defined('WP_SQLITE_OBJECT_CACHE_APCU') && WP_SQLITE_OBJECT_CACHE_APCU) {
            $this->assertEquals($this->plugin->apcu_extension_is_enabled(), $is_activated);
        } else {
            $this->assertFalse($is_activated);
        }
    }

    public function testSyncApcuGlobalToOptionReturnsString(): void
    {
        $result = $this->plugin->sync_apcu_global_to_option(true);
        $this->assertContains($result, ['on', 'off']);
    }

    public function testCleanJobHandlesInvalidCacheGracefully(): void
    {
        // Test with no cache object - should not throw exception
        global $wp_object_cache;
        $wp_object_cache = null;
        
        $this->expectNotToPerformAssertions(); // Just ensure no exceptions
        $this->plugin->clean_job(1.0);
    }

    public function testCleanJobWithValidCacheObject(): void
    {
        // Mock a cache object with required methods
        global $wp_object_cache;
        $wp_object_cache = new class {
            public function sqlite_get_size() { return 1000; }
            public function sqlite_reset_statistics($retention) { return true; }
            public function sqlite_remove_expired() { return false; }
            public function sqlite_delete_old($target, $current) { return true; }
        };
        
        // Should not throw exception
        $this->expectNotToPerformAssertions();
        $this->plugin->clean_job(1.0);
    }

    public function testFileSystemWritingTestRequiresValidPaths(): void
    {
        // Create temporary directories to test with
        $temp_dir = $this->createTempDir();
        $temp_file = $temp_dir . '/object-cache.php';
        
        // Create a simple drop-in file for testing
        file_put_contents($temp_file, "<?php\n/* Version: 1.5.6 */");
        
        // Update the plugin's dropin source path for testing
        $reflection = new \ReflectionClass($this->plugin);
        $property = $reflection->getProperty('dropinfilesource');
        $property->setAccessible(true);
        $property->setValue($this->plugin, $temp_file);
        
        $result = $this->plugin->test_filesystem_writing();
        
        // Clean up
        unlink($temp_file);
        rmdir($temp_dir);
        
        // Result should be either true or WP_Error
        $this->assertTrue($result === true || $result instanceof \WP_Error);
    }

    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('sqlite_object_cache_clean', \SQLite_Object_Cache::CLEAN_EVENT_HOOK);
    }

    public function testDropinPathsAreSet(): void
    {
        $this->assertStringEndsWith('object-cache.php', $this->plugin->dropinfilesource);
        $this->assertStringEndsWith('object-cache.php', $this->plugin->dropinfiledest);
    }

    public function testAssetsUrlIsFormed(): void
    {
        $this->assertStringContains('assets', $this->plugin->assets_url);
        $this->assertStringStartsWith('http', $this->plugin->assets_url);
    }

    public function testScriptSuffixHandlesDebugMode(): void
    {
        // Test depends on SCRIPT_DEBUG constant
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            $this->assertEquals('', $this->plugin->script_suffix);
        } else {
            $this->assertEquals('.min', $this->plugin->script_suffix);
        }
    }
}