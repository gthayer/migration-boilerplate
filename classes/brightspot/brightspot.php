<?php 
/**
 * Command Abstract class.
 */

namespace MigrationBoilerplate\Brightspot;

class Brightspot {

    public  $next_url = null;
    private $api_url       = BRIGHTSPOT_API_URL;
    private $client_id     = BRIGHTSPOT_CLIENT_ID;
    private $client_secret = BRIGHTSPOT_CLIENT_SECRET;

    private $token;

	public function __construct() {
        $this->token = $this->get_token();
	}

    /**
     * Get the token needed for Brightspot requests.
     *
     * @return string
     */
    public function get_token() {

        $token   = get_option( 'brightspot_token' );
        $expires = get_option( 'brightspot_token_expires' );

        if ( ! empty( $token ) && $expires > time() ) {
            return $token;
        }

        \MigrationBoilerplate\log( 'Fetching token...' );
        // Don't use the api_url here because the path is different.
        $response = wp_remote_post( 'https://cms.hanleywood.com/api/v1_8/auth/token', array(
            'body' => array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'client_credentials'
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            \MigrationBoilerplate\error( 'Error fetching token' );
            exit;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( isset( $data->status ) && $data->status !== 'success' ) {
            \MigrationBoilerplate\error( 'Error fetching token. ' . $data->message );
            exit;
        }

        \MigrationBoilerplate\log( 'Token fetched' );

        update_option( 'brightspot_token', $data->access_token );
        update_option( 'brightspot_token_expires', time() + $data->expires_in );
        
        return $data->access_token;
    }

    public function get_content( $site = null, $url = null ) {

        if ( empty( $url ) ) {
            $url = $this->api_url . 'contentitems';

            if ( ! empty( $site ) ) {
                $url .= "/sites/{$site}/";
            }
        }

        \MigrationBoilerplate\log( 'Fetching content: ' . $url );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token,
            ),
            'timeout' => 60,
        ));

		if ( is_wp_error( $response ) ) {
            var_dump( $response );
            \MigrationBoilerplate\error( 'Error retrieving data' );
            exit;
		}

		$resp_body = wp_remote_retrieve_body( $response );
		$json_body = json_decode( $resp_body, true );

        if ( $json_body['status'] !== 'success' ) {
            \MigrationBoilerplate\error( 'Error retrieving data. Code: ' . $json_body['code'] );
            return 'Error retrieving data. Code: ' . $json_body['code'];
        }

        $data     = $json_body['data'] ?? [];
        $links    = $data['links'] ?? [];
        $next_url = $this->set_next_url( $links );

        return $data;
    }

    /**
     * Set the next URL.
     *
     * @param array $links The links array.
     * @return void
     */
    public function set_next_url( $links ) {
        $next_url  = null;
        $next_link = array_filter( $links, function( $link ) {
            return $link['rel'] === 'next';
        });
        if ( ! empty( $next_link ) ) {
            // array_filter returns an array with numeric keys, so we need to reset it
            $next_link = reset($next_link);
            $next_url = $next_link['href'] ?? null;
        }

        $this->next_url = $next_url;
    }
}
