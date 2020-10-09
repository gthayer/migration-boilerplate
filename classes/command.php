<?php 
/**
 * Command Abstract class.
 */

namespace MigrationBoilerplate;

abstract class MigrationCommand {

	abstract protected static function callback( $post_id );

	public function __construct() {

		// Set the callback name. This will grab the class and namespace for the command being run.
		$this->callback    = get_class( $this ) . '::callback';
		
		// Set the default processing numbers.
		$this->processed   = 0;
		$this->found_posts = 0;
	}

	/**
	 * Loop through all posts within the query.
	 *
	 * @param array   $args WP_Query args.
	 * @param integer $processed The number of posts processed
	 * @param integer $found_posts The total number of posts found. 
	 * @param string  $message The message to display when processing posts.
	 * @return array/bool
	 */
	public function query_posts( $args ) {

		$query = new \WP_Query( $args );

		// Initialize the process. Only runs once.
		if ( 0 === $this->processed ) {
	
			$this->found_posts = $query->found_posts;

			// Give the user a chance to cancel the process. Just in case...
			log( "Processing {$this->found_posts} posts." );
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

			if ( ! isset( $this->progress ) ) {
				$this->progress = \WP_CLI\Utils\make_progress_bar( esc_html( 'Paginating through posts' ), $this->found_posts, 10 );
			}
		}

		// Loop over each post and run the callback function for the specific command.
		foreach ( $query->posts as $post ) {
			$this->processed++;
			call_user_func( $this->callback, $post );
			$this->progress->tick();
		}

		// End the command once you've processed everything.
		if ( $this->processed >= $this->found_posts ) {
			$this->progress->finish();
			return true;
		} else {
			return false;
		}
	}

}