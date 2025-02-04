<?php

// Function to check WooCommerce order statuses and update 'overall_status' if any orders are paid
// Function to check WooCommerce order statuses and update 'overall_status' if any orders are paid
function woocrcall_check_paid_status() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Query for records where overall_status does not contain 'paid' and user_orders do not start with 'temp_'
    $records = $wpdb->get_results(
        "SELECT * FROM $table_name 
        WHERE overall_status NOT LIKE '%paid%' 
        AND user_orders NOT LIKE 'temp_%'"
    );

    // Load WooCommerce functions if not already loaded
    if (!function_exists('wc_get_order')) {
        include_once(ABSPATH . 'wp-content/plugins/woocommerce/includes/wc-order-functions.php');
    }

    // Loop through the records and check if any of their associated orders are paid
    foreach ($records as $record) {
        check_and_update_paid_status($record); // Call the checker function for each record
    }
}

// Helper function to check if any orders in a record are paid and update the status accordingly
function check_and_update_paid_status($record) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Extract the order IDs from the record
    $order_ids = explode(',', $record->user_orders);
    $is_paid = false;

    // Check each order's payment status
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id); // Get WooCommerce order
        if ($order && $order->is_paid()) {
            $is_paid = true;
            break; // Stop checking once we find a paid order
        }
    }

    // If any order is paid, update the 'overall_status' to the 'paid' equivalent
    if ($is_paid) {
        // Convert 'calling' and 'notReady' to their 'paid' versions
        $new_status = str_replace(
            ['calling', 'notReady'], 
            ['paid-calling', 'paid-notReady'], 
            $record->overall_status
        );

        // Update the status in the database if it's changed
        if ($new_status !== $record->overall_status) {
            $wpdb->update(
                $table_name,
                ['overall_status' => $new_status], // New status
                ['id' => $record->id],             // Where condition
                ['%s'],                            // Format for status
                ['%d']                             // Format for ID
            );
        }
    }
}



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

// Function to check for duplicate records based on phone number and paid status
function woocrcall_update_duplicated_orders_in_batch() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';
    $past_14_days = strtotime('-14 days');

    // First Query: Get all phone numbers with a 'paid-*' status within the last 14 days
    $paid_phone_numbers = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT user_phone 
            FROM $table_name 
            WHERE overall_status LIKE 'paid%%'
            AND retain_unix >= %d",
            $past_14_days
        )
    );

    // Check if we have any phone numbers to work with
    if (empty($paid_phone_numbers)) {
        return; // Exit if there are no paid phone numbers in the last 2 weeks
    }
    
    // Manually escape each phone number and build the IN clause
    $escaped_phone_numbers = array_map(function($phone) use ($wpdb) {
        return $wpdb->prepare('%s', $phone);
    }, $paid_phone_numbers);


    $in_clause = implode(',', $escaped_phone_numbers);


    $retain_ids_to_update = [];
    $results = [];

    // Second Query: Get all retain_ids for these phone numbers within the last 14 days that are not 'paid-*' or 'duplicated'
    $phone_number_chunks = array_chunk($paid_phone_numbers, 100); // Adjust chunk size as needed

    foreach ($phone_number_chunks as $chunk) {
        // Prepare the IN clause for the current chunk
        $escaped_numbers = array_map(function($phone) use ($wpdb) {
            return $wpdb->prepare('%s', $phone);
        }, $chunk);
        $in_clause = implode(',', $escaped_numbers);

        // Run the query for the current chunk of phone numbers
        $query = "
            SELECT DISTINCT retain_id 
            FROM $table_name 
            WHERE user_phone IN ($in_clause)
            AND overall_status != 'paid-ready'
            AND retain_unix >= %d
            
        ";
        
        $results = $wpdb->get_col($wpdb->prepare($query, $past_14_days));

        // Merge results with the main retain_ids_to_update array
        $retain_ids_to_update = array_merge($retain_ids_to_update, $results);
    }



    if (empty($retain_ids_to_update)) {
        return;
    }


    // Construct the IN clause directly if retain_ids are already safe integers
    $retain_placeholders = implode(',', $retain_ids_to_update);

    $update_query = "
        UPDATE $table_name 
        SET overall_status = 'duplicated'
        WHERE retain_id IN ($retain_placeholders)
        AND overall_status != 'duplicated'
    ";

    // Execute the update query
    $wpdb->query($update_query);

    // Check for errors
    if ($wpdb->last_error) {
        error_log( 'Error: ' . $wpdb->last_error);
    }
}




// Hook the function to WordPress cron
add_action('woocrcall_update_expired_statuses_hook', 'woocrcall_update_expired_statuses');



function woocrcall_cronjob_handler() {
    // Handle expired status updates
    woocrcall_update_expired_statuses();

    // Handle WooCommerce order paid status updates
    woocrcall_check_paid_status();

    // Handle CRM duplicated Records
    woocrcall_check_duplicate_orders();

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
