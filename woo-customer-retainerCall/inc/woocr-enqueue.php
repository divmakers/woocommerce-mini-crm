<?php 
function woocr_call_crm_enq() {

    // Check if we are on specific pages "ONLY FOR PATTERN HANDLING"
    if (isset($_GET['page']) && in_array($_GET['page'], ['woocr_call_patterns'])) {
        // Enqueue Vue.js
        wp_enqueue_script('vue-js', WOOCRCALL_ASSETS . 'js/vue.global.js', [], '3.4.37', true);

        // Enqueue your custom script and make sure it's loaded after Vue.js
        wp_enqueue_script('woocr-call_pattern_JS', WOOCRCALL_ASSETS . 'js/woocr-call_patterns.js', ['vue-js'], '1.0', true);

        // Enqueue your style
        wp_enqueue_style('woocr-call_style', WOOCRCALL_ASSETS . 'css/woocr-call.css?v=2.1');

        // Pass data to the script
        wp_localize_script('woocr-call_pattern_JS', 'woocrCall', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woocr_nonce'),
            'user_id' => get_current_user_id(), // Pass the current user ID
        ]);
    }

    // Check if we are on specific pages "ONLY FOR RETAIN HANDLING"
    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'woocr_call',
            'woocr_call_all_data',
            'woocr_call_add_record',
            'woocr_call_report'
            ])
        )
        {

        // Enqueue Custom style
        wp_enqueue_style('woocr-call_style', WOOCRCALL_ASSETS . 'css/woocr-call.css');
        // Enqueue Vue.js
        wp_enqueue_script('vue-js', WOOCRCALL_ASSETS . 'js/vue.global.js', null, '3.4.37', true);
        // Enqueue moment.min.js and make Vue.js Dependencie
        wp_enqueue_script('moment', WOOCRCALL_ASSETS . 'js/moment.min.js', ['vue-js'], '1.0', true);
        wp_enqueue_script('moment-jalaali', WOOCRCALL_ASSETS . 'js/moment-jalaali.min.js', ['vue-js'], '1.0', true);
        // Enqueue JalaliMoment.min.js and make Vue.js Dependencie
        wp_enqueue_script('jalaali-vue3', WOOCRCALL_ASSETS . 'js/vue3-persian-datetime-picker.umd.min.js', ['vue-js'], '1.0', true);
    }
    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'woocr_call_add_record'
            ])
        )
        {
            // Enqueue custom script and make Vue.js Dependencie
            wp_enqueue_script('woocr-call', WOOCRCALL_ASSETS . 'js/woocr-call_new-records.js', ['jquery','vue-js'], '1.2', true);

            wp_enqueue_script('woocr-select2', WOOCRCALL_ASSETS . 'js/select2.min.js', ['jquery','vue-js'], '1.0', true);
            wp_enqueue_style('woocr-select2', WOOCRCALL_ASSETS . 'css/select2.min.css');

            wp_enqueue_script('woocr-xlsx', WOOCRCALL_ASSETS . 'js/xlsx.full.min.js', ['jquery','vue-js'], '1.0', true);
            // Pass data to the script
            wp_localize_script('woocr-call', 'woocrCall', [
                'ajax_url'  => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('woocr_nonce'),
                'user_id'   => get_current_user_id(), // Pass the current user ID
            ]);
        }

    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'woocr_call'
            ])
        ){
        $today = new DateTimeImmutable(); // Gets the current date and time
        $lastweek = $today->sub(new DateInterval('P7D'));
        $todayTimestamp    = $today->getTimestamp();
        $lastweekTimestamp = $lastweek->getTimestamp();

        // Enqueue custom script and make Vue.js Dependencie
        wp_enqueue_script('woocr-call_JS', WOOCRCALL_ASSETS . 'js/woocr-call.js', ['vue-js'], '1.3', true);

        // Pass data to the script
        wp_localize_script('woocr-call_JS', 'woocrCall', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('woocr_nonce'),
            'user_id'   => get_current_user_id(), // Pass the current user ID
            'today'     => $todayTimestamp,
            'lastweek'  => $lastweekTimestamp
        ]);
    }
    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'woocr_call_all_data'
            ])
        ){
        $today = new DateTimeImmutable(); // Gets the current date and time
        $lastweek = $today->sub(new DateInterval('P7D'));
        $todayTimestamp    = $today->getTimestamp();
        $lastweekTimestamp = $lastweek->getTimestamp();

        // Enqueue custom script and make Vue.js Dependencie
        wp_enqueue_script('woocr-call_JS', WOOCRCALL_ASSETS . 'js/woocr-call-admin.js', ['vue-js'], '1.3', true);

        // Pass data to the script
        wp_localize_script('woocr-call_JS', 'woocrCall', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('woocr_nonce'),
            'user_id'   => get_current_user_id(), // Pass the current user ID
            'today'     => $todayTimestamp,
            'lastweek'  => $lastweekTimestamp
        ]);
    }
    if (
        isset($_GET['page']) && in_array($_GET['page'], [
            'woocr_call_report'
            ])
        ){
        $today = new DateTimeImmutable(); // Gets the current date and time
        $lastweek = $today->sub(new DateInterval('P7D'));
        $todayTimestamp    = $today->getTimestamp();
        $lastweekTimestamp = $lastweek->getTimestamp();

        // Enqueue custom script and make Vue.js Dependencie
        wp_enqueue_script('woocr-call_JS', WOOCRCALL_ASSETS . 'js/woocr-call-report.js', ['vue-js'], '1.1', true);

        // Pass data to the script
        wp_localize_script('woocr-call_JS', 'woocrCall', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('woocr_nonce'),
            'user_id'   => get_current_user_id(), // Pass the current user ID
            'today'     => $todayTimestamp,
            'lastweek'  => $lastweekTimestamp
        ]);
    }
}
add_action('admin_enqueue_scripts', 'woocr_call_crm_enq');
