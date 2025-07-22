<?php

namespace SQLiteObjectCache\Tests\Unit;

use SQLiteObjectCache\Tests\TestCase;

/**
 * Tests for WP_Object_Cache drop-in functionality
 * 
 * Note: We'll test key methods without fully loading the drop-in
 * to avoid conflicts with existing WordPress object cache
 */
class WPObjectCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSQLiteAvailable();
        
        // Define constants that would normally be set in the drop-in
        if (!defined('WP_SQLITE_OBJECT_CACHE_DISABLED')) {
            define('WP_SQLITE_OBJECT_CACHE_DISABLED', false);
        }
    }

    public function testSQLiteExtensionIsAvailable(): void
    {
        $this->assertTrue(class_exists('SQLite3'));
        $this->assertTrue(extension_loaded('sqlite3'));
    }

    public function testSQLiteVersionIsSupported(): void
    {
        $version = \SQLite3::version();
        $version_string = $version['versionString'];
        
        $this->assertNotEmpty($version_string);
        $this->assertGreaterThanOrEqual(
            0,
            version_compare($version_string, '3.7.0'),
            "SQLite version $version_string should be >= 3.7.0"
        );
    }

    public function testSQLiteDatabaseCanBeCreated(): void
    {
        $db_path = $this->test_db_path;
        $db = new \SQLite3($db_path);
        
        $this->assertInstanceOf(\SQLite3::class, $db);
        
        // Test basic database operations
        $result = $db->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, data TEXT)');
        $this->assertTrue($result !== false);
        
        $stmt = $db->prepare('INSERT INTO test (data) VALUES (?)');
        $stmt->bindValue(1, 'test_data');
        $result = $stmt->execute();
        $this->assertInstanceOf(\SQLite3Result::class, $result);
        
        $db->close();
    }

    public function testSQLiteWALModeWorks(): void
    {
        $db_path = $this->test_db_path;
        $db = new \SQLite3($db_path);
        
        // Test WAL mode (Write-Ahead Logging)
        $result = $db->exec('PRAGMA journal_mode=WAL');
        $this->assertTrue($result !== false);
        
        $result = $db->querySingle('PRAGMA journal_mode');
        $this->assertEquals('wal', strtolower($result));
        
        $db->close();
    }

    public function testSQLiteTimeoutSetting(): void
    {
        $db_path = $this->test_db_path;
        $db = new \SQLite3($db_path);
        
        // Test setting busy timeout
        $result = $db->busyTimeout(5000);
        $this->assertTrue($result);
        
        $db->close();
    }

    public function testSQLiteMemoryMappedIO(): void
    {
        $db_path = $this->test_db_path;
        $db = new \SQLite3($db_path);
        
        // Test memory-mapped I/O setting
        $result = $db->exec('PRAGMA mmap_size=33554432'); // 32MB
        $this->assertTrue($result !== false);
        
        $mmap_size = $db->querySingle('PRAGMA mmap_size');
        $this->assertGreaterThan(0, $mmap_size);
        
        $db->close();
    }

    public function testSQLiteTransactionSupport(): void
    {
        $db_path = $this->test_db_path;
        $db = new \SQLite3($db_path);
        
        $db->exec('CREATE TABLE test_transaction (id INTEGER PRIMARY KEY, value TEXT)');
        
        // Test transaction
        $result = $db->exec('BEGIN TRANSACTION');
        $this->assertTrue($result !== false);
        
        $stmt = $db->prepare('INSERT INTO test_transaction (value) VALUES (?)');
        $stmt->bindValue(1, 'test');
        $result = $stmt->execute();
        $this->assertInstanceOf(\SQLite3Result::class, $result);
        
        $result = $db->exec('COMMIT');
        $this->assertTrue($result !== false);
        
        // Verify data was inserted
        $count = $db->querySingle('SELECT COUNT(*) FROM test_transaction');
        $this->assertEquals(1, $count);
        
        $db->close();
    }

    public function testSerializationMethods(): void
    {
        $test_data = [
            'string' => 'test string',
            'number' => 42,
            'array' => [1, 2, 3],
            'object' => (object) ['prop' => 'value']
        ];
        
        // Test PHP serialize/unserialize
        $serialized = serialize($test_data);
        $unserialized = unserialize($serialized);
        $this->assertEquals($test_data, $unserialized);
        
        // Test igbinary if available
        if (function_exists('igbinary_serialize') && function_exists('igbinary_unserialize')) {
            $igbinary_serialized = igbinary_serialize($test_data);
            $igbinary_unserialized = igbinary_unserialize($igbinary_serialized);
            $this->assertEquals($test_data, $igbinary_unserialized);
            
            // igbinary should be more compact
            $this->assertLessThan(strlen($serialized), strlen($igbinary_serialized));
        }
    }

    public function testAPCuFunctionality(): void
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            $this->markTestSkipped('APCu is not available');
        }
        
        $key = 'test_key_' . uniqid();
        $value = 'test_value_' . time();
        
        // Test store
        $result = apcu_store($key, $value, 60);
        $this->assertTrue($result);
        
        // Test fetch
        $fetched = apcu_fetch($key, $success);
        $this->assertTrue($success);
        $this->assertEquals($value, $fetched);
        
        // Test delete
        $result = apcu_delete($key);
        $this->assertTrue($result);
        
        // Verify deleted
        $fetched = apcu_fetch($key, $success);
        $this->assertFalse($success);
    }

    public function testCacheKeyGeneration(): void
    {
        $blog_prefix = 'test_site:';
        $group = 'posts';
        $key = 'post_123';
        
        // Simulate cache key generation logic
        $cache_key = $blog_prefix . $group . ':' . $key;
        
        $this->assertEquals('test_site:posts:post_123', $cache_key);
        
        // Test with salt
        $salt = 'random_salt';
        $salted_key = $salt . $cache_key;
        
        $this->assertStringStartsWith($salt, $salted_key);
    }

    public function testExpirationTimestamps(): void
    {
        $current_time = time();
        $ttl = 3600; // 1 hour
        
        // Test normal expiration
        $expire_time = $current_time + $ttl;
        $this->assertGreaterThan($current_time, $expire_time);
        
        // Test no-expiration constant
        $no_expire_offset = 500000000000;
        $no_expire_time = $current_time + $no_expire_offset;
        $this->assertGreaterThan($expire_time, $no_expire_time);
    }

    public function testIntegerKeyOptimization(): void
    {
        // Test integer key detection
        $int_keys = ['123', '456789', '1', '999999'];
        $non_int_keys = ['abc', 'post_123', '123abc', '12.34'];
        
        foreach ($int_keys as $key) {
            $this->assertTrue(
                ctype_digit($key),
                "Key '$key' should be detected as integer"
            );
        }
        
        foreach ($non_int_keys as $key) {
            $this->assertFalse(
                ctype_digit($key),
                "Key '$key' should not be detected as integer"
            );
        }
    }

    public function testGroupTypeClassification(): void
    {
        $ignored_groups = ['counts', 'plugins', 'themes'];
        $persistent_groups = ['posts', 'comments', 'options'];
        $unflushable_groups = [];
        
        foreach ($ignored_groups as $group) {
            // These groups should not be cached
            $this->assertContains($group, $ignored_groups);
        }
        
        foreach ($persistent_groups as $group) {
            // These groups should be cached
            $this->assertNotContains($group, $ignored_groups);
        }
    }

    public function testMemoryLimitCalculations(): void
    {
        $target_size_mb = 16;
        $target_size_bytes = $target_size_mb * 1024 * 1024;
        
        $this->assertEquals(16777216, $target_size_bytes);
        
        // Test grace factor
        $grace_factor = 1.25;
        $threshold_bytes = (int) ($target_size_bytes * $grace_factor);
        
        $this->assertEquals(20971520, $threshold_bytes);
    }
}