<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

echo "<div class='wrap'><h1>Settings</h1><br>";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_filter_settings') {
        // Logic for saving filters
        $permanentFilter = isset($_POST['wpmetricslab_permanent_filter']) ? 'on' : 'off';
        $filter_banned_users = isset($_POST['wpmetricslab_filter_banned_users']) ? 'on' : 'off'; // Adding a new setting
        update_option('wpmetricslab_permanent_filter', $permanentFilter);
        update_option('wpmetricslab_filter_banned_users', $filter_banned_users); // Saving the new setting
        echo '<div id="message" class="updated fade"><p>' . __('Settings saved.', 'wpmetricslab') . '</p></div>';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_all_logs' && wp_verify_nonce($_POST['delete_logs_nonce'], 'delete_logs')) {
        // Logic for deleting logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpmetricslab';
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        add_settings_error('wpmetricslab_settings', 'wpmetricslab_settings_message', 'All log files deleted.', 'updated');
        settings_errors('wpmetricslab_settings');
    }
}

// Retrieve the current setting
$isPermanentFilter = get_option('wpmetricslab_permanent_filter', 'off'); // Default value

// Form to save filters
echo '<form action="" method="post">';
echo '<input type="hidden" name="action" value="save_filter_settings">';
echo '<p><b>Always display filters:</b>&nbsp;&nbsp;<input type="checkbox" name="wpmetricslab_permanent_filter" ' . checked($isPermanentFilter, 'on', false) . ' /><br><i>Check this box if you want the filters to always be visible on the user interface.</i>';
echo '<p class="submit"><button type="submit" class="wpmetricslab-button wpmetricslab-button-normal wpmetricslab-button-info" /><i class="fa fa-floppy-disk"></i>&nbsp;&nbsp;Save</button></p>';
echo '</form>';

// Form to delete all logs
echo "<form id='settings-delete-logs-form' method='post' action='' onsubmit='return confirmDeleteAllLog();'>";
echo '<input type="hidden" name="action" value="delete_all_logs">';
wp_nonce_field('delete_logs', 'delete_logs_nonce');
echo "<button type='submit' class='wpmetricslab-button wpmetricslab-button-normal wpmetricslab-button-danger'><i class='fa fa-trash'></i>&nbsp;&nbsp;Delete All Log Files</button></form>";

// JavaScript for confirmation modal
?>
<script>
function confirmDeleteAllLog() {
    return confirm("Are you sure you want to delete all log files?");
}
</script>
