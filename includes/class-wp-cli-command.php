<?php 
/**
 * Main WP CLI command integration
 */

namespace MigrationBoilerplate;

\WP_CLI::add_command( 'migration-boilerplate', 'MigrationBoilerplate\WP_CLI_Command' );

/**
 * Register migration commands.
 * Class WP_CLI_Command
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
	 * : Let's you skip the first n posts 
	 *
	 * [<per-page>]
	 * : Let's you determine the amount of posts to be indexed per bulk index
	 *
	 * [<include>]
	 * : Choose which object IDs to include in the index.
	 *
	 * @synopsis [--offset=<offset>] [--per-page=<per-page>] [--include=<include>]
	 */
	public function migrate( $args, $assoc_args ) {

		// Organize the params to be better consumed by WP_Query.
		if ( ! empty( $assoc_args['per-page'] ) ) {
			$assoc_args['posts_per_page'] = absint( $assoc_args['per-page'] );
			unset( $assoc_args['per-page'] );
		}

		if ( ! empty( $assoc_args['offset'] ) ) {
			$assoc_args['offset'] = absint( $assoc_args['offset'] );
		}

		if ( ! empty( $assoc_args['include'] ) ) {
			$include                = explode( ',', str_replace( ' ', '', $assoc_args['include'] ) );
			$assoc_args['include']  = array_map( 'absint', $include );
			$assoc_args['per-page'] = count( $assoc_args['include'] );
		}

		$MigratePosts = new MigratePosts();
		$MigratePosts->migrate_posts( $args, $assoc_args );
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
	 * : Let's you skip the first n posts 
	 *
	 * [<per-page>]
	 * : Let's you determine the amount of posts to be indexed per bulk index
	 *
	 * [<include>]
	 * : Choose which object IDs to include in the index.
	 *
	 * @synopsis [--offset=<offset>] [--per-page=<per-page>] [--include=<include>]
	 */
	public function delete( $args, $assoc_args ) {
		
		// Organize the params to be better consumed by WP_Query.
		if ( ! empty( $assoc_args['per-page'] ) ) {
			$assoc_args['posts_per_page'] = absint( $assoc_args['per-page'] );
			unset( $assoc_args['per-page'] );
		}

		if ( ! empty( $assoc_args['offset'] ) ) {
			$assoc_args['offset'] = absint( $assoc_args['offset'] );
		}

		if ( ! empty( $assoc_args['include'] ) ) {
			$include                = explode( ',', str_replace( ' ', '', $assoc_args['include'] ) );
			$assoc_args['include']  = array_map( 'absint', $include );
			$assoc_args['per-page'] = count( $assoc_args['include'] );
		}

		$DeletePosts = new DeletePosts();
		$DeletePosts->delete_posts( $args, $assoc_args );
	}
}