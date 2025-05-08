<?php
/**
 * Migrate Posts command class.
 *
 * @package MigrationBoilerplate\Command
 */

namespace MigrationBoilerplate\Command;

use MigrationBoilerplate\CraftCMS\CraftCMS;

class MigratePosts extends Command {

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
		}

		$this->migrate_content();
	}

	public function migrate_content( $offset = 0 ) {
		// blogArticles?
		$type_id = 34;
		$craft = new CraftCMS();
		$entries = $craft->get_entries( $type_id, 100, $offset );

		foreach ( $entries as $entry ) {
			$this->callback( $entry );
		}

		if ( count( $entries ) === 100 ) {
			\MigrationBoilerplate\stop_the_insanity();
			$this->migrate_content( $offset + 100 );
		}
	}

	/**
	 * The callback which effects the individual post.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function callback( $entry ) {
		var_dump($entry);
		exit;
	}
}