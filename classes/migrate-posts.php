<?php 
/**
 * Migrate Posts
 */

namespace MigrationBoilerplate;

class MigratePosts extends MigrationCommand {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Migrate posts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function migrate_posts( $args, $assoc_args ) {

		// Move the file path into the object.
		if ( isset( $assoc_args['file-path'] ) ) {
			$this->file_path = $assoc_args['file-path'];
			unset( $assoc_args['file-path'] );

			$assoc_args['post__in'] = $this->get_post_includes();
		}

		$default_args = [
			'fields'         => 'ids',
			'include'        => [],
			'offset'         => '0',
			'post_status'    => 'any',
			'post_type'      => 'any',
			'posts_per_page' => '100',
		];

		$assoc_args = wp_parse_args( $assoc_args, $default_args );

		$result     = $this->query_posts( $assoc_args );
		while ( ! $result ) {
			$assoc_args['offset'] = $this->processed;
			$result               = $this->query_posts( $assoc_args );
		}

		success( "Migration Complete!" );
	}

	/**
	 * The callback which effects the individual post.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function callback( $post_id ) {

		log( "Post ID {$post_id}" );

		$file = file( $this->file_path );

		if ( false === $file ) {
			error( 'File can not be found' );
		}

		// Restructure the data into something useable.
		$csv       = array_map( 'str_getcsv', $file );
		$csv       = rekey_csv_array( $csv, 0 );
		$post_data = $csv[ $post_id ];

		if ( empty( $post_data ) ) {
			error( 'Unable to find post data in CSV.' );
		}

		// Ignore posts tagged for deletion or changing post types.
		if ( ! empty( $post_data[16] ) || ! empty( $post_data[17] ) ) {
			return;
		}

		$current_terms = get_the_terms( $post_id, 'story_category' );
		$new_terms     = get_new_terms( $post_data, 'story_category', [8,9,10] );

		if ( ! empty( $current_terms ) ) {
			foreach ( $current_terms as $current_term ) {
				if ( 'Good Things are Happening' === $current_term->name ) {
					$new_terms[] = $current_term->term_id;
				}
			}
		}

		// Remove existing term relationships.
		wp_delete_object_term_relationships( $post_id, 'story_category' );
		wp_set_post_terms( $post_id, $new_terms, 'story_category' );
	}

	/**
	 * Get the post IDs from the CSV to include in the query.
	 * This function will need to be updated based on the CSV you're working with.
	 * 
	 * @return void
	 */
	public function get_post_includes() {

		// This function will depend on the CSV being imported.
		$include = [];
		$i        = 0;
	
		$handle = fopen( $this->file_path, 'r' );

		if ( false === $handle ) {
			error( 'File can not be found' );
		}

		while ( ( $data = fgetcsv( $handle ) ) !== FALSE ) {

			// Skip the header.
			if ( 0 === $i ) {
				$i++;
				continue;
			}

			$include[] = (int) $data[0];
			$i++;
		}

		return $include;
	}
}