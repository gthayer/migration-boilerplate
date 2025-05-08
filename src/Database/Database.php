<?php
/**
 * Database class for handling migration tables.
 *
 * @package MigrationBoilerplate\Database
 */

namespace MigrationBoilerplate\Database;

use wpdb;

class Database {

	public function __construct() {}

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'migration_legacy_sitemap';

        // Create the tables
        $sql = "CREATE TABLE $table_name (
            url longtext NOT NULL
        ) $charset_collate;";

        $table_name = $wpdb->prefix . 'migration_content_report';
        $sql .= "CREATE TABLE $table_name (
            `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `api_id` longtext NULL DEFAULT NULL,
            `title` longtext NULL DEFAULT NULL,
            `content_type` longtext NULL DEFAULT NULL,
            `site_name` text NULL DEFAULT NULL,
            `url` longtext NULL DEFAULT NULL,
            `canonical_url` longtext NULL DEFAULT NULL,
            `in_sitemap` tinyint(1) NULL DEFAULT 0
        );";

        $table_name = $wpdb->prefix . 'migration_missing';
        $sql .= "CREATE TABLE $table_name (
            `url` longtext NULL DEFAULT NULL
        );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function drop_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'migration_legacy_sitemap';
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query( $sql );

        $table_name = $wpdb->prefix . 'migration_content_report';
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query( $sql );

        return true;
    }
}
