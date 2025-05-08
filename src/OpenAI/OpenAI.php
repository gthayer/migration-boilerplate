<?php
/**
 * OpenAI
 *
 * @package MigrationBoilerplate
 */

namespace MigrationBoilerplate\Openai;

/**
 * OpenAI
 *
 * @package MigrationBoilerplate\Openai
 */
class OpenAI {

	/**
	 * Open AI API URL - Set in wp-config.php
	 *
	 * @var string
	 */
	private $api_url = OPENAI_API_URL;

	/**
	 * Open AI key - Set in wp-config.php
	 *
	 * @var string
	 */
	private $client_secret = OPENAI_CLIENT_SECRET;

	/**
	 * Open AI Model
	 *
	 * @var string
	 */
	private $model = 'gpt-4o-mini';

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Send a prompt to the OpenAI API.
	 *
	 * @param string $prompt The prompt to send.
	 *
	 * @return array The response from the API.
	 */
	public function send_prompt( $prompt ) {
		$response = wp_remote_post(
			$this->api_url . 'chat/completions',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->client_secret,
				),
				'body'    => wp_json_encode(
					[
						'model'    => 'gpt-4o-mini',
						'messages' => [
							[
								'role'    => 'system',
								'content' => 'You are a helpful assistant.',
							],
							[
								'role'    => 'user',
								'content' => $prompt,
							],
						],
					]
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			\WP_CLI::log( 'Error: ' . $response->get_error_message() );
			exit;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $response['error'] ) ) {
			\WP_CLI::log( 'Error: ' . $response['error']['message'] );
			exit;
		}

		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			\WP_CLI::log( 'Error: No content in response' );
			\WP_CLI::log( $response );
			exit;
		}

		return $response;
	}
}
