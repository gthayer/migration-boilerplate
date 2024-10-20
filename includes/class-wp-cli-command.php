<?php
/**
 * Main WP CLI command integration
 */

namespace MigrationBoilerplate;

\WP_CLI::add_command( 'migration-boilerplate', 'MigrationBoilerplate\WP_CLI_Command' );

/**
 * Register migration commands.
 * Class WP_CLI_Command
 *
 * @package MigrationBoilerplate
 */
class WP_CLI_Command extends \WP_CLI_Command {

	/**
	 * Import Posts
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 *
	 * ## OPTIONS
	 *
	 * [<offset>]
	 * : Let's you skip the first n posts.
	 *
	 * [<per-page>]
	 * : Let's you determine the amount of posts to be indexed per bulk index.
	 *
	 * [<include>]
	 * : Choose which object IDs to include in the index.
	 *
	 * [<file-path>]
	 * : The path to a CSV to read from.
	 *
	 * @synopsis [--offset=<offset>] [--per-page=<per-page>] [--include=<include>] [--file-path=<file-path>]
	 */
	public function migrate( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$migratePosts = new MigratePosts();
		$migratePosts->migrate_posts( $args, $assoc_args );
	}

	/**
	 * Delete Posts
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 *
	 * ## OPTIONS
	 *
	 * [<offset>]
	 * : Let's you skip the first n posts.
	 *
	 * [<per-page>]
	 * : Let's you determine the amount of posts to be indexed per bulk index.
	 *
	 * [<include>]
	 * : Choose which object IDs to include in the index.
	 *
	 * [<file-path>]
	 * : The path to a CSV to read from.
	 *
	 * @synopsis [--offset=<offset>] [--per-page=<per-page>] [--include=<include>] [--file-path=<file-path>]
	 */
	public function delete( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$deletePosts = new DeletePosts();
		$deletePosts->delete_posts( $args, $assoc_args );
	}

	/**
	 * Delete Posts
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 *
	 * ## OPTIONS
	 *
	 * [<offset>]
	 * : Let's you skip the first n posts.
	 *
	 * [<per-page>]
	 * : Let's you determine the amount of posts to be indexed per bulk index.
	 *
	 * [<include>]
	 * : Choose which object IDs to include in the index.
	 *
	 * [<file-path>]
	 * : The path to a CSV to read from.
	 *
	 * @synopsis [--offset=<offset>] [--per-page=<per-page>] [--include=<include>] [--file-path=<file-path>]
	 */
	public function change_post_types( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$deletePosts = new ChangePostTypes();
		$deletePosts->change_post_types( $args, $assoc_args );
	}

	/**
	 * Import Posts from CSV
	 *
	 * @todo make import command use column headers to determine data type (post_title, post_content...etc)
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 *
	 * ## OPTIONS
	 *
	 * [<file-path>]
	 * : The path to a CSV to read from.
	 *
	 * [<term-type>]
	 * : Term Type to apply to imported posts. This is a custom taxonomy specific to this example.
	 *
	 * @synopsis [--file-path=<file-path>] [--term-type=<term-type>]
	 */
	public function import( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$importer = new ImportPosts();
		$importer->import_posts( $args, $assoc_args );
	}

	/**
	 * Create a report of content types.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 * 
	 * ## OPTIONS
	 *
	 * [<site>]
	 * : The name of the site to pull content from.
	 * 
	 * [<offset>]
	 * : Let's you skip the first n posts.
	 *
	 */
	public function content_export_report( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$importer = new ExportContentReport();
		$importer->content_report( $args, $assoc_args );
	}

	public function content_export_missing_items( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$assoc_args = filter_cli_args( $assoc_args );

		$importer = new ExportContentReport();
		$importer->find_missing_items( $args, $assoc_args );
	}

	public function create_migration_tables( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );
		Database::create_tables();
	}

	public function delete_migration_tables( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );
		Database::drop_tables();
	}
}
