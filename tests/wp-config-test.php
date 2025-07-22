<?php
/**
 * WordPress test configuration for SQLite Object Cache
 */

// Test database configuration
define('DB_NAME', 'sqlite_object_cache_test');
define('DB_USER', 'test_user');
define('DB_PASSWORD', 'test_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Test-specific constants
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

// WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);

// Memory limit
define('WP_MEMORY_LIMIT', '512M');

// Disable file modifications
define('DISALLOW_FILE_MODS', false);
define('DISALLOW_FILE_EDIT', false);

// Plugin-specific test constants
define('WP_SQLITE_OBJECT_CACHE_APCU', false); // Disabled for most tests
define('WP_SQLITE_OBJECT_CACHE_SERIALIZE', false); // Use igbinary if available
define('WP_SQLITE_OBJECT_CACHE_TIMEOUT', 1000); // Shorter timeout for tests
define('WP_SQLITE_OBJECT_CACHE_JOURNAL_MODE', 'MEMORY'); // Faster for tests
define('WP_CACHE_KEY_SALT', 'test_salt_' . md5(__FILE__));

// Test SQLite file location
define('WP_SQLITE_OBJECT_CACHE_DB_FILE', sys_get_temp_dir() . '/wp_test_object_cache.sqlite');

// WordPress table prefix
$table_prefix = 'wp_test_';

// Authentication keys and salts
define('AUTH_KEY',         'test-auth-key');
define('SECURE_AUTH_KEY',  'test-secure-auth-key');
define('LOGGED_IN_KEY',    'test-logged-in-key');
define('NONCE_KEY',        'test-nonce-key');
define('AUTH_SALT',        'test-auth-salt');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
define('LOGGED_IN_SALT',   'test-logged-in-salt');
define('NONCE_SALT',       'test-nonce-salt');