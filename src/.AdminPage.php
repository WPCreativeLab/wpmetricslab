<?php

namespace WPMetricsLab;
ob_start();

if (!defined('ABSPATH')) {
    exit;
}

class AdminPage {

    /**
     * Initializes the admin menu items and associated pages.
     */
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
        add_menu_page(
            __('WPMetricsLab', 'wpmetricslab'),
            __('WPMetricsLab', 'wpmetricslab'),
            'manage_options',
            'wpmetricslab',
            [$this, 'renderDashboard'],
            'dashicons-admin-site-alt3',
            6
        );
        add_submenu_page(
            'wpmetricslab',
            'Settings',
            'Settings',
            'manage_options',
            'settings',
            [$this, 'renderSettings']
        );

        add_action('admin_init', [$this, 'wpmetricslab_register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'add_fontawesome']);
    }

    /**
     * Enqueues stylesheets for the admin page.
     */
    public function enqueueAdminStyles() {
        wp_enqueue_style('custom_wp_admin_css', WPMETRICSLAB_URI . 'assets/css/admin-style.css');
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');

    }

    public function wpmetricslab_register_settings() {
        register_setting('wpmetricslab_settings_group', 'wpmetricslab_permanent_filter', ['default' => 'off']);
    }
    

    /**
     * Renders the content of the Dashboard page.
     */
    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';

        $user_filter = isset($_GET['user_filter']) ? $_GET['user_filter'] : '';
        $phone_filter = isset($_GET['phone_filter']) ? $_GET['phone_filter'] : '';
        $event_type_filter = isset($_GET['event_type_filter']) ? $_GET['event_type_filter'] : '';
        $ip_filter = isset($_GET['ip_filter']) ? $_GET['ip_filter'] : '';
        $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
        $user_agent_filter = isset($_GET['user_agent_filter']) ? $_GET['user_agent_filter'] : '';
        $fingerprint_filter = isset($_GET['fingerprint_filter']) ? $_GET['fingerprint_filter'] : '';
        $search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
        

        $current_page = $this->getCurrentPage();
        $per_page = 10;
    
        $logs = $this->fetchLogs($table_name, $user_filter, $current_page, $per_page, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
        $total_logs = $this->countLogs($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
        $page_links = $this->paginate($total_logs, $per_page, $current_page, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);

        // Check if there's a request for export
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            $this->export_to_csv($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
            exit();
        }

        include(WPMETRICSLAB_PATH . 'views/dashboard.php');
    }
    

    /**
     * Renders the content of the Settings page.
     */
    public function renderSettings() {
        $user_id = get_current_user_id();
        $wpmetricslab_permanent_filter = get_user_meta($user_id, 'wpmetricslab_permanent_filter', true) ?: 'off';
        include(WPMETRICSLAB_PATH . 'views/settings.php');
    }

    /**
     * Determines the current page number.
     */
    private function getCurrentPage() {
        return isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    }    

    /**
     * Fetches log entries from the database.
     */
    private function fetchLogs($table_name, $user_filter, $current_page, $per_page, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
        global $wpdb;
        $offset = ($current_page - 1) * $per_page;
        /* $sql = "SELECT * FROM {$table_name} WHERE 1=1 "; */
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
    private function countLogs($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
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
     * Generates pagination links.
     */
    private function paginate($total_logs, $per_page, $current_page, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
        $total_pages = ceil($total_logs / $per_page);
        $base_url = add_query_arg([
            'page' => 'wpmetricslab',
            'user_filter' => $user_filter,
            'phone_filter' => $phone_filter,
            'event_type_filter' => $event_type_filter,
            'ip_filter' => $ip_filter,
            'date_filter' => $date_filter,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'fingerprint_filter', $fingerprint_filter
        ], admin_url('admin.php'));
    
        return paginate_links([
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;')
        ]);
    }

    public function fetchLogsByUserAgent($user_agent) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE user_agent = %s ORDER BY session_start DESC", $user_agent);
        return $wpdb->get_results($sql);
    }
    
    public function hasFrequentIPChanges($user_agent) {
        $logs = $this->fetchLogsByUserAgent($user_agent);
        $ips = [];
        foreach ($logs as $log) {
            $ips[] = $log->ip_address;
        }
        $unique_ips = array_unique($ips);
        $ipChangeCount = count($unique_ips);
        return $ipChangeCount;  // Returns the count of unique IP addresses
    }
    
    public function hasMultipleConsecutiveVisits($user_agent, $date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $sql = $wpdb->prepare("SELECT event_type, session_start FROM $table_name WHERE user_agent = %s AND DATE(session_start) = %s ORDER BY session_start ASC", $user_agent, $date);
        $results = $wpdb->get_results($sql);
    
        $consecutiveVisits = 0;
        $maxConsecutiveVisits = 0;
        $lastEventType = '';
    
        foreach ($results as $result) {
            if ($result->event_type === 'visit' && $lastEventType === 'visit') {
                $consecutiveVisits++;
                if ($consecutiveVisits > $maxConsecutiveVisits) {
                    $maxConsecutiveVisits = $consecutiveVisits;
                }
            } else {
                $consecutiveVisits = 0;
            }
            $lastEventType = $result->event_type;
        }
    
        return $maxConsecutiveVisits;  // Returns the maximum number of consecutive visits
    }
    
    private function export_to_csv($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
    
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
