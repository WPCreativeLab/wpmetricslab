<?php

namespace WPMetricsLab;

use \Exception;

if (!defined('ABSPATH')) {
    exit;
}

class Tracker {

    public function isAdminAjaxRequest() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'track_') === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Logs user activity in the database.
     * @param string $fingerprint The user's unique identifier.
     * @param string $fingerprintData Detailed fingerprint data.
     * @param string $currentUrl The current URL.
     */
    public function track($fingerprint, $fingerprintData, $currentUrl) {
        if ($this->isStaticResource() || $this->isBot() || $this->isAdminAjaxRequest()) {
            error_log('Skipping log: static resource, bot, or admin ajax request');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $user = wp_get_current_user();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $session_start = current_time('mysql');
        $current_url = !empty($currentUrl) ? $currentUrl : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $object_type = $this->determineObjectType($user_agent); // Determine if it's user or system activity

        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => is_user_logged_in() ? $user->ID : 0,
                'user_name' => is_user_logged_in() ? $user->user_login : 'Anonymous',
                'user_role' => is_user_logged_in() ? implode(', ', $user->roles) : 'Guest',
                'object_type' => $object_type,
                'event_type' => 'visit',
                'message' => 'Page visited',
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'session_start' => $session_start,
                'current_url' => $current_url,
                'fingerprint' => $fingerprint,
                'fingerprint_data' => $fingerprintData
            ),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );

        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
        } else {
            error_log('Activity logged successfully');
        }
    }

    /**
     * Determines whether the activity is user-generated or system-generated.
     */
    private function determineObjectType($user_agent) {
        if ($this->isBot($user_agent)) {
            return 'system'; // Bots and other automated processes are considered system activity
        }
        return 'user'; // Default: user activity
    }

    /**
     * Checks if the current request is for a static resource.
     */
    private function isStaticResource() {
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        
        return preg_match('/\.(jpg|jpeg|png|gif|css|js|map|html_gzip)$/i', $path);
    }

    /**
     * Checks if the user agent contains a specific bot identifier string.
     */
    private function isBot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $bot_signatures = [
            'google.com/bot.html', // Google bot specific identifier
            'Site24x7',            // Site24x7 bot identifier
            'ahrefs.com/robot',     // Ahref bot
            'bingbot',
            'yandexbot',
            'facebook.com/externalhit_uatext.php',
            'semrush.com/bot.html',
            'google.com/adsbot.html',
            'bushbaby',
            'WhatsApp',
            'Java',
            'Google-InspectionTool',
            'python-request',
            'TelegramBot',
            'Java',
            'Linux Mozilla'
        ];
    
        foreach ($bot_signatures as $signature) {
            if (strpos($user_agent, $signature) !== false) {
                return true;
            }
        }
    
        return false;
    }

    public function logActivity($object_id, $action, $type) {
        global $wpdb;
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            error_log('No valid user for logging activity.');
            return;
        }
    
        $session_start = current_time('mysql');
        $current_url = admin_url('post.php?post=' . $object_id . '&action=edit');

        $result = $wpdb->insert(
            $wpdb->prefix . 'wpmetricslab',
            [
                'user_id' => $user->ID,
                'user_name' => $user->user_login,
                'user_role' => implode(', ', $user->roles),
                'object_id' => $object_id,
                'object_type' => $type,
                'event_type' => $action,
                'message' => ucfirst($action) . " " . $type,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'session_start' => $session_start,
                'current_url' => $current_url
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    
        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
        }
    }

    public function logLinkClick($url, $event_type, $message, $fingerprint, $fingerprintData) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $user = wp_get_current_user();

        $wpdb->insert(
            $table_name,
            [
                'user_id' => is_user_logged_in() ? $user->ID : 0,
                'user_name' => is_user_logged_in() ? $user->user_login : 'Anonymous',
                'user_role' => is_user_logged_in() ? implode(', ', $user->roles) : 'Guest',
                'object_type' => 'link',
                'event_type' => $event_type,
                'message' => $message,
                'current_url' => $url,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'session_start' => current_time('mysql'),
                'fingerprint' => $fingerprint,
                'fingerprint_data' => $fingerprintData
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    
        if ($wpdb->last_error) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
        }
    }

    public function logPageView($fingerprint, $fingerprintData) {

        if ($this->isAdminAjaxRequest()) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $user = wp_get_current_user();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $session_start = current_time('mysql');
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => is_user_logged_in() ? $user->ID : 0,
                'user_name' => is_user_logged_in() ? $user->user_login : 'Anonymous',
                'user_role' => is_user_logged_in() ? implode(', ', $user->roles) : 'Guest',
                'object_type' => 'page',
                'event_type' => 'visit',
                'message' => 'Page visited',
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'session_start' => $session_start,
                'current_url' => $current_url,
                'fingerprint' => $fingerprint,
                'fingerprint_data' => $fingerprintData
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('Failed to log activity: ' . $wpdb->last_error);
            throw new Exception('Failed to log activity: ' . $wpdb->last_error);
        } else {
            error_log('Activity logged successfully.');
        }
    }
}
