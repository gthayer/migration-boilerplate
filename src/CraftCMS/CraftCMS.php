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
	 * @param int    $type_id Entry type ID to fetch
	 * @param int    $limit Number of entries to fetch
	 * @param int    $offset Number of entries to skip
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
	 * Create WordPress post from Craft CMS entry
	 *
	 * @param object $entry Craft CMS entry
	 * @return int|WP_Error Post ID on success, WP_Error on failure
	 */
	public function create_post( $entry ) {
		$post_data = array(
			'post_title'    => $entry->title,
			'post_status'   => 'draft',
			'post_type'     => 'post',
			'post_date'     => $entry->dateCreated,
			'post_modified' => $entry->dateUpdated,
		);

		return wp_insert_post( $post_data );
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