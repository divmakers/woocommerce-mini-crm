<?php

// Function to update expired statuses based on exp_unix and status mappings
function woocrcall_update_expired_statuses() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';
    $current_time = time();

    // Status mappings for expired rows
    $status_updates = [
        'calling' => 'ready',
        'notReady' => 'ready',
        'paid-calling' => 'paid-ready',
        'paid-notReady' => 'paid-ready'
    ];

    // Build placeholders for the SQL query
    $placeholders = implode(',', array_fill(0, count($status_updates), '%s'));

    // Prepare the update query for expired records
    $sql = $wpdb->prepare(
        "UPDATE $table_name 
        SET overall_status = CASE 
            WHEN overall_status = 'calling' THEN 'ready'
            WHEN overall_status = 'notReady' THEN 'ready'
            WHEN overall_status = 'paid-calling' THEN 'paid-ready'
            WHEN overall_status = 'paid-notReady' THEN 'paid-ready'
        END
        WHERE exp_unix < %d 
        AND overall_status IN ($placeholders)",
        array_merge([$current_time], array_keys($status_updates))
    );

    // Execute the query
    $result = $wpdb->query($sql);

    if ($result === false) {
        error_log('Failed to update expired statuses: ' . $wpdb->last_error);
    } else {
        error_log('Updated ' . $result . ' rows with expired statuses.');
    }
}




// Hook the function to WordPress cron
add_action('woocrcall_update_expired_statuses_hook', 'woocrcall_update_expired_statuses');



function woocrcall_cronjob_handler() {
    // Handle expired status updates
    woocrcall_update_expired_statuses();

}


// Add custom cron schedule
function woocrcall_add_cron_interval($schedules) {
    $schedules['every_5minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display'  => esc_html__('Every Five Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'woocrcall_add_cron_interval');

// Schedule the cron job if not already scheduled
if (!wp_next_scheduled('woocrcall_update_expired_statuses_hook')) {
    wp_schedule_event(time(), 'every_5minutes', 'woocrcall_update_expired_statuses_hook');
}
