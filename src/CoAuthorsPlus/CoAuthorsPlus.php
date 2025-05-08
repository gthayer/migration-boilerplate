<?php
/**
 * CoAuthorsPlus class.
 *
 * @package MigrationBoilerplate\CoAuthorsPlus
 */

namespace MigrationBoilerplate\CoAuthorsPlus;

/**
 * CoAuthorsPlus
 *
 * @package MigrationBoilerplate\CoAuthorsPlus
 */
class CoAuthorsPlus {

	/**
	 * CoAuthorsPlus instance.
	 *
	 * @var object
	 */
	public $coauthors_plus;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Check if global exists.
		global $coauthors_plus;
		if ( ! $coauthors_plus instanceof \CoAuthors_Plus || ! $coauthors_plus->guest_authors instanceof \CoAuthors_Guest_Authors ) {
			log( 'Error: this migration is dependent on the Co-Authors Plus plugin, which was not found.' );
			exit;
		}

		$this->coauthors_plus = $coauthors_plus;
	}

	/**
	 * Find or create an author using Co-Authors Plus plugin > Guest Authors.
	 *
	 * @param array  $author_data The author data.
	 * @param string $ref         The author reference ID (optional).
	 *
	 * @return object|WP_Error The guest author object or WP_Error on failure.
	 */
	public function maybe_create_author( $author_data, $ref = '' ) {
		$default_author_data = [
			'display_name' => '', // Required.
			'user_login'   => '', // Required.
			'description'  => '',
			'first_name'   => '',
			'last_name'    => '',
			'user_email'   => '',
			'avatar'       => '',
		];

		// Merge default data with provided author data.
		$data = wp_parse_args( $author_data, $default_author_data );

		// Validate required fields.
		if ( empty( $data['display_name'] ) || empty( $data['user_login'] ) ) {
			error_log( 'Missing required fields: ' . print_r( $data, true ) ); // phpcs:ignore
			return new WP_Error( 'missing_required_fields', 'Display name and user login are required.' );
		}

		// Check for an existing guest author by user_login.
		$guest_author = $this->coauthors_plus->guest_authors->get_guest_author_by( 'user_login', $data['user_login'] );
		if ( $guest_author ) {
			return $guest_author;
		}

		// Fetch additional author data from an external source if a reference ID is provided.
		if ( $ref ) {
			$brightspot   = new Brightspot();
			$api_response = $brightspot->get_content( null, $ref );

			if ( is_wp_error( $api_response ) ) {
				error_log( 'Failed to fetch author data: ' . $api_response->get_error_message() ); // phpcs:ignore
				return $api_response;
			}

			if ( isset( $api_response['results'][0] ) ) {
				$result = $api_response['results'][0];
				$data   = $this->map_api_response_to_author_data( $data, $result );
			}
		}

		// Validate user_email if present.
		if ( ! empty( $data['user_email'] ) && ! is_email( $data['user_email'] ) ) {
			error_log( 'Invalid email address: ' . $data['user_email'] ); // phpcs:ignore
			return new WP_Error( 'invalid_email', 'Provided email address is not valid.' );
		}

		// Attempt to create the guest author.
		$author_id = $this->coauthors_plus->guest_authors->create( $data );
		if ( is_wp_error( $author_id ) ) {
			error_log( 'Failed to create guest author: ' . $author_id->get_error_message() ); // phpcs:ignore
			return $author_id;
		}

		// Retrieve and return the created guest author.
		$guest_author = $this->coauthors_plus->guest_authors->get_guest_author_by( 'ID', $author_id );
		if ( ! $guest_author ) {
			error_log( "Failed to retrieve guest author after creation: Author ID - $author_id" ); // phpcs:ignore
			return new WP_Error( 'retrieval_failed', 'Failed to retrieve the newly created guest author.' );
		}

		return $guest_author;
	}


	/**
	 * Map API response to author data.
	 *
	 * @param array $data   The existing author data array.
	 * @param array $result The API response data.
	 *
	 * @return array The updated author data.
	 */
	private function map_api_response_to_author_data( $data, $result ) {
		// Map API response to corresponding fields.
		$data['display_name'] = $result['name'] ?? $data['display_name'];
		$data['description']  = wp_strip_all_tags( $result['bio'] ?? $data['description'] );
		$data['user_email']   = sanitize_email( $result['emailAddress'] ?? $data['user_email'] );
		$data['first_name']   = sanitize_text_field( $result['firstName'] ?? $data['first_name'] );
		$data['last_name']    = sanitize_text_field( $result['lastName'] ?? $data['last_name'] );

		// Website and social media links.
		$data['website']      = $result['websiteLink']['url'] ?? '';
		$data['twitter_url']  = $result['twitterLink']['url'] ?? '';
		$data['facebook_url'] = $result['facebookLink']['url'] ?? '';
		$data['linkedin_url'] = $result['linkedinLink']['url'] ?? '';

		// Profile image.
		if ( ! empty( $result['image'] ) ) {
			$brightspot    = new Brightspot();
			$attachment_id = $brightspot->import_image( $result['image'] );
			if ( ! is_wp_error( $attachment_id ) ) {
				$data['avatar'] = $attachment_id;
			}
		}

		// Generate user_login if missing.
		if ( empty( $data['user_login'] ) ) {
			$data['user_login'] = sanitize_title( $data['display_name'] );
		}

		return $data;
	}

	/**
	 * Assign authors to a post.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $authors The authors array.
	 */
	public function assign_authors_to_post( $post_id, $authors ) {
		$this->coauthors_plus->add_coauthors( $post_id, $authors );
	}
}
