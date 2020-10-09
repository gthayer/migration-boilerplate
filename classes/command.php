<?php 
/**
 * Command Abstract class.
 */

namespace MigrationBoilerplate;

abstract class MigrationCommand {

	abstract protected function callback( $post );

	public function __construct() {
		$this->callback = get_class( $this ) . '::callback';
	}

	/**
	 * Loop through all posts within the query.
	 *
	 * @param array   $args WP_Query args.
	 * @param integer $processed The number of posts processed
	 * @param integer $found_posts The total number of posts found. 
	 * @return array/bool
	 */
	public function query_posts( $args, $processed = 0, $found_posts = 0 ) {

		$query = new \WP_Query( $args );
	
		if ( 0 === $processed ) {
	
			$found_posts = $query->found_posts;
			success( "Processed {$processed}/{$found_posts}" );
	
			// log( "Migration starting in: 5" );
			// sleep(1);
			// log( "Migration starting in: 4" );
			// sleep(1);
			// log( "Migration starting in: 3" );
			// sleep(1);
			// log( "Migration starting in: 2" );
			// sleep(1);
			// log( "Migration starting in: 1" );
			// sleep(1);
		}
	
		foreach ( $query->posts as $post ) {
			$processed++;
			call_user_func( $this->callback, $post );
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