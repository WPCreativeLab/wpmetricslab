<?php

namespace WPMetricsLab\Models;
ob_start();

use \wpdb;

if (!defined('ABSPATH')) {
    exit;
}

class AdminPageModel {

    /**
     * Fetches log entries from the database.
     */
    public function fetchLogs($table_name, $user_filter, $current_page, $per_page, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
        global $wpdb;
        $offset = ($current_page - 1) * $per_page;
        $sql = "SELECT *, COUNT(*) OVER (PARTITION BY user_agent) as visit_count,
                LAG(session_start) OVER (PARTITION BY user_agent ORDER BY session_start) as prev_visit,
                LEAD(session_start) OVER (PARTITION BY user_agent ORDER BY session_start) as next_visit
                FROM {$table_name} WHERE 1=1 ";

        // Adding date-based filtering
        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'all_time':
                    // No additional SQL needed
                    break;
                case 'today':
                    $sql .= " AND session_start BETWEEN CURDATE() AND CURDATE() + INTERVAL 1 DAY - INTERVAL 1 SECOND";
                    break;
                case 'yesterday':
                    $sql .= " AND session_start BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE() - INTERVAL 1 SECOND";
                    break;
                case 'last_7_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 7 DAY";
                    break;
                case 'last_14_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 14 DAY";
                    break;
                case 'last_30_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 30 DAY";
                    break;
                case 'this_week':
                    $start_of_week = date('Y-m-d', strtotime('monday this week'));
                    $sql .= $wpdb->prepare(" AND session_start >= %s", $start_of_week);
                    break;
                case 'last_week':
                    $start_of_last_week = date('Y-m-d', strtotime('monday last week'));
                    $end_of_last_week = date('Y-m-d', strtotime('sunday last week'));
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_of_last_week . ' 00:00:00', 
                                            $end_of_last_week . ' 23:59:59');
                    break;
                case 'this_month':
                    $start_of_month = date('Y-m-01');
                    $sql .= $wpdb->prepare(" AND session_start >= %s", $start_of_month);
                    break;
                case 'last_month':
                    $start_of_last_month = date('Y-m-01', strtotime('first day of last month'));
                    $end_of_last_month = date('Y-m-t', strtotime('last day of last month'));
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_of_last_month . ' 00:00:00', 
                                            $end_of_last_month . ' 23:59:59');
                    break;
                case 'custom':
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_date . ' 00:00:00', 
                                            $end_date . ' 23:59:59');
                    break;
            }
        }

        if (!empty($user_filter)) {
            if ($user_filter === 'Anonymous') {
                $sql .= " AND user_id = 0";
            } else {
                $sql .= $wpdb->prepare(" AND user_name = %s", $user_filter);
            }
        }

        if (!empty($phone_filter)) {
            $sql .= $wpdb->prepare(" AND current_url LIKE %s", '%' . $wpdb->esc_like($phone_filter) . '%');
        }

        if (!empty($event_type_filter)) {
            $sql .= $wpdb->prepare(" AND event_type = %s", $event_type_filter);
        }

        if (!empty($ip_filter)) {
            $sql .= $wpdb->prepare(" AND ip_address = %s", $ip_filter);
        }

        if (!empty($user_agent_filter)) {
            $sql .= $wpdb->prepare(" AND user_agent = %s", $user_agent_filter);
        }

        if (!empty($fingerprint_filter)) {
            $sql .= $wpdb->prepare(" AND fingerprint = %s", $fingerprint_filter);
        }

        // Adding text search
        if (!empty($search_query)) {
            $search_like = '%' . $wpdb->esc_like($search_query) . '%';
            $sql .= $wpdb->prepare(" AND (id LIKE %s OR session_start LIKE %s OR user_name LIKE %s OR ip_address LIKE %s OR user_agent LIKE %s OR event_type LIKE %s OR message LIKE %s OR current_url LIKE %s OR fingerprint LIKE %s)",
                $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
        }

        $sql .= $wpdb->prepare(" ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset);
        $logs = $wpdb->get_results($sql);

        return $logs;
    }

    /**
     * Counts the number of relevant log entries.
     */
    public function countLogs($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1 ";

        if (!empty($user_filter)) {
            if ($user_filter === 'Anonymous') {
                $sql .= " AND user_id = 0";
            } else {
                $sql .= $wpdb->prepare(" AND user_name = %s", $user_filter);
            }
        }
        if (!empty($phone_filter)) {
            $sql .= $wpdb->prepare(" AND current_url LIKE %s", '%' . $wpdb->esc_like($phone_filter) . '%');
        }
        if (!empty($event_type_filter)) {
            $sql .= $wpdb->prepare(" AND event_type = %s", $event_type_filter);
        }
        if (!empty($ip_filter)) {
            $sql .= $wpdb->prepare(" AND ip_address = %s", $ip_filter);
        }
        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'all_time':
                    // No additional SQL needed
                    break;
                case 'today':
                    $sql .= " AND session_start BETWEEN CURDATE() AND CURDATE() + INTERVAL 1 DAY - INTERVAL 1 SECOND";
                    break;
                case 'yesterday':
                    $sql .= " AND session_start BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE() - INTERVAL 1 SECOND";
                    break;
                case 'last_7_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 7 DAY";
                    break;
                case 'last_14_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 14 DAY";
                    break;
                case 'last_30_days':
                    $sql .= " AND session_start >= CURDATE() - INTERVAL 30 DAY";
                    break;
                case 'this_week':
                    $start_of_week = date('Y-m-d', strtotime('monday this week'));
                    $sql .= $wpdb->prepare(" AND session_start >= %s", $start_of_week);
                    break;
                case 'last_week':
                    $start_of_last_week = date('Y-m-d', strtotime('monday last week'));
                    $end_of_last_week = date('Y-m-d', strtotime('sunday last week'));
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_of_last_week . ' 00:00:00', 
                                            $end_of_last_week . ' 23:59:59');
                    break;
                case 'this_month':
                    $start_of_month = date('Y-m-01');
                    $sql .= $wpdb->prepare(" AND session_start >= %s", $start_of_month);
                    break;
                case 'last_month':
                    $start_of_last_month = date('Y-m-01', strtotime('first day of last month'));
                    $end_of_last_month = date('Y-m-t', strtotime('last day of last month'));
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_of_last_month . ' 00:00:00', 
                                            $end_of_last_month . ' 23:59:59');
                    break;
                case 'custom':
                    $sql .= $wpdb->prepare(" AND session_start BETWEEN %s AND %s", 
                                            $start_date . ' 00:00:00', 
                                            $end_date . ' 23:59:59');
                    break;
            }
        }
        if (!empty($user_agent_filter)) {
            $sql .= $wpdb->prepare(" AND user_agent = %s", $user_agent_filter);
        }

        if (!empty($fingerprint_filter)) {
            $sql .= $wpdb->prepare(" AND fingerprint = %s", $fingerprint_filter);
        }

        // Adding text search
        if (!empty($search_query)) {
            $search_like = '%' . $wpdb->esc_like($search_query) . '%';
            $sql .= $wpdb->prepare(" AND (id LIKE %s OR session_start LIKE %s OR user_name LIKE %s OR ip_address LIKE %s OR user_agent LIKE %s OR event_type LIKE %s OR message LIKE %s OR current_url LIKE %s OR fingerprint LIKE %s)",
                $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
        }
        
        return $wpdb->get_var($sql);
    }

    /**
     * Fetches logs by user agent.
     */
    public function fetchLogsByUserAgent($user_agent) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE user_agent = %s ORDER BY session_start DESC", $user_agent);
        return $wpdb->get_results($sql);
    }

    /**
     * Exports logs to CSV.
     */
    public function export_to_csv($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
    
        $logs = $this->fetchLogs($table_name, $user_filter, 1, PHP_INT_MAX, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);

        // Use the WordPress current_time() function with 'timestamp' to get the local timestamp and format it
        $local_timestamp = current_time('timestamp');
        $formatted_local_time = date('Ymd-Hi', $local_timestamp); // Format the timestamp according to local timezone
        $site_name = sanitize_title(get_bloginfo('name'));
        $filename = strtolower('wpmetricslab-' . $site_name . '-' . $formatted_local_time . '.csv');
    
        ob_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date', 'User', 'IP Address', 'User Agent', 'Event Type', 'Message', 'URL']);
    
        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id,
                $log->session_start,
                $log->user_name,
                $log->ip_address,
                $log->user_agent,
                $log->event_type,
                $log->message,
                $log->current_url
            ]);
        }
    
        fclose($output);
        exit;
    }
    
}
