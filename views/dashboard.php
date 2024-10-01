<?php
if (!defined('ABSPATH')) {
    exit;
}

$activityLog = new WPMetricsLab\Controllers\ActivityLogController;

global $wpdb;

// Retrieve logged users
$logged_users = $activityLog->getLoggedUsers();
$has_anonymous = in_array(0, array_map(function($user) { return $user->ID; }, $logged_users));  // Check if there is a user with ID 0

// Retrieve available event types
$event_types = $activityLog->getEventTypes();

$ip_counts = $activityLog->getIPCounts();  // Retrieve IP addresses and their occurrence counts

echo '<div class="wrap"><h1>WPMetricsLab Dashboard hehehe</h1><br>';

echo '<button class="wpmetricslab-log-filter wpmetricslab-button wpmetricslab-button-normal wpmetricslab-button-info"><i class="fa fa-filter wpmetricslab-filter-icon"></i>&nbsp;&nbsp;<span class="wpmetricslab-filter-text">Open Filter</span></button>';

echo '<div class="wpmetricslab-filters" style="display:none;">';

echo '<form method="get" class="wpmetricslab-form">';

echo '<input type="hidden" name="page" value="wpmetricslab" />';

echo '<div>';
echo '<label for="user_filter">Users:</label>';
echo '<select name="user_filter">';
echo '<option value="">All Users</option>';
if ($has_anonymous) {
    echo '<option value="Anonymous"' . selected($user_filter, 'Anonymous', false) . '>Anonymous</option>';
}
foreach ($logged_users as $user) {
    if ($user->ID != 0) {
        echo '<option value="' . esc_attr($user->user_login) . '"' . selected($user_filter, $user->user_login, false) . '>' . esc_html($user->user_login) . '</option>';
    }
}
echo '</select>';
echo '</div>';


echo '<div>';
echo '<label for="event_type_filter">Events:</label>';
echo '<select name="event_type_filter">';
echo '<option value="">All Events</option>';
foreach ($event_types as $event_type) {
    echo '<option value="' . esc_attr($event_type) . '"' . selected($event_type_filter, $event_type, false) . '>' . esc_html($event_type) . '</option>';
}
echo '</select>';
echo '</div>';

// IP address filter
echo '<div>';
echo '<label for="name">IP Addresses:</label>';
echo '<select name="ip_filter">';
echo '<option value="">All IP Addresses</option>';
foreach ($ip_counts as $ip_count) {
    echo '<option value="' . esc_attr($ip_count->ip_address) . '"' . selected($ip_filter, $ip_count->ip_address, false) . '>(' . $ip_count->count . ') ' . esc_html($ip_count->ip_address) . '</option>';
}
echo '</select>';
echo '</div>';

// User Agent filter
$user_agents = $wpdb->get_results("SELECT user_agent, COUNT(*) as count FROM {$wpdb->prefix}wpmetricslab GROUP BY user_agent");
echo '<div>';
echo '<label for="name">User Agents:</label>';
echo '<select name="user_agent_filter">';
echo '<option value="">All User Agents</option>';
foreach ($user_agents as $item) {
    // Display the user agent with count in parentheses
    echo '<option value="' . esc_attr($item->user_agent) . '"' . selected($user_agent_filter, $item->user_agent, false) . '>' . '(' . esc_html($item->count) . ') ' . esc_html($item->user_agent) . '</option>';
}
echo '</select>';
echo '</div>';

// Date filter
echo '<div>';
echo '<label for="name">Date:</label>';
echo '<select name="date_filter">';
echo '<option value="all_time"' . selected($date_filter, 'all_time', false) . '>All Time</option>';
echo '<option value="today"' . selected($date_filter, 'today', false) . '>Today</option>';
echo '<option value="yesterday"' . selected($date_filter, 'yesterday', false) . '>Yesterday</option>';
echo '<option value="last_7_days"' . selected($date_filter, 'last_7_days', false) . '>Last 7 Days</option>';
echo '<option value="last_14_days"' . selected($date_filter, 'last_14_days', false) . '>Last 14 Days</option>';
echo '<option value="last_30_days"' . selected($date_filter, 'last_30_days', false) . '>Last 30 Days</option>';
echo '<option value="this_week"' . selected($date_filter, 'this_week', false) . '>This Week</option>';
echo '<option value="last_week"' . selected($date_filter, 'last_week', false) . '>Last Week</option>';
echo '<option value="this_month"' . selected($date_filter, 'this_month', false) . '>This Month</option>';
echo '<option value="last_month"' . selected($date_filter, 'last_month', false) . '>Last Month</option>';
echo '<option value="custom"' . selected($date_filter, 'custom', false) . '>Custom</option>';
echo '</select>';
echo '</div>';

// Custom date range input fields, if needed
echo '<div class="custom-date-fields" style="display: none;">';
echo '<label for="name">Start Date:</label>';
echo '<input type="date" name="start_date" value="' . esc_attr($start_date) . '" /> ';
echo '</div>';
echo '<div class="custom-date-fields" style="display: none;">';
echo '<label for="name">End Date:</label>';
echo '<input type="date" name="end_date" value="' . esc_attr($end_date) . '" />';
echo '</div>';

// Fingerprint ID filter
$fingerprint_ids = $wpdb->get_results("SELECT fingerprint, COUNT(*) as count FROM {$wpdb->prefix}wpmetricslab WHERE fingerprint IS NOT NULL AND fingerprint != '' GROUP BY fingerprint");
echo '<div>';
echo '<label for="fingerprint_filter">Fingerprint ID:</label>';
echo '<select name="fingerprint_filter">';
echo '<option value="">All Fingerprint IDs</option>';
foreach ($fingerprint_ids as $fingerprint_id) {
    echo '<option value="' . esc_attr($fingerprint_id->fingerprint) . '"' . selected($fingerprint_filter, $fingerprint_id->fingerprint, false) . '>' . '(' . esc_html($fingerprint_id->count) . ') ' . esc_html($fingerprint_id->fingerprint) . '</option>';
}
echo '</select>';
echo '</div>';

// Search field addition
echo '<div>';
echo '<label for="search_query">Search:</label>';
echo '<input type="text" name="search_query" value="' . esc_attr($search_query) . '" placeholder="Search...">';
echo '</div>';

echo '<button type="submit" value="Filter" class="wpmetricslab-button wpmetricslab-button-normal wpmetricslab-button-info" /><i class="fa fa-filter"></i>&nbsp;&nbsp;Filter</button>';
echo "<a class='wpmetricslab-button wpmetricslab-button-small wpmetricslab-button-danger-secondary' href='?page=wpmetricslab'><i class='fa fa-trash'></i>&nbsp;&nbsp;Clear Filters</a>";
echo '</form>';

echo '</div>'; // End of filter container

// Display active filters
$has_filters = !empty($_GET['user_filter']) || !empty($_GET['phone_filter']) || !empty($_GET['event_type_filter']) || !empty($_GET['ip_filter']) || !empty($_GET['date_filter']) || (!empty($_GET['start_date']) && !empty($_GET['end_date'])) || !empty($_GET['user_agent_filter']) || !empty($_GET['fingerprint_filter']) || !empty($_GET['search_query']);

function get_readable_date_filter($filter_key) {
    $filters = [
        'all_time' => 'All Time',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_14_days' => 'Last 14 Days',
        'last_30_days' => 'Last 30 Days',
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom' => 'Custom Date Range'  // Only use this if you're displaying both the start and end dates
    ];

    return $filters[$filter_key] ?? $filter_key;
}

if ($has_filters) {
    echo '<div class="wpmetricslab-active-filters">';
    echo '<span style="font-size: 1.1rem;">Active Filters</span>';
    if (!empty($_GET['user_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">User: ' . esc_html($_GET['user_filter']) . '</span>';
    }
    if (!empty($_GET['phone_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">Phone Number: ' . esc_html($_GET['phone_filter']) . '</span>';
    }
    if (!empty($_GET['event_type_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">Event: ' . esc_html($_GET['event_type_filter']) . '</span>';
    }
    if (!empty($_GET['ip_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">IP Address: ' . esc_html($_GET['ip_filter']) . '</span>';
    }
    if (!empty($_GET['user_agent_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">User Agent: ' . esc_html($_GET['user_agent_filter']) . '</span>';
    }
    if (!empty($_GET['date_filter'])) {
        if ($_GET['date_filter'] != 'all_time') {
            $readable_filter = get_readable_date_filter($_GET['date_filter']);
            echo '<span class="wpmetricslab-filter-tag">Date: ' . esc_html($readable_filter) . '</span>';
        }
    }
    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        echo '<span class="wpmetricslab-filter-tag">Date Range: ' . esc_html($_GET['start_date']) . ' - ' . esc_html($_GET['end_date']) . '</span>';
    }
    if (!empty($_GET['fingerprint_filter'])) {
        echo '<span class="wpmetricslab-filter-tag">Fingerprint ID: ' . esc_html($_GET['fingerprint_filter']) . '</span>';
    }
    if (!empty($_GET['search_query'])) {
        echo '<span class="wpmetricslab-filter-tag">Search: ' . esc_html($_GET['search_query']) . '</span>';
    }  
    echo "<div class='delete-filters'><a class='wpmetricslab-button wpmetricslab-button-small wpmetricslab-button-danger-secondary' href='?page=wpmetricslab'><i class='fa fa-trash'></i>&nbsp;&nbsp;Clear Filters</a></div>";
    echo '</div>';
}

echo '<div class="wpmetricslab-controls-container">';
    echo '<div class="wpmetricslab-buttons-container">';
        echo '<span style="font-size: 1.1rem;">Log Count: ' . $total_logs . '</span><br><br>';
    echo '</div>'; // wpmetricslab-buttons-container closing tag

    echo '<div class="wpmetricslab-pagination-container">';
        // Display pagination links in Dashboard.php
        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    echo '</div>'; // wpmetricslab-pagination-container closing tag
echo '</div>'; // wpmetricslab-controls-container closing tag

echo '<form id="delete-logs-form" method="post" action="admin.php?page=wpmetricslab">';

// Current user IP address
$current_user_ip = $_SERVER['REMOTE_ADDR'];

// Display table
echo '<table class="wp-list-table widefat fixed striped wpmetricslab-activity-log-table">';
echo '
<thead>
    <tr>
        <th class="checkbox"><input type="checkbox" id="select_all"/></th>
        <th class="id">ID</th>
        <!--<th class="activity">Activity</th>-->
        <th>Date</th>
        <th>User</th>
        <th>IP Address</th>
        <th class="user-agent">User Agent</th>
        <th class="event-type">Event Type</th>
        <th>Message</th>
        <th class="url">URL / Phone Number</th>
    </tr>
</thead>
';

echo '<tbody>';

/**
 * @var array|object $logs Array or object of logged data.
 */

foreach ($logs as $log) {
    $debugInfo = '';

    // Copy of the current GET parameters
    $current_query_params = $_GET;
    
    // Update the IP address filter parameter if needed
    $current_query_params_for_ip = $_GET;
    $current_query_params_for_ip['ip_filter'] = $log->ip_address;
    $ip_filter_url = add_query_arg($current_query_params_for_ip, admin_url('admin.php?page=wpmetricslab'));
    
    // Update the User Agent filter parameter if needed
    $current_query_params_for_user_agent = $_GET;
    $current_query_params['user_agent_filter'] = $log->user_agent;
    $current_query_params_for_user_agent['user_agent_filter'] = $log->user_agent;
    $user_agent_filter_url = add_query_arg($current_query_params_for_user_agent, admin_url('admin.php?page=wpmetricslab'));

    // Create Fingerprint ID filter link
    $current_query_params_for_fingerprint = $_GET;
    $current_query_params_for_fingerprint['fingerprint_filter'] = $log->fingerprint;
    $fingerprint_filter_url = add_query_arg($current_query_params_for_fingerprint, admin_url('admin.php?wpmetricslab'));
    echo '<tr>';
    echo '<td data-label="Selection:"><input type="checkbox" class="log_checkbox" name="selected_logs[]" value="' . esc_attr($log->id) . '"/></td>';
    echo '<td data-label="ID:">' . esc_html($log->id) . '</td>';
    echo '<td data-label="Date:">' . esc_html($log->session_start) . '</td>';
    echo '<td data-label="User:">' . esc_html($log->user_name) . '<br><small>(' . esc_html($log->user_role) . ')</small><br>';
    if (!empty($log->fingerprint)) {
        echo 'Fingerprint ID: ' . esc_html($log->fingerprint);
        echo ' <a href="#" class="info-icon" data-fingerprint="' . esc_attr($log->fingerprint_data) . '"><i class="fa fa-info-circle"></i></a><br>';
        echo '<a href="' . esc_url($fingerprint_filter_url) . '">Filter by this Fingerprint ID</a><br>';
    }
    echo '</td>';

    echo '<td data-label="IP Address:">' . esc_attr($log->ip_address) . ($log->user_name === 'Anonymous' && $log->ip_address === $current_user_ip ? ' <br><small>(You)</small>' : '') . '<br>';
    echo '<a href="' . esc_url($ip_filter_url) . '">Filter by IP Address</a><br><br><a href="https://whatismyipaddress.com/ip/' . esc_attr($log->ip_address) . '" target="_blank">More Info (What Is My IP Address)</a>';
    echo '</td>';
    echo '<td data-label="User Agent:">' . esc_html($log->user_agent) . '<br>';
    echo '<a href="' . esc_url($user_agent_filter_url) . '">Filter by User Agent</a>';
    echo '</td>';

    echo '<td data-label="Event Type:">' . esc_html($log->event_type) . '</td>';
    echo '<td data-label="Message:">' . esc_html($log->message) . '</td>';
    echo '<td data-label="URL:">' . esc_html($log->current_url) . '<br>';
    if (!empty($log->phone_number)) {
        echo 'Phone Number: ' . esc_html($log->phone_number);
    }
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table><br>';

/* Fingerprint Modal */

echo'
<div id="wpmetricslabModal" class="wpmetricslab-modal">
    <div class="wpmetricslab-modal-content">
        <span class="wpmetricslab-close">&times;</span>
        <div id="wpmetricslabModalContent"></div>
    </div>
</div>';

echo '<div class="wpmetricslab-controls-container">';
    echo '<div class="wpmetricslab-buttons-container">';
        echo wp_nonce_field('delete_logs_action', 'delete_logs_nonce');
        echo '<input type="hidden" name="user_filter" value="' . esc_attr($user_filter) . '">';
        echo '<input type="hidden" name="phone_filter" value="' . esc_attr($phone_filter) . '">';
        echo '<input type="hidden" name="event_type_filter" value="' . esc_attr($event_type_filter) . '">';
        echo '<input type="hidden" name="ip_filter" value="' . esc_attr($ip_filter) . '">';
        echo '<input type="hidden" name="date_filter" value="' . esc_attr($date_filter) . '">';
        echo '<input type="hidden" name="start_date" value="' . esc_attr($start_date) . '">';
        echo '<input type="hidden" name="end_date" value="' . esc_attr($end_date) . '">';
        echo '<input type="hidden" name="user_agent_filter" value="' . esc_attr($user_agent_filter) . '">';
        echo '<button type="submit" value="Delete Selected Records" class="wpmetricslab-button wpmetricslab-button-small wpmetricslab-button-danger"/><i class="fa fa-trash"></i>&nbsp;&nbsp;Delete Selected Records</button>';
        echo '</form>';

        echo '<form method="get" action="admin.php">';
        echo '<input type="hidden" name="page" value="wpmetricslab" />';
        echo '<input type="hidden" name="action" value="export_csv" />';
        // Forward all filter conditions
        echo '<input type="hidden" name="user_filter" value="' . esc_attr($user_filter) . '" />';
        echo '<input type="hidden" name="phone_filter" value="' . esc_attr($phone_filter) . '" />';
        echo '<input type="hidden" name="event_type_filter" value="' . esc_attr($event_type_filter) . '" />';
        echo '<input type="hidden" name="ip_filter" value="' . esc_attr($ip_filter) . '" />';
        echo '<input type="hidden" name="date_filter" value="' . esc_attr($date_filter) . '" />';
        echo '<input type="hidden" name="start_date" value="' . esc_attr($start_date) . '" />';
        echo '<input type="hidden" name="end_date" value="' . esc_attr($end_date) . '" />';
        echo '<input type="hidden" name="user_agent_filter" value="' . esc_attr($user_agent_filter) . '" />';
        echo '<input type="hidden" name="action" value="export_csv" />';
        echo '<button type="submit" name="export_csv" value="Export to CSV" class="wpmetricslab-button wpmetricslab-button-small wpmetricslab-button-info" /><i class="fa fa-download"></i>&nbsp;&nbsp;Export to CSV</button>';
        echo '</form>';

        echo '</div>'; // wpmetricslab-buttons-container closing tag
        
        echo '<div class="wpmetricslab-pagination-container">';
        // Display pagination links in Dashboard.php
        if ($page_links) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
        }
    echo '</div>'; // wpmetricslab-pagination-container closing tag
echo '</div>'; // wpmetricslab-controls-container closing tag

echo '</div>';  // wpmetricslab Activity Log Wrap End

// Hide or display the filter button based on settings
$permanent_filter = get_option('wpmetricslab_permanent_filter', 'off');

if ($permanent_filter === 'on') {
    // Filter always visible
    echo '<script>document.querySelector(".wpmetricslab-filters").style.display = "block";</script>';
    echo '<style>.wpmetricslab-log-filter { display: none; }</style>';  // Hide the open/close button
} else {
    // Filter behavior according to normal logic
}

?>
<script>
document.getElementById('select_all').onclick = function() {
    var checkboxes = document.querySelectorAll('.log_checkbox'); // Check if elements with class 'log_checkbox' are present in the rows
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked; // Set the state of the other checkboxes
    }
}

// JavaScript for confirmation modal window
function confirmDelete() {
    return confirm("Are you sure you want to delete the selected logs?");
}

document.addEventListener('DOMContentLoaded', function() {
    var dateFilterSelect = document.querySelector('select[name="date_filter"]');
    var customDateFields = document.querySelectorAll('.custom-date-fields');
    var toggleButton = document.querySelector('.wpmetricslab-log-filter');
    var filtersDiv = document.querySelector('.wpmetricslab-filters');
    var icon = toggleButton.querySelector('.wpmetricslab-filter-icon');
    var filterText = toggleButton.querySelector('.wpmetricslab-filter-text'); // Add text element

    toggleButton.addEventListener('click', function() {
        if (filtersDiv.style.display === 'none') {
            filtersDiv.style.display = 'block';
            icon.className = 'fa fa-filter-circle-xmark wpmetricslab-filter-icon'; // Change the icon to "up" position
            filterText.textContent = 'Close Filter'; // Change only the text
        } else {
            filtersDiv.style.display = 'none';
            icon.className = 'fa fa-filter wpmetricslab-filter-icon'; // Change the icon to "down" position
            filterText.textContent = 'Open Filter'; // Change only the text
        }
    });

    function toggleCustomDateFields(display) {
        customDateFields.forEach(function(field) {
            field.style.display = display;
        });
    }

    dateFilterSelect.addEventListener('change', function() {
        toggleCustomDateFields(dateFilterSelect.value === 'custom' ? 'block' : 'none');
    });

    // Display fields if "custom" is selected when the page loads
    if (dateFilterSelect.value === 'custom') {
        toggleCustomDateFields('block');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('wpmetricslabModal');
    var span = document.getElementsByClassName('wpmetricslab-close')[0];
    
    span.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    var infoIcons = document.querySelectorAll('.info-icon');
    infoIcons.forEach(function(icon) {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            var fingerprintData = JSON.parse(this.getAttribute('data-fingerprint'));
            var modalContent = document.getElementById('wpmetricslabModalContent');
            modalContent.innerHTML = generateFingerprintHTML(fingerprintData);
            modal.style.display = 'flex';
        });
    });

    function generateFingerprintHTML(data) {
        var html = '<h2>Fingerprint Data</h2>';
        html += '<pre>' + formatJSON(data) + '</pre>';
        return html;
    }

    function formatJSON(json) {
        return JSON.stringify(json, null, 2)
            .replace(/["{}]/g, function(match) {
                return match === '{' ? '{\n' : (match === '}' ? '\n}' : match);
            })
            .replace(/,/g, ',\n')
            .replace(/\n\s*\n/g, '\n')
            .replace(/":/g, '": ')
            .replace(/"(\w+)"\s*:/g, function(match, p1) {
                return `"${p1}":`;
            });
    }
});

</script>