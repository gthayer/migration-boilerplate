<?php 
/**
 * Command Abstract class.
 */

namespace MigrationBoilerplate;

abstract class MigrationCommand {

	public function query_posts( $args, $processed = 0, $found_posts = 0 ) {

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

}