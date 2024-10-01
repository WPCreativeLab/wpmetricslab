<?php

namespace WPMetricsLab;

use \Exception;


if (!defined('ABSPATH')) {
    exit;
}

class ActivityLog {

    private $tracker;
    private $logged_posts = [];


    public function __construct() {
        $this->tracker = new Tracker();
        add_action('admin_menu', [$this, 'initializeAdminPage']);
        add_action('wp_login', [$this, 'trackUserActivity']);
    
        /* Initialize Public Scripts */

        add_action('wp_footer', [$this, 'enqueue_public_scripts']);
        
        /* Initialize Admin Scripts */

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    
        /* Initialize AJAX for tracking link clicks */

        add_action('wp_ajax_nopriv_track_link_click', [$this, 'trackLinkClick']);

        /* Initialize AJAX for logged in users */

        add_action('wp_ajax_track_link_click', [$this, 'trackLinkClick']);     

        add_action('wp_ajax_nopriv_delete_logs', [$this, 'deleteLogs']);
        add_action('wp_ajax_delete_logs', [$this, 'delete_logs_handler']);
    
        /* Watch WordPress specific actions */

        add_action('save_post', [$this, 'handlePostSave'], 10, 3);
        add_action('before_delete_post', [$this, 'handlePostDeletion']);
        add_action('wp_trash_post', [$this, 'handlePostTrashing']);
    
        /* Fingerprinting */
        add_action('wp_ajax_nopriv_track_page_view', [$this, 'trackUserActivity']);
        add_action('wp_ajax_track_page_view', [$this, 'trackUserActivity']);
    

        add_action('admin_notices', function() {
            if (isset($_GET['deleted']) && $_GET['deleted'] == 'true' && isset($_GET['message'])) {
                $message = sanitize_text_field(urldecode($_GET['message']));
                echo "<div class='updated'><p>{$message}</p></div>";
            }
        });
    }




    /* Load Public Scripts */

    public function enqueue_public_scripts() {
        if (!is_admin()) { // Check if we are not on the admin panel
            wp_enqueue_script('thumbmarkjs', 'https://cdn.jsdelivr.net/npm/@thumbmarkjs/thumbmarkjs/dist/thumbmark.umd.js', [], false, true);
            wp_enqueue_script('wpmetricslab-user-tracking', WPMETRICSLAB_URI . 'assets/js/user-tracking.js', ['jquery'], false, true);

            wp_localize_script('wpmetricslab-user-tracking', 'wpmetricslabLinkTracker', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'track_link_nonce' => wp_create_nonce('track_link_click_nonce')
            ]);
        }
    }
    
    /* Load Admin Scripts */

    public function enqueue_admin_scripts() {
        // Load admin-specific script if necessary
        wp_enqueue_script('wp-admin-scripts', WPMETRICSLAB_URI . 'assets/js/admin-scripts.js', ['jquery'], null, true);
    
        // Localization settings to ensure JavaScript can access necessary data
        wp_localize_script('wp-admin-scripts', 'wpmetricslabDeleteLogs', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'delete_logs_nonce' => wp_create_nonce('delete_logs_nonce')
        ]);

        /* IS THIS NEEDED? I don't want to track suspicious activities */
        wp_localize_script('wp-admin-scripts', 'wpmetricslabCheckSuspiciousActivities', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'check_suspicious_nonce' => wp_create_nonce('check_suspicious_nonce')
        ]);
    }





    /* Initialize Admin Page */

    public function initializeAdminPage() {
        $adminPage = new AdminPage();
        $adminPage->init();
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
    



    /* WORDPRESS SPECIFIC ACTIONS */

    public function handlePostSave($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || $post->post_type == 'revision') {
            return;
        }
        
        if ('trash' === get_post_status($post_id)) {
            return;
        }
    
        /* 
         * Here we check if the current execution of the save_post action is the first one.
         * When adding the save_post action with a priority of 10 and 3 parameters,
         * this must be 1 when it is the first execution of the save_post action with this identifier and priority.
         */

        if (did_action('save_post') > 1) {
            return;
        }
    
        $action = $update ? 'updated' : 'created';
        $this->tracker->logActivity($post_id, $action, $post->post_type);
    }

    public function handlePostDeletion($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
    
        if ('post' === $post->post_type || 'page' === $post->post_type) {
            $this->tracker->logActivity($post_id, 'deleted', $post->post_type);
        }
    }

    public function handlePostTrashing($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        if ('post' === $post->post_type || 'page' === $post->post_type) {
            $this->tracker->logActivity($post_id, 'trashed', $post->post_type);
        }
    }

    /* END WORDPRESS SPECIFIC ACTIONS */




    
    public function delete_logs_handler() {
        if (!current_user_can('manage_options') || !check_ajax_referer('delete_logs_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => "You don't have permission to do this task."]);
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $log_ids = isset($_POST['log_ids']) ? array_map('intval', $_POST['log_ids']) : [];
    
        if (!empty($log_ids)) {
            $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
            $sql = "DELETE FROM {$table_name} WHERE id IN ($placeholders)";
            $result = $wpdb->query($wpdb->prepare($sql, $log_ids));
    
            if ($result !== false) {
                wp_send_json_success(['message' => 'Selected logs successfully deleted.']);
            } else {
                wp_send_json_error(['message' => 'Something went wrong.']);
            }
        } else {
            wp_send_json_error(['message' => 'Select logs to delete.']);
        }
    }

    public function getLoggedUsers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
    
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

    public function getEventTypes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
    
        // Query the unique event types from the database
        $event_types = $wpdb->get_col("SELECT DISTINCT event_type FROM {$table_name} WHERE event_type IS NOT NULL");
    
        return $event_types;
    }

    /**
     * Fetches IP addresses and their occurrence counts from the database.
     * @return object[] The array of retrieved data objects.
     */
    public function getIPCounts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $sql = "SELECT ip_address, COUNT(*) as count FROM {$table_name} GROUP BY ip_address ORDER BY count DESC";
        return $wpdb->get_results($sql);
    }

    public function getPhoneNumbers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $sql = "SELECT DISTINCT current_url FROM {$table_name} WHERE event_type = 'call'";  // Assuming "call" event type indicates phone calls.
        $results = $wpdb->get_col($sql);
        $phone_numbers = array_map(function($url) {
            return $this->formatPhoneNumber($url);
        }, $results);
        return $phone_numbers;
    }

}
