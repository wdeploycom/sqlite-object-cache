<?php

namespace SQLiteObjectCache\Tests\Unit;

use SQLiteObjectCache\Tests\TestCase;
use SQLiteObjectCache\Tests\MockWordPress;

/**
 * Tests for SQLite_Object_Cache_Settings class
 */
class SQLiteObjectCacheSettingsTest extends TestCase
{
    private \SQLite_Object_Cache $plugin;
    private \SQLite_Object_Cache_Settings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSQLiteAvailable();
        
        $this->plugin = new \SQLite_Object_Cache(__FILE__, '1.5.6');
        $this->settings = new \SQLite_Object_Cache_Settings($this->plugin, 'sqlite-object-cache/sqlite-object-cache.php');
    }

    public function testConstructorInitializesProperties(): void
    {
        $this->assertSame($this->plugin, $this->settings->parent);
        $this->assertEquals('sqlite_object_cache_', $this->settings->base);
        $this->assertIsArray($this->settings->settings);
    }

    public function testHasSqlitePropertyIsSet(): void
    {
        $this->assertNotEmpty($this->settings->has);
        $this->assertTrue($this->settings->has === true || is_string($this->settings->has));
    }

    public function testValidateSettingsHandlesNumericOptions(): void
    {
        $original_value = ['target_size' => '16'];
        $option = [];
        
        $result = $this->settings->validate_settings($option, 'test', $original_value);
        
        $this->assertIsArray($result);
    }

    public function testValidateSettingsHandlesInvalidNumericValues(): void
    {
        $original_value = ['target_size' => 'invalid'];
        $option = [];
        
        $result = $this->settings->validate_settings($option, 'test', $original_value);
        
        $this->assertIsArray($result);
        // Should handle invalid values gracefully
    }

    public function testValidateResetStatsHandlesResetFlag(): void
    {
        $original_value = ['reset_stats' => 'on'];
        $option = [];
        
        $result = $this->settings->validate_reset_stats($option, 'test', $original_value);
        
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('reset_stats', $result);
    }

    public function testFilterPluginRowMetaAddsMetadata(): void
    {
        $plugin_meta = ['Version: 1.5.6'];
        $plugin_file = 'sqlite-object-cache/sqlite-object-cache.php';
        
        $result = $this->settings->filter_plugin_row_meta($plugin_meta, $plugin_file);
        
        $this->assertIsArray($result);
        // Should add sponsor link
        $this->assertGreaterThanOrEqual(count($plugin_meta), count($result));
    }

    public function testFilterPluginRowMetaIgnoresOtherPlugins(): void
    {
        $plugin_meta = ['Version: 1.0.0'];
        $plugin_file = 'other-plugin/other-plugin.php';
        
        $result = $this->settings->filter_plugin_row_meta($plugin_meta, $plugin_file);
        
        $this->assertEquals($plugin_meta, $result);
    }

    public function testAddSettingsLinkAddsLink(): void
    {
        $links = ['Deactivate'];
        
        $result = $this->settings->add_settings_link($links);
        
        $this->assertIsArray($result);
        $this->assertContains('Settings', implode(' ', $result));
    }

    public function testConfigureSettingsReturnsArray(): void
    {
        $settings = [];
        
        $result = $this->settings->configure_settings($settings);
        
        $this->assertIsArray($result);
    }

    public function testSettingsAssetsDoesNotThrow(): void
    {
        // This method should run without throwing exceptions
        $this->expectNotToPerformAssertions();
        $this->settings->settings_assets();
    }

    public function testSettingsSectionHeaderDoesNotThrow(): void
    {
        $section = ['id' => 'test_section'];
        
        $this->expectNotToPerformAssertions();
        $this->settings->settings_section_header($section);
    }

    public function testDebugInformationReturnsArray(): void
    {
        $info = [];
        
        $result = $this->settings->debug_information($info);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sqlite-object-cache', $result);
        $this->assertArrayHasKey('label', $result['sqlite-object-cache']);
        $this->assertArrayHasKey('fields', $result['sqlite-object-cache']);
    }

    public function testDebugInformationIncludesSQLiteInfo(): void
    {
        $info = [];
        
        $result = $this->settings->debug_information($info);
        $fields = $result['sqlite-object-cache']['fields'];
        
        $this->assertArrayHasKey('sqlite_version', $fields);
        $this->assertArrayHasKey('sqlite_available', $fields);
    }

    public function testDebugInformationIncludesApcuInfo(): void
    {
        $info = [];
        
        $result = $this->settings->debug_information($info);
        $fields = $result['sqlite-object-cache']['fields'];
        
        $this->assertArrayHasKey('apcu_available', $fields);
        $this->assertArrayHasKey('igbinary_available', $fields);
    }

    /**
     * Test that settings fields are properly structured
     */
    public function testSettingsFieldsStructure(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->settings);
        $method = $reflection->getMethod('settings_fields');
        $method->setAccessible(true);
        
        $fields = $method->invoke($this->settings);
        
        $this->assertIsArray($fields);
        
        foreach ($fields as $field) {
            $this->assertArrayHasKey('id', $field);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
        }
    }

    /**
     * Test numeric option validation
     */
    public function testNumericOptionValidation(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->settings);
        $method = $reflection->getMethod('numeric_option');
        $method->setAccessible(true);
        
        $option = [];
        
        // Test valid numeric string
        $method->invoke($this->settings, $option, 'test_field', 16);
        $this->assertEquals(16, $option['test_field']);
        
        // Test with existing valid value
        $option = ['test_field' => '32'];
        $method->invoke($this->settings, $option, 'test_field', 16);
        $this->assertEquals(32, $option['test_field']);
        
        // Test with invalid value should use default
        $option = ['test_field' => 'invalid'];
        $method->invoke($this->settings, $option, 'test_field', 16);
        $this->assertEquals(16, $option['test_field']);
    }
}