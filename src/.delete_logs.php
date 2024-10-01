<?php
// require_once('wp-load.php');

if (!defined('ABSPATH')) {
    exit;
}

if (current_user_can('manage_options') && check_ajax_referer('delete_logs_action', 'nonce')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpmetricslab';

    if (!empty($_POST['selected_logs'])) {
        $log_ids = array_map('intval', $_POST['selected_logs']);
        $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
        $sql = "DELETE FROM {$table_name} WHERE id IN ($placeholders)";
        $wpdb->query($wpdb->prepare($sql, $log_ids));
        exit;
    }
} else {
    wp_die("You don't have permission to do this action.");
}