<?php 
/**
 * Main WP CLI command integration
 */

namespace MigrationBoilerplate;

/**
 * Download a file from a specific URL and then add it to the media library.
 *
 * @param string $url       URL for the file to be downloaded.
 * @param int    $post_id   Optionally attach to a specific Post.
 * @param string $desc      Optionally add a description to the attachment.
 * @param array  $post_data Optionally add post data to the attachment.
 * @param int    $timeout   Optionally change the timeout. Defaults to 30 seconds.
 * @return int|\WP_Error Attachment ID on success.
 */
function download_and_sideload( $url, $post_id = 0, $desc = null, $post_data = [], $timeout = 300 ) {
	if ( empty( $url ) ) {
		return false;
	}

	$tmp_name = download_url( $url, $timeout );
	if ( is_wp_error( $tmp_name ) ) {
		return $tmp_name;
	}

	$name = basename( $url );
	if ( ! empty( $post_data['do_rename'] ) ) {
		/**
		 * For some reason, PNG profile pictures are stored as .file in the URL. So we replace it with .png otherwise
		 * WP will reject it as an invalid file type.
		 */
		$name = str_replace( '.file', '.png', $name );
		unset( $post_data['do_rename'] );
	}

	if ( '.' === substr( $url, -1 ) ) {
		$response = wp_remote_head( $url );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		foreach ( wp_get_mime_types() as $extensions => $mime_type ) {
			if ( $content_type === $mime_type ) {
				$extension = explode( '|', $extensions );
				if ( empty( $extension ) ) {
					continue;
				}
				$name = $name . $extension[0];
			}
		}
	}

	$file_array = [
		'name'     => $name,
		'tmp_name' => $tmp_name,
	];
	return media_handle_sideload( $file_array, $post_id, $desc, $post_data );
}

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

/**
 * Retrieves the specific shortcode from a snippet of HTML.
 *
 * @param string $shortcode Shortcode to extract.
 * @param string $html      HTML to parse for shortcodes.
 * @return string[]|null
 */
function extract_shortcodes( $shortcode, $html ) {
	preg_match_all( '/\[' . $shortcode . '(?:.*?)\[\/' . $shortcode . '\]/i', $html, $matches );

	// If there are no matches, return null or an empty array, depending on the use case.
	return isset( $matches[0] ) ? $matches[0] : null;
}

/**
 * Get post ID by meta key and value
 *
 * @param string $meta_key Meta key
 * @param string $meta_value Meta value
 * @return int|null Post ID on success, null on failure
 */
function get_post_id_by_meta( $meta_key, $meta_value ) {
	global $wpdb;

	$post_id = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
			$meta_key,
			$meta_value
		)
	);

	return $post_id;
}