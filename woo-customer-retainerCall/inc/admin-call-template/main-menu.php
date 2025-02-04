<tr?php



$current_user = get_current_user();
?>


<div class="wrap woocr_callCRM">
    <div id="crm-records">
        <!-- Filter inputs -->
        <div class="date-selection">
            <div class="fields">
                <div class="items-top">
                    <h1><?php _e('CRM Phone Follow-up System', 'woo-cr'); ?></h1>
                    <rtdatepicker color="#2271b1" inline auto-submit append-to="date-selection" auto-submit v-model="filterCriteria.dateRange" range></rtdatepicker>

                    <select v-model="filterCriteria.status">
                        <option value="all"><?php _e('All Statuses', 'woo-cr'); ?></option>
                        <option value="paid"><?php _e('Paid Records', 'woo-cr'); ?></option>
                        <option value="notPaid"><?php _e('Unpaid Records', 'woo-cr'); ?></option>
                    </select>
                    <input type="number" v-model="filterCriteria.order_id" placeholder="جستجو بر حسب آیدی سفارش" disabled>
                    <input type="text" v-model="filterCriteria.customerName" placeholder="جستجو بر اساس نام مشتری">

                    <div class="switch-container">
                        <span>فقط لیدهای من</span>
                        <label class="switch">
                            <input v-model="filterCriteria.mineOnly" type="checkbox" @change="toggleMineOnly">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="switch-container">
                        <span>فقط لیدهای جدید</span>
                        <label class="switch">
                            <input v-model="filterCriteria.newOnly" type="checkbox" @change="toggleNewOnly">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="items-bottom">
                    <select v-model="filterCriteria.limit" disabled>
                        <!-- <option value="2">تعداد برای تست (2 عدد)</option> -->
                        <option value="20">تعداد رکورد (پیشفرض 20)</option>
                        <option value="50">50 در هر صفحه</option>
                        <option value="100">100 در هر صفحه</option>
                        <option value="150">150 در هر صفحه</option>
                        <option value="200">200 در هر صفحه</option>
                    </select>
                    <button class="button-primary" @click="applyFilters">اعمال فیلتر‌ها</button>
                    <!-- Pagination controls -->
                    <div class="text-center">در حال نمایش صفحه {{ currentPage }} از {{ totalPages }} کل صفحات</div>
                    <div class="text-center">{{ totalRecords }} رکورد پیدا شده</div>
                    <div class="pagination">
                        <button class="button-primary" @click="prevPage" :disabled="currentPage === 1">رفتن به صفحه قبل</button>
                        <button class="button-primary" @click="nextPage" :disabled="currentPage === totalPages">رفتن به صفحه بعد</button>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Records table -->
        <div v-if="!records || records.length === 0" class="nothing-found">رکوردی مطابق با جستجوی شما پیدا نشد...</div>
        <table v-else class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr class="text-center">
                    <th width="45px">شناسه</th>
                    <th width="175px">نام مشتری</th>
                    <!-- <th width="200px">ایجاد کننده</th> -->
                    <th width="130px">آخرین آپدیت ردیف</th>
                    <!-- <th>کارشناسِ تماس</th> -->
                    <th>منبع لید</th>
                    <!-- <th width="170px">آخرین وضعیت لید</th> -->
                    <th>سفارشات</th>
                    <th>تماس I</th>
                    <th>تماس II</th>
                    <th>تماس III</th>
                    <th>تماس IV</th>
                    <th>تماس V</th>
                    <th width="120px">تجمیع ردیف</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="record in records" :key="record.id" :id="record.id" :class="
                        {'green-row': ['paid', 'paid-notReady', 'paid-ready', 'paid-calling', 'paid-end'].includes(record.status)}
                    ">
                    <td class="text-center">{{ record.id }}</td>
                    <td class="text-center">{{ record.userName }}</td>
                    <!-- <td class="text-center">{{ record.creatorName }}<span class="hidden-creator-name">{{ record.creatorId }}</span></td> -->
                    <td class="text-center">{{ convertUnixToJalaali(record.date) }}</td>
                    <!-- <td class="text-center" v-if="record.creditHolder">{{ record.creditHolderName }}<span class="hidden-creator-name">{{ record.creditHolder }}</span></td>
                    <td class="text-center" v-else>بدون پیگیری<span class="hidden-creator-name">امتیاز رکورد، برای آخرین کارشناسی که پیش از پرداخت، بازیابی داشته است، ثبت می‌گردد.</span></td> -->
                    <td class="text-center"
                        :class="{
                            'normal-source'  : record.source && ['', 'campaign-didogram'].includes(record.source),
                            'webinar-source' : record.source && ['webinar'].includes(record.source),
                            'porsline-source': record.source && ['porsline'].includes(record.source),
                        }">
                        <span>{{ leadSourceTranslate(record.source) }}</span>
                    </td>
                    <!-- <td class="text-center">
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
                    </td> -->
                    <td class="text-center orders_list">
                        <ul>
                            <li v-for="(value, key) in parseOrders(record.orders)" :key="key">
                                <span>{{ value }}</span>
                                <a v-if="key != '' && !key.startsWith('temp_')" class="hidden-span" :href="`<?php echo get_site_url(); ?>/wp-admin/post.php?post=${key}&action=edit`">{{ key }}</a>
                            </li>
                        </ul>
                    </td>
                    <td
                    class="text-center phasing"
                    :class="[
                        record.phaseI.status.admin_feedback,
                        record.phaseI.status.user_reaction,
                        {
                        'green'             : record.phaseI.status.user_reaction == 'paid',
                        'green otherNumber' : record.phaseI.status.user_reaction == 'paid-otherNumber',
                        'yellow'            : record.phaseI.status.user_reaction == 'noAnswer',
                        'red'               : record.phaseI.status.user_reaction == 'notWilling',
                        'purple'            : record.phaseI.status.user_reaction == 'isWilling',
                        'tg-blue'           : record.phaseI.status.user_reaction == 'proceed-telegram'
                        }
                        ]"
                    v-if="record.status == 'ready'">
                        <div class="modulePhase">
                            <button      v-if="record.phaseI.status.phaseStatus == 'ready'"         class="button-primary" @click="openModal(record.id, 'phaseI', record.userName, record.useruserPhone)">شروع پیگیری</button>
                            <button v-else-if="record.phaseI.status.phaseStatus == 'done'"          class="button-secondary" @click="openModal(record.id, 'phaseI', record.userName, record.useruserPhone)">{{ record.phaseI.data.creditHolderName }}</button>
                            <button v-else-if="record.phaseI.status.phaseStatus == 'processing'"    class="button-secondary" @click="openModal(record.id, 'phaseI', record.userName, record.useruserPhone)">اعلام نتیجه</button>
                            <button v-else disabled                                                 class="button-primary">در انتظار</button>
                            <br>
                            <span        v-if="record.phaseI.status.phaseStatus == 'done' && record.phaseI.timing.phaseEnd">{{ record.phaseI.timing.timeDiff }}</span>
                        </div>
                    </td>
                    <td
                    :class="[
                        record.phaseII.status.admin_feedback,
                        record.phaseII.status.user_reaction,
                        {
                        'green'             : record.phaseII.status.user_reaction == 'paid',
                        'green otherNumber' : record.phaseII.status.user_reaction == 'paid-otherNumber',
                        'yellow'            : record.phaseII.status.user_reaction == 'noAnswer',
                        'red'               : record.phaseII.status.user_reaction == 'notWilling',
                        'purple'            : record.phaseII.status.user_reaction == 'isWilling',
                        'tg-blue'           : record.phaseII.status.user_reaction == 'proceed-telegram'
                        }
                    ]"
                    v-if="record.status == 'ready'" class="text-center phasing">
                        <div class="modulePhase">
                            <button      v-if="record.phaseI.status.phaseStatus == 'done'
                                               && record.phaseII.status.phaseStatus != 'done'
                                               && record.phaseII.timing.smsSent == '0'"             class="button-primary" @click="openModal(record.id, 'phaseII', record.userName, record.userPhone)">شروع پیگیری</button>
                            <button v-else-if="record.phaseII.status.phaseStatus == 'done'"         class="button-secondary" @click="openModal(record.id, 'phaseII', record.userName, record.userPhone)">{{ record.phaseII.data.creditHolderName }}</button>
                            <button v-else-if="record.phaseII.status.phaseStatus == 'processing'"   class="button-secondary" @click="openModal(record.id, 'phaseII', record.userName, record.userPhone)">اعلام نتیجه</button>
                            <button v-else disabled                                                 class="button-primary">در انتظار</button>
                            <br>
                            <span        v-if="record.phaseII.status.phaseStatus == 'done' && record.phaseII.timing.phaseEnd">{{ record.phaseII.timing.timeDiff }}</span>
                        </div>
                    </td>
                    <td
                    :class="[
                        record.phaseIII.status.admin_feedback,
                        record.phaseIII.status.user_reaction,
                    {
                        'green'             : record.phaseIII.status.user_reaction == 'paid',
                        'green otherNumber' : record.phaseIII.status.user_reaction == 'paid-otherNumber',
                        'yellow'            : record.phaseIII.status.user_reaction == 'noAnswer',
                        'red'               : record.phaseIII.status.user_reaction == 'notWilling',
                        'purple'            : record.phaseIII.status.user_reaction == 'isWilling',
                        'tg-blue'           : record.phaseIII.status.user_reaction == 'proceed-telegram'
                        }
                    ]"
                    v-if="record.status == 'ready'" class="text-center phasing">
                        <div class="modulePhase">
                            <button       v-if="record.phaseII.status.phaseStatus == 'done'
                                                && record.phaseIII.status.phaseStatus != 'done'
                                                && record.phaseIII.timing.smsSent == '0'"           class="button-primary" @click="openModal(record.id, 'phaseIII', record.userName, record.userPhone)">شروع پیگیری</button>
                            <button v-else-if="record.phaseIII.status.phaseStatus == 'done'"        class="button-secondary" @click="openModal(record.id, 'phaseIII', record.userName, record.userPhone)">{{ record.phaseIII.data.creditHolderName }}</button>
                            <button v-else-if="record.phaseIII.status.phaseStatus == 'processing'"  class="button-secondary" @click="openModal(record.id, 'phaseIII', record.userName, record.userPhone)">اعلام نتیجه</button>
                            <button v-else disabled                                                 class="button-primary">در انتظار</button>
                            <br>
                            <span        v-if="record.phaseIII.status.phaseStatus == 'done' && record.phaseIII.timing.phaseEnd">{{ record.phaseIII.timing.timeDiff }}</span>
                        </div>
                    </td>
                    <td
                    :class="[
                        record.phaseIV.status.admin_feedback,
                        record.phaseIV.status.user_reaction,
                    {
                        'green'             : record.phaseIV.status.user_reaction == 'paid',
                        'green otherNumber' : record.phaseIV.status.user_reaction == 'paid-otherNumber',
                        'yellow'            : record.phaseIV.status.user_reaction == 'noAnswer',
                        'red'               : record.phaseIV.status.user_reaction == 'notWilling',
                        'purple'            : record.phaseIV.status.user_reaction == 'isWilling',
                        'tg-blue'           : record.phaseIV.status.user_reaction == 'proceed-telegram'
                        }
                    ]"
                    v-if="record.status == 'ready'" class="text-center phasing">
                        <div class="modulePhase">
                            <button      v-if="record.phaseIII.status.phaseStatus == 'done'
                                               && record.phaseIV.status.phaseStatus != 'done'
                                               && record.phaseIV.timing.smsSent == '0'"             class="button-primary" @click="openModal(record.id, 'phaseIV', record.userName, record.userPhone)">شروع پیگیری</button>
                            <button v-else-if="record.phaseIV.status.phaseStatus == 'done'"         class="button-secondary" @click="openModal(record.id, 'phaseIV', record.userName, record.userPhone)">{{ record.phaseIV.data.creditHolderName }}</button>
                            <button v-else-if="record.phaseIV.status.phaseStatus == 'processing'"   class="button-secondary" @click="openModal(record.id, 'phaseIV', record.userName, record.userPhone)">اعلام نتیجه</button>
                            <button v-else disabled                                                 class="button-primary">در انتظار</button>
                            <br>
                            <span        v-if="record.phaseIV.status.phaseStatus == 'done' && record.phaseIV.timing.phaseEnd">{{ record.phaseIV.timing.timeDiff }}</span>
                        </div>
                    </td>
                    <td
                    :class="[
                        record.phaseV.status.admin_feedback,
                        record.phaseV.status.user_reaction,
                    {
                        'green'             : record.phaseV.status.user_reaction == 'paid',
                        'green otherNumber' : record.phaseV.status.user_reaction == 'paid-otherNumber',
                        'yellow'            : record.phaseV.status.user_reaction == 'noAnswer',
                        'red'               : record.phaseV.status.user_reaction == 'notWilling',
                        'purple'            : record.phaseV.status.user_reaction == 'isWilling',
                        'tg-blue'           : record.phaseV.status.user_reaction == 'proceed-telegram'
                        }
                    ]"
                    v-if="record.status == 'ready'" class="text-center phasing">
                        <div class="modulePhase">
                            <button      v-if="record.phaseIV.status.phaseStatus == 'done'
                                               && record.phaseV.status.phaseStatus != 'done'
                                               && record.phaseV.timing.smsSent == '0'"              class="button-primary" @click="openModal(record.id, 'phaseV', record.userName, record.userPhone)">شروع پیگیری</button>
                            <button v-else-if="record.phaseV.status.phaseStatus == 'done'"          class="button-secondary" @click="openModal(record.id, 'phaseV', record.userName, record.userPhone)">{{ record.phaseV.data.creditHolderName }}</button>
                            <button v-else-if="record.phaseV.status.phaseStatus == 'processing'"    class="button-secondary" @click="openModal(record.id, 'phaseV', record.userName, record.userPhone)">اعلام نتیجه</button>
                            <button v-else disabled                                                 class="button-primary">در انتظار</button>
                            <br>
                            <span v-if="record.phaseV.status.phaseStatus == 'done' && record.phaseV.timing.phaseEnd">{{ record.phaseV.timing.timeDiff }}</span>
                        </div>
                    </td>
                    <td v-else-if="record.status == 'notReady' || record.status == 'paid-notReady'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-secondary" disabled>آماده پیگیری نیست. <strong v-if="record.lockExp">تا {{ record.lockExp }} در دسترس قرار می‌گیرد.</strong><strong v-else>(بزودی)</strong></button>
                    </td>
                    <td v-else-if="record.status == 'calling' || record.status == 'paid-calling'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-secondary" disabled>تحت پیگیری توسط کارشناس دیگر  <strong v-if="record.lockExp">تا {{ record.lockExp }} بصورت خودکار در دسترس قرار می‌گیرد.</strong><strong v-else>(بزودی)</strong></button>
                    </td>
                    <td v-else-if="record.status == 'duplicated'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-primary" disabled>رکورد تکراری</button>
                    </td>
                    <td v-else-if="record.status == 'locked' || record.status == 'paid-locked'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-secondary" disabled>ردیف توسط مدیر سیستم قفل شده است.</button>
                    </td>
                    <td v-else-if="record.status == 'paid'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-primary" disabled>خرید توسط مشتری انجام شده است.</button>
                    </td>
                    <td v-else-if="record.status == 'end' || record.status == 'paid-end'" colspan="5" class="text-center phasing">
                        <button class="w-100 button-primary" disabled></button>
                    </td>
                    <td v-else-if="record.status == 'paid-ready' " colspan="5" class="text-center phasing">
                        <button class="w-100 button-secondary" disabled>خرید موفق <strong>قابل پیگیری نیست</strong></button>
                    </td>

                    <td class="text-center phasingLookup">
                        <button :id="record.id" class="button-secondary" @click="openModal_statusCheck(record.id)">نمایش اطلاعات</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Modal Structure -->
        <div v-if="showModal" id="phase-popup" class="modal">
            <div @click="closeModal" class="backdrop"></div>
            <div class="modal-content">
                <span class="close" @click="closeModal">&times;</span>
                <p v-if="modalParrent" class="text-center">
                    شما در حال پیگیری "<strong class="modalDataPhase"> {{ modalParrent.phase }} </strong>"
                    از رکورد "<strong id="modalDataRecord">{{ modalParrent.record }}</strong>"
                    از داده های بازیابی تلفنی هستید.
                </p>

                <div v-if="modalData.status.phaseStatus != 'done'" class="form-phasing">
                        <form id="phase-handler" @submit.prevent="handlePhaseSubmit">
                            <div class="form-group">
                                <div class="group">
                                    <label for="smsText">متن اس‌ام‌اس:</label>

                                    <div class="textarea-sms-pattern-cont w-100">
                                        <!-- PHP block to generate the buttons -->
                                        <?php if ($smsPatterns = get_patterns()) { ?>
                                            <div v-if="showPatternsDropdown" class="btn-group_patterns">
                                                <?php foreach ($smsPatterns as $smsPattern) { ?>
                                                    <button 
                                                        class="button-primary" 
                                                        id="pattern_<?=$smsPattern['pattern_id']?>" 
                                                        data-text="<?=htmlspecialchars($smsPattern['pattern_text'], ENT_QUOTES, 'UTF-8')?>" 
                                                        @click="selectPattern($event)"
                                                    >
                                                        <?=htmlspecialchars($smsPattern['pattern_name'], ENT_QUOTES, 'UTF-8')?>
                                                    </button>
                                                <?php } ?>
                                                <span class="insider_placeholder">
                                                    * با استفاده از الگوی %name%، این متغیر در متن ارسالی با نام کاربر: {{ modalrecord.userName }} جایگزین و ارسال می‌شود.
                                                </span>
                                            </div>
                                        <?php } ?>
                                        
                                        <!-- Textarea with v-model and @input for manual entry -->
                                        <textarea 
                                            id="smsText" 
                                            v-model="modalForm.smsText" 
                                            @input="hidePatterns"
                                        ></textarea>
                                    </div>



                                    <div class="btn-group">
                                        <button v-if="modalData.timing.smsSent != '0'" type="button" class="button-primary" disabled @click="nope">"پیامک این فاز ارسال شده است"</button>
                                        <button v-else type="button" class="button-primary" :disabled="isSMSSent" @click="sendSMS">ارسال پیامک</button>

                                    </div>
                                </div>
                                <div class="group">
                                    <div class="form-group_insider">
                                        <div class="group">
                                            <label for="userReaction">وضعیت تماس</label>
                                            <select id="userReaction" v-model="modalForm.userReaction" @change="toggleUserReaction" required>
                                                <option value="noAnswer">پاسخ نداده</option>
                                                <option value="isWilling">تمایل به خرید</option>
                                                <option value="proceed-telegram">پیگیری شده در تلگرام</option>
                                                <option value="notWilling">عدم تمایل به خرید</option>
                                                <option value="paid-otherNumber">خرید با شماره دیگر</option>
                                            </select>
                                        </div>
                                        <div class="group">
                                            <label for="adminReaction">کیفیت لید</label>
                                            <select id="adminReaction" v-model="modalForm.adminReaction" :disabled="isAdminReactionDisabled">
                                                <option value="hot">داغ</option>
                                                <option value="warm">گرم</option>
                                                <option value="cold">سرد</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label for="adminComment">نظر ادمین</label>
                                    <textarea id="adminComment" v-model="modalForm.adminComment" placeholder="نظر خود را وارد کنید" required></textarea>
                                    <button class="button-primary specialSubmit" type="submit">ذخیره اطلاعات و پایان پیگیری</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <hr v-if="modalData.status.phaseStatus != 'done'">


                <div id="phase-details">
                    <div class="user_details">
                        <p><strong>نام کاربر:</strong> {{ modalrecord.userName }}</p>
                        <p><strong>شماره تماس:</strong> {{ modalrecord.userPhone }}</p>
                        <!-- <p><strong>ایجاد شده توسط:</strong> {{ modalrecord.creatorName }}</p> -->
                        <p><strong>مرجع لید</strong>
                            <span v-if="modalrecord.source">{{ modalrecord.source }}</span>
                            <span v-else>بدون مرجع</span>
                            
                        </p>
                        <p><strong>آخرین آپدیت</strong> {{ convertUnixToJalaali(modalrecord.date) }}</p>
                        <div class="flex">
                            <div class="text-center">
                                <strong>لیست سفارشات مشتری</strong>
                                <ul>
                                    <li v-for="(value, key) in modalrecord.parsedOrders" :key="key">
                                        <a v-if="!key.startsWith('temp_')" :href="`<?php echo get_site_url(); ?>/wp-admin/post.php?post=${key}&action=edit`">
                                            {{ key }} : {{ value }}
                                        </a>
                                        <span v-else>{{ value }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p><strong>انجام دهنده این پیگیری</strong>
                            <span v-if="modalData.data.creditHolder">
                                {{ modalData.data.creditHolder }} : {{ modalData.data.creditHolderName }}
                            </span>
                            <span v-else>
                                عدم تکمیل تماس
                            </span>
                        </p>
                        <!-- <p><strong>وضعیت کلی رکورد:</strong> {{ modalrecord.status }}</p> -->
                    </div>
                    <div v-if="modalParrent.similarity.totalRecords > 1" class="similarities">
                        <h2 class="text-center">رکورد مشابه در سیستم پیدا شد !!!</h2>
                        <table class="wp-list-table text-center widefat fixed striped table-view-list">
                            <thead>
                                <tr class="text-center">
                                    <th width="55px">شناسه</th>
                                    <th>نام کاربر</th>
                                    <th>وضعیت</th>
                                    <th>شماره تماس</th>
                                    <th>کارشناس پیگیری</th>
                                    <th>تاریخ اضافه شدن</th>
                                    <th width="350px">سفارشات ثبت شده</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            <tr v-for="(value, key) in modalParrent.similarity.records" :key="key"
                            :class="[
                                value.status,
                                {
                                    'green-row': ['paid', 'paid-ready', 'paid-calling'].includes(value.status)
                                }
                            ]">
                                    
                                    <td>
                                        <span v-if="value.id == modalParrent.record" class="marked-currentRow">{{ value.id }}</span>
                                        <span v-else>{{ value.id }}</span>
                                    </td>
                                    <td>{{ value.userName }}</td>
                                    <td>{{ record_statusTranslate(value.status) }}</td>
                                    <td>{{ value.userPhone }}</td>
                                    <td v-if="value.creditHolder">{{ value.creditHolderName }}<span class="hidden-creator-name">{{ value.creditHolder }}</span></td>
                                    <td v-else>رکورد بدون کارشناس</td>
                                    </td>
                                    <td>{{ convertUnixToJalaali(value.date) }}</td>
                                    <td class="text-center orders_list">
                                        <ul>
                                            <!-- Parse the orders before rendering -->
                                            <li v-for="(ordersValue, ordersKey) in parseOrders(value.orders)" :key="ordersKey">
                                                <a v-if="!ordersValue.startsWith('temp_')" :href="`<?php echo get_site_url(); ?>/wp-admin/post.php?post=${ordersKey}&action=edit`">
                                                    {{ ordersKey }} : {{ ordersValue }}
                                                </a>
                                                <span v-else>{{ ordersValue }}</span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr v-if="!modalParrent.similarity.totalRecords > 1">

                    <div class="phase_extra_details">
                        <p>
                            <strong>زمان شروع این پیگیری</strong>
                            <span v-if="modalData.timing.phaseStart == '0'">
                                پیگیری این فاز انجام نشده است.
                            </span>
                            <span v-else>
                                {{ convertUnixToJalaali(modalData.timing.phaseStart) }}
                            </span>
                        </p>

                        <p>
                            <strong>وضعیت تماس</strong>
                            <span v-if="modalData.status.phaseStatus == 'ready'">
                                آماده پیگیری
                            </span>
                            <span v-else-if="modalData.status.phaseStatus == 'processing'">
                                در حال پیگیری
                            </span>
                            <span v-else-if="modalData.status.phaseStatus == 'done'">
                                پیگیری تکمیل است، امکان پیگیری مجدد در این فاز وجود ندارد
                            </span>
                            <span v-else>
                                وضعیت پیگیری "نامشخص"
                            </span>
                        </p>
                        <p>
                            <strong>زمان پایان این پیگیری</strong>
                            <span v-if="modalData.timing.phaseEnd == '0'">
                                پیگیری به پایان نرسیده
                            </span>
                            <span v-else>
                                {{ convertUnixToJalaali(modalData.timing.phaseEnd) }}
                            </span>
                        </p>

                        <p v-if="modalData.data.smsText" ><strong>متن ارسالی در اس‌ام‌اس</strong> {{ modalData.data.smsText }}</p>
                        <p v-if="modalData.data.admin_feedback"><strong>کیفیت لید</strong> {{ admin_feedbackTranslate(modalData.status.admin_feedback) }}</p>
                        <p v-if="modalData.data.creditHolderComment"><strong>نظر ادمین</strong> {{ modalData.data.creditHolderComment }}</p>
                        <p v-if="modalData.status.user_reaction"><strong>بازخوردِ یوزر</strong>
                        <span>
                            {{                  
                                modalData.status.user_reaction === 'answer'           ? 'پاسخ داده شده' :
                                modalData.status.user_reaction === 'isWilling'        ? 'علاقمند است' :
                                modalData.status.user_reaction === 'notWilling'       ? 'علاقمند نیست' :
                                modalData.status.user_reaction === 'noAnswer'         ? 'پاسخ نداده' :
                                modalData.status.user_reaction === 'paid-otherNumber' ? 'با شماره دیگر پرداخت شده' :
                                'نامشخص'
                            }}
                        </span>
                        </p>
                    </div>


                </div>
            </div>
        </div>
        <!-- Modal Structure END -->



        <div v-if="showStatusModal" id="status-popup" class="modal status-popup">
            <div @click="closeStatusModal" class="backdrop"></div>
            <div class="modal-content">
                <span class="close" @click="closeStatusModal">&times;</span>
                
                <p v-if="modalStatusData" class="text-center">
                    شما در حال پیگیری
                    رکورد "<strong id="modalDataRecord">{{ modalStatusData.retain_id || 'نامشخص' }}</strong>"
                    از داده‌های بازیابی تلفنی هستید.
                </p>

                <!-- User Details -->
                <div id="phase-details">
                    <div class="user_details">
                        <p><strong>نام کاربر:</strong> {{ modalStatusData.user_name || 'نامشخص' }}</p>
                        <p><strong>شماره تماس:</strong> از این بخش حذف شده</p>
                        <p><strong>مرجع لید:</strong> {{ modalStatusData.source || 'نامشخص' }}</p>
                        <p><strong>آخرین آپدیت:</strong> {{ convertUnixToJalaali(modalStatusData.retain_unix) }}</p>

                        <!-- Orders List -->
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
                        <!-- Manual Actions -->
                        <div class="text-center">
                            <strong>مدیریت رکورد</strong>
                            <span>وضعیت فعلی رکورد: <b>{{ record_statusTranslate(modalStatusData.overall_status) }}</b></span>
                            <!-- <div class="admin-controlling-fields">
                                <button class="button-primary">پایان دادن</button>
                                <button class="button-primary">فعال کردن</button>
                                <button class="button-primary">قفل کردن</button>
                            </div> -->
                        </div>
                    </div>

                    <br>

                    <!-- Phases Table -->
                    <div class="phase-container">
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr class="text-center">
                                    <th>تماس</th>
                                    <th>وضعیت تماس</th>
                                    <th>نظر ادمین</th>
                                    <th>بازخورد یوزر</th>
                                    <th>متن پیامک</th>
                                    <th>کیفیت لید</th>
                                    
                                    <th>پیگیری</th>
                                    <th>پیامک ارسال شد در</th>
                                    <th>پایان تماس</th>
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
        </div>

        

    </div>



</div>

<!-- Menu labels and status messages -->
<?php
$menu_labels = [
    'system_title' => 'CRM Phone Follow-up System',  // سیستم CRM پیگیری تلفنی
    'my_leads' => 'My Leads Only',                   // فقط لیدهای من
    'new_leads' => 'New Leads Only',                 // فقط لیدهای جدید
    'all_statuses' => 'All Statuses',                // همه وضعیت‌ها
    'paid_records' => 'Paid Records',                // رکوردهای پرداخت شده
    'unpaid_records' => 'Unpaid Records',            // رکوردهای پرداخت نشده
    'search_by_name' => 'Search by Customer Name'     // جستجو بر اساس نام مشتری
];