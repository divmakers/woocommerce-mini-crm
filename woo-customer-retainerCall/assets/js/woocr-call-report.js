document.addEventListener('DOMContentLoaded', function () {

    const today = moment(); // Current date
    const lastweek = moment().subtract(7, 'days'); // Date from 7 days ago
    const formattedToday = today.format('jYYYY/jMM/jDD');
    const formattedLastweek = lastweek.format('jYYYY/jMM/jDD');

    const { createApp } = Vue;
    createApp({
        data() {
            return {
                currentSortColumn: 'creditHolder',
                currentSortOrder: 'asc',
                groupedRecords: {},
                nonce: woocrCall.nonce,
                showStatusModal: false,
                modalStatusData: {},
                modalrecord: {},
                currentPage: 1,
                totalPages: 0,
                totalRecords: 0,
                offset: 0,
                filterCriteria: {
                    limit: 10000,
                    dateRange: [formattedLastweek, formattedToday],
                    status: 'all'
                }
            }
        },
        created() {
            this.fetchRecords();
        },
        methods: {
            exportToCSV() {
                // Define the headers for the CSV file
                const headers = [
                    'کارشناس تماس', 'مجموع', 'آماده', 'بی پاسخ', 'سرد', 'گرم', 'داغ', 'نرخ موفقیت کل', 'نرخ موفقیت تماس وصل شده', 'پرداخت شده', 'جمع مبلغ فروش'
                ];
            
                // Convert the table data into CSV format
                let csvContent = headers.join(',') + '\n'; // Add headers
            
                Object.keys(this.groupedRecords).forEach(creditHolder => {
                    const row = [
                    creditHolder === 'Unassigned' ? 'بدون کارشناس' : creditHolder,  // Column 1: creditHolder
                    this.creditHolderStats[creditHolder].totalRecords || 0,           // Column 2: totalRecords
                    this.creditHolderStats[creditHolder].readyCount || 0,             // Column 3: readyCount
                    this.creditHolderStats[creditHolder].noAnswerCount || 0,          // Column 4: noAnswerCount
                    this.creditHolderStats[creditHolder].coldCount || 0,              // Column 5: coldCount
                    this.creditHolderStats[creditHolder].warmCount || 0,              // Column 6: warmCount
                    this.creditHolderStats[creditHolder].hotCount || 0,               // Column 7: hotCount
                    this.creditHolderStats[creditHolder].successPercentage + '%' || '0%', // Column 8: successPercentage
                    this.creditHolderStats[creditHolder].successPercentageConnected + '%' || '0%', // Column 9: successPercentageConnected
                    this.creditHolderStats[creditHolder].paidCount || 0,              // Column 10: paidCount
                    this.creditHolderStats[creditHolder].totalSell || 0  // Column 11: totalSell
                    ];
            
                    csvContent += row.join(',') + '\n'; // Add each row as a CSV line
                });
            
                // UTF-8 BOM (Byte Order Mark) to ensure proper encoding in Excel
                const BOM = '\uFEFF';
            
                // Create a Blob with the CSV content and proper encoding
                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            
                // Trigger file download using an anchor element
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.setAttribute('download', 'گزارش CRM.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                },
                formattedCsvPrice(price) {
                // This is your existing formattedPrice method, which should return a formatted price
                return price ? price.toLocaleString() : 0;
                },
            sortTable(column) {
                // If the same column is clicked, toggle the sort order
                if (this.currentSortColumn === column) {
                  this.currentSortOrder = this.currentSortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                  // Set the new column and default to ascending order
                  this.currentSortColumn = column;
                  this.currentSortOrder = 'asc';
                }
              },
              getValueForSort(creditHolder) {
                const column = this.currentSortColumn;
                let value = this.creditHolderStats[creditHolder][column];
            
                if (typeof value === 'string' && value.includes('%')) {
                    value = parseFloat(value.replace('%', ''));
                }
                if (typeof value === 'string' && value.includes('تومان')) {
                    const hiddenSpan = document.querySelector('.hidden-span');
                    if (hiddenSpan) {
                        value = parseInt(hiddenSpan.textContent, 10); // Convert to an integer
                    }
                }
                return value !== null && value !== undefined ? value : '';
            },
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
                    action: 'fetch_crm_records_report',
                    nonce: this.nonce,
                    offset: this.offset,
                    limit: this.limit,
                    // Add filter criteria
                    userID: woocrCall.user_id,
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
                        this.groupRecords(data.data.records);
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
            groupRecords(records) {
                this.groupedRecords = records.reduce((acc, record) => {
                    const creditHolder = record.creditHolderName || 'Unassigned';
                    if (!acc[creditHolder]) {
                        acc[creditHolder] = [];
                    }
                    acc[creditHolder].push(record);
                    return acc;
                }, {});
            },
            applyFilters() {
                this.records = [];
                this.currentPage = 1;
                this.fetchRecords(this.currentPage, this.filterCriteria);
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

            formattedPrice(value) {
                let currency = 'تومان';
                if (value >= 1000000) {
                    value /= 1000000;
                    currency = 'میلیون تومان';
                } else if (value >= 1000) {
                    value /= 1000;
                    currency = 'هزار تومان';
                }
                return `${value.toFixed(0)} ${currency}`; // Format the number without decimals
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
            getAdminFeedback(record) {
                // Function to extract admin feedback from any of the phases
                if (record.phaseV && record.phaseV.status && record.phaseV.status.admin_feedback) return record.phaseV.status.admin_feedback;
                if (record.phaseIV && record.phaseIV.status && record.phaseIV.status.admin_feedback) return record.phaseIV.status.admin_feedback;
                if (record.phaseIII && record.phaseIII.status && record.phaseIII.status.admin_feedback) return record.phaseIII.status.admin_feedback;
                if (record.phaseII && record.phaseII.status && record.phaseII.status.admin_feedback) return record.phaseII.status.admin_feedback;
                if (record.phaseI && record.phaseI.status && record.phaseI.status.admin_feedback) return record.phaseI.status.admin_feedback;
                return 'unknown'; // Default if no feedback is found
            },
            getUserReaction(record){
                // Function to extract admin feedback from any of the phases
                if (record.phaseV && record.phaseV.status && record.phaseV.status.user_reaction) return record.phaseV.status.user_reaction;
                if (record.phaseIV && record.phaseIV.status && record.phaseIV.status.user_reaction) return record.phaseIV.status.user_reaction;
                if (record.phaseIII && record.phaseIII.status && record.phaseIII.status.user_reaction) return record.phaseIII.status.user_reaction;
                if (record.phaseII && record.phaseII.status && record.phaseII.status.user_reaction) return record.phaseII.status.user_reaction;
                if (record.phaseI && record.phaseI.status && record.phaseI.status.user_reaction) return record.phaseI.status.user_reaction;
                return 'unknown'; // Default if no feedback is found
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
        },
        computed: {
            creditHolderStats() {
                const stats = {};
                
                // Iterate through the grouped records to calculate stats
                for (const [creditHolder, records] of Object.entries(this.groupedRecords)) {
                    const totalRecords = records.length;
                    const paidCount = records.filter(r => r.status.startsWith('paid')).length;
                    const noAnswerCount = records.filter(r => this.getUserReaction(r) === 'noAnswer').length;
                    
                    // Calculate totalSell
                    let totalSell = 0;
        
                    // Accumulate lastOrderTotal for paid records
                    for (const record of records) {
                        if (record.status.startsWith('paid')) {
                            totalSell += parseFloat(record.lastOrderTotal) || 0; // Default to 0 if parsing fails
                        }
                    }
        
                    // Initialize stats for each credit holder
                    stats[creditHolder] = {
                        totalRecords,
                        notReadyCount: records.filter(r => r.status === 'notReady').length,
                        readyCount: records.filter(r => r.status === 'ready').length,
                        callingCount: records.filter(r => r.status === 'calling').length,
                        endCount: records.filter(r => r.status === 'end').length,
                        paidCount,
                        noAnswerCount,
                        hotCount: records.filter(r => this.getAdminFeedback(r) === 'hot').length,
                        coldCount: records.filter(r => this.getAdminFeedback(r) === 'cold').length,
                        warmCount: records.filter(r => this.getAdminFeedback(r) === 'warm').length,
                        noAnswerPercent: totalRecords > noAnswerCount ? ((noAnswerCount / totalRecords) * 100).toFixed(0) : 0,
                        totalSell, // Add the totalSell calculated
                        successPercentage: totalRecords > 0 ? ((paidCount / totalRecords) * 100).toFixed(2) : 0,
                        successPercentageConnected: totalRecords > noAnswerCount ? ((paidCount / (totalRecords - noAnswerCount)) * 100).toFixed(2) : 0,
                        
                    };
                }
        
                // Ensure there's a default entry for 'Unassigned'
                if (!stats['Unassigned']) {
                    stats['Unassigned'] = {
                        totalRecords: 0,
                        notReadyCount: 0,
                        readyCount: 0,
                        callingCount: 0,
                        endCount: 0,
                        paidCount: 0,
                        noAnswerCount: 0,
                        noAnswerPercent:0,
                        hotCount: 0,
                        coldCount: 0,
                        warmCount: 0,
                        totalSell: 0,
                        successPercentage: 0,
                        successPercentageConnected: 0,
                    };
                }
        
                return stats;
            },
            sortedRecords() {
                let sortedRecords = Object.keys(this.groupedRecords);
            
                if (this.currentSortColumn) {
                    sortedRecords.sort((a, b) => {
                        let aValue = this.getValueForSort(a);
                        let bValue = this.getValueForSort(b);
            
                        // Handle cases where values are not numbers or strings
                        if (typeof aValue === 'string') aValue = aValue.toLowerCase();
                        if (typeof bValue === 'string') bValue = bValue.toLowerCase();
            
                        // Sorting logic (ascending or descending)
                        if (this.currentSortOrder === 'asc') {
                            return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
                        } else {
                            return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
                        }
                    });
                }
            
                // Return sorted groupedRecords as an object
                return sortedRecords.reduce((obj, key) => {
                    obj[key] = this.groupedRecords[key];
                    return obj;
                }, {})
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