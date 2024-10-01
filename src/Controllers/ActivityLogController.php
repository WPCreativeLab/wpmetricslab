<?php

namespace WPMetricsLab\Controllers;

use WPMetricsLab\Models\ActivityLogModel;
use WPMetricsLab\Models\TrackerModel;
use WPMetricsLab\Controllers\TrackerController;
use WPMetricsLab\Controllers\WordPressActivityController;

if (!defined('ABSPATH')) {
    exit;
}

class ActivityLogController {

    private $tracker;
    private $model;
    private $adminPageController;
    private $trackerModel;
    private $wpActivityController;
    private $enqueueScripts;

    public function __construct() {
        $this->tracker = new TrackerController();
        $this->model = new ActivityLogModel();
        $this->trackerModel = new TrackerModel();
        $this->adminPageController = new AdminPageController();
        $this->wpActivityController = new WordPressActivityController();
        
        $this->initializeActions();
    }

    public function getLoggedUsers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        return $this->model->getLoggedUsers( $table_name);
    }

    public function getEventTypes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        return $this->model->getEventTypes( $table_name);
    }

    public function getIPCounts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        return $this->model->getIPCounts( $table_name);
    }

    private function initializeActions() {
        add_action('admin_menu', [$this->adminPageController, 'init']);
        add_action('wp_login', [$this, 'trackUserActivity']);
        add_action('wp_footer', [$this, 'enqueue_public_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_nopriv_track_link_click', [$this, 'trackLinkClick']);
        add_action('wp_ajax_track_link_click', [$this, 'trackLinkClick']); 
        add_action('save_post', [$this, 'handlePostSave'], 10, 3);
        add_action('before_delete_post', [$this, 'handlePostDeletion']);
        add_action('wp_trash_post', [$this, 'handlePostTrashing']);
        add_action('wp_ajax_nopriv_track_page_view', [$this, 'trackUserActivity']);
        add_action('wp_ajax_track_page_view', [$this, 'trackUserActivity']);
        add_action('wp_ajax_delete_selected_logs', [$this, 'deleteSelectedLogs']);
        add_action('wp_ajax_delete_all_logs', [$this, 'deleteAllLogs']);


        add_action('admin_notices', function() {
            if (isset($_GET['deleted']) && $_GET['deleted'] == 'true' && isset($_GET['message'])) {
                $message = sanitize_text_field(urldecode($_GET['message']));
                echo "<div class='updated'><p>{$message}</p></div>";
            }
        });
    }

    private function formatPhoneNumber($phone) {
        $phone = str_replace('tel:', '', $phone);
        $phone = urldecode($phone);
        $cleanNumber = preg_replace('/[^\d]/', '', $phone);
        if (preg_match('/^36(1|30|20|70|23)(\d{7})$/', $cleanNumber, $matches)) {
            switch ($matches[1]) {
                case '1': $prefix = '06 1 '; break;
                case '30': $prefix = '06 30 '; break;
                case '20': $prefix = '06 20 '; break;
                case '70': $prefix = '06 70 '; break;
                case '23': $prefix = '06 23 '; break;
                default: $prefix = '06 '; break;
            }
            $formatted = $prefix . substr($matches[2], 0, 3) . ' ' . substr($matches[2], 3);
        } elseif (preg_match('/^06(1|30|20|70|23)(\d{7})$/', $cleanNumber, $matches)) {
            switch ($matches[1]) {
                case '1': $prefix = '06 1 '; break;
                case '30': $prefix = '06 30 '; break;
                case '20': $prefix = '06 20 '; break;
                case '70': $prefix = '06 70 '; break;
                case '23': $prefix = '06 23 '; break;
                default: $prefix = '06 '; break;
            }
            $formatted = $prefix . substr($matches[2], 0, 3) . ' ' . substr($matches[2], 3);
        } else {
            $formatted = $phone;
        }
        return $formatted;
    }

    public function trackUserActivity() {
        $fingerprint = isset($_POST['fingerprint']) ? sanitize_text_field($_POST['fingerprint']) : '';
        $fingerprintData = isset($_POST['fingerprintData']) ? wp_unslash($_POST['fingerprintData']) : '';
        $currentUrl = isset($_POST['currentUrl']) ? esc_url_raw($_POST['currentUrl']) : '';

        if (!$this->tracker->isAdminAjaxRequest()) {
            $this->tracker->track($fingerprint, $fingerprintData, $currentUrl);
        }
    }

    public function trackLinkClick() {
        check_ajax_referer('track_link_click_nonce', 'nonce');
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $event_type = isset($_POST['eventType']) ? sanitize_text_field($_POST['eventType']) : 'click';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : 'Link clicked';
        $fingerprint = isset($_POST['fingerprint']) ? sanitize_text_field($_POST['fingerprint']) : '';
        $fingerprintData = isset($_POST['fingerprintData']) ? wp_unslash($_POST['fingerprintData']) : '';
        $assignedPhoneNumber = isset($_POST['assignedPhoneNumber']) ? sanitize_text_field($_POST['assignedPhoneNumber']) : '';

        if ($event_type === 'call') {
            $phone = str_replace(['tel:', ' '], '', $url);  // Remove 'tel:' prefix and spaces
            $formattedPhone = $this->formatPhoneNumber($phone);  // Format the phone number correctly
            $url = $formattedPhone;  // Use the formatted phone number
        }

        $this->tracker->logLinkClick($url, $event_type, $message, $fingerprint, $fingerprintData, $assignedPhoneNumber);
        wp_die();  // End the execution for AJAX requests
    }

    public function deleteSelectedLogs() {
        global $wpdb;
    
        if (!current_user_can('manage_options') || !check_ajax_referer('delete_logs_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => "You don't have permission to do this task."]);
        }
    
        $log_ids = isset($_POST['log_ids']) ? array_map('intval', $_POST['log_ids']) : [];
    
        if (!empty($log_ids)) {
            $result = $this->model->deleteSelectedLogs($wpdb->prefix . 'wpmetricslab', $log_ids);
            if ($result !== false) {
                wp_send_json_success(['message' => 'Selected logs successfully deleted.']);
            } else {
                wp_send_json_error(['message' => 'Something went wrong.']);
            }
        } else {
            wp_send_json_error(['message' => 'Select logs to delete.']);
        }
    }

    public function deleteAllLogs() {
        global $wpdb;
    
        if (!current_user_can('manage_options') || !check_ajax_referer('delete_all_logs_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => "You don't have permission to do this task."]);
        }
    
        $result = $this->model->deleteAllLogs($wpdb->prefix . 'wpmetricslab');
        if ($result !== false) {
            wp_send_json_success(['message' => 'All logs successfully deleted.']);
        } else {
            wp_send_json_error(['message' => 'Something went wrong.']);
        }
    }    

    public function enqueue_public_scripts() {
        if (!is_admin()) {
            wp_enqueue_script('thumbmarkjs', 'https://cdn.jsdelivr.net/npm/@thumbmarkjs/thumbmarkjs/dist/thumbmark.umd.js', [], false, true);
            wp_enqueue_script('wpmetricslab-user-tracking', WPMETRICSLAB_URI . 'assets/js/user-tracking.js', ['jquery'], false, true);

            wp_localize_script('wpmetricslab-user-tracking', 'wpmetricslabLinkTracker', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'track_link_nonce' => wp_create_nonce('track_link_click_nonce')
            ]);
        }
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('wp-admin-scripts', WPMETRICSLAB_URI . 'assets/js/admin-scripts.js', ['jquery'], null, true);
    
        wp_localize_script('wp-admin-scripts', 'wpmetricslabDeleteLogs', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'delete_logs_nonce' => wp_create_nonce('delete_logs_nonce')
        ]);
    }
}
