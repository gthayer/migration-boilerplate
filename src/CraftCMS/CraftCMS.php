<?php
/**
 * CraftCMS Migration Class
 *
 * @package MigrationBoilerplate\CraftCMS
 */

namespace MigrationBoilerplate\CraftCMS;

use WP_CLI;
use wpdb;

class CraftCMS {
	/**
	 * WordPress database instance
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Legacy database instance
	 *
	 * @var wpdb
	 */
	private $legacy_db;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		// Initialize legacy database connection
		$this->init_legacy_db();
	}

	/**
	 * Initialize connection to legacy database
	 *
	 * @return void
	 */
	private function init_legacy_db() {
		// Connect to legacy database
		$this->legacy_db = new wpdb(
			DB_USER,
			DB_PASSWORD,
			'legacy',
			DB_HOST
		);
		$rows = $this->legacy_db;

		if ( $this->legacy_db->last_error ) {
			\MigrationBoilerplate\error( 'Failed to connect to legacy database: ' . $this->legacy_db->last_error );
		}
	}

	/**
	 * Get entries from Craft CMS
	 *
	 * @param int $type_id Entry type ID to fetch
	 * @param int $limit Number of entries to fetch
	 * @param int $offset Number of entries to skip
	 * @return array
	 */
	public function get_entries( $type_id, $limit = 100, $offset = 0 ) {
		$entries = $this->legacy_db->get_results(
			$this->legacy_db->prepare(
				"SELECT * FROM entries WHERE typeId = %s LIMIT %d OFFSET %d",
				$type_id,
				$limit,
				$offset
			)
		);

		if ( empty( $entries ) ) {
			\MigrationBoilerplate\error( 'No entries found in Craft CMS database.' );
			return [];
		}

		return $entries;
	}

	/**
	 * Get content for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return object|null
	 */
	private function get_entry_content( $entry_id ) {
		return $this->legacy_db->get_row(
			$this->legacy_db->prepare(
				"SELECT * FROM content WHERE elementId = %d",
				$entry_id
			)
		);
	}

	/**
	 * Get featured image for an entry
	 *
	 * @param int $entry_id Entry ID
	 * @return object|null
	 */
	private function get_featured_image( $entry_id ) {
		// First get the asset ID from the content table
		$asset_id = $this->legacy_db->get_var(
			$this->legacy_db->prepare(
				"SELECT field_featuredImage FROM content WHERE elementId = %d",
				$entry_id
			)
		);

		if ( ! $asset_id ) {
			return null;
		}

		// Then get the asset details
		return $this->legacy_db->get_row(
			$this->legacy_db->prepare(
				"SELECT * FROM assets WHERE id = %d",
				$asset_id
			)
		);
	}

	/**
	 * Create WordPress post from Craft CMS entry
	 *
	 * @param object $entry Craft CMS entry
	 * @return int|WP_Error Post ID on success, WP_Error on failure
	 */
	public function create_post( $entry ) {

		// Check if the post already exists.
		$post_id = 0;

		// TODO: Add this to the CLI args.
		$overwrite = 'overwrite';
		if ( 'overwrite' === $overwrite || 'skip' === $overwrite ) {
			$post_id = (int) \MigrationBoilerplate\get_post_id_by_meta( '_craft_cms_post_id', $entry->id );
		}

		// Get additional entry data
		$content = $this->get_entry_content( $entry->id );
		
		// Not Correct meta field mapping.
		//$featured_image = $this->get_featured_image( $entry->id );

		// Prepare post data
		$post_data = [
			'ID'            => $post_id,
			'post_title'    => $content->title,
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_date'     => $entry->dateCreated,
			'post_modified' => $entry->dateUpdated,
		];

		// TODO: Incorrect Mapping.
		// if ( $content ) {
		// 	$post_data['post_content'] = $content->field_body ?? '';
		// 	$post_data['post_excerpt'] = $content->field_excerpt ?? '';
		// }

		// Insert post
		$post_id = wp_insert_post( $post_data );

		// Set the legacy post ID.
		update_post_meta( $post_id, '_craft_cms_post_id', $entry->id );

		if ( is_wp_error( $post_id ) ) {
			\MigrationBoilerplate\error( "Failed to create post for entry {$entry->id}: " . $post_id->get_error_message() );
			return $post_id;
		}

		// Handle featured image if available
		if ( $featured_image ) {
			// TODO: Download and attach featured image
			// This will require additional implementation to:
			// 1. Download the image from the source URL
			// 2. Create a WordPress attachment
			// 3. Set it as the featured image
			\MigrationBoilerplate\log( "Featured image found for entry {$entry->id}: {$featured_image->filename}" );
		}

		return $post_id;
	}

	/**
	 * Process a single entry
	 *
	 * @param object $entry Craft CMS entry
	 * @return bool True on success, false on failure
	 */
	public function process_entry( $entry ) {
		$post_id = $this->create_post( $entry );

		if ( is_wp_error( $post_id ) ) {
			\MigrationBoilerplate\error( "Failed to create post for entry {$entry->id}: " . $post_id->get_error_message() );
			return false;
		}

		return true;
	}
} 