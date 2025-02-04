<?php $current_user = get_current_user(); ?>


<div class="wrap woocr_callCRM">
    <div id="crm-records">
        <!-- Filter inputs -->
        <div class="date-selection">
            <div class="fields">
                <div class="items-top">
                    <h1><?php _e('System Reports', 'woo-cr'); ?></h1>

                    <rtdatepicker color="#2271b1" inline auto-submit append-to="date-selection" auto-submit v-model="filterCriteria.dateRange" range></rtdatepicker>
                    <button class="button-primary" @click="exportToCSV">
                        <?php _e('Export to Excel', 'woo-cr'); ?>
                    </button>
                    <button class="button-primary" @click="applyFilters">
                        <?php _e('Apply Date Range', 'woo-cr'); ?>
                    </button>
                    <!-- Pagination controls -->
                    <div class="text-center">{{ totalRecords }} <?php _e('Records Found', 'woo-cr'); ?></div>
                </div>
            </div>
        </div>

    <!-- Records table -->
    <table id="report-table" class="wp-list-table widefat fixed striped table-view-list">
    <thead>
      <tr>
        <th class="text-center" @click="sortTable('creditHolder')">
          کارشناس تماس
          <span v-if="currentSortColumn === 'creditHolder'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="75px" class="text-center" @click="sortTable('totalRecords')">
          مجموع
          <span v-if="currentSortColumn === 'totalRecords'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="50px" class="text-center" @click="sortTable('readyCount')">
          آماده
          <span v-if="currentSortColumn === 'readyCount'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="75px" class="text-center" @click="sortTable('noAnswerPercent')">
          بی پاسخ
          <span v-if="currentSortColumn === 'noAnswerPercent'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="50px" class="text-center" @click="sortTable('coldCount')">
          سرد
          <span v-if="currentSortColumn === 'coldCount'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="50px" class="text-center" @click="sortTable('warmCount')">
          گرم
          <span v-if="currentSortColumn === 'warmCount'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="50px" class="text-center" @click="sortTable('hotCount')">
          داغ
          <span v-if="currentSortColumn === 'hotCount'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="135px" class="text-center" @click="sortTable('successPercentage')">
          نرخ موفقیت کل
          <span v-if="currentSortColumn === 'successPercentage'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="195px" class="text-center" @click="sortTable('successPercentageConnected')">
          نرخ موفقیت تماس وصل شده
          <span v-if="currentSortColumn === 'successPercentageConnected'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="80px" class="text-center" @click="sortTable('paidCount')">
          پرداخت شده
          <span v-if="currentSortColumn === 'paidCount'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
        <th width="175px" class="text-center" @click="sortTable('totalSell')">
          جمع مبلغ فروش
          <span v-if="currentSortColumn === 'totalSell'">{{ currentSortOrder === 'asc' ? '⏶' : '⏷' }}</span>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(records, creditHolder) in sortedRecords" :key="creditHolder">
        <td class="text-center">{{ creditHolder === 'Unassigned' ? 'بدون کارشناس' : creditHolder }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].totalRecords }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].readyCount }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].noAnswerCount }} ❖ <b>{{ creditHolderStats[creditHolder].noAnswerPercent }}%</b></td>
        <td class="text-center">{{ creditHolderStats[creditHolder].coldCount }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].warmCount }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].hotCount }}</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].successPercentage }}%</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].successPercentageConnected }}%</td>
        <td class="text-center">{{ creditHolderStats[creditHolder].paidCount }}</td>
        <td class="text-center">
          <span>{{ formattedPrice(creditHolderStats[creditHolder].totalSell) }}
          <span v-if="creditHolderStats[creditHolder].totalSell" class="hidden-span">{{ creditHolderStats[creditHolder].totalSell }}</span>
        </td>
      </tr>
    </tbody>
  </table>
    <!-- <div v-for="(records, creditHolder) in groupedRecords" :key="creditHolder" class="credit-holder-group">
        <h2>{{ creditHolder === 'Unassigned' ? 'Unassigned' : `ردیف های تفکیکی کارشناس: ${creditHolder}` }}</h2>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr class="text-center">
                    <th width="45px">شناسه</th>
                    <th width="175px">نام مشتری</th>
                    <th width="130px">آخرین آپدیت ردیف</th>
                    <th>منبع لید</th>
                    <th width="170px">آخرین وضعیت لید</th> 
                    <th>سفارشات</th>
                    <th width="120px">تجمیع ردیف</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in records" :key="record.id" :id="record.id" :class="
                        {'green-row': ['paid', 'paid-notReady', 'paid-ready', 'paid-calling', 'paid-end'].includes(record.status)}
                    ">
                    <td class="text-center">{{ record.id }}</td>
                    <td class="text-center">{{ record.userName }}</td>
                    <td class="text-center">{{ convertUnixToJalaali(record.date) }}</td>
                    <td class="text-center"
                        :class="{
                            'normal-source'  : record.source && ['', 'campaign-didogram'].includes(record.source),
                            'webinar-source' : record.source && ['webinar'].includes(record.source),
                            'porsline-source': record.source && ['porsline'].includes(record.source),
                        }">
                        <span>{{ leadSourceTranslate(record.source) }}</span>
                    </td>
                    <td class="text-center">
                        <div class="row">کیفیت لید: 
                            <span      v-if="record.phaseV.status.admin_feedback">  {{ admin_feedbackTranslate(record.phaseV.status.admin_feedback) }}</span>
                            <span v-else-if="record.phaseIV.status.admin_feedback"> {{ admin_feedbackTranslate(record.phaseIV.status.admin_feedback) }}</span>
                            <span v-else-if="record.phaseIII.status.admin_feedback">{{ admin_feedbackTranslate(record.phaseIII.status.admin_feedback) }}</span>
                            <span v-else-if="record.phaseII.status.admin_feedback"> {{ admin_feedbackTranslate(record.phaseII.status.admin_feedback) }}</span>
                            <span v-else-if="record.phaseI.status.admin_feedback">  {{ admin_feedbackTranslate(record.phaseI.status.admin_feedback) }}</span>
                            <span v-else>{{ admin_feedbackTranslate() }}</span>
                        </div>
                        <div class="row">نتیجه تماس: 
                            <span      v-if="record.phaseV.status.user_reaction">  {{ user_feedbackTranslate(record.phaseV.status.user_reaction) }}</span>
                            <span v-else-if="record.phaseIV.status.user_reaction"> {{ user_feedbackTranslate(record.phaseIV.status.user_reaction) }}</span>
                            <span v-else-if="record.phaseIII.status.user_reaction">{{ user_feedbackTranslate(record.phaseIII.status.user_reaction) }}</span>
                            <span v-else-if="record.phaseII.status.user_reaction"> {{ user_feedbackTranslate(record.phaseII.status.user_reaction) }}</span>
                            <span v-else-if="record.phaseI.status.user_reaction">  {{ user_feedbackTranslate(record.phaseI.status.user_reaction) }}</span>
                            <span v-else>{{ user_feedbackTranslate() }}</span>
                        </div>
                        <div class="row">
                            <span      v-if="record.status">  {{ record_statusTranslate(record.status) }}</span>
                            <span v-else>{{ record.status }}</span>
                        </div>
                    </td>
                    <td class="text-center orders_list">
                        <ul>
                            <li v-for="(value, key) in parseOrders(record.orders)" :key="key">
                                <span>{{ value }}</span>
                                <a v-if="key != '' && !key.startsWith('temp_')" class="hidden-span" :href="`<?php echo get_site_url(); ?>/wp-admin/post.php?post=${key}&action=edit`">{{ key }}</a>
                            </li>
                        </ul>
                    </td>
                    <td class="text-center phasingLookup">
                        <button :id="record.id" class="button-secondary" @click="openModal_statusCheck(record.id)">نمایش اطلاعات</button>
                    </td>
                </tr>
            </tbody>
        </table> -->


        <!-- <div v-if="showStatusModal" id="status-popup" class="modal status-popup">
            <div @click="closeStatusModal" class="backdrop"></div>
            <div class="modal-content">
                <span class="close" @click="closeStatusModal">&times;</span>
                
                <p v-if="modalStatusData" class="text-center">
                    شما در حال مشاهده
                    رکورد "<strong id="modalDataRecord">{{ modalStatusData.retain_id || 'نامشخص' }}</strong>"
                    از داده‌های بازیابی تلفنی هستید.
                </p>

                <div id="phase-details">
                    <div class="user_details">
                        <p><strong>نام کاربر:</strong> {{ modalStatusData.user_name || 'نامشخص' }}</p>
                        <p><strong>شماره تماس:</strong> {{ modalStatusData.user_phone || 'نامشخص' }}</p>
                        <p><strong>مرجع لید:</strong> {{ modalStatusData.source || 'نامشخص' }}</p>
                        <p><strong>آخرین آپدیت ردیف:</strong> {{ convertUnixToJalaali(modalStatusData.retain_unix) }}</p>

                        <div class="text-center">
                            <strong>لیست سفارشات مشتری</strong>
                            <ul>
                                <li v-for="(name, id) in modalStatusData.parsedOrders" :key="id">
                                    <a v-if="!id.startsWith('temp_')" :href="`<?php echo get_site_url(); ?>/wp-admin/post.php?post=${id}&action=edit`">
                                        {{ id }} : {{ name }}
                                    </a>
                                    <span v-else>{{ name }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="text-center">
                            <strong>مدیریت رکورد</strong>
                            <span>وضعیت فعلی رکورد: <b>{{ record_statusTranslate(modalStatusData.overall_status) }}</b></span>
                        </div>
                    </div>

                    <br>

                    <div class="phase-container">
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr class="text-center">
                                    <th>تماس</th>
                                    <th>وضعیت فاز</th>
                                    <th>نظر ادمین</th>
                                    <th>بازخورد یوزر</th>
                                    <th>متن پیامک</th>
                                    <th>کیفیت لید</th>
                                    
                                    <th>پیگیری</th>
                                    <th>پیامک ارسال شد در</th>
                                    <th>پایان فاز</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="modalStatusData.lvl1">
                                    <td>تماس 1</td>
                                    <td>{{ phase_statusTranslate(modalStatusData.lvl1.status.phaseStatus) }}</td>
                                    <td>{{ modalStatusData.lvl1.data.creditHolderComment }}</td>
                                    <td>{{ user_feedbackTranslate(modalStatusData.lvl1.status.user_reaction) }}</td>
                                    <td>{{ modalStatusData.lvl1.data.smsText }}</td>
                                    <td>{{ phase_leadTypeTranslate(modalStatusData.lvl1.status.admin_feedback) }}</td>
                                    <td>{{ modalStatusData.lvl1.timing.phaseStart === '0' ? 'شروع نشده' : convertUnixToJalaali(modalStatusData.lvl1.timing.phaseStart) }}</td>
                                    <td>{{ modalStatusData.lvl1.timing.smsSent === '0' ? 'ارسال نشده' : convertUnixToJalaali(modalStatusData.lvl1.timing.smsSent) }}</td>
                                    <td>{{ modalStatusData.lvl1.timing.phaseEnd === '0' ? 'پایان نیافته' : convertUnixToJalaali(modalStatusData.lvl1.timing.phaseEnd) }}</td>
                                </tr>

                                <tr v-if="modalStatusData.lvl2">
                                    <td>تماس 2</td>
                                    <td>{{ phase_statusTranslate(modalStatusData.lvl2.status.phaseStatus) }}</td>
                                    <td>{{ modalStatusData.lvl2.data.creditHolderComment }}</td>
                                    <td>{{ user_feedbackTranslate(modalStatusData.lvl2.status.user_reaction) }}</td>
                                    <td>{{ modalStatusData.lvl2.data.smsText }}</td>
                                    <td>{{ phase_leadTypeTranslate(modalStatusData.lvl2.status.admin_feedback) }}</td>
                                    <td>{{ modalStatusData.lvl2.timing.phaseStart === '0' ? 'شروع نشده' : convertUnixToJalaali(modalStatusData.lvl2.timing.phaseStart) }}</td>
                                    <td>{{ modalStatusData.lvl2.timing.smsSent === '0' ? 'ارسال نشده' : convertUnixToJalaali(modalStatusData.lvl2.timing.smsSent) }}</td>
                                    <td>{{ modalStatusData.lvl2.timing.phaseEnd === '0' ? 'پایان نیافته' : convertUnixToJalaali(modalStatusData.lvl2.timing.phaseEnd) }}</td>
                                </tr>

                                <tr v-if="modalStatusData.lvl3">
                                    <td>تماس 3</td>
                                    <td>{{ phase_statusTranslate(modalStatusData.lvl3.status.phaseStatus) }}</td>
                                    <td>{{ modalStatusData.lvl3.data.creditHolderComment }}</td>
                                    <td>{{ user_feedbackTranslate(modalStatusData.lvl3.status.user_reaction) }}</td>
                                    <td>{{ modalStatusData.lvl3.data.smsText }}</td>
                                    <td>{{ phase_leadTypeTranslate(modalStatusData.lvl3.status.admin_feedback) }}</td>
                                    <td>{{ modalStatusData.lvl3.timing.phaseStart === '0' ? 'شروع نشده' : convertUnixToJalaali(modalStatusData.lvl3.timing.phaseStart) }}</td>
                                    <td>{{ modalStatusData.lvl3.timing.smsSent === '0' ? 'ارسال نشده' : convertUnixToJalaali(modalStatusData.lvl3.timing.smsSent) }}</td>
                                    <td>{{ modalStatusData.lvl3.timing.phaseEnd === '0' ? 'پایان نیافته' : convertUnixToJalaali(modalStatusData.lvl3.timing.phaseEnd) }}</td>
                                </tr>

                                <tr v-if="modalStatusData.lvl4">
                                    <td>تماس 4</td>
                                    <td>{{ phase_statusTranslate(modalStatusData.lvl4.status.phaseStatus) }}</td>
                                    <td>{{ modalStatusData.lvl4.data.creditHolderComment }}</td>
                                    <td>{{ user_feedbackTranslate(modalStatusData.lvl4.status.user_reaction) }}</td>
                                    <td>{{ modalStatusData.lvl4.data.smsText }}</td>
                                    <td>{{ phase_leadTypeTranslate(modalStatusData.lvl4.status.admin_feedback) }}</td>
                                    <td>{{ modalStatusData.lvl4.timing.phaseStart === '0' ? 'شروع نشده' : convertUnixToJalaali(modalStatusData.lvl4.timing.phaseStart) }}</td>
                                    <td>{{ modalStatusData.lvl4.timing.smsSent === '0' ? 'ارسال نشده' : convertUnixToJalaali(modalStatusData.lvl4.timing.smsSent) }}</td>
                                    <td>{{ modalStatusData.lvl4.timing.phaseEnd === '0' ? 'پایان نیافته' : convertUnixToJalaali(modalStatusData.lvl4.timing.phaseEnd) }}</td>
                                </tr>

                                <tr v-if="modalStatusData.lvl5">
                                    <td>تماس 5</td>
                                    <td>{{ phase_statusTranslate(modalStatusData.lvl5.status.phaseStatus) }}</td>
                                    <td>{{ modalStatusData.lvl5.data.creditHolderComment }}</td>
                                    <td>{{ user_feedbackTranslate(modalStatusData.lvl5.status.user_reaction) }}</td>
                                    <td>{{ modalStatusData.lvl5.data.smsText }}</td>
                                    <td>{{ phase_leadTypeTranslate(modalStatusData.lvl5.status.admin_feedback) }}</td>
                                    <td>{{ modalStatusData.lvl5.timing.phaseStart === '0' ? 'شروع نشده' : convertUnixToJalaali(modalStatusData.lvl5.timing.phaseStart) }}</td>
                                    <td>{{ modalStatusData.lvl5.timing.smsSent === '0' ? 'ارسال نشده' : convertUnixToJalaali(modalStatusData.lvl5.timing.smsSent) }}</td>
                                    <td>{{ modalStatusData.lvl5.timing.phaseEnd === '0' ? 'پایان نیافته' : convertUnixToJalaali(modalStatusData.lvl5.timing.phaseEnd) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> -->

    </div>
</div>

<!-- Report page titles and headers -->
<?php
$report_labels = [
    'title' => 'System Reports',                    // گزارشات سیستم
    'date_range' => 'Date Range',                   // بازه زمانی
    'export_excel' => 'Export to Excel',            // خروجی اکسل
    'apply_filters' => 'Apply Filters',             // اعمال فیلترها
    'records_found' => 'Records Found',             // رکورد یافت شد
    'expert_name' => 'Expert Name',                 // نام کارشناس
    'total_calls' => 'Total Calls',                 // مجموع تماس‌ها
    'success_rate' => 'Success Rate',               // نرخ موفقیت
    'total_sales' => 'Total Sales'                  // مجموع فروش
];