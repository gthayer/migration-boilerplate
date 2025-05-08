<?php
/**
 * Yoast Helpers
 *
 * @package MigrationBoilerplate\Yoast
 */

namespace MigrationBoilerplate\Yoast;

/**
 * Yoast Helpers
 *
 * @package MigrationBoilerplate\Yoast
 */
class Yoast {

	/**
	 * Redirect manager instance
	 *
	 * @var string
	 */
	private $redirect_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( class_exists( 'WPSEO_Redirect_Manager' ) ) {
			$this->redirect_manager = new \WPSEO_Redirect_Manager();
		}
	}

	/**
	 * Save redirects to Yoast SEO redirects
	 *
	 * @param string $origin_url The original URL to redirect from (relative path or regex pattern)
	 * @param string $target_url The target URL to redirect to
	 * @param int    $type       Redirect type (301, 302, 307, etc). Default 301
	 * @param string $format     Redirect format (plain or regex). Default 'plain'
	 *
	 * @return array|WP_Error Array with status and message, or WP_Error on failure
	 */
	public function create_redirect( $origin_url, $target_url, $type = 301, $format = 'plain' ) {

			// Check if Yoast SEO is active
		if ( ! class_exists( 'WPSEO_Redirect_Manager' ) ) {
			return new WP_Error( 'yoast_missing', 'Yoast SEO plugin is not active' );
		}

		// Validate parameters
		if ( empty( $origin_url ) || empty( $target_url ) ) {
			return new WP_Error( 'invalid_params', 'Origin and target URLs are required' );
		}

		// Sanitize URLs
		$origin_url = sanitize_url( $origin_url );
		$target_url = sanitize_url( $target_url );

		// Validate redirect type
		$valid_types = [ 301, 302, 307, 308, 410, 451 ];
		if ( ! in_array( $type, $valid_types, true ) ) {
			return new WP_Error( 'invalid_type', 'Invalid redirect type' );
		}

		// Validate format
		$format = in_array( strtolower( $format ), [ 'plain', 'regex' ], true ) ? strtolower( $format ) : 'plain';

		try {
			// Add new redirect
			$redirect = new \WPSEO_Redirect(
				$origin_url,
				$target_url,
				$type,
				$format
			);

			$this->redirect_manager->create_redirect( $redirect );

			return [
				'status'  => 'success',
				'message' => 'Redirect successfully added',
			];
		} catch ( Exception $e ) {
			return new WP_Error( 'redirect_error', $e->getMessage() );
		}
	}
}