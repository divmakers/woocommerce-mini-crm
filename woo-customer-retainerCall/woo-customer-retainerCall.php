<?php
/**
 * Plugin Name: Mini wp-Crm
 * Plugin URI: divmakers.com
 * Description: A Customer Care Call System.
 * Version: 1.0.0
 * Author: @divmakers
 * Author URI: https://divmakers.com/
 * Text Domain: woo-cr-call
 * Domain Path: /i18n/languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 */

defined('ABSPATH') || exit;

define('WOOCRCALL_DIR', plugin_dir_path(__FILE__)); //Such as => /home/USER/public_html/wp-content/plugins/woocr/
define('WOOCRCALL_URL', plugin_dir_url(__FILE__)); //Such as => https://DOMAIN.TLD/wp-content/plugins/woocr/
define('WOOCRCALL_ASSETS', WOOCRCALL_URL . 'assets/');
define('WOOCRCALL_INC', WOOCRCALL_DIR . 'inc/');

define('WOOCRCALL_TEMPLATE', WOOCRCALL_INC . 'admin-call-template/');

function woocrcall_create_retainer_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table3_name = $wpdb->prefix . 'wooc_retain_call';
    $table4_name = $wpdb->prefix . 'wooc_retain_call_smspatterns';
    $sql = array();


# FOR CALL RETAINING PROCCESS
  // Creates The Main Data Structure of SMS retain
    $sql[] = "CREATE TABLE $table3_name (
        retain_id       BIGINT(20)  AUTO_INCREMENT NOT NULL,
        retain_unix     INT(11)     NOT NULL,
        creator_id      BIGINT(20)  NULL,
        

        credit_holder   BIGINT(20)  NULL,
        last_actioner   BIGINT(20)  NULL,

        user_orders     JSON        NOT NULL,
        paid_order      BIGINT(20)  NULL,

        user_name       VARCHAR(100)    NOT NULL,
        user_phone      VARCHAR(20)     NOT NULL,
        user_id         BIGINT(20)      NULL,
        overall_status  ENUM(
                            'notReady',
                            'ready',
                            'calling',
                            'noBuy',
                            'noBuyIgnored',
                            'end',
                            'duplicated',
                            'paid-ready',
                            'paid-notReady',
                            'paid-calling',
                            'paid-end',
                            'paid-noBuy',
                            --Really?! this will not possible in MainTained Logic... but let me make it available here...
                            'paid-noBuyIgnored'
                            )           NOT NULL,
        locked_by       BIGINT(20)      NULL,
        source          VARCHAR(100)    NULL,

        exp_unix        INT(11)         Null,

        lvl1 JSON NULL,
        lvl2 JSON NULL,
        lvl3 JSON NULL,
        lvl4 JSON NULL,
        lvl5 JSON NULL,

        meta JSON NULL,

        PRIMARY KEY (retain_id),
        INDEX (credit_holder),
        INDEX (last_actioner),
        INDEX (retain_unix)
    ) $charset_collate;";

  // Creates the product List DATABASE TABLE
    $sql[] = "CREATE TABLE $table4_name (
        pattern_id      tinyint(3)  NOT NULL AUTO_INCREMENT,
        pattern_name    TINYTEXT    NOT NULL,
        creator_id      bigint(20)  NOT NULL,
        pattern_text    TEXT        NULL,
        PRIMARY KEY  (pattern_id)
    ) $charset_collate;";


# Lets Create Our Tables
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach( $sql as $key ) {
        dbDelta($key);
    }
}

function woocrcall_drop_retainer_tables() {
    $table_names = array();
    global $wpdb;
    $table_names[] = $wpdb->prefix . 'wooc_retain_call';
    $table_names[] = $wpdb->prefix . 'wooc_retain_call_smspatterns';

    foreach ( $table_names as $table_name ) {
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }
}

// Plugin activation hook
register_activation_hook(__FILE__, 'woocrcall_create_retainer_tables');
// Plugin deactivation hook
// register_deactivation_hook(__FILE__, 'woocrcall_drop_retainer_tables');

if (is_admin()) {
 // include WOOCRCALL_INC . 'admin/menus.php';
 // include WOOCRCALL_INC . 'admin/woocr_ajax_rowshandler.php';

    include WOOCRCALL_INC . 'admin-call/menus.php';
    include WOOCRCALL_INC . 'woocr-enqueue.php';
    include WOOCRCALL_INC . 'send-sms.php';
    include WOOCRCALL_INC . 'admin-call/crm-helper.php';
    
    include WOOCRCALL_INC . 'admin-call/woocr-call-ajaxhandler.php';
}
include_once WOOCRCALL_INC . 'cronjobs/woocr-call-cron-updates.php';
include_once WOOCRCALL_INC . 'cronjobs/woocr-call-cron-heavythread.php';

function woocrcall_addRawRecord($creator_id, $user_orders, $user_name, $user_phone, $user_id, $last_actioner, $meta, $overall_status = 'notReady', $credit_holder = null, $paid_order = null, $locked_by = null, $source = 'خرید از وبسایت', $lock_duration = 900) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain_call';

    // Default value for phases
    $default_phase = [
        "status" => [
            "phaseStatus" => "ready",
        ],
        "data" => [
            "prosecutor" => 0,
            "smsText" => "",
        ],
        "timing" => [
            "phaseStart" => "0",
            "smsSent" => "0",
            "phaseEnd" => "0",
        ]
    ];

    $lvl_default = wp_json_encode($default_phase);
    $action_exp_unix = time() + $lock_duration;

    // Prepare data for insertion
    $data = [
        'retain_unix' => time(),
        'creator_id' => $creator_id,
        'credit_holder' => $credit_holder,
        'last_actioner' => $last_actioner,
        'user_orders' => $user_orders, // Store user_orders as JSON
        'user_name' => $user_name,
        'user_phone' => $user_phone,
        'user_id' => $user_id,
        'overall_status' => $overall_status,
        'paid_order' => $paid_order,
        'locked_by' => $locked_by,
        'source' => $source,
        'exp_unix' => $action_exp_unix,
        'lvl1' => $lvl_default,
        'lvl2' => $lvl_default,
        'lvl3' => $lvl_default,
        'lvl4' => $lvl_default,
        'lvl5' => $lvl_default,
        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ];

    // Insert data into the table
    $result = $wpdb->insert($table_name, $data, [
        '%d', // retain_unix
        '%d', // creator_id
        '%d', // credit_holder
        '%d', // last_actioner
        '%s', // user_orders
        '%s', // user_name
        '%s', // user_phone
        '%d', // user_id
        '%s', // overall_status
        '%d', // paid_order
        '%d', // locked_by
        '%s', // source
        '%d', // exp_unix
        '%s', // lvl1
        '%s', // lvl2
        '%s', // lvl3
        '%s', // lvl4
        '%s', // lvl5
        '%s'  // meta
    ]);

    return $result ? $wpdb->insert_id : false;
}



function woocr_handle_crmOrder_newRecord($order, $lead_source = 'woocommerce') {

    // Define an array of product IDs to exclude
    $excluded_items = [
        7374,
        7376,
        7378,
        7381,
        7382,
        7383,
        // 161109
    ];

    // Check if any item in the order is in the excluded_items array
    foreach ($order->get_items() as $item) {
        if (in_array($item->get_product_id(), $excluded_items)) {
            return; // Exit if the item is excluded
        }
    }
    global $wpdb;
    // Check if $order is an integer (order ID) or an object
    if (is_int($order)) {
        // If it's an integer, get the order object
        $order = wc_get_order($order);
    } elseif (is_array($order) && isset($order['id'])) {
        // If it's an array, get the order object using the provided ID
        $order_id = $order['id'];
        $order = wc_get_order($order_id);
    }

    // Exit if the order is not found
    if (!$order) {
        return;
    }

    // Extract necessary details from the order
    $user_phone = $order->get_billing_phone();
    $user_id = $order->get_user_id();
    
    // Prepare user_orders with product details
    $user_orders = [];
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name(); // Get the product name
        $user_orders[] = [
            'id'   => $order->get_id(), // Use product ID for better reference
            'item' => $item->get_product_id(), // Use the unique item ID
            'name' => $product_name
        ];
    }
    $user_orders_json = json_encode($user_orders); // Convert to JSON format

    // Check for existing records with the specified overall_status and user_phone or user_id
    $existing_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wooc_retain_call
         WHERE overall_status IN ('notReady', 'ready', 'calling', 'end')
         AND (user_phone = %s OR user_id = %d)",
        $user_phone,
        $user_id
    ));

    if ($existing_record) {
        // Decode the existing user_orders JSON
        $existing_user_orders = json_decode($existing_record->user_orders, true);
        
        // Add the new order(s) to existing orders
        $existing_user_orders = array_merge($existing_user_orders, json_decode($user_orders_json, true)); // Merge arrays

        // Encode back to JSON
        $updated_user_orders = json_encode($existing_user_orders);

        // Get the current Unix timestamp
        $current_timestamp = time();

        // Update the database, including retain_unix
        $wpdb->update(
            "{$wpdb->prefix}wooc_retain_call",
            [
                'user_orders' => $updated_user_orders,
                'retain_unix' => $current_timestamp // Update retain_unix with the current timestamp
            ],
            ['retain_id' => $existing_record->retain_id],
            ['%s', '%d'], // Format for user_orders and retain_unix
            ['%d']  // retain_id format
        );
    } else {
        // Create a new record if no existing record is found
        $insert_id = woocrcall_addRawRecord(
            0, // Pass your creator ID here
            $user_orders_json, // New order as JSON
            $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            $user_phone,
            $user_id,
            null, // Set your last actioner here
            null, // Set your meta here
            'notReady',
            null,
            null,
            null,
            $lead_source,
            300 // Lock duration
        );
    }
}



add_action('woocommerce_order_status_completed', 'woocr_update_crm_order_status');
add_action('woocommerce_update_order', 'woocr_update_crm_order_status');

function woocr_update_crm_order_status($order) {
    // Get the order object
    if (!is_array($order)) {
        
        $order = wc_get_order($order);
    }

    if (!$order) {
        return; // Exit if the order doesn't exist
    }

    // Get necessary details from the order
    $user_id = $order->get_user_id();
    $user_phone = $order->get_billing_phone();

    // Fetch the corresponding CRM record
    global $wpdb;
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wooc_retain_call WHERE user_id = %d OR user_phone = %s",
        $user_id,
        $user_phone
    ));

    if ($record) {
        // Get the current overall status
        $current_status = $record->overall_status;

        // Determine the new status based on the current status and action type
        if (in_array($current_status, ['notReady', 'ready', 'calling', 'end'])) {
            $new_status = 'paid-ready'; // Update to paid-ready if currently in a not paid state
        } elseif (in_array($current_status, ['paid-notReady', 'paid-calling', 'paid-end'])) {
            $new_status = $current_status; // Keep the current paid status
        } else {
            return; // Exit if the current status is already a paid status
        }

        // Get the current Unix timestamp
        $current_timestamp = time();

        // Update the overall status, paid order, and retain_unix
        $order_status = $order->get_status();
        if (in_array($order_status, ['completed', 'qp-paying', 'qp-overdue', 'wp-qp-paying', 'wp-qp-overdue'])) {
            // If the order is completed, update the CRM
            $wpdb->update(
                "{$wpdb->prefix}wooc_retain_call",
                [
                    'overall_status' => $new_status,
                    'paid_order'     => $order->get_id(), // Set the paid_order to the current order ID
                    'retain_unix'    => $current_timestamp // Update retain_unix with the current timestamp
                ],
                ['retain_id' => $record->retain_id],
                ['%s', '%d', '%d'], // Format for overall_status, paid_order, and retain_unix
                ['%d'] // Format for retain_id
            );
        }
    }
}