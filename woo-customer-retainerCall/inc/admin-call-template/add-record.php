<div class="wrap woocr_callCRM">
    <h1>افزودن رکورد به سیستم</h1>


    <div class="crm-main form-styled-cont" id="vue-crm-form">
        <div class="woocr-column form">
            <div>
                <form @submit.prevent="submitForm_crmNewRecord">
                    <label for="user_name">نام مشتری: 
                        <input type="text" id="user_name" v-model="userName" required>
                    </label>

                    <label for="user_phone">شماره تلفن: 
                        <input type="text" id="user_phone" v-model="userPhone" required>
                    </label>

                    <div class="vue-orders_setup">
                        <div class="orders-listing-vueform" v-for="(order, index) in userOrders" :key="index">
                            <label class="item" :for="'order_item_' + index">
                                <input placeholder="موضوع سفارش" type="text" :id="'order_item_' + index" v-model="order.name" required>
                            </label>
                            <label class="key" :for="'order_id_' + index">
                                <input placeholder="" type="text" :id="'order_id_' + index" v-model="order.id" disabled>
                            </label>
                            <button class="button-secondary remove-item" type="button" @click="removeOrder(index)">حذف</button>
                        </div>
                        <button class="button-primary w-100" type="button" @click="addOrder">اضافه کردن آیتم</button>
                    </div>

                    <label for="overall_status">وضعیت پیگیری:
                        <select id="overall_status" v-model="overallStatus" required disabled>
                            <option value="ready">آماده برای تماس (رکورد بصورت اماده پیگیری درج می‌شود)</option>
                            <option value="notReady">آماده تماس نیست</option>
                            <option value="finished">پایان فرآیند</option>
                            <option value="locked">قفل شده</option>
                        </select>
                    </label>

                    <div class="flexed-row-buttons">
                        <button class="button-primary" type="submit">افزودن رکورد</button>
                        <button class="button-secondary" @click.prevent="resetForm" type="button">ریست فرم</button>
                    </div>
                </form>

            <p v-if="successMessage">{{ successMessage }}</p>
            <p v-if="errorMessage">{{ errorMessage }}</p>
        </div>

        </div>
        <div class="woocr-column showcase">
            <div>
                <h3>راهنما/لاجیک استفاده</h3>
                <p>
                    افراد بسته به ثبت سفارشات، به لیست اضافه می‌شوند، همچنین امکانِ اضافه کردن رکورد به سیستم، بصورت
                    دستی به وسیله فرم وجود دارد.
                </p>
                <p>
                    افراد پس از ثبت سفارش، وارد لیست می شوند، در حالت notReady قرار گرفته و پس از ده دقیقه انتظار، آماده
                    پیگیری تلفنی می‌شوند. همچنین هربار که یک مرحله از پیگیری تلفنی در حالتِ انجام قرار بگیرد، ردیف برای
                    ده دقیقه قفل می‌شود تا شخص دیگر، نتواند بصورت همزمان اقدام به پیگیری تلفنی کند.
                </p>
                <p>
                    .پس از تکمیل شدن هرگونه خریدِ غیر رایگان توسط مشتری، کاربر از لیست خارج شده و همزمان با خروج از لیست
                    بازیابی پیامکی، اعتبار خرید حاصله، به نام آخرین مسئول پیگیری ثبت می‌شود.
                </p>
                <p>
                    *در نظر داشته باشید، تگ پایان فرآیند که به صورت سیستمی یا دستی تنظیم می‌شود، منجر به اتمام پروسه
                    پیگیری تلفنی شده و اجازه می‌دهد تا کاربر، با ثبت خرید دیگری، مجددا وارد پروسه پیگیری تلفنی گردد.
                </p>
                <p>
                    **عدم نمایش شماره تماس کاربر در لیست اولیه، به دلیل پیشگیری از تداخلات و همچنین فرآیند‌های سیستمی
                    همراه با ثبت زمان بندی و اطلاعات تعریف می‌گردد، هر ادمین در حالتی که یک پیگیری باز داشته باشد، امکان
                    پیگیری مشتری دیگری را ندارد.
                </p>
            </div>
        </div>
    </div>


<?php if (user_can( get_current_user_id(), 'administrator' )) { ?>
    <h2>افزودن رکورد دسته‌ای به سیستم</h2>
<div id="excel-insertCRM">
    <label for="excel-insert">آپلود فایل excel / xlsx جهت درون ریزی به سیستم:</label>
    <input id="excel-insert" type="file" @change="importFile" />
    <p v-if="currFileName">نام فایل آپلود شده: <strong>{{ currFileName }}</strong></p>
    <br>
    <button v-if="currFileName && !isSubmitting" class="button-primary w-100" type="button" @click="addOrderBatch" :disabled="isSubmitting">
        اضافه کردن رکوردهای جدول
    </button>
    <div>
        <span v-for="sheet in sheets" @click="selectSheet(sheet)" :key="sheet" :class="{'selected': sheet === currSheet}">
            {{ sheet }}
        </span>
    </div>
    <div class="table">
        <table id="batchedOrders" class="wp-list-table widefat fixed striped table-view-list">
            <tr v-for="(row, rowIndex) in rows" :key="rowIndex" :class="getRowClass(rowIndex)">
                <td v-for="(cell, colIndex) in row" :key="colIndex">
                    <div>{{ cell }}</div>
                    <select v-if="rowIndex === 0" class="select2-dropdown" v-model="selectedOptions[colIndex]" :data-position="`${rowIndex}.${colIndex}`">
                        <option value="fullName">نام کامل</option>
                        <option value="phoneNumber">شماره تماس</option>
                        <option value="source">منبع لید</option>
                        <option value="importantMeta">متای مهم</option>
                        <option value="normalMeta">متای معمول</option>
                        <option value="utm">دیتای UTM</option>
                        <option value="empty">خالی</option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
</div>

<?php } ?>

</div>