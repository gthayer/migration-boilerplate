<?php 
/**
 * Delete Posts
 */

namespace MigrationBoilerplate;

class DeletePosts extends MigrationCommand {

	public function __construct() {
		parent::__construct();

		$this->redirects = [];
	}

	/**
	 * Migrate posts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function delete_posts( $args, $assoc_args ) {

		// Move the file path into the object.
		if ( isset( $assoc_args['file-path'] ) ) {
			$this->file_path = $assoc_args['file-path'];
			unset( $assoc_args['file-path'] );

			$assoc_args['post__in'] = $this->get_post_includes();
		}

		$default_args = [
			'fields'              => 'ids',
			'ignore_sticky_posts' => 1,
			'offset'              => '0',
			'post__in'            => [],
			'post_status'         => 'any',
			'post_type'           => 'any',
			'posts_per_page'      => '100',
		];

		$assoc_args = wp_parse_args( $assoc_args, $default_args );

		$result     = $this->query_posts( $assoc_args );
		while ( ! $result ) {
			$assoc_args['offset'] = $this->processed;
			$result               = $this->query_posts( $assoc_args );
		}

		var_export( $this->redirects );

		success( "Deletion Complete!" );
	}

	/**
	 * The callback which effects the individual post.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function callback( $post_id ) {
		$permalink = get_post_permalink( $post_id );

		if ( is_wp_error( $permalink ) ) {
			error( "ID {$post_id}: " . $permalink->get_error_message() );
		}

		$this->redirects[ $post_id ] = $permalink;
		$resp = wp_delete_post( $post_id, true );

		success( "Deleted post {$post_id}" );
	}

	/**
	 * Get the post IDs from the CSV to include in the query.
	 * This function will need to be updated based on the CSV you're working with.
	 * 
	 * CSV key values:
	 * 
	 * 0  => 'ID',
	 * 1  => 'post_title',
	 * 2  => 'post_status',
	 * 3  => 'post_name',
	 * 4  => 'post_type',
	 * 5  => 'URL',
	 * 6  => 'story_category (original)',
	 * 7  => 'story_category (updated)',
	 * 8  => 'People',
	 * 9  => 'Planet',
	 * 10 => 'Coffee & Craft',
	 * 11 => 'Community ',
	 * 12 => 'Equity & Inclusion',
	 * 13 => 'Opportunity',
	 * 14 => 'Coffee and Food',
	 * 15 => 'Customer Experience',
	 * 16 => 'Press (move)',
	 * 17 => 'Delete?',
	 * 18 => 'news_category (Deprecated)',
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

			// Get all the posts tagged for deletion.
			if ( ! empty( $data[17] ) ) {
				$include[] = (int) $data[0];
			}

			$i++;
		}

		return $include;
	}
}