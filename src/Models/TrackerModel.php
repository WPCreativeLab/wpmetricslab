<?php

namespace WPMetricsLab\Models;

use wpdb;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class TrackerModel {

    public function logActivity($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';

        $result = $wpdb->insert($table_name, $data, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
            throw new Exception('Failed to log activity: ' . $wpdb->last_error);
        } else {
            error_log('Activity logged successfully');
        }
    }

    public function logLinkClick($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';

        $result = $wpdb->insert($table_name, $data, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
            throw new Exception('Failed to log activity: ' . $wpdb->last_error);
        }
    }

    public function logPageView($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';

        $result = $wpdb->insert($table_name, $data, ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
            throw new Exception('Failed to log activity: ' . $wpdb->last_error);
        } else {
            error_log('Activity logged successfully.');
        }
    }

}
