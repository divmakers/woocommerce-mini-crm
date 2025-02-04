<?php


add_action('wp_ajax_fetch_patterns', 'fetch_patterns');
add_action('wp_ajax_save_pattern', 'save_pattern');
add_action('wp_ajax_delete_pattern', 'delete_pattern');
add_action('wp_ajax_fetch_crm_records', 'fetch_crm_records');
add_action('wp_ajax_fetch_crm_records_report', 'fetch_crm_records_report');

add_action('wp_ajax_fetch_phase_data', 'woocr_call_fetch_phase_data');
add_action('wp_ajax_unlock_crm_row', 'woocr_crm_rowUnlock');
add_action('wp_ajax_add_crm_record', 'woocr_call_add_crm_record');
add_action('wp_ajax_woocr_send_sms', 'woocr_send_sms_func');
add_action('wp_ajax_woocr_update_phase_data', 'woocr_update_phase_data');

add_action('wp_ajax_fetch_singleRow', 'fetch_singleRow');

add_action('wp_ajax_insert_crm_records', 'insert_crm_records_handler');