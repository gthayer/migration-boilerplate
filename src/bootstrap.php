<?php
/**
 * Bootstrap file for the migration boilerplate.
 *
 * @package MigrationBoilerplate
 */

namespace MigrationBoilerplate;

// Only register the command if WP-CLI is available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    \WP_CLI::add_command( 'migration-boilerplate', 'MigrationBoilerplate\Command\WP_CLI_Command' );
} 