<?php

namespace WPMetricsLab\Controllers;

use WPMetricsLab\Models\TrackerModel;

if (!defined('ABSPATH')) {
    exit;
}

class WordPressActivityController {

    private $trackerModel;

    public function __construct() {
        $this->trackerModel = new TrackerModel();
        $this->initializeWordPressHooks();
    }

    private function initializeWordPressHooks() {
        // Felhasználói események figyelése
        add_action('user_register', [$this, 'logUserRegistration']);
        add_action('profile_update', [$this, 'logProfileUpdate'], 10, 2);
        add_action('delete_user', [$this, 'logUserDeletion']);

        // Bejelentkezési események figyelése
        add_action('wp_login', [$this, 'logUserLogin'], 10, 2);
        add_action('wp_logout', [$this, 'logUserLogout']);
        add_action('wp_login_failed', [$this, 'logFailedLogin']);

        // Poszt és oldalak változásainak figyelése
        add_action('save_post', [$this, 'logPostSave'], 10, 3);
        add_action('before_delete_post', [$this, 'logPostDeletion']);
        add_action('wp_trash_post', [$this, 'logPostTrashing']);

        // Plugin és téma változások
        add_action('activated_plugin', [$this, 'logPluginActivation'], 10, 2);
        add_action('deactivated_plugin', [$this, 'logPluginDeactivation'], 10, 2);
    }

    // Metódusok az egyes WordPress események kezelésére

    public function logUserRegistration($user_id) {
        $user_info = get_userdata($user_id);
        $this->trackerModel->logActivity([
            'event_type' => 'user_register',
            'message' => 'New user registered: ' . $user_info->user_login,
            'user_id' => $user_id,
        ]);
    }

    public function logProfileUpdate($user_id, $old_user_data) {
        $user_info = get_userdata($user_id);
        $this->trackerModel->logActivity([
            'event_type' => 'profile_update',
            'message' => 'User profile updated: ' . $user_info->user_login,
            'user_id' => $user_id,
        ]);
    }

    public function logUserDeletion($user_id) {
        $user_info = get_userdata($user_id);
        $this->trackerModel->logActivity([
            'event_type' => 'user_deletion',
            'message' => 'User deleted: ' . $user_info->user_login,
            'user_id' => $user_id,
        ]);
    }

    public function logUserLogin($user_login, $user) {
        $this->trackerModel->logActivity([
            'event_type' => 'user_login',
            'message' => 'User logged in: ' . $user_login,
            'user_id' => $user->ID,
        ]);
    }

    public function logUserLogout() {
        $current_user = wp_get_current_user();
        $this->trackerModel->logActivity([
            'event_type' => 'user_logout',
            'message' => 'User logged out: ' . $current_user->user_login,
            'user_id' => $current_user->ID,
        ]);
    }

    public function logFailedLogin($username) {
        $this->trackerModel->logActivity([
            'event_type' => 'failed_login',
            'message' => 'Failed login attempt: ' . $username,
            'user_id' => 0,  // Unknown user
        ]);
    }

    public function logPostSave($post_id, $post, $update) {
        $action = $update ? 'updated' : 'created';
        $this->trackerModel->logActivity([
            'event_type' => 'post_' . $action,
            'message' => 'Post ' . $action . ': ' . $post->post_title,
            'user_id' => get_current_user_id(),
            'object_id' => $post_id,
        ]);
    }

    public function logPostDeletion($post_id) {
        $post = get_post($post_id);
        $this->trackerModel->logActivity([
            'event_type' => 'post_deleted',
            'message' => 'Post deleted: ' . $post->post_title,
            'user_id' => get_current_user_id(),
            'object_id' => $post_id,
        ]);
    }

    public function logPostTrashing($post_id) {
        $post = get_post($post_id);
        $this->trackerModel->logActivity([
            'event_type' => 'post_trashed',
            'message' => 'Post trashed: ' . $post->post_title,
            'user_id' => get_current_user_id(),
            'object_id' => $post_id,
        ]);
    }

    public function logPluginActivation($plugin, $network_wide) {
        $this->trackerModel->logActivity([
            'event_type' => 'plugin_activation',
            'message' => 'Plugin activated: ' . $plugin,
            'user_id' => get_current_user_id(),
        ]);
    }

    public function logPluginDeactivation($plugin, $network_wide) {
        $this->trackerModel->logActivity([
            'event_type' => 'plugin_deactivation',
            'message' => 'Plugin deactivated: ' . $plugin,
            'user_id' => get_current_user_id(),
        ]);
    }

/*     public function handlePostSave($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || $post->post_type == 'revision') {
            return;
        }

        if ('trash' === get_post_status($post_id)) {
            return;
        }

        if (did_action('save_post') > 1) {
            return;
        }

        $action = $update ? 'updated' : 'created';
        $this->trackerModel->logActivity($post_id, $action, $post->post_type);
    }

    public function handlePostDeletion($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        if ('post' === $post->post_type || 'page' === $post->post_type) {
            $this->trackerModel->logActivity($post_id, 'deleted', $post->post_type);
        }
    }

    public function handlePostTrashing($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        if ('post' === $post->post_type || 'page' === $post->post_type) {
            $this->trackerModel->logActivity($post_id, 'trashed', $post->post_type);
        }
    } */
}
