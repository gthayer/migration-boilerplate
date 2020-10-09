<?php 
/**
 * Migrate Posts
 */

namespace MigrationBoilerplate;

class MigratePosts extends MigrationCommand {

	/**
	 * Migrate posts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function migrate_posts( $args, $assoc_args ) {

		$default_args = [
			'fields'         => 'ids',
			'include'        => [],
			'offset'         => '0',
			'post_status'    => 'any',
			'post_type'      => 'press',
			'posts_per_page' => '20',
		];

		$assoc_args = wp_parse_args( $assoc_args, $default_args );

		$result     = $this->query_posts( $assoc_args );
		while ( ! $result ) {
			$assoc_args['offset'] = $result['processed'];
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
	public static function callback( $post_id ) {
		//var_dump( $post_id );
	}
}