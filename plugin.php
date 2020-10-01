<?php
/**
 * Plugin Name: Migration Boilerplate
 * Plugin URI:
 * Description:
 * Version:     0.1.0
 * Author:      10up
 * Author URI:  https://10up.com
 * Text Domain: migration-boilerplate
 * Domain Path: /languages
 *
 * @package MigrationBoilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cannot access page directly' );
}

// This plugin is only usable via WP_CLI
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// Useful global constants.
define( 'MIGRATION_BOILERPLATE_VERSION', '0.1.0' );
define( 'MIGRATION_BOILERPLATE_URL', plugin_dir_url( __FILE__ ) );
define( 'MIGRATION_BOILERPLATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MIGRATION_BOILERPLATE_INC', MIGRATION_BOILERPLATE_PATH . 'includes/' );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	error_log( 'Composer autoload file not found, aborting migration.' );
	return;
}

// Load our composer files
require __DIR__ . '/vendor/autoload.php' ;
