<?php

namespace MigrationBoilerplate;

/**
 * Output and log info records
 *
 * @param       $message
 */
function log( $message ) {
	\WP_CLI::log( $message );
}

/**
 * Output and log success records
 *
 * @param      $message
 */
function success( $message ) {
	\WP_CLI::success( $message );
}

/**
 * Log and output warning messages
 *
 * @param      $message
 */
function warning( $message, $context = array() ) {
	\WP_CLI::warning( $message );
}

/**
 * Log and output error messages
 *
 * @param      $message
 */
function error( $message ) {
	\WP_CLI::error( $message );
}

/**
 * Log and output debug messages
 * Note these only show in console where `--debug` is set
 *
 * @param      $message
 */
function debug( $message ) {
	\WP_CLI::debug( $message );
}


/**
 * Dump data and stop
 */
function stop( $message ) {
	var_dump( $message );
	\WP_CLI::error( '0' );
}