<?php
/**
 * Import Posts command class.
 *
 * @package MigrationBoilerplate\Command
 */

namespace MigrationBoilerplate\Command;

class ImportPosts extends Command {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Imports Posts from a CSV
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Whether or not to greet the person with success or error.
	 * ---
	 * default: success
	 * options:
	 *   - success
	 *   - error
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp example hello Newman
	 *
	 * @when after_wp_load
	 */
	public function import_posts( $args, $assoc_args ) {

		// Move the file path into the object.
		if ( isset( $assoc_args['file-path'] ) ) {
			$this->file_path = $assoc_args['file-path'];
		}

		$file = fopen( $this->file_path, 'r' );

		if ( false === $file ) {
			error( 'File can not be found' );
		}

		$i = 0;
		while ( ( $data = fgetcsv( $file ) ) !== false ) {

			// Skip the header.
			if ( 0 === $i ) {
				$i++;
				continue;
			}

			$inclusivity_area = $data[3] ?? false;
			$term_type        = $assoc_args['term-type'] ?? false;

			$post_content = '';

			if ( 'non-inclusive' === $term_type ) {
				$post_content = "
				<!-- wp:heading {\"level\":\"3\",\"placeholder\":\"Term Description Heading (optional)\"} -->
					<h3>{$data[1]}</h3>
				<!-- /wp:heading -->
				";
			}

			$post_content .= "
				<!-- wp:paragraph {\"placeholder\":\"Term Description (Definition or Phrase/Terms to use)\"} -->
					<p>{$data[2]}</p>
				<!-- /wp:paragraph -->
			";

			$post_id = wp_insert_post(
				[
					'post_title'   => $data[0],
					'post_content' => $post_content,
					'post_status'  => 'publish',
					'post_type'    => 'sf-language-term',
				]
			);

			wp_set_object_terms( $post_id, $data[3] ?? false, 'sf-inclusivity-area' );
			wp_set_object_terms( $post_id, $term_type, 'sf-term-type' );

			$i++;
		}

		success( 'Migration Complete!' );
	}

	/**
	 * No op.
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function callback( $post_id ) {}


}
