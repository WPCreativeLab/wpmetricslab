<?php

namespace WPMetricsLab\Controllers;

use WPMetricsLab\Models\TrackerModel;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class TrackerController {

    private $model;

    public function __construct() {
        $this->model = new TrackerModel();
    }

    public function isAdminAjaxRequest() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'track_') === false) {
                return true;
            }
        }
        return false;
    }

    public function track($fingerprint, $fingerprintData, $currentUrl) {
        if ($this->isStaticResource() || $this->isBot() || $this->isAdminAjaxRequest()) {
            error_log('Skipping log: static resource, bot, or admin ajax request');
            return;
        }

        $user = wp_get_current_user();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $session_start = current_time('mysql');
        $current_url = !empty($currentUrl) ? $currentUrl : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $object_type = $this->determineObjectType($user_agent);

        $data = [
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
        ];

        $this->model->logActivity($data);
    }

    public function logLinkClick($url, $event_type, $message, $fingerprint, $fingerprintData) {
        $user = wp_get_current_user();
        $data = [
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
        ];

        $this->model->logLinkClick($data);
    }

    public function logPageView($fingerprint, $fingerprintData) {
        if ($this->isAdminAjaxRequest()) {
            return;
        }

        $user = wp_get_current_user();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $session_start = current_time('mysql');
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $data = [
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
        ];

        $this->model->logPageView($data);
    }

    private function determineObjectType($user_agent) {
        if ($this->isBot($user_agent)) {
            return 'system';
        }
        return 'user';
    }

    private function isStaticResource() {
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        return preg_match('/\.(jpg|jpeg|png|gif|css|js|map|html_gzip)$/i', $path);
    }

    private function isBot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $bot_signatures = [
            'google.com/bot.html',
            'Site24x7',
            'ahrefs.com/robot',
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
}
