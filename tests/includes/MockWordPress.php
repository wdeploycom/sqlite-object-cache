<?php

namespace SQLiteObjectCache\Tests;

/**
 * Mock WordPress functions and classes for testing
 */
class MockWordPress
{
    public static array $options = [];
    public static array $hooks = [];
    public static array $scheduled_events = [];
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::mockWordPressFunctions();
        self::mockWordPressClasses();
        self::$initialized = true;
    }

    public static function reset(): void
    {
        self::$options = [];
        self::$hooks = [];
        self::$scheduled_events = [];
    }

    private static function mockWordPressFunctions(): void
    {
        // Mock WordPress functions that don't exist in test environment
        if (!function_exists('get_option')) {
            function get_option($option, $default = false) {
                return \SQLiteObjectCache\Tests\MockWordPress::$options[$option] ?? $default;
            }
        }

        if (!function_exists('update_option')) {
            function update_option($option, $value, $autoload = null) {
                \SQLiteObjectCache\Tests\MockWordPress::$options[$option] = $value;
                return true;
            }
        }

        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                \SQLiteObjectCache\Tests\MockWordPress::$hooks[$hook][] = [
                    'callback' => $callback,
                    'priority' => $priority,
                    'args' => $accepted_args
                ];
            }
        }

        if (!function_exists('register_activation_hook')) {
            function register_activation_hook($file, $callback) {
                // Store for potential testing
            }
        }

        if (!function_exists('register_deactivation_hook')) {
            function register_deactivation_hook($file, $callback) {
                // Store for potential testing
            }
        }

        if (!function_exists('wp_schedule_event')) {
            function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) {
                \SQLiteObjectCache\Tests\MockWordPress::$scheduled_events[$hook] = [
                    'timestamp' => $timestamp,
                    'recurrence' => $recurrence,
                    'args' => $args
                ];
                return true;
            }
        }

        if (!function_exists('wp_next_scheduled')) {
            function wp_next_scheduled($hook, $args = []) {
                return isset(\SQLiteObjectCache\Tests\MockWordPress::$scheduled_events[$hook]) 
                    ? \SQLiteObjectCache\Tests\MockWordPress::$scheduled_events[$hook]['timestamp'] 
                    : false;
            }
        }

        if (!function_exists('wp_unschedule_hook')) {
            function wp_unschedule_hook($hook) {
                unset(\SQLiteObjectCache\Tests\MockWordPress::$scheduled_events[$hook]);
                return true;
            }
        }

        if (!function_exists('wp_cache_flush')) {
            function wp_cache_flush() {
                return true;
            }
        }

        if (!function_exists('__')) {
            function __($text, $domain = 'default') {
                return $text;
            }
        }

        if (!function_exists('esc_html__')) {
            function esc_html__($text, $domain = 'default') {
                return htmlspecialchars($text);
            }
        }

        if (!function_exists('esc_url')) {
            function esc_url($url) {
                return filter_var($url, FILTER_SANITIZE_URL);
            }
        }

        if (!function_exists('trailingslashit')) {
            function trailingslashit($string) {
                return rtrim($string, '/\\') . '/';
            }
        }


        if (!function_exists('plugin_basename')) {
            function plugin_basename($file) {
                return basename(dirname($file)) . '/' . basename($file);
            }
        }

        if (!function_exists('plugins_url')) {
            function plugins_url($path = '', $plugin = '') {
                return 'https://example.com/wp-content/plugins/' . ltrim($path, '/');
            }
        }

        if (!function_exists('get_plugin_data')) {
            function get_plugin_data($plugin_file, $markup = true, $translate = true) {
                return [
                    'Name' => 'SQLite Object Cache',
                    'Version' => '1.5.6',
                    'PluginURI' => 'https://wordpress.org/plugins/sqlite-object-cache/',
                    'Description' => 'A persistent object cache backend powered by SQLite3.',
                    'Author' => 'Oliver Jones',
                    'AuthorURI' => 'https://plumislandmedia.net',
                ];
            }
        }

        if (!function_exists('get_file_data')) {
            function get_file_data($file, $default_headers, $context = '') {
                if (!file_exists($file)) {
                    return array_fill_keys(array_keys($default_headers), '');
                }
                
                $file_data = [];
                $file_content = file_get_contents($file);
                
                foreach ($default_headers as $field => $regex) {
                    if (preg_match('/' . preg_quote($regex, '/') . ':\s*(.+)/i', $file_content, $matches)) {
                        $file_data[$field] = trim($matches[1]);
                    } else {
                        $file_data[$field] = '';
                    }
                }
                
                return $file_data;
            }
        }

        if (!function_exists('is_admin')) {
            function is_admin() {
                return false; // Default to frontend for tests
            }
        }

        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value, ...$args) {
                return $value; // Simple passthrough for tests
            }
        }

        if (!function_exists('do_action')) {
            function do_action($hook, ...$args) {
                // Simple mock - could be extended for testing hooks
            }
        }

        if (!function_exists('rand')) {
            function rand($min = null, $max = null) {
                return \rand($min, $max);
            }
        }

        if (!function_exists('time')) {
            function time() {
                return \time();
            }
        }

    }

    private static function mockWordPressClasses(): void
    {
        // Classes are defined at the bottom of this file to avoid nesting issues
    }

    public static function getOption(string $key, $default = false)
    {
        return self::$options[$key] ?? $default;
    }

    public static function setOption(string $key, $value): void
    {
        self::$options[$key] = $value;
    }

    public static function getScheduledEvents(): array
    {
        return self::$scheduled_events;
    }

    public static function getHooks(): array
    {
        return self::$hooks;
    }
}

// Mock WordPress classes - defined outside of class to avoid nesting issues
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $errors = [];
        private $error_data = [];

        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }

        public function get_error_code() {
            return key($this->errors);
        }

        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return $this->errors[$code][0] ?? '';
        }
    }
}