<?php
/**
 * PHPUnit Bootstrap for SQLite Object Cache Plugin Tests
 * Using WordPress Test Suite
 */

// Define plugin directory for tests
define( 'SQLITE_OBJECT_CACHE_PLUGIN_DIR', dirname( __DIR__ ) );

// Load PHPUnit Polyfills if available
$polyfills_autoload = SQLITE_OBJECT_CACHE_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills_autoload ) ) {
    require_once $polyfills_autoload;
}

// Plugin activation callback
function _manually_load_plugin() {
    require SQLITE_OBJECT_CACHE_PLUGIN_DIR . '/sqlite-object-cache.php';
}

// Determine if we're in a wp-env environment or local development
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

// In wp-env, the WordPress test suite is available at specific locations
if ( ! $wp_tests_dir ) {
    // wp-env specific paths
    $possible_paths = [
        '/wordpress-phpunit',                    // wp-env standard path
        '/var/www/html/wp-tests',               // alternative wp-env path
        '/tmp/wordpress-tests-lib',             // local development
        dirname( __FILE__ ) . '/tmp/wordpress-tests-lib'  // fallback
    ];
    
    foreach ( $possible_paths as $path ) {
        if ( file_exists( $path . '/includes/functions.php' ) ) {
            $wp_tests_dir = $path;
            break;
        }
    }
}

// Load WordPress test suite functions first
if ( $wp_tests_dir && file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
    require_once $wp_tests_dir . '/includes/functions.php';
    
    // Now we can use tests_add_filter
    tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
    
    // Load the main bootstrap
    require $wp_tests_dir . '/includes/bootstrap.php';
    
    // After WordPress is loaded, ensure our test classes are available
    require_once dirname( __FILE__ ) . '/includes/TestCase.php';
    
} else {
    // Fallback for environments without WordPress test suite
    echo "Warning: WordPress test suite not found. Falling back to mock environment.\n";
    
    // Define basic WordPress constants if not defined
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', '/tmp/wordpress/' );
    }
    
    if ( ! defined( 'WP_CONTENT_DIR' ) ) {
        define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
    }
    
    if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
        define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
    }
    
    if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
        define( 'HOUR_IN_SECONDS', 3600 );
    }
    
    // Load the plugin directly in fallback mode
    _manually_load_plugin();
    
    // Load our fallback test framework
    if ( file_exists( dirname( __FILE__ ) . '/includes/TestCase.php' ) ) {
        require dirname( __FILE__ ) . '/includes/TestCase.php';
    }
}