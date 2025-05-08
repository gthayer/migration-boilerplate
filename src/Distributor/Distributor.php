<?php
/**
 * Distributor
 *
 * @package MigrationBoilerplate\Distributor
 */

namespace MigrationBoilerplate\Distributor;

/**
 * OpenAI
 *
 * @package MigrationBoilerplate\Distributor
 */
class Distributor {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Push a post to a remote site.
	 *
	 * @param int   $post_id The post ID.
	 * @param int   $current_site_id The current site ID.
	 * @param int   $receiving_site_id The receiving site ID.
	 * @param array $content_item The content item to migrate.
	 *
	 * @return void
	 */
	public function push_post( $post_id, $current_site_id, $receiving_site_id, $content_item ) {

		// Get the connection map.
		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );
		if ( empty( $connection_map ) ) {
			$connection_map = array();
		}

		// Ensure the connection map has the necessary keys.
		if ( empty( $connection_map['external'] ) ) {
			$connection_map['external'] = array();
		}

		if ( empty( $connection_map['internal'] ) ) {
			$connection_map['internal'] = array();
		}

		$args = [];
		if ( ! empty( $connection_map['internal'][ $receiving_site_id ] ) ) {
			$args = [
				'remote_post_id' => $connection_map['internal'][ $receiving_site_id ]['post_id'],
			];
		}

		$connection  = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $receiving_site_id ) );
		$remote_post = $connection->push( $post_id, $args );

		if ( empty( $remote_post['id'] ) ) {
			log( "Failed to push post to remote site. {$post_id}" );
			exit;
		}

		// Set the post date on the remote post.
		// Otherwise, the post will be published at the current time.
		$post_date     = get_post_field( 'post_date', $post_id );
		$post_date_gmt = get_post_field( 'post_date_gmt', $post_id );
		switch_to_blog( $receiving_site_id );
		wp_update_post(
			[
				'ID'            => $remote_post['id'],
				'post_date'     => $post_date,
				'post_date_gmt' => $post_date_gmt,
			]
		);
		restore_current_blog();

		// Build the connection information.
		$connection_map['internal'][ (int) $receiving_site_id ] = array(
			'post_id' => (int) $remote_post['id'],
			'time'    => time(),
		);

		update_post_meta( $post_id, 'dt_connection_map', $connection_map );
	}
}
