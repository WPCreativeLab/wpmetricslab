<?php

namespace WPMetricsLab\Models;

use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

class ActivityLogModel {

    public function getLoggedUsers($table_name) {
        global $wpdb;
    
        // Query the unique user_ids in the logs, including 0
        $user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM {$table_name}");
    
        // If there's a '0' ID, add an 'Anonymous' option to the user list
        $has_anonymous = in_array(0, $user_ids);
    
        // Remove '0' ID from the user IDs so get_users only fetches valid users
        $user_ids = array_filter($user_ids, function($id) { return $id > 0; });
    
        if (empty($user_ids) && !$has_anonymous) {
            return [];  // If no valid users and no 'Anonymous', return an empty array
        }
    
        $users = [];
        if (!empty($user_ids)) {
            $args = [
                'include' => $user_ids,
                'orderby' => 'login',
                'order' => 'ASC'
            ];
            $users = get_users($args);  // Fetch users with logs
        }
    
        if ($has_anonymous) {
            // Add a fake user for 'Anonymous'
            $anonymous = new \stdClass();
            $anonymous->ID = 0;
            $anonymous->user_login = 'Anonymous';
            array_unshift($users, $anonymous);
        }
    
        return $users;
    }

    public function getEventTypes($table_name) {
        global $wpdb;
        return $wpdb->get_col("SELECT DISTINCT event_type FROM {$table_name} WHERE event_type IS NOT NULL");
    }

    public function getIPCounts($table_name) {
        global $wpdb;
        $sql = "SELECT ip_address, COUNT(*) as count FROM {$table_name} GROUP BY ip_address ORDER BY count DESC";
        return $wpdb->get_results($sql);
    }

    public function getPhoneNumbers($table_name) {
        global $wpdb;
        $sql = "SELECT DISTINCT current_url FROM {$table_name} WHERE event_type = 'call'";
        $results = $wpdb->get_col($sql);
        return array_map([$this, 'formatPhoneNumber'], $results);
    }

    public function deleteSelectedLogs($table_name, $log_ids) {
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
        $sql = "DELETE FROM {$table_name} WHERE id IN ($placeholders)";
        return $wpdb->query($wpdb->prepare($sql, $log_ids));
    }

    public function deleteAllLogs($table_name) {
        global $wpdb;
        $sql = "DELETE FROM {$table_name}";
        return $wpdb->query($sql);
    }
    
}
