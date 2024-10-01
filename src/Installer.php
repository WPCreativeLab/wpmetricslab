<?php

namespace WPMetricsLab;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {

    /**
     * Runs the installation process, creating the database table.
     */
    public function run() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
    
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) DEFAULT NULL,
            user_name varchar(60) DEFAULT NULL,
            user_role varchar(60) DEFAULT NULL,
            object_id mediumint(9) DEFAULT NULL,
            object_type varchar(60) DEFAULT NULL,
            event_type varchar(60) NOT NULL,
            message text NOT NULL,
            ip_address varchar(100) NOT NULL,
            user_agent text NOT NULL,
            session_start datetime DEFAULT NULL,
            session_end datetime DEFAULT NULL,
            session_duration int DEFAULT 0,
            current_url text NOT NULL,
            fingerprint text DEFAULT NULL,
            fingerprint_data longtext DEFAULT NULL,
            PRIMARY KEY  (id)
        ) {$wpdb->get_charset_collate()};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if ($wpdb->last_error) {
            error_log('Database error: ' . $wpdb->last_error);
        }
    }
}