<?php
/**
 * Main WP CLI command integration
 *
 * @package MigrationBoilerplate\Command
 */

namespace MigrationBoilerplate\Command;

use MigrationBoilerplate\Command\DeletePosts;
use MigrationBoilerplate\Command\MigratePosts;
use MigrationBoilerplate\Command\ChangePostTypes;
use MigrationBoilerplate\Command\ImportPosts;
use MigrationBoilerplate\Command\ExportContentReport;
use MigrationBoilerplate\Database\Database;

/**
 * Register migration commands.
 * Class WP_CLI_Command
 *
 * @package MigrationBoilerplate\Command
 */
class WP_CLI_Command extends \WP_CLI_Command {

	/**
	 * Initialize the command environment.
	 *
	 * @param array $assoc_args The associative arguments.
	 * @return array The filtered arguments.
	 */
	protected function init_command( $assoc_args ) {
		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_invalidation( true );

		return \MigrationBoilerplate\filter_cli_args( $assoc_args );
	}

	/**
	 * Import Posts
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return bool
	 *
	 * ## OPTIONS
	 *
	 * [<offset>]
	 * : Let's you skip the first n posts.
	 *
	 * [<legacy-id>]
	 * : The legacy ID of the post to migrate.
	 *
	 * @synopsis [--offset=<offset>] [--legacy-id=<legacy-id>]
	 */
	public function migrate_posts( $args, $assoc_args ) {
		$assoc_args = $this->init_command( $assoc_args );
		$migratePosts = new MigratePosts();
		$migratePosts->migrate_posts( $args, $assoc_args );
	}

	/**
	 * Delete Posts
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param array $args
	 * @param array $assoc_args
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
		$assoc_args = $this->init_command( $assoc_args );
		$deletePosts = new DeletePosts();
		$deletePosts->delete_posts( $args, $assoc_args );
	}

	/**
	 * Change post types
	 *
	 * Can specify offset and the number of posts to import
	 *
	 * @param array $args
	 * @param array $assoc_args
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
		$assoc_args = $this->init_command( $assoc_args );
		$changePostTypes = new ChangePostTypes();
		$changePostTypes->change_post_types( $args, $assoc_args );
	}

	/**
	 * Import Posts from CSV
	 *
	 * @todo make import command use column headers to determine data type (post_title, post_content...etc)
	 *
	 * @param array $args
	 * @param array $assoc_args
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
		$assoc_args = $this->init_command( $assoc_args );
		$importer = new ImportPosts();
		$importer->import_posts( $args, $assoc_args );
	}

	/**
	 * Create a report of content types.
	 *
	 * @param array $args
	 * @param array $assoc_args
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
		$assoc_args = $this->init_command( $assoc_args );
		$exporter = new ExportContentReport();
		$exporter->content_report( $args, $assoc_args );
	}

	/**
	 * Export missing items report.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return bool
	 */
	public function content_export_missing_items( $args, $assoc_args ) {
		$assoc_args = $this->init_command( $assoc_args );
		$exporter = new ExportContentReport();
		$exporter->find_missing_items( $args, $assoc_args );
	}

	/**
	 * Create migration tables.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return bool
	 */
	public function create_migration_tables( $args, $assoc_args ) {
		$this->init_command( $assoc_args );
		Database::create_tables();
	}

	/**
	 * Delete migration tables.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return bool
	 */
	public function delete_migration_tables( $args, $assoc_args ) {
		$this->init_command( $assoc_args );
		Database::drop_tables();
	}
} 