<?php
// For CallRetaining Proccess
function woocr_register_admin_menu_calling_retain()
{
    add_menu_page(
        'لیست تسک بازیابی تلفنی',
        'بازیابی تلفنی',
        'manage_options',
        'woocr_call',
        'woocr_call_menu_handler',
        null,
        2
    );
    add_submenu_page(
        'woocr_call',
        'تعریف پترن پیامک برای بازیابی تلفنی',
        'تعریف پترن پیامک',
        'manage_options',
        'woocr_call_patterns',
        'woocr_callPatterns_handler'
    );
    add_submenu_page(
        'woocr_call', 
        'همه اطلاعات بازیابی',
        'همه اطلاعات بازیابی',
        'manage_options',
        'woocr_call_all_data',
        'woocr_callAllData_handler' 
    );

    add_submenu_page(
        'woocr_call', 
        'گزارشات سیستم بازیابی تلفنی',
        'گزارش گیری',
        'manage_options',
        'woocr_call_report',
        'woocr_callReport_handler' 
    );

    add_submenu_page(
        'woocr_call', 
        'افزودن رکورد بازیابی',
        'افزودن رکورد',
        'manage_options',
        'woocr_call_add_record',
        'woocr_callAddRecord_handler' 
    );
}
add_action( 'admin_menu', 'woocr_register_admin_menu_calling_retain',5 );



function woocr_call_menu_handler(){
    include WOOCRCALL_TEMPLATE . 'main-menu.php';
}
function woocr_callPatterns_handler(){
    include WOOCRCALL_TEMPLATE . 'call-pattern.php';
}
function woocr_callAllData_handler(){
    include WOOCRCALL_TEMPLATE . 'all-data.php';
}

function woocr_callReport_handler(){
    include WOOCRCALL_TEMPLATE . 'report.php';
}

function woocr_callAddRecord_handler(){
    include WOOCRCALL_TEMPLATE . 'add-record.php';
}


add_action( 'init', 'stop_heartbeat_onWoocrCallPages', 1 );
function stop_heartbeat_onWoocrCallPages() {
    if  (isset($_GET['page']) && in_array($_GET['page'], ['woocr_call_patterns', 'woocr_call', 'woocr_call_all_data', 'woocr_call_add_record' ])) {
        wp_deregister_script('heartbeat');
    }
}