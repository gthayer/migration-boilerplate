<?php
/**
 * Export Content Report
 *
 * @package MigrationBoilerplate\Command
 */
namespace MigrationBoilerplate\Command;

use MigrationBoilerplate\Brightspot\Brightspot;

class ExportContentReport extends Command {

	public $site;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Create a report of content types.
	 *
	 * ## OPTIONS
	 * 
	*/
	/**
	 * ## OPTIONS
	 * 
	 * [--legacy_id=<post_id>]
	 * : A post's legacy ID.
	 *
	 * ## EXAMPLES
	 *
	 * wp migration-boilerplate event-redesign migrate --post_id=post_id --dry-run=false
	 *
	 * @when after_wp_load
	 *
	 * @param array $args Array with arguments.
	 * @param array $assoc_args Associative array with arguments.
	 */
	public function content_report( $args, $assoc_args ) {
		$this->site = $assoc_args['site'] ?? null;
		$this->build_content_report();
	}

	function build_content_report( $next_url = null ) {
		$brightspot = new Brightspot();
		$data       = $brightspot->get_content( $this->site, $next_url );
		$next_url   = $brightspot->next_url;
        
		foreach( $data['results'] as $result ) {
			$this->callback( $result );
        }

		if ( ! empty( $next_url ) ) {
			// Free up memory and run again.
			stop_the_insanity();
			$this->build_content_report( $next_url );
		}
	}

	function find_missing_items( $offset = 0 ) {

		$offset = intval($offset);

		global $wpdb;
		$sitemap_table        = $wpdb->prefix . 'migration_legacy_sitemap';
		$content_report_table = $wpdb->prefix . 'migration_content_report';

		log( 'Checking for missing items. Offset: ' . $offset );
		$sitemap_resp = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT * FROM $sitemap_table LIMIT 50 OFFSET %d",
				$offset
			)
		);

		if ( empty( $sitemap_resp ) ) {
			log( 'No more items to process.' );
			return;
		}

		foreach( $sitemap_resp as $sitemap_item ) {
			$resp = $wpdb->get_results( 
				$wpdb->prepare( 
					"SELECT * FROM $content_report_table WHERE url = %s",
					$sitemap_item->url
				)
			);

			if ( empty( $resp ) ) {
				$wpdb->insert( $wpdb->prefix . 'migration_missing', [ 'url' => $sitemap_item->url ] );
			}
		}

		$this->find_missing_items( $offset + 50 );
	}

	/**
	 * Callback function for the content report.
	 *
	 * @param array $content_item The content item to process.
	 * @return void
	 */
    public function callback( $content_item ) {

		$parsed_item = [
			'api_id' 	    => $content_item['id'] ?? '',
			'title'         => $content_item['headline'] ?? '',
			'content_type'  => $content_item['contentTypeName'] ?? '',
			'site_name'     => $content_item['ownerSite']['name'] ?? '',
			'url'           => $this->get_url( $content_item ),
			'canonical_url' => $content_item['canonicalUrl'] ?? '',
		];

		// Check if the URL is in the database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'migration_legacy_sitemap';
		$exists     = $wpdb->get_var( $wpdb->prepare( "SELECT * FROM $table_name WHERE url = %s", $parsed_item['url'] ) );

		$parsed_item['in_sitemap'] = $exists ? 1 : 0;

		$this->write_to_db( $parsed_item );
    }

	function get_url( $content_item ) {

		$content_urls  = $content_item['contentUris'] ?? [];

		// If there are no URLs at all, return null or handle the error
		if ( empty( $content_urls ) ) {
			return null;
		}

		// If no specific site is set, return the first URL
		if ( empty( $this->site ) ) {
			return $content_urls[0]['uri'] ?? null;
		}

		foreach ( $content_urls as $content_url ) {
			if ( strtolower( $content_url['site']['name'] ) === strtolower( $this->site ) ) {
				return $content_url['uri'];
			}
		}

		// If no matching site found, either return the first URL or null
		return $content_urls[0]['uri'] ?? null;
	}

	function write_to_db( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'migration_content_report';
		$resp = $wpdb->insert( $table_name, $data );

		if ( ! $resp ) {
			var_dump( $wpdb->last_error );
			var_dump( $data );
			exit;
		}
	}
}
