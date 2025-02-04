document.addEventListener('DOMContentLoaded', function () {

    const today = moment(); // Current date
    const lastweek = moment().subtract(7, 'days'); // Date from 7 days ago
    const formattedToday = today.format('jYYYY/jMM/jDD');
    const formattedLastweek = lastweek.format('jYYYY/jMM/jDD');

    const { createApp } = Vue;
    createApp({
        data() {
            return {
                records: [],
                showPatterns: true,
                nonce: woocrCall.nonce,
                offset: 0,
                limit: 20,
                showModal: false,
                showStatusModal: false,
                modalData: {},
                modalStatusData: {},
                modalParrent: {},
                modalrecord: {},
                modalForm: {
                    smsText: '',
                    userReaction: '',
                    adminComment: ''
                },
                isAdminReactionDisabled: true,
                showPatternsDropdown: true,  // Initially show the pattern buttons
                isSMSSent: false,  // Track if SMS is sent

                currentPage: 1,
                totalPages: 0,
                totalRecords: 0,
                offset:0, 
                filterCriteria: {
                    limit: 20,
                    order_id: '',
                    customerName: '',
                    mineOnly: false,
                    newOnly: false,
                    dateRange: [formattedLastweek , formattedToday],
                    status: 'all'
                }
            }
        },
        created() {
            this.fetchRecords();
        },
        methods: {
            phase_leadTypeTranslate(leadType){
                switch (leadType) {
                    case 'cold':
                        return 'سرد';
                        break;

                    case 'hot':
                        return 'داغ';
                        break;

                    case 'warm':
                        return 'گرم';
                        break;
                    
                    // case 'done':
                    //     return 'تکمیل';
                    //     break;

                    default:
                        return 'نامشخص / بدون شروع'
                        break;
                }
            },
            leadSourceTranslate(leadSource){
                switch (leadSource) {
                    case 'campaign-didogram':
                        return 'کمپین دیدوگرام';
                        break;

                    case 'webinar':
                        return 'وبینار';
                        break;

                    case 'porsline':
                        return 'پُرس‌لاین';
                        break;
                    
                    case 'other':
                        return 'دیگر';
                        break;

                    default:
                        return leadSource;
                        break;
                }
            },
            phase_statusTranslate(phase_status){
                switch (phase_status) {
                    case 'ready':
                        return 'آماده';
                        break;

                    case 'done':
                        return 'پایان یافته';
                        break;

                    case 'processing':
                        return 'در حال پردازش';
                        break;
                    
                    case 'done':
                        return 'تکمیل';
                        break;

                    default:
                        return 'نامشخص:' + phase_status;
                        break;
                }
            },
            admin_feedbackTranslate(admin_feedback){
                switch (admin_feedback) {
                    case 'noAnswer':
                        return 'بی پاسخ';
                        break;

                    case 'isWilling':
                        return 'مایل است';
                        break;

                    case 'notWilling':
                        return 'مایل نیست';
                        break;
                    
                    case 'hot':
                        return 'داغ';
                        break;
                    case 'cold':
                        return 'سرد';
                        break;
                    case 'warm':
                        return 'گرم';
                        break;
                    case 'paid-otherNumber':
                        return 'پرداخت شده با شماره دیگر';
                        break;

                    default:
                        return 'نامشخص';
                        break;
                }
            },
            record_statusTranslate(overall_status){
                switch (overall_status) {
                    // 'notReady',
                    // 'ready',
                    // 'calling',
                    // 'end',
                    // 'paid-ready',
                    // 'paid-notReady',
                    // 'paid-calling',
                    // 'paid-end',

                    case 'notReady':
                        return 'آماده پیگیری نیست';
                        break;
                    case 'paid-notReady':
                        return 'خریده آماده پیگیری نیست';
                        break;

                    case 'ready':
                        return 'آماده پیگیری';
                        break;
                    case 'paid-ready':
                        return 'خریده آماده پیگیری';
                        break;

                    case 'calling':
                        return 'در حال تماس';
                        break;
                    case 'paid-calling':
                        return 'خریده در حال تماس';
                        break;

                    case 'end':
                        return 'پایان یافته';
                        break;
                    case 'paid-end':
                        return 'خریده پایان یافته';
                        break;
                    case 'duplicated':
                        return 'تکراری';
                        break;
                    default:
                        return 'نامشخص!!!';
                        break;
                }
            },
            user_feedbackTranslate(user_feedback){
                switch (user_feedback) {
                    case 'noAnswer':
                        return 'بی پاسخ';
                        break;

                    case 'isWilling':
                        return 'تمایل به خرید';
                        break;

                    case 'notWilling':
                        return 'عدم تمایل خرید';
                        break;

                    case 'paid-otherNumber':
                        return 'پرداخت با شماره دیگر'
                        break;

                    case 'proceed-telegram':
                        return 'ادامه پیگیری در تلگرام'
                        break;

                    default:
                        return 'نامشخص';
                        break;
                }
            },
            convertUnixToJalaali(unixTime){
                if (unixTime) {
                    return moment.unix(unixTime).format('HH:mm ❖ jYYYY/jMM/jDD');
                }else{
                    return '*یافت نشد*';
                }
                // convertToJalali(timestamp).format('jYYYY/jMM/jDD hh:mm');
            },
            convertJalaaliToUnix(jalaaliDateRange) {
                if (jalaaliDateRange) {
                    let dates = [];
            
                    // Check if jalaaliDateRange is a string (comma-separated) or an array
                    if (typeof jalaaliDateRange === 'string') {
                        dates = jalaaliDateRange.split(','); // Split by comma if it's a string
                    } else if (Array.isArray(jalaaliDateRange)) {
                        dates = jalaaliDateRange; // Use it directly if it's an array
                    }
            
                    // Convert each date in the array to Unix timestamp
                    const unixDates = dates.map(date => 
                        moment(date.trim(), 'jYYYY/jMM/jDD').unix()
                    );
            
                    // Join the converted Unix timestamps back into a comma-separated string
                    return unixDates.join(',');
                } else {
                    return '0,0'; // Default if no date range is provided
                }
            },
            fetchRecords(page = this.currentPage, filters = {}) {
                if (page == 1) {
                    this.offset = 0;
                }else{
                    this.offset = (page - 1) * this.limit;
                }
                const unixDateRange = this.convertJalaaliToUnix(filters.dateRange || this.filterCriteria.dateRange);
                const formData = new URLSearchParams({
                    action: 'fetch_crm_records',
                    nonce: this.nonce,
                    offset: this.offset,
                    limit: this.limit,
                    // Add filter criteria
                    mineOnly: filters.mineOnly || this.filterCriteria.mineOnly,
                    newOnly: filters.newOnly || this.filterCriteria.newOnly,
                    userID: woocrCall.user_id,
                    orderID: filters.order_id ||this.filterCriteria.order_id,
                    customerName: filters.customerName ||this.filterCriteria.customerName,
                    dateRange: unixDateRange,
                    status: filters.status || this.filterCriteria.status,
                    limit: filters.limit || this.filterCriteria.limit
                }).toString();
            
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.records = data.data.records.map(record => {
                            try {
                                const ordersObj = this.parseOrders(record.orders);
                                return {
                                    ...record,
                                    orderKeys: Object.keys(ordersObj),
                                    orderValues: Object.values(ordersObj)
                                };
                            } catch (error) {
                                console.error('Error parsing orders JSON:', error);
                                return {
                                    ...record,
                                    orderKeys: [],
                                    orderValues: []
                                };
                            }
                        });
                        this.currentPage = page;
                        this.totalRecords = data.data.totalRecords;
                        this.totalPages = Math.ceil(data.data.totalRecords / this.limit);
                    } else {
                        console.error('Failed to fetch records:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
    
            applyFilters() {
                this.records = [];
                this.currentPage = 1;
                this.fetchRecords(this.currentPage, this.filterCriteria);
            },
    
            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.fetchRecords(this.currentPage + 1, this.filterCriteria);
                }
            },
    
            prevPage() {
                if (this.currentPage > 1) {
                    this.fetchRecords(this.currentPage - 1, this.filterCriteria);
                }
            },
    
            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.fetchRecords(page, this.filterCriteria);
                }
            },



            selectPattern(event) {
                // Get the pattern text from the clicked button's data attribute
                const patternText = event.target.getAttribute('data-text');
                this.modalForm.smsText = patternText;  // Insert the selected pattern text into the textarea
                this.showPatternsDropdown = false;  // Hide the pattern buttons after selecting a pattern
            },
            hidePatterns() {
                // If the textarea is empty, show the patterns dropdown again
                if (this.modalForm.smsText.length === 0) {
                    this.showPatternsDropdown = true;  // Show the pattern buttons when the textarea is empty
                } else {
                    this.showPatternsDropdown = false;  // Hide the pattern buttons when there is input
                }
            },
            parseOrders(orders) {
                try {
                    // If orders is a string, attempt to parse it as JSON
                    if (typeof orders === 'string') {
                        orders = JSON.parse(orders);
                    }
            
                    // Process if orders is an array
                    if (Array.isArray(orders)) {
                        return orders.reduce((acc, order) => {
                            if (order && typeof order.id !== 'undefined' && order.name) {
                                acc[String(order.id)] = order.name;
                            } else {
                                console.warn("Invalid order format", order);
                            }
                            return acc;
                        }, {});
                    }
                    // Process if orders is an object
                    else if (typeof orders === 'object' && orders !== null) {
                        return Object.entries(orders).reduce((acc, [key, value]) => {
                            acc[String(key)] = value;
                            return acc;
                        }, {});
                    } else {
                        console.error("Unexpected orders format", orders);
                        return {};
                    }
                } catch (e) {
                    console.error("Failed to parse orders", e);
                    return {};
                }
            },
            openModal(recordId, phase, customer_name, customer_phone) {
                this.fetchPhaseData(recordId, phase, customer_name, customer_phone);
            
                const record_self = this.records.find(r => r.id === recordId);
                if (record_self) {
                    this.modalrecord = {
                        ...record_self,
                        parsedOrders: record_self.parsedOrders || this.parseOrders(record_self.orders || '{}')
                    };
                    // this.showModal = true;
                } else {
                    console.error('Record not found');
                    this.modalrecord = null;
                    this.showModal = false;
                }
            },

            openModal_statusCheck(recordId) {
                this.fetchRowData(recordId).then(data => {
                    if (data) {
                        this.modalStatusData = {
                            ...data,
                            parsedOrders: this.parseOrders(data.user_orders)
                        };
                        this.showStatusModal = true;
                    } else {
                        console.error('Record not found');
                        this.modalStatusData = null;
                        this.showStatusModal = false;
                    }
                }).catch(error => {
                    console.error('Error fetching data:', error);
                    this.modalStatusData = null;
                    this.showStatusModal = false;
                });
            },

            fetchRowData(recordId) {
                const formData = new URLSearchParams({
                    action: 'fetch_singleRow',
                    record_id: recordId,
                    nonce: this.nonce
                }).toString();
        
                return fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        return data.data;
                    } else {
                        this.alertHandler(data.data);
                        return null;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    return null;
                });
            },

            closeModal() {
                this.rowUnlock(this.modalParrent.record);
                this.showModal = false;
                this.modalData = '';
                this.showPatternsDropdown = true;
                this.modalParrent = null;
                this.modalrecord = null;
                this.resetModalForm();
                this.isSMSSent = false; // Reset SMS sent status
            },
            closeStatusModal(){
                this.showStatusModal = false;
                this.modalDataStatus = '';
            },
            alertHandler(alert) {
                // Check if the alert is a string or an object
                if (typeof alert === 'string') {
                  window.alert(alert);
                } else if (typeof alert === 'object' && alert.message) {
                  // If it's an object, show the message property
                  window.alert(alert.message);
                } else {
                  // Default alert if something unexpected is passed
                  window.alert(alert);
                }
            },
            rowUnlock(recordId) {
                const formData = new URLSearchParams({
                    action: 'unlock_crm_row',
                    record_id: recordId,
                    nonce: this.nonce
                }).toString();
        
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // this.modalData = data.data;
                        this.showModal = false;
                        // this.fetchRecords();
                    } else {
                        this.alertHandler(data.data);
                        // console.error('Error fetching phase data:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            fetchPhaseData(recordId, phase, customer_name, customer_phone) {
                const formData = new URLSearchParams({
                    action: 'fetch_phase_data',
                    record_id:      recordId,
                    phase:          phase,
                    customerName:   customer_name,
                    customerPhone:  customer_phone,
                    nonce: this.nonce
                }).toString();
            
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.modalParrent = data.data;
                        this.modalData = data.data.data;
                        this.showModal = true;
                        // console.log(data.data.data);
                    } else {
                        // console.error('Error fetching phase data:', data.message);
                        this.alertHandler(data.data);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            
            resetModalForm() {
                this.modalForm.smsText = '';
                this.modalForm.userReaction = '';
                this.modalForm.adminComment = '';
            },
            sendSMS() {
                const formData = new URLSearchParams({
                    action: 'woocr_send_sms',  // Action name corresponding to your WordPress handler
                    smsText: this.modalForm.smsText,
                    nonce: this.nonce,
                    crm_rowID:    this.modalParrent.record,
                    crm_phase:    this.modalParrent.phase,
                    crm_userName: this.modalrecord.userName,
                }).toString();
        
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.isSMSSent = true; // Disable SMS button after sending
                        this.alertHandler(data.data);
                        // console.log('SMS sent successfully:', data.data);
                    } else {
                        this.alertHandler(data.data);
                        console.error('Error sending SMS:', data.data);
                        // Handle error, show notification, etc.
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    // Handle error, show notification, etc.
                });
            },

            handlePhaseSubmit() {
                const formData = new URLSearchParams({
                    action: 'woocr_update_phase_data',
                    nonce: this.nonce,
                    record_id: this.modalrecord.id,
                    phase: this.modalParrent.phase,
                    // sms_text: this.modalForm.smsText,
                    user_reaction:  this.modalForm.userReaction,
                    admin_reaction: this.modalForm.adminReaction,
                    admin_comment: this.modalForm.adminComment,
                    admin_id: woocrCall.user_id
                }).toString();
    
                fetch(woocrCall.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Phase data updated successfully');
                        this.alertHandler(data.data);
                        this.closeModal();
                    } else {
                        console.error('Error updating phase data:', data.message);
                        this.alertHandler(data.data);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
            },
            toggleNewOnly() {
                setTimeout(() => {
                    if (this.filterCriteria.newOnly === true) {
                        // this.filterCriteria.mineOnly = false;
                    }else{
                        // this.filterCriteria.mineOnly = true;
                    }
                }, 0);
            },
            toggleMineOnly() {
                setTimeout(() => {
                    if (this.filterCriteria.mineOnly === true) {
                        // this.filterCriteria.newOnly = false;
                    }else{
                        // this.filterCriteria.newOnly = true;
                    }
                }, 0); // 1000 milliseconds = 1 second
            },
            toggleUserReaction() {
                setTimeout(() => {
                    if (this.modalForm.userReaction === 'noAnswer') {
                        // this.modalForm.adminReaction = null;
                        // this.isAdminReactionDisabled = true;
                    } else {
                        // this.isAdminReactionDisabled = false;
                    }
                }, 100);
            }
        },
        components: {
            rtdatepicker: Vue3PersianDatetimePicker
        },
    }).mount('#crm-records');
});

document.addEventListener('DOMContentLoaded', function () {
    // Find the vpd-wrapper div
const vpdWrapper = document.querySelector('.vpd-wrapper.vpd-dir-rtl.vpd-is-range.vpd-is-inline.vpd-no-footer');

// Find the date-selection div
const dateSelection = document.querySelector('.date-selection');

// Check if both elements exist
if (vpdWrapper && dateSelection) {
  // Remove the vpd-wrapper from its current position
  vpdWrapper.parentElement.removeChild(vpdWrapper);
  
  // Append it to the date-selection div at the end
  dateSelection.appendChild(vpdWrapper);
}

});