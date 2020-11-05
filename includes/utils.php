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

/**
 * Rekey the CSV array so the post ids are the key for easier retrieval.
 *
 * @param array $csv The CSV data in an array format.
 * @return void
 */
function rekey_csv_array( $csv, $pos = 0 ) {

	$i         = 0;
	$keyed_csv = [];

	foreach( $csv as $row ) {

		// Skip the header.
		if ( 0 === $i ) {
			$i++;
			continue;
		}

		$keyed_csv[ $row[ $pos ] ] = $row;
	}

	return $keyed_csv;
}

/**
 * Get the terms, or create them, based on the term name in the csv.
 *
 * @param array $post_data Migration data from the CSV.
 * @return void
 */
function get_new_terms( $post_data, $taxonomy, $pos_array = [] ) {

	$new_term_names = [];
	$new_terms      = [];

	// Fallback to make sure the position is an array.
	if ( ! empty( $pos_array ) && ! is_array( $pos_array ) ) {
		$pos_array = [ $pos_array ];
	}

	foreach ( $pos_array as $pos ) {
		// Get terms from specific columns. 
		if ( ! empty( $post_data[ $pos ] ) ) {
			$new_term_names[] = $post_data[ $pos ];
		}
	}

	foreach ( $new_term_names as $new_term_name ) {
		$term = get_term_by( 'slug', sanitize_title( $new_term_name ), $taxonomy );

		// If the term does not exist, create it.
		if ( empty( $term ) ) {
			$resp = wp_insert_term( $new_term_name, $taxonomy );
			$term = get_term( $resp['term_id'], $taxonomy );
		}

		if ( is_a( $term, 'WP_Term' ) ) {
			$new_terms[] = $term->term_id;
		}
	}

	return $new_terms;
}
