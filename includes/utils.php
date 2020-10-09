<?php 
/**
 * Main WP CLI command integration
 */

namespace MigrationBoilerplate;

/**
 * Filter the args provided by the CLI script and convert them to WP_Query args.
 *
 * @param array $assoc_args WP CLI args.
 * @return void
 */
function filter_cli_args( $assoc_args ) {

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

	return $assoc_args;
}

/**
 * Check if the post exists by ID
 * 
 * https://tommcfarlin.com/wordpress-post-exists-by-id/
 *
 * @param int $post_id The post ID
 * @return void
 */
function post_exists( $post_id ) {
	return is_string( get_post_status( $post_id ) );
}

/**
 * Run this to free up system memory.
 *
 * @return void
 */
function stop_the_insanity() {
	global $wpdb, $wp_object_cache;

	$wpdb->queries = array();

	if ( is_object( $wp_object_cache ) ) {
		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}
	}
}