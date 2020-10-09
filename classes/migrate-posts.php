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

		$args      = wp_parse_args( $assoc_args, $default_args );

		$result = $this->query_posts( $args );

		while ( true !== $result ) {
			$args['offset'] = $result['processed'];
			$result = $this->query_posts( $args, $result['processed'], $result['found_posts'] );
		}

		success( "Migration Complete!" );
	}
}