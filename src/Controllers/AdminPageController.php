<?php

namespace WPMetricsLab\Controllers;

use WPMetricsLab\Models\AdminPageModel;

if (!defined('ABSPATH')) {
    exit;
}

class AdminPageController {

    private $model;

    public function __construct() {
        $this->model = new AdminPageModel();
    }

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

    public function enqueueAdminStyles() {
        wp_enqueue_style('custom_wp_admin_css', WPMETRICSLAB_URI . 'assets/css/admin-main.css');
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    }

    public function wpmetricslab_register_settings() {
        register_setting('wpmetricslab_settings_group', 'wpmetricslab_permanent_filter', ['default' => 'off']);
    }

    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';

        // Fetching filters and current page
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

        // Using the model to fetch logs
        $logs = $this->model->fetchLogs($table_name, $user_filter, $current_page, $per_page, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
        $total_logs = $this->model->countLogs($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
        $page_links = $this->paginate($total_logs, $per_page, $current_page, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);

        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            $this->model->export_to_csv($table_name, $user_filter, $phone_filter, $event_type_filter, $ip_filter, $date_filter, $start_date, $end_date, $user_agent_filter, $fingerprint_filter, $search_query);
            exit();
        }

        // Load the dashboard view
        include(WPMETRICSLAB_PATH . 'views/dashboard.php');
    }

    public function renderSettings() {
        $user_id = get_current_user_id();
        $wpmetricslab_permanent_filter = get_user_meta($user_id, 'wpmetricslab_permanent_filter', true) ?: 'off';
        include(WPMETRICSLAB_PATH . 'views/settings.php');
    }

    private function getCurrentPage() {
        return isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    }

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
            'fingerprint_filter' => $fingerprint_filter
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
}
