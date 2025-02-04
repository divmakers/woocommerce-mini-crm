<div class="wrap woocr_callCRM">
    <h1><?php _e('SMS Sending Patterns', 'woo-cr'); ?></h1>
    <p><?php _e('Use the %name% pattern for customer name', 'woo-cr'); ?></p>

    <div id="app_woocrCall_smsPattern">
        <div class="woocr-column form">
            <form @submit.prevent="submitForm">
                <label for="pattern_name">
                    <span><?php _e('Pattern Name:', 'woo-cr'); ?></span>
                    <input type="text" v-model="pattern_name" id="pattern_name" />
                </label>
                <label for="creator_id">
                    <span><?php _e('Creator ID:', 'woo-cr'); ?></span>
                    <input v-model="creator_id" id="creator_id" disabled/>
                </label>
                <label for="pattern_text">
                    <span><?php _e('Pattern Text:', 'woo-cr'); ?></span>
                    <textarea style="min-height: 75px" v-model="pattern_text" id="pattern_text"></textarea>
                </label>
                <button class="button-primary" type="submit">
                    <?php _e('Save Pattern', 'woo-cr'); ?>
                </button>
                <button class="button-secondary" @click.prevent="resetForm" type="submit">
                    <?php _e('Reset Form', 'woo-cr'); ?>
                </button>
            </form>
            <h2>الگوهای ذخیره شده:</h2>
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th width="45px">شناسه</th>
                        <th width="100px">نام</th>
                        <th width="45px">سازنده</th>
                        <th>متن</th>
                        <th width="77px">حذف/ویرایش</th>
                    </tr>
                </thead>
                <tr v-for="pattern in patterns" :key="pattern.pattern_id">
                    <td>{{ pattern.pattern_id }}</td>
                    <td>{{ pattern.pattern_name }}</td>
                    <td>{{ pattern.creator_id }}<span class="hidden-creator-name">{{ pattern.user_fullName }}</span></td>
                    <td>{{ pattern.pattern_text }}</td>
                    <td class="edit-remove-pattern_outer">
                        <div class="edit-remove-pattern">
                            <button class="button-primary" @click="editPattern(pattern)">ویرایش</button>
                            <button class="button-secondary" @click="deletePattern(pattern.pattern_id)">حذف الگو</button>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="woocr-column showcase">
            <div class="mirror">
                <div class="iphone" id="iphoneLeft">
                    <div class="silence-switch outer-button"></div>
                    <div class="volume-rocker-top outer-button"></div>
                    <div class="volume-rocker-bottom outer-button"></div>
                    <div class="power-button outer-button-reversed"></div>
                    <!--...................................TOP SECTION..................................-->
                    <div class="top-section">
                        <div class="top-section-time">12:00</div>
                        <div class="top-section-symbols">
                            <i class="fas fa-signal"></i>
                            <i class="fas fa-wifi"></i>
                            <i class="fas fa-battery-three-quarters"></i>
                        </div>
                        <div class="top-section-middle">
                            <div class="speaker"><div class="front-camera"></div></div>
                            <div class="top-section-user-pic selectDisable"><i class="fas fa-user-circle"></i></div>
                            <div class="top-section-user-name"><?php echo get_bloginfo( 'name' )?> ↔ <span v-if="!pattern_name">نام الگو</span>{{ pattern_name }}</div>
                        </div>
                        <!--...................................MESSAGE SECTION..................................-->
                        <div class="messages-section" id="messages-left">
                            <div class="message to">
                                <span class="patterncounter">{{ pattern_text_count }} کاراکتر</span>
                                <b v-if="pattern_text">{{ pattern_text }}</b>
                                <b v-else>
                محمدرضا راستی عزیز، سلام<br>
                کد تخفیف ویژه برای شما: #offer_support#
                                </b>
                            </div>
                            <div class="message from">این یک پیامک تست است، که می‌تواند بر اساس الگو، نام و نام خانوادگی را جایگزین کند، مثل نمونه ای که در بالا وجود دارد.</br>از الگوی %name% می‌توانید استفاده کنید.</div>
                        </div>
                        <div class="keyboard-bottom-symbols">
                            <div class="home-screen-button"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pattern management labels -->
<?php
$pattern_labels = [
    'title' => 'SMS Sending Patterns',               // پترن های ارسال پیامک
    'name_placeholder' => 'Pattern Name',            // نام الگو
    'creator_id' => 'Creator ID',                    // شناسه سازنده
    'pattern_text' => 'Pattern Text',                // متن الگو
    'save_pattern' => 'Save Pattern',                // ذخیره الگو
    'reset_form' => 'Reset Form',                    // پاک کردن فرم
    'saved_patterns' => 'Saved Patterns',            // الگوهای ذخیره شده
    'edit_pattern' => 'Edit Pattern',                // ویرایش الگو
    'delete_pattern' => 'Delete Pattern'             // حذف الگو
];