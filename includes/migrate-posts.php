<?php 
/**
 * Migrate Posts
 */

namespace MigrationBoilerplate;

/**
 * Migrate posts
 *
 * @param array $args
 * @param array $assoc_args
 * @return void
 */
function migrate_posts( $args, $assoc_args ) {

	$default_args = [
		'fields'         => 'ids',
		'include'        => [],
		'offset'         => '0',
		'post_status'    => 'any',
		'post_type'      => 'press',
		'posts_per_page' => '20',
	];

	$args      = wp_parse_args( $assoc_args, $default_args );

	$result = query_posts( $args );

	while ( true !== $result ) {
		$args['offset'] = $result['processed'];
		$result = query_posts( $args, $result['processed'], $result['found_posts'] );
	}

	success( "Migration Complete!" );
}

function query_posts( $args, $processed = 0, $found_posts = 0 ) {

	$query = new \WP_Query( $args );

	if ( 0 === $processed ) {

		$found_posts = $query->found_posts;
		success( "Processed {$processed}/{$found_posts}" );

		log( "Migration starting in: 5" );
		sleep(1);
		log( "Migration starting in: 4" );
		sleep(1);
		log( "Migration starting in: 3" );
		sleep(1);
		log( "Migration starting in: 2" );
		sleep(1);
		log( "Migration starting in: 1" );
		sleep(1);
	}

	foreach ( $query->posts as $post ) {
		$processed++;
		// TODO: Individual Post Action goes here.
	}

	success( "Processed {$processed}/{$found_posts}" );

	if ( $processed >= $found_posts ) {
		return true;
	} else {
		return [
			'processed'   => $processed,
			'found_posts' => $found_posts,
		];
	}
}