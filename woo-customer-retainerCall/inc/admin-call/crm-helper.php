<?php

function woocr_crm_rowStatChange($row_id, $new_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Attempt to LockUp the row
    $result = $wpdb->update(
        $table_name,
        array('overall_status' => $new_status),
        array('retain_id' => $row_id),
        array('%s', '%d'),
        array('%d')
    );

    // Check for errors
    if ($result === false) {
        // Log the last error from the database
        error_log("Failed to update row. Error: " . $wpdb->last_error);
        return false;
    }

    // Return true if the update was successful
    return true;
}



function woocr_crm_rowUnlock() {

    check_ajax_referer('woocr_nonce', 'nonce');
    if ($_POST['record_id']) {
        $record_id = intval($_POST['record_id']);
    }else{
        wp_send_json_error('ROW NOT FOUND');
        return;
    }

    $record_current_status = woocr_call_checkRowStatus($record_id);
    switch ($record_current_status) {
        case 'notReady';
            $record_new_status = 'ready';
            break;
        case 'paid-notReady';
            $record_new_status = 'paid-ready';
            break;
        case 'calling';
            $record_new_status = 'ready';
            break;
        case 'paid-calling';
            $record_new_status = 'paid-ready';
            break;
        case 'end';
            $record_new_status = 'ready';
            break;
        case 'paid-end';
            $record_new_status = 'paid-ready';
            break;
        default:
            wp_send_json_error([
                'message' => "Call expert not changed | $record_current_status",
                'record_status' => $record_current_status
            ]);
            return;
            break;
    }

    $row_update_result = woocr_call_updateRowStatus($record_id, $record_new_status);
    if (!$row_update_result) {
        wp_send_json_error(['message' => "Failed To Lock The Row"]);
        return;
    }else{
        wp_send_json_success(['message' => "Row Unlocked Successfully"]);
    }
}



// Handle the AJAX request
function woocr_send_sms_func() {
    // Check the nonce and required POST parameters
    if (!isset($_POST['nonce'], $_POST['crm_rowID'], $_POST['crm_phase']) || !wp_verify_nonce($_POST['nonce'], 'woocr_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce or missing data']);
        return;
    }

    // Sanitize and retrieve POST parameters
    $sms_text = sanitize_text_field($_POST['smsText']);
    $record_id = sanitize_text_field($_POST['crm_rowID']);
    $phase = sanitize_text_field($_POST['crm_phase']);
    $user_name = sanitize_text_field($_POST['crm_userName']);

    if (strpos($sms_text, '%name%') !== false) {
        $sms_text = str_replace('%name%', $user_name, $sms_text);
    }

    // Retrieve phase data
    $phase_data = woocr_get_phase_data($record_id, $phase);

    // Check if phase data is valid and if the phase is not 'done'
    if ($phase_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wooc_retain_call';

        // Map phase_id to the corresponding database column
        $phase_column_map = [
            'phaseI'   => 'lvl1',
            'phaseII'  => 'lvl2',
            'phaseIII' => 'lvl3',
            'phaseIV'  => 'lvl4',
            'phaseV'   => 'lvl5'
        ];

        // Check if the phase is valid
        if (!array_key_exists($phase, $phase_column_map)) {
            wp_send_json_error(['message' => 'Invalid phase ID']);
            return;
        }

        // Get the corresponding column name for the phase_id
        $phase_column = $phase_column_map[$phase];

        // Retrieve the user's phone number
        $user_phone = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_phone FROM $table_name WHERE retain_id = %d",
                $record_id
            )
        );

        if ($user_phone === null) {
            wp_send_json_error(['message' => 'No user_phone found for the given record']);
            return;
        }

        // Retrieve the current JSON data from the database
        $current_data = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $phase_column FROM $table_name WHERE retain_id = %d",
                $record_id
            )
        );

        if ($current_data === null) {
            wp_send_json_error(['message' => 'No data found for the given record']);
            return;
        }

        // Decode the current JSON data
        $current_phase_data = json_decode($current_data, true);

        if ($current_phase_data === null) {
            wp_send_json_error(['message' => 'Failed to decode JSON data']);
            return;
        }

        // Update the specific parts of the JSON data
        $current_time = time(); // Gets the current time in MySQL format
        $current_phase_data['status']['phaseStatus'] = 'processing'; // Ensure phaseStart is updated correctly
        $current_phase_data['timing']['smsSent'] = $current_time;
        $current_phase_data['data']['smsText'] = $sms_text;

        // Encode the updated phase data back to JSON
        $updated_phase_data = json_encode($current_phase_data);

        if ($updated_phase_data === false) {
            wp_send_json_error(['message' => 'Failed to encode JSON data']);
            return;
        }

        // Send SMS using user_phone
        if (woocr_do_send_melipayamak($sms_text, $user_phone)) {
            // Update the database
            $wpdb->update(
                $table_name,
                [$phase_column => $updated_phase_data],
                ['retain_id' => $record_id],
                ['%s'],
                ['%d']
            );
            // Send success response
            wp_send_json_success([
                'message' => "SMS sent to user",
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Error: SMS not sent, phase not updated',
                'alert' => 'Error: SMS not sent, phase not updated',
            ]);
        }
    } else {
        // If phase status is 'done' or SMS already sent
        wp_send_json_error(
            [
                'message' => 'Failed to Send SMS',
                'alert' => 'Error: SMS not sent, invalid phase',
            ]);
    }
}




// Pattern controller Base
// Fetch all patterns and add user full name:
function fetch_patterns() {
    global $wpdb;
    $table4_name = $wpdb->prefix . 'wooc_retain_call_smspatterns';

    // Fetch all records from the patterns table
    $results = $wpdb->get_results("SELECT * FROM $table4_name", ARRAY_A);

    // Loop through each result and add the user's full name
    foreach ($results as &$result) {
        $result['user_fullName'] = woocr_call_get_userName($result['creditor_id']);
    }

    // Send the results as a JSON response
    wp_send_json($results);
}

function get_patterns(){
    global $wpdb;
    $table4_name = $wpdb->prefix . 'wooc_retain_call_smspatterns';
    // Fetch all records from the patterns table
    $results = $wpdb->get_results("SELECT * FROM $table4_name", ARRAY_A);

    // No Need To -> // Loop through each result and add the user's full name
    // foreach ($results as &$result) {
    //     $result['user_fullName'] = woocr_call_get_userName($result['creditor_id']);
    // }

    // Send the results as an array response
    return $results;
}

// Save a pattern (add or update)
function save_pattern() {
    global $wpdb;
    $table4_name = $wpdb->prefix . 'wooc_retain_call_smspatterns';

    // Check nonce for security
    check_ajax_referer('woocr_nonce', 'nonce');

    // Sanitize input data
    $pattern_id = isset($_POST['pattern_id']) ? intval($_POST['pattern_id']) : 0;
    $pattern_name = sanitize_text_field($_POST['pattern_name']);
    $creator_id = intval($_POST['creator_id']);
    $pattern_text = sanitize_textarea_field($_POST['pattern_text']);

    // Prepare data for database operation
    $data = [
        'pattern_name' => $pattern_name,
        'creator_id' => $creator_id,
        'pattern_text' => $pattern_text,
    ];

    $where = ['pattern_id' => $pattern_id];

    if ($pattern_id) {
        // If pattern_id is provided, update existing record
        $updated = $wpdb->update($table4_name, $data, $where);

        if ($updated !== false) {
            wp_send_json(['success' => true, 'message' => 'Pattern updated successfully.']);
        } else {
            wp_send_json(['success' => false, 'message' => 'Failed to update pattern.']);
        }
    } else {
        // If no pattern_id, insert new record
        $inserted = $wpdb->insert($table4_name, $data);

        if ($inserted) {
            wp_send_json(['success' => true, 'message' => 'Pattern saved successfully.']);
        } else {
            wp_send_json(['success' => false, 'message' => 'Failed to save pattern.']);
        }
    }
}


// Delete a pattern
function delete_pattern() {
    global $wpdb;
    $table4_name = $wpdb->prefix . 'wooc_retain_call_smspatterns';
    $pattern_id = isset($_POST['pattern_id']) ? intval($_POST['pattern_id']) : null;

    if ($pattern_id) {
        $wpdb->delete($table4_name, ['pattern_id' => $pattern_id]);
    }

    wp_send_json(['success' => true]);
}

// End Of Pattern Controll











// Start of Main Job Handling for Call Retain Jobs
// Hook for handling AJAX requests


// AJAX handler function
function fetch_crm_records() {
    // Check nonce for security
    check_ajax_referer('woocr_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Get pagination parameters
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;

    // Initialize WHERE clause
    $where_clauses = array('1=1');
    $query_args = array();

    // Date range filter
    if (!empty($_POST['dateRange'])) {
        $range = $_POST['dateRange'];
        $dates = explode(',', $range);
        if (count($dates) == 2) {
            $startDate = intval($dates[0]);
            $endDate = intval($dates[1]) + 86400; // Add one day to include the end date
            $where_clauses[] = 'retain_unix BETWEEN %d AND %d';
            $query_args[] = $startDate;
            $query_args[] = $endDate;
        }
    }

    // // User ID filter
    // if (!empty($_POST['userID'])) {
    //     $user_id = intval($_POST['userID']);
    //     $where_clauses[] = 'user_id = %d';
    //     $query_args[] = $user_id;
    // }

    // Order ID filter (only if provided and not empty)
    if (!empty($_POST['orderID'])) {
        $order_id = intval($_POST['orderID']);
        $where_clauses[] = 'JSON_CONTAINS(user_orders, CAST(%d AS JSON), "$")';
        $query_args[] = $order_id;
    }

    // Customer Name filter (only if provided and not empty)
    if (!empty($customer_name = $_POST['customerName'])) {
        $customer_name = sanitize_text_field(urldecode(str_replace(' ', '', $customer_name)));
        $customer_name_escaped = '%' . $wpdb->esc_like($customer_name) . '%';
        $search_words = preg_split('/\s+/', $customer_name, -1, PREG_SPLIT_NO_EMPTY);
        $name_where_clauses = array();
        $name_where_clauses[] = 'REPLACE(user_name, " ", "") LIKE %s'; // Remove spaces and search
        $query_args[] = '%' . $wpdb->esc_like(str_replace(' ', '', $customer_name)) . '%';
        
        // 2. Add a clause for the individual words search (if there are spaces)
        if (!empty($search_words)) {
            foreach ($search_words as $word) {
                $name_where_clauses[] = 'user_name LIKE %s';
                $query_args[] = '%' . $wpdb->esc_like($word) . '%';
            }
        }
    
        // Combine the clauses using OR to match concatenated or spaced versions
        $where_clauses[] = '(' . implode(' OR ', $name_where_clauses) . ')';
    }

    // Status filter (only if provided and not 'all')
    if (!empty($_POST['status'])) {
        $status = sanitize_text_field($_POST['status']);
        switch ($status) {
            case 'all':
                // No additional WHERE clause needed
                break;
            case 'paid':
                $where_clauses[] = 'overall_status IN (%s, %s, %s, %s)';
                $query_args[] = 'paid-ready';
                $query_args[] = 'paid-notReady';
                $query_args[] = 'paid-calling';
                $query_args[] = 'paid-end';
                break;
            case 'notPaid':
                $where_clauses[] = 'overall_status IN (%s, %s, %s, %s)';
                $query_args[] = 'notReady';
                $query_args[] = 'ready';
                $query_args[] = 'calling';
                $query_args[] = 'end';
                break;
            default:
                // For any other specific status
                $where_clauses[] = 'overall_status = %s';
                $query_args[] = $status;
                break;
        }
    }

    // MineOnly and NewOnly filters
    $mine_only = isset($_POST['mineOnly']) && $_POST['mineOnly'] === 'true';
    $new_only = isset($_POST['newOnly']) && $_POST['newOnly'] === 'true';
    $current_user_id = get_current_user_id();

    if ($mine_only) {
        $where_clauses[] = '(creator_id = %d OR credit_holder = %d OR last_actioner = %d)';
        $query_args[] = $current_user_id;
        $query_args[] = $current_user_id;
        $query_args[] = $current_user_id;
    } elseif ($new_only) {
        $where_clauses[] = '(credit_holder IS NULL OR credit_holder = 0)';
    }

    // Combine all WHERE clauses
    $where_clause = implode(' AND ', $where_clauses);

    // Prepare the final WHERE clause
    $prepared_where_clause = $wpdb->prepare($where_clause, $query_args);

    // Query the database
    $query = $wpdb->prepare("
        SELECT
            retain_id       AS id,
            retain_unix     AS date,
            creator_id      AS creatorId,
            credit_holder   AS creditHolder,
            user_name       AS userName,
            user_id         AS userId,
            user_orders     AS orders,
            user_phone      AS userPhone,
            overall_status  AS status,
            source          AS source,
            exp_unix        AS lockExp,
            lvl1            AS phaseI,
            lvl2            AS phaseII,
            lvl3            AS phaseIII,
            lvl4            AS phaseIV,
            lvl5            AS phaseV,
            meta            AS meta
        FROM $table_name
        WHERE $prepared_where_clause
        ORDER BY retain_unix DESC
        LIMIT %d, %d
    ", $offset, $limit);

    // Counter for total records
    $total_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) AS total_count
        FROM $table_name
        WHERE $where_clause
    ", $query_args));

    // Fetch the results
    $results = $wpdb->get_results($query, ARRAY_A);
    $current_time = time();

    foreach ($results as &$result) {
        // Decode JSON fields
        $result['phaseI']   = json_decode($result['phaseI'], true);
        $result['phaseII']  = json_decode($result['phaseII'], true);
        $result['phaseIII'] = json_decode($result['phaseIII'], true);
        $result['phaseIV']  = json_decode($result['phaseIV'], true);
        $result['phaseV']   = json_decode($result['phaseV'], true);

        // Handle lockExp
        if (isset($result['lockExp']) && is_numeric($result['lockExp'])) {
            if ($result['lockExp'] > $current_time) {
                $result['lockExp'] = human_time_diff($current_time, $result['lockExp']) . ' آینده';
            } else {
                unset($result['lockExp']);
            }
        } else {
            unset($result['lockExp']);
        }

        // Handle phases timing
        $phases = ['phaseI', 'phaseII', 'phaseIII', 'phaseIV', 'phaseV'];
        foreach ($phases as $phase) {
            if (isset($result[$phase]['timing']['phaseEnd']) && $result[$phase]['timing']['phaseEnd'] != '0') {
                $phase_end_timestamp = $result[$phase]['timing']['phaseEnd'];
                $result[$phase]['timing']['timeDiff'] = human_time_diff($phase_end_timestamp, $current_time) . ' پیش';
            }
        }

        // Set creditHolderName and creatorName
        if ($result['creditHolder']) {
            $result['creditHolderName'] = woocr_call_get_userName($result['creditHolder']);
        }
        $result['creatorName'] = ($result['creatorId'] == 0) ? 'سیستمی' : woocr_call_get_userName($result['creatorId']);
    }

    $final_result = [
        'records' => $results,
        'totalRecords' => $total_count
    ];

    // Return the results as JSON
    wp_send_json_success($final_result);
}


function fetch_crm_records_report() {
    // Check nonce for security
    check_ajax_referer('woocr_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10000;
    $offset = 0;

    // Initialize WHERE clause
    $where_clauses = array('1=1');
    $query_args = array();

    // Date range filter
    if (!empty($_POST['dateRange'])) {
        $range = $_POST['dateRange'];
        $dates = explode(',', $range);
        if (count($dates) == 2) {
            $startDate = intval($dates[0]);
            $endDate = intval($dates[1]) + 86400; // Add one day to include the end date
            $where_clauses[] = 'retain_unix BETWEEN %d AND %d';
            $query_args[] = $startDate;
            $query_args[] = $endDate;
        }
    }

    // Combine all WHERE clauses
    $where_clause = implode(' AND ', $where_clauses);

    // Prepare the final WHERE clause
    $prepared_where_clause = $wpdb->prepare($where_clause, $query_args);

    // Query the database
    $query = $wpdb->prepare("
        SELECT
            retain_id       AS id,
            retain_unix     AS date,
            creator_id      AS creatorId,
            credit_holder   AS creditHolder,
            user_name       AS userName,
            user_id         AS userId,
            user_orders     AS orders,
            overall_status  AS status,
            source          AS source,
            lvl1            AS phaseI,
            lvl2            AS phaseII,
            lvl3            AS phaseIII,
            lvl4            AS phaseIV,
            lvl5            AS phaseV,
            meta            AS meta
        FROM $table_name
        WHERE $prepared_where_clause
        ORDER BY retain_unix DESC
        LIMIT %d, %d
    ", $offset, $limit);

    // Counter for total records
    $total_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) AS total_count
        FROM $table_name
        WHERE $where_clause
    ", $query_args));

    // Fetch the results
    $results = $wpdb->get_results($query, ARRAY_A);
    $current_time = time();

    foreach ($results as &$result) {
        // Decode JSON fields
        $result['phaseI']   = json_decode($result['phaseI'], true);
        $result['phaseII']  = json_decode($result['phaseII'], true);
        $result['phaseIII'] = json_decode($result['phaseIII'], true);
        $result['phaseIV']  = json_decode($result['phaseIV'], true);
        $result['phaseV']   = json_decode($result['phaseV'], true);

        // Handle phases timing
        $phases = ['phaseI', 'phaseII', 'phaseIII', 'phaseIV', 'phaseV'];
        foreach ($phases as $phase) {
            if (isset($result[$phase]['timing']['phaseEnd']) && $result[$phase]['timing']['phaseEnd'] != '0') {
                $phase_end_timestamp = $result[$phase]['timing']['phaseEnd'];
                $result[$phase]['timing']['timeDiff'] = human_time_diff($phase_end_timestamp, $current_time) . ' پیش';
            }
        }

        // Set creditHolderName and creatorName
        if ($result['creditHolder']) {
            $result['creditHolderName'] = woocr_call_get_userName($result['creditHolder']);
            $paid_conditions = ['paid-ready', 'paid-notReady', 'paid-calling', 'paid-end'];
            if (in_array($result['status'], $paid_conditions)) {

                // Process user_orders (assumed to be a JSON field)
                if (!empty($result['orders'])) {
                    $orders = json_decode($result['orders'], true);
                    if (is_array($orders) && count($orders) > 0) {
                        // Get the last order
                        $orders = json_decode($result['orders'], true);
                        $last_order = end($orders);

                        // Fetch order details (status and total price)
                        if (isset($last_order['id'])) {
                            $order_details = woocr_call_get_order_details_by_id($last_order['id']);
                            if ($order_details && in_array($order_details['status'], ['completed', 'qp-paying', 'qp-overdue'])) {
                                // Add total price if the last order is "paid"
                                $result['lastOrderTotal'] = isset($order_details['total']) ? $order_details['total'] : $last_order;
                            } else {
                                // If the last order is not paid, set total as 0
                                // $result['lastOrderTotal'] = $order_details;
                            }
                        }
                    } else {
                        // If no valid orders, set total as 0
                        // $result['lastOrderTotal'] = $orders;
                    }
                }
            }
        }
        $result['creatorName'] = ($result['creatorId'] == 0) ? 'سیستمی' : woocr_call_get_userName($result['creatorId']);
    }

    $final_result = [
        'records' => $results,
        'totalRecords' => $total_count
    ];

    // Return the results as JSON
    wp_send_json_success($final_result);
}





function get_all_phase_data($phase_id, $record_id){
    $data = [];
    $data['phase_id']  = $phase_id;
    $data['record_id'] = $record_id;
    return $data;
}


// We Fetch phase Data ++Locking The Row
function woocr_call_fetch_phase_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woocr_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }
    $record_id = intval($_POST['record_id']);
    $phase_id = sanitize_text_field($_POST['phase']);
    $customer_name = sanitize_text_field($_POST['customerName']);
    $customer_phone = sanitize_text_field($_POST['customerPhone']);
    // Fetch phase data
    $phase_data = woocr_get_phase_data($record_id, $phase_id);

    if ($phase_data['data']) {
        if (!woocr_setup_phase_start($phase_data)) {
            wp_send_json_error(['message' => 'Failed to set phase Start Time']);
            return;
        }

        // Lets Lock The Row
        $record_current_status = woocr_call_checkRowStatus($record_id);
        // 'notReady','ready','calling','end','paid-ready','paid-notReady','paid-calling','paid-end'
        

        switch ($record_current_status) {
            case 'ready';
                $record_new_status = 'calling';
                break;
            case 'paid-ready';
                $record_new_status = 'paid-calling';
                break;
            default:
                wp_send_json_error(['message' => "Failed Because Record Current Status: $record_current_status"]);
                return;
                break;
        }

        $row_update_result = woocr_call_updateRowStatus($record_id, $record_new_status, $exp = 300 );
        if (!$row_update_result) {
            wp_send_json_error(['message' => "Failed To Lock The Row"]);
            return;
        }
        $phase_data['similarity'] = woocr_call_searchNamePhone($customer_name, $customer_phone ,null);
        // Send success response with phase data
        wp_send_json_success($phase_data);
    } else {
        wp_send_json_error(['message' => 'No data found']);
    }
}

function woocr_call_searchNamePhone($customer_name, $customer_phone, $range = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';
    
    $where_clauses = [];
    $query_args = [];

    // Name search clauses
    if (!empty($customer_name)) {
        $customer_name = sanitize_text_field(urldecode(str_replace(' ', '', $customer_name)));
        $search_words = preg_split('/\s+/', $customer_name, -1, PREG_SPLIT_NO_EMPTY);
        $name_where_clauses = [];
        $name_where_clauses[] = 'REPLACE(user_name, " ", "") LIKE %s'; // Remove spaces and search
        $query_args[] = '%' . $wpdb->esc_like(str_replace(' ', '', $customer_name)) . '%';

        // Add clauses for individual words search (if there are spaces in the name)
        foreach ($search_words as $word) {
            $name_where_clauses[] = 'user_name LIKE %s';
            $query_args[] = '%' . $wpdb->esc_like($word) . '%';
        }

        // Combine the clauses using OR to match concatenated or spaced versions
        $where_clauses[] = '(' . implode(' OR ', $name_where_clauses) . ')';
    }

    // Phone search clause (no need for a loop)
    if (!empty($customer_phone)) {
        $customer_phone = sanitize_text_field(urldecode(str_replace(' ', '', $customer_phone)));
        $where_clauses[] = 'REPLACE(user_phone, " ", "") LIKE %s'; // Remove spaces and search
        $query_args[] = '%' . $wpdb->esc_like($customer_phone) . '%';
    }
    
    // Date range filter
    if (!empty($range)) {
        $dates = explode(',', $range);
        if (count($dates) == 2) {
            $startDate = intval($dates[0]);
            $endDate = intval($dates[1]) + 86400; // Add one day to include the end date
            $where_clauses[] = 'retain_unix BETWEEN %d AND %d';
            $query_args[] = $startDate;
            $query_args[] = $endDate;
        }
    }

    // If no search terms were provided, return empty result set
    if (empty($where_clauses)) {
        return [
            'records' => [],
            'totalRecords' => 0
        ];
    }

    // Combine all WHERE clauses using OR between them (matching either name or phone)
    $where_clause = implode(' OR ', $where_clauses);

    // Prepare the final query
    $query = $wpdb->prepare("
        SELECT
            retain_id       AS id,
            retain_unix     AS date,
            credit_holder   AS creditHolder,
            user_name       AS userName,
            user_orders     AS orders,
            user_phone      AS userPhone,
            overall_status  AS status
        FROM $table_name
        WHERE $where_clause
        ORDER BY retain_unix DESC
    ", ...$query_args);

    // Get total record count
    $total_count_query = $wpdb->prepare("
        SELECT COUNT(*) AS total_count
        FROM $table_name
        WHERE $where_clause
    ", ...$query_args);
    $total_count = $wpdb->get_var($total_count_query);

    // Fetch the results
    $results = $wpdb->get_results($query, ARRAY_A);

    if ($results) {
        foreach ($results as &$result) {
            $result['creditHolderName'] = woocr_call_get_userName($result['creditHolder']);
        }
    }

    $final_result = [
        'records' => $results,
        'totalRecords' => $total_count
    ];

    // Return the results
    return $final_result;
}



function fetch_singleRow() {
    global $wpdb;
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woocr_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    // Get record ID from the request
    $record_id = intval($_POST['record_id']);
    if (!$record_id) {
        wp_send_json_error(['message' => 'Invalid record ID']);
        return;
    }

    // Define the table name
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Query the row data
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE retain_id = %d", $record_id),
        ARRAY_A // Return as associative array
    );

    // Check if the row exists
    if (!$row) {
        wp_send_json_error(['message' => 'Record not found']);
        return;
    }

    // Decode the JSON fields
    $row['lvl1'] = !empty($row['lvl1']) ? json_decode($row['lvl1'], true) : null;
    $row['lvl2'] = !empty($row['lvl2']) ? json_decode($row['lvl2'], true) : null;
    $row['lvl3'] = !empty($row['lvl3']) ? json_decode($row['lvl3'], true) : null;
    $row['lvl4'] = !empty($row['lvl4']) ? json_decode($row['lvl4'], true) : null;
    $row['lvl5'] = !empty($row['lvl5']) ? json_decode($row['lvl5'], true) : null;
    $row['meta'] = !empty($row['meta']) ? json_decode($row['meta'], true) : null;

    // Send the row data as a JSON response
    wp_send_json_success($row);
}



function woocr_setup_phase_start($phase_data) {
    $record_id  = $phase_data['record'] ;
    $phase_id   = $phase_data['phase'];
    $phase_data = $phase_data['data'];

    // Map phase IDs to their corresponding database columns
    $phase_column_map = [
        'phaseI'   => 'lvl1',
        'phaseII'  => 'lvl2',
        'phaseIII' => 'lvl3',
        'phaseIV'  => 'lvl4',  // Corrected phaseIV mapping
        'phaseV'   => 'lvl5'
    ];

    // Check if the provided phase_id is valid
    if (!isset($phase_column_map[$phase_id])) {
        return false; // Invalid phase_id provided
    }

    $phase_column = $phase_column_map[$phase_id];

    if ($phase_data['status']['phaseStatus'] !== 'done' && $phase_data['timing']['phaseEnd'] == '0' && $phase_data['timing']['smsSent'] == '0') {
        // Set the current time as the phase start time
        $current_time = time();
        $phase_data['timing']['phaseStart'] = $current_time;

        // Encode the updated phase data back to JSON
        $updated_phase_data = wp_json_encode($phase_data);

        global $wpdb;
        $table_name = $wpdb->prefix . 'wooc_retain_call'; // Updated table name
        // Update the phase data in the database
        $update_result = $wpdb->update(
            $table_name,
            [$phase_column => $updated_phase_data],
            ['retain_id' => $record_id],
            ['%s'], // phase data is JSON
            ['%d']  // retain_id is integer
        );

        // Check for update failure
        if ($update_result === false) {
            return false; // Update failed
        }
    }
    // Successfully set the phase start time or it was already set
    return true;
}



function insert_crm_records_batch_with_report($records) {
    // Arrays to store report details
    $report = [
        'success' => [],  // To store successful records
        'failed' => [],   // To store failed records
    ];

    if (is_string($records)) {
        $records = json_decode(stripslashes($records), true);  // Decode JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'failed' => ['error' => 'Failed to decode batch data: ' . json_last_error_msg()]
            ];
        }
    }
    // At this point, $records should be an array

    foreach ($records as $index => $record) {
        try {
            // Validate required fields
            if (!isset($record['data']['fullName']) || !isset($record['data']['phoneNumber'])) {
                $report['failed'][] = [
                    'index' => $index,
                    'status' => 'failed',
                    'error' => 'Missing required fields (phoneNumber or fullName).'
                ];
                continue;  // Skip invalid record
            }

            // Example data: You can adjust the following based on your actual data structure
            $user_orders_json = json_encode($record['data']['user_orders']);  // Example, if there are user orders
            $user_phone = sanitize_text_field($record['data']['phoneNumber']);
            $full_name = sanitize_text_field($record['data']['fullName']);
            $user_id = null;  // Assuming you do not have a user ID
            $lead_source = sanitize_text_field($record['data']['source']);  // Lead source

            // Prepare meta data
            $meta = [
                'utm'       => $record['data']['utm'],
                'normal'    => $record['data']['normalMeta'],
                'important' => $record['data']['importantMeta'],
                'filename'  => $record['data']['fileName'],
                'importdate'=> time()
            ];

            // Insert record using woocrcall_addRawRecord function
            $insert_id = woocrcall_addRawRecord(
                0, // creator_id (default to 0 for now)
                $user_orders_json,  // user_orders (as JSON)
                $full_name,  // user_name
                $user_phone,  // user_phone
                $user_id,  // user_id
                null,  // last_actioner
                $meta,  // meta
                'notReady',  // overall_status
                null,  // credit_holder
                null,  // paid_order
                null,  // locked_by
                $lead_source,  // source
                1800  // Lock duration
            );

            if ($insert_id) {
                // Store the success report with the inserted record ID
                $report['success'][] = [
                    'index' => $index,
                    'status' => 'success',
                    'insert_id' => $insert_id
                ];
            } else {
                throw new Exception("Failed to insert record");
            }

        } catch (Exception $e) {
            // Store the failure report with the error message
            $report['failed'][] = [
                'index' => $index,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    // Return the report at the end
    return $report;
}



function insert_crm_records_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    // Get data from POST request
    $batch = isset($_POST['batch']) ? $_POST['batch'] : [];

    if (empty($batch)) {
        wp_send_json_error('No records to insert');
    }

    // Call the batch insert function with report generation
    $report = insert_crm_records_batch_with_report($batch);

    // Return the report
    wp_send_json_success($report);
}




// AJAX handler function to add a record
function woocr_call_add_crm_record() {
    // Check nonce for security
    check_ajax_referer('woocr_nonce', 'nonce');

    // Get current user ID
    $current_user_id = get_current_user_id();

    // Retrieve and sanitize form data
    $user_name = sanitize_text_field($_POST['user_name']);
    $user_phone = sanitize_text_field($_POST['user_phone']);
    $user_orders = isset($_POST['user_orders']) ? wp_unslash($_POST['user_orders']) : ''; // JSON string
    // $overall_status = sanitize_text_field($_POST['overall_status']);
    $overall_status = 'ready';
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    // Optional fields
    $credit_holder = isset($_POST['credit_holder']) ? intval($_POST['credit_holder']) : null;
    $last_actioner = isset($_POST['last_actioner']) ? intval($_POST['last_actioner']) : null;
    $paid_order = isset($_POST['paid_order']) ? intval($_POST['paid_order']) : null;
    $locked_by = isset($_POST['locked_by']) ? intval($_POST['locked_by']) : null;
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'نامشخص'; // Default to 'نامشخص' if not provided

    // Determine creator_id
    $creator_id = isset($_POST['creator_id']) ? intval($_POST['creator_id']) : $current_user_id;

    // Validate creator_id (must be 0 or the current user ID)
    if ($creator_id !== 0 && $creator_id !== $current_user_id) {
        wp_send_json_error(['message' => 'Invalid creator ID']);
        return;
    }

    // Prepare meta data
    $meta = isset($_POST['meta']) ? wp_unslash($_POST['meta']) : [];

    // Call the `woocrcall_addRawRecord()` function
    $insert_id = woocrcall_addRawRecord($creator_id, $user_orders, $user_name, $user_phone, $user_id, $last_actioner, $meta, $overall_status, $credit_holder, $paid_order, $locked_by, $source);

    if ($insert_id) {
        wp_send_json_success(['insert_id' => $insert_id]); // Return the ID of the inserted record
    } else {
        wp_send_json_error(['message' => 'Failed to add record']);
    }
}


// Helper functions
function woocr_call_get_userName($user_id) {
    $user_info = get_userdata($user_id);
    if ($user_info) {
        $first_name = $user_info->first_name;
        $last_name = $user_info->last_name;
        return $first_name . ' ' . $last_name;
    } else {
        return 'User not found.';
    }
}


// On DB Actions, Mainly Helper Functions Stores Here:
function woocr_call_checkRowStatus($row_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Prepare the query to get the overall_status for the given retain_id
    $status = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT overall_status FROM $table_name WHERE retain_id = %d",
            $row_id
        )
    );

    // Return the status (or handle cases where the row does not exist)
    if ($status !== null) {
        return $status;
    } else {
        return 'Status not found or row does not exist';
    }
}

function woocr_call_updateRowStatus($row_id, $new_status, $expire = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    $update_data = [
        'overall_status' => sanitize_text_field($new_status)
    ];

    $update_format = ['%s'];

    // Set expiration time if provided
    if (!is_null($expire) && is_numeric($expire)) {
        $expiration_time = time() + ($expire);
        $update_data['exp_unix'] = $expiration_time;
        $update_format[] = '%d';
    }

    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['retain_id' => $row_id],
        $update_format,
        ['%d']
    );

    if ($result === false) {
        // Log the error or handle it as needed
        error_log("Failed to update row status. Error: " . $wpdb->last_error);
        return false;
    }

    return $result;
}

function woocr_call_updatePhaseStartTime($row_id, $level, $start_time) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    $current_json = $wpdb->get_var($wpdb->prepare(
        "SELECT $level FROM $table_name WHERE retain_id = %d",
        $row_id
    ));

    if ($current_json) {
        $phase_data = json_decode($current_json, true);
        $phase_data['timing']['phaseStart'] = sanitize_text_field($start_time);
        $new_json = wp_json_encode($phase_data);

        $wpdb->update(
            $table_name,
            [$level => $new_json],
            ['retain_id' => $row_id],
            ['%s'],
            ['%d']
        );
    }
}

function woocr_call_updatePhaseSmsTime($row_id, $level, $sms_time) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    $current_json = $wpdb->get_var($wpdb->prepare(
        "SELECT $level FROM $table_name WHERE retain_id = %d",
        $row_id
    ));

    if ($current_json) {
        $phase_data = json_decode($current_json, true);
        $phase_data['timing']['smsSent'] = sanitize_text_field($sms_time);
        $new_json = wp_json_encode($phase_data);

        $wpdb->update(
            $table_name,
            [$level => $new_json],
            ['retain_id' => $row_id],
            ['%s'],
            ['%d']
        );
    }
}



function woocr_call_updatePhaseSmsText($row_id, $level, $sms_text) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    $current_json = $wpdb->get_var($wpdb->prepare(
        "SELECT $level FROM $table_name WHERE retain_id = %d",
        $row_id
    ));

    if ($current_json) {
        $phase_data = json_decode($current_json, true);
        $phase_data['data']['smsText'] = sanitize_text_field($sms_text);
        $new_json = wp_json_encode($phase_data);

        $wpdb->update(
            $table_name,
            [$level => $new_json],
            ['retain_id' => $row_id],
            ['%s'],
            ['%d']
        );
    }
}


function woocr_unlock_crm_record($record_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Unlock the record by updating the status
    $unlock_result = $wpdb->update(
        $table_name,
        ['overall_status' => 'unlocked'], // Set status to "unlocked" or another suitable status
        ['retain_id' => $record_id],
        ['%s'], // Data type for status (string)
        ['%d']  // Data type for ID (integer)
    );

    return $unlock_result !== false;
}

function woocr_get_phase_data($record_id, $phase_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Map phase IDs to their corresponding database columns
    $phase_column_map = [
        'phaseI'   => 'lvl1',
        'phaseII'  => 'lvl2',
        'phaseIII' => 'lvl3',
        'phaseIV'  => 'lvl4',
        'phaseV'   => 'lvl5'
    ];

    // Check if the provided phase_id is valid
    if (!isset($phase_column_map[$phase_id])) {
        // Prepare and execute the query to fetch phase data
        $query = $wpdb->prepare("
            SELECT *
            FROM $table_name
            WHERE retain_id = %d
            LIMIT 1
        ", $record_id);

        $result = $wpdb->get_row($query, ARRAY_A);
        // $result = json_decode($result, true);
        // Return the whole row data
        return $result;

    }

    $phase_column = $phase_column_map[$phase_id];

    // Prepare and execute the query to fetch phase data
    $query = $wpdb->prepare("
        SELECT $phase_column AS phase_data
        FROM $table_name
        WHERE retain_id = %d
        LIMIT 1
    ", $record_id);

    $result = $wpdb->get_var($query);

    if ($result) {
        $phase_data = json_decode($result, true);
        // Return the phase data
        return [
            'phase'  => $phase_id,
            'record' => $record_id,
            'data'   => $phase_data
        ];
    }

    // Return null if no data was found
    return null;
}


function woocr_update_phase_data() {
    // Validate and sanitize inputs
    $record_id     = intval($_POST['record_id']);
    $phase         = sanitize_text_field($_POST['phase']);
    $credit_holder = sanitize_text_field($_POST['admin_id']); // Changed to credit_holder
    $user_react    = sanitize_text_field($_POST['user_reaction']);
    $admin_react   = sanitize_text_field($_POST['admin_reaction']);
    $admin_comment = sanitize_text_field($_POST['admin_comment']);
    $nonce         = $_POST['nonce'];

    // Validate required fields
    if ( !wp_verify_nonce($nonce, 'woocr_nonce') ) {
        wp_send_json_error([
            'message' => 'خطای اعتبارسنجی درخواست',
        ]);
        return;
    }

    // Fetch existing record to check user_orders
    global $wpdb;
    $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wooc_retain_call WHERE retain_id = %d", $record_id));

    if (!$record) {
        wp_send_json_error(['message' => 'رکورد یافت نشد.']);
        return;
    }

    // Decode user_orders JSON
    $user_orders = json_decode($record->user_orders, true);

    // Initialize variables to track order statuses
    $any_completed = false;
    $completed_orderID = null;
    $completed_orderTotal = 0.0;

    // Check each order's status
    foreach ($user_orders as $order) {
        // Assuming you have a way to check each order's status
        $order_status = woocr_call_getOrderStatus($order['id']); // You need to implement this function

        if (in_array($order_status, ['completed', 'qp-paying', 'qp-overdue'])) {
            $any_completed = true;
            $completed_orderID = $order['id'];
            $completed_orderTotal = $order['total']; // Corrected 'totla' to 'total'
            break; // Exit loop if any order is completed
        }
    }

    // Call the function to update phase data
    $phase_update_result = woocr_crm_record_phaseEnder($record_id, $phase, $credit_holder, $admin_comment, $admin_react, $user_react, $completed_orderID, $completed_orderTotal);
    if ($user_react == 'noAnswer') {
        $credit_holder = null;
    }
    if ($any_completed) {
        // Update overall_status to a "paid" version
        $wpdb->update(
            "{$wpdb->prefix}wooc_retain_call",
            ['overall_status' => 'paid-ready'], // Set to paid-ready or adjust as needed
            ['retain_id' => $record_id],
            ['%s'], // Format for overall_status
            ['%d']  // Format for retain_id
        );

        // Update the paid_order with the last completed order ID
        $wpdb->update(
            "{$wpdb->prefix}wooc_retain_call",
            ['paid_order' => $completed_orderID], // Store the last completed order ID
            ['retain_id' => $record_id],
            ['%d'], // Format for paid_order
            ['%d']  // Format for retain_id
        );
    } else {
        // If not submitted, add the credit_holder
        $wpdb->update(
            "{$wpdb->prefix}wooc_retain_call",
            ['credit_holder' => $credit_holder], // Update the credit_holder ID
            ['retain_id' => $record_id],
            ['%d'], // Format for credit_holder
            ['%d']  // Format for retain_id
        );
    }

    if ($phase) {
        $phase_column_map = [
            'phaseI'   => 'lvl1',
            'phaseII'  => 'lvl2',
            'phaseIII' => 'lvl3',
            'phaseIV'  => 'lvl4',
            'phaseV'   => 'lvl5'
        ];
        $level = $phase_column_map[$phase];
        wp_send_json_success([
            'message' => "Record follow-up result in $level successfully saved."
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Error updating data. Please try again.',
        ]);
    }
}

function woocr_crm_record_phaseEnder($record_id, $phase, $credit_holder, $admin_comment, $admin_react, $user_react, $completed_order_id = null, $completed_order_total = null) {
    // Define phase column mapping
    $phase_column_map = [
        'phaseI'   => 'lvl1',
        'phaseII'  => 'lvl2',
        'phaseIII' => 'lvl3',
        'phaseIV'  => 'lvl4',
        'phaseV'   => 'lvl5'
    ];

    // Check if the phase is valid
    if (!array_key_exists($phase, $phase_column_map)) {
        return false;
    }

    // Get the corresponding column name for the phase
    $level = $phase_column_map[$phase];
    // Update the phase end time to current time
    $current_time = time();
    // Retrieve the current JSON data from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';
    $current_json = $wpdb->get_var($wpdb->prepare(
        "SELECT $level FROM $table_name WHERE retain_id = %d",
        $record_id
    ));

    // Check if current_json is available
    if ($current_json) {
        $phase_data = json_decode($current_json, true);

        if (is_array($phase_data)) {
            // Update the admin comment, admin react, and user react
            $phase_data['status']['phaseStatus'] = 'done'; // Ensure phaseStatus is updated correctly
            $phase_data['timing']['phaseEnd'] = $current_time;

            $phase_data['data']['creditHolderComment'] = $admin_comment; // Change to reflect credit holder

            if ($credit_holder) {
                $credit_holder_name = woocr_call_get_userName($credit_holder);
                $credit_holder_name = $credit_holder_name ?: 'Name not specified';
            } else {
                $credit_holder = 0;
                $credit_holder_name = 'System/Remote';
            }

            $phase_data['data']['creditHolder'] = $credit_holder; // Update credit holder
            $phase_data['data']['creditHolderName'] = $credit_holder_name;

            $phase_data['status']['admin_feedback'] = $admin_react;
            $phase_data['status']['user_reaction'] = $user_react;

            // Re-encode the updated JSON
            $new_json = wp_json_encode($phase_data);

            // Update the phase data in the database
            $result = $wpdb->update(
                $table_name,
                [$level => $new_json],
                ['retain_id' => $record_id],
                ['%s'],
                ['%d']
            );

            // If there are completed orders, update the paid order and totals
            if ($completed_order_id !== null && $completed_order_total !== null) {
                // Update paid order information
                $wpdb->update(
                    $table_name,
                    [
                        'paid_order' => $completed_order_id, // Store the last completed order ID
                        'overall_status' => 'paid-ready' // Update to a paid status
                    ],
                    ['retain_id' => $record_id],
                    ['%d', '%s'], // Format for paid_order and overall_status
                    ['%d'] // Format for retain_id
                );
            }

            if ($result) {
                return true; // Successfully updated
            }
        }
    }

    return false; // Return false if any step fails
}

function woocr_call_getOrderStatus($order_id) {
    // Use WooCommerce function to get the order object
    $order = wc_get_order($order_id);

    // Check if the order object is valid
    if ($order) {
        return $order->get_status(); // Return the order status
    }

    return null; // Return null if the order is not found
}

function woocr_call_get_order_details_by_id($order_id) {
    // Get the order object using WooCommerce function
    $order = wc_get_order($order_id);

    if ($order) {
        // Get the status and total of the order
        $order_status = $order->get_status(); // e.g., 'completed', 'processing', 'on-hold', etc.
        $order_total = $order->get_total();   // Gets the total price of the order

        return [
            'status' => $order_status,
            'total' => $order_total
        ];
    }

    return false; // Return false if the order is not found
}