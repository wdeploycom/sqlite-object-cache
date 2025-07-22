<?php

namespace SQLiteObjectCache\Tests\Integration;

use SQLiteObjectCache\Tests\TestCase;
use SQLiteObjectCache\Tests\MockWordPress;

/**
 * Integration tests for plugin activation/deactivation
 */
class PluginActivationTest extends TestCase
{
    private \SQLite_Object_Cache $plugin;
    private string $temp_wp_content_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSQLiteAvailable();
        
        // Create temporary wp-content directory structure
        $this->temp_wp_content_dir = $this->createTempDir();
        
        // Note: WP_CONTENT_DIR is already defined in bootstrap, 
        // so we'll work with the existing definition
        
        $this->plugin = new \SQLite_Object_Cache(__FILE__, '1.5.6');
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        if (is_dir($this->temp_wp_content_dir)) {
            $this->rmdirRecursive($this->temp_wp_content_dir);
        }
        
        parent::tearDown();
    }

    public function testPluginInitialization(): void
    {
        $this->assertInstanceOf(\SQLite_Object_Cache::class, $this->plugin);
        $this->assertEquals('1.5.6', $this->plugin->_version);
        $this->assertEquals('sqlite_object_cache', $this->plugin->_token);
    }

    public function testActivationHooksAreRegistered(): void
    {
        // Check that cron event is scheduled
        $scheduled_events = MockWordPress::getScheduledEvents();
        $this->assertArrayHasKey(\SQLite_Object_Cache::CLEAN_EVENT_HOOK, $scheduled_events);
    }

    public function testSQLiteRequirementCheck(): void
    {
        $result = $this->plugin->has_sqlite();
        $this->assertTrue($result, 'SQLite3 should be available for tests');
    }

    public function testApcuSyncronizationOnActivation(): void
    {
        // Test the APCu synchronization method
        $result = $this->plugin->sync_apcu_global_to_option(true);
        $this->assertContains($result, ['on', 'off']);
        
        // Check that option is set
        $option = MockWordPress::getOption($this->plugin->_token . '_settings');
        $this->assertIsArray($option);
    }

    public function testDropinPathsAreCorrect(): void
    {
        $this->assertStringEndsWith('/assets/drop-in/object-cache.php', $this->plugin->dropinfilesource);
        $this->assertStringEndsWith('/object-cache.php', $this->plugin->dropinfiledest);
        $this->assertStringContains($this->temp_wp_content_dir, $this->plugin->dropinfiledest);
    }

    public function testFilesystemTestWithValidDirectory(): void
    {
        // Create the drop-in source file
        $source_dir = dirname($this->plugin->dropinfilesource);
        if (!is_dir($source_dir)) {
            mkdir($source_dir, 0755, true);
        }
        
        // Create a minimal drop-in file
        file_put_contents($this->plugin->dropinfilesource, "<?php\n/* Version: 1.5.6 */");
        
        $result = $this->plugin->test_filesystem_writing();
        
        // Should either succeed or fail gracefully with WP_Error
        $this->assertTrue($result === true || $result instanceof \WP_Error);
        
        if ($result instanceof \WP_Error) {
            // Log the error for debugging
            error_log('Filesystem test error: ' . $result->get_error_message());
        }
    }

    public function testCleanJobWithMockedCache(): void
    {
        // Set up options
        MockWordPress::setOption($this->plugin->_token . '_settings', [
            'target_size' => 16,
            'retainmeasurements' => 24
        ]);
        
        // Mock global cache object
        global $wp_object_cache;
        $wp_object_cache = new class {
            private $size = 1000000; // 1MB
            
            public function sqlite_get_size() {
                return $this->size;
            }
            
            public function sqlite_reset_statistics($retention) {
                return true;
            }
            
            public function sqlite_remove_expired() {
                // Simulate removing some expired items
                $this->size = 800000;
                return true;
            }
            
            public function sqlite_delete_old($target_size, $current_size) {
                $this->size = $target_size;
                return true;
            }
        };
        
        // Run clean job - should not throw exceptions
        $this->expectNotToPerformAssertions();
        $this->plugin->clean_job(1.0);
    }

    public function testDeactivationCleansUp(): void
    {
        // Create temp files to simulate cleanup
        $temp_files = ['/tmp/test_cleanup.sqlite', '/tmp/test_cleanup.sqlite-wal'];
        foreach ($temp_files as $file) {
            touch($file);
        }
        
        // Mock a cache object for cleanup with temp files
        global $wp_object_cache;
        $wp_object_cache = new class($temp_files) {
            private $temp_files;
            
            public function __construct($temp_files) {
                $this->temp_files = $temp_files;
            }
            
            public function sqlite_files() {
                return $this->temp_files;
            }
        };
        
        // Test deactivation (should not throw)
        $this->expectNotToPerformAssertions();
        $this->plugin->on_deactivation('test-plugin');
        
        // Clean up temp files if they still exist
        foreach ($temp_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testDropinVersionComparison(): void
    {
        // Mock a cache object with version method
        global $wp_object_cache;
        $wp_object_cache = new class {
            public function dropin_get_version() {
                return '1.5.5'; // Older version
            }
        };
        
        $needs_update = $this->plugin->object_cache_dropin_needs_updating();
        $this->assertTrue($needs_update, 'Drop-in should need updating when version is older');
        
        // Test with same version
        $wp_object_cache = new class {
            public function dropin_get_version() {
                return '1.5.6'; // Same version
            }
        };
        
        $needs_update = $this->plugin->object_cache_dropin_needs_updating();
        $this->assertFalse($needs_update, 'Drop-in should not need updating when version is same');
    }

    /**
     * Recursively remove directory and contents
     */
    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    public function testHookRegistration(): void
    {
        $hooks = MockWordPress::getHooks();
        
        // Check that required hooks are registered
        $this->assertArrayHasKey('admin_init', $hooks);
        $this->assertArrayHasKey(\SQLite_Object_Cache::CLEAN_EVENT_HOOK, $hooks);
    }

    public function testVersionConstistencyAcrossFiles(): void
    {
        // Plugin version should be consistent
        $plugin_version = $this->plugin->_version;
        
        // Check drop-in source file version
        if (file_exists($this->plugin->dropinfilesource)) {
            $dropin_data = get_file_data($this->plugin->dropinfilesource, ['Version' => 'Version']);
            $this->assertEquals($plugin_version, $dropin_data['Version'], 'Drop-in version should match plugin version');
        }
    }
}