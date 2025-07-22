# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SQLite Object Cache is a WordPress plugin that provides a persistent object cache backend powered by SQLite3. It replaces WordPress's default in-memory object cache with persistent storage, improving site performance by reducing database queries.

## Core Architecture

- **Main Plugin File**: `sqlite-object-cache.php` - Entry point that initializes the plugin
- **Drop-in Cache**: `assets/drop-in/object-cache.php` - The actual cache implementation that WordPress uses
- **Core Class**: `includes/class-sqlite-object-cache.php` - Main plugin functionality
- **Settings**: `includes/class-sqlite-object-cache-settings.php` - Admin interface and configuration
- **WP-CLI Support**: `includes/cli.php` - Command-line interface for cache management
- **Statistics**: `includes/lib/class-sqlite-object-cache-statistics.php` - Performance monitoring

## Key Features

- Uses SQLite3 as persistent storage backend
- Optional APCu acceleration for faster lookups
- WP-CLI integration with `wp sqlite-object-cache` commands
- Admin dashboard with statistics and configuration options
- Automatic cache cleanup and size management
- Backup plugin exclusion for cache files

## Configuration Constants

The plugin supports several wp-config.php constants:

- `WP_SQLITE_OBJECT_CACHE_APCU` - Enable APCu acceleration (boolean)
- `WP_SQLITE_OBJECT_CACHE_DB_FILE` - Custom SQLite file path
- `WP_SQLITE_OBJECT_CACHE_TIMEOUT` - SQLite timeout in milliseconds (default: 5000)
- `WP_SQLITE_OBJECT_CACHE_JOURNAL_MODE` - SQLite journal mode (default: WAL)
- `WP_CACHE_KEY_SALT` - Salt for cache keys for security
- `WP_SQLITE_OBJECT_CACHE_MMAP_SIZE` - Memory-mapped I/O size in MiB

## WP-CLI Commands

The plugin provides extensive WP-CLI support. Use `wp help sqlite-object-cache` to see all available commands including:
- Cache size management
- Statistics viewing
- Cache flushing and cleanup operations

## Development Notes

- This is a WordPress plugin, not a standalone application
- No traditional build process - it's pure PHP
- Testing appears to be manual (tests/urls1.txt contains test URLs)
- Plugin follows WordPress coding standards and plugin architecture
- Uses WordPress hooks and filters for integration

## File Structure

- `assets/` - Static assets including the drop-in cache file
- `includes/` - Core PHP classes and functionality
- `includes/lib/` - Utility classes for statistics, admin API, and backup exclusion
- `languages/` - Translation files
- `tests/` - Test data (URL lists for manual testing)

## WordPress Integration

The plugin integrates with WordPress through:
- WordPress drop-in mechanism (`wp-content/object-cache.php`)
- Admin menu under Settings > Object Cache
- Site Health integration for diagnostics
- Multisite support
- WP-CLI command integration