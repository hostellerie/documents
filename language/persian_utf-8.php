<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | persian_utf-8.php                                                               |
// |                                                                           |
// | Persian language file                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the              |
// | GNU General Public License for more details.                              |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

global $LANG32;

$LANG_DOCUMENTS_1 = array(
    'plugin_name'         => 'اسناد',
    'categories'          => 'دسته‌بندی‌ها',
    'browse_categories'   => 'مرور اسناد کاربردی بر اساس دسته‌بندی',
    'browse_documents'    => 'مرور اسناد',
    'documents'           => 'اسناد',
    'category'            => 'دسته‌بندی',
    'new_cat'             => 'ایجاد دسته‌بندی جدید',
    'edit_cat'            => 'ویرایش دسته‌بندی',
    'cat_name'            => 'نام دسته‌بندی',
    'cat_url'             => 'نام URL (بدون فاصله)',
    'cat_url_exists'      => 'این نام URL از قبل وجود دارد. نام دیگری انتخاب کنید.',
    'css'                 => 'CSS',
    'template'            => 'قالب',
    'cat_order'           => 'ترتیب',
    'existing_cat'        => 'دسته‌بندی‌های موجود (ترتیب، نام و نام URL)',
    'none'                => 'هیچ‌کدام',
    'cat_help'            => 'راهنمای فرم ارسال (می‌تواند شامل برچسب خودکار باشد)',
    'list_index'          => 'نمایش این دسته‌بندی در فهرست',
    'submitable'          => 'کاربران می‌توانند اسناد را به این دسته‌بندی ارسال کنند',
    'custom_header'       => 'سربرگ سفارشی',
    'custom_footer'       => 'پابرگ سفارشی',
    'required_field'      => 'نشان‌دهنده فیلد الزامی',
    'admin'               => 'مدیریت',
    'list_categories'     => 'دسته‌بندی‌ها',
    'edit'                => 'ویرایش',
    'save_button'         => 'ذخیره',
    'delete_button'       => 'حذف',
    'error'               => 'خطا',
    'missing_field'       => 'حداقل یک فیلد وارد نشده است. بررسی کنید:',
    'save_fail'           => 'ذخیره ناموفق بود',
    'save_success'        => 'با موفقیت ذخیره شد',
    'delete_fail'         => 'حذف ناموفق بود',
    'delete_success'      => 'با موفقیت حذف شد',
    'message'             => 'پیام',
    'validate_button'     => 'تأیید',
    'fields'              => 'فیلدها',
    'new_field'           => 'ایجاد فیلد جدید',
    'list_fields'         => 'فهرست فیلدها',
    'field_name'          => 'نام فیلد',
    'field_order'         => 'ترتیب فیلد',
    'existing_field'      => 'فیلدهای موجود (ترتیب و نام)',
    'var_name'            => 'نام متغیر',
    'field_help'          => 'راهنمای فرم ارسال',
    'type'                => 'نوع',
    'sel_group'           => 'گروه انتخاب',
    'create_new_doc'      => 'ایجاد سند جدید',
    'field_require'       => 'الزام این فیلد در فرم‌های ارسال و ویرایش',
    'field_on_list'       => 'نمایش این فیلد به‌عنوان ستون در فهرست اسناد',
    'edit_doc'            => 'ویرایش سند',
    'selects'             => 'انتخاب‌ها',
    'list_groups'         => 'فهرست گروه‌ها',
    'new_group'           => 'ایجاد گروه جدید',
    'list_selects'        => 'فهرست انتخاب‌ها',
    'new_select'          => 'ایجاد انتخاب جدید',
    'new_option'          => 'ایجاد گزینه جدید',
    'group'               => 'گروه',
    'group_name'          => 'نام گروه',
    'group_help'          => 'راهنمای گروه',
    'edit_group'          => 'ویرایش گروه',
    'select_name'         => 'نام (یا مقدار گزینه)',
    'select_value'        => 'مقدار نمایش‌داده‌شده به کاربران',
    'existing_select'     => 'انتخاب‌های موجود',
    'select_order'        => 'ترتیب انتخاب',
    'doc_submission'      => 'ارسال سند',
    'submission'          => 'ارسال',
    'pending_moderation'  => 'در انتظار بررسی',
    'not_active'          => 'غیرفعال',
    'draft'               => 'پیش‌نویس',
    'active_label'        => 'وضعیت سند',
    'active'              => 'فعال',
    'submission_recorded' => 'سند شما ذخیره شد. پیش از انتشار بررسی خواهد شد. سپاسگزاریم.',
    'submissions_list'    => 'فهرست ارسال‌ها',
    'submissions_list_2'  => 'فهرست اسناد در حال بررسی',
    'drafts_list'         => 'فهرست اسناد پیش‌نویس',
    'documents_list'      => 'فهرست اسناد',
    'reserved_to'         => 'برای دسترسی به این سند باید عضو گروه زیر باشید:',
    'cat_hidden'          => 'دسته‌بندی پنهان',
    'see_all_docs'        => 'همه اسناد',
    'use_map'             => 'استفاده از نقشه',
    'use_map_details'     => 'برای مکان‌یابی اسناد، نقشه‌ای را که نشانگرها باید روی آن افزوده شوند انتخاب کنید.',
    'nonactive'           => 'غیرفعال',
    'limited_access'      => 'دسترسی محدود',
    'private'             => 'خصوصی',
    'doc_by'              => 'این سند از',
    'displayed'           => 'نمایش داده شد',
    'times'               => 'بار.',
    'select_album'        => 'انتخاب آلبوم:',
    'no_map'              => '-- هیچ‌کدام --',
    'read_more_marker'    => 'نمایش سند',
    'document_draft'      => 'این سند در حالت پیش‌نویس است. دسترسی فقط برای مالک آن محفوظ است.',
    'document_submit'     => 'این سند هنوز تأیید نشده و در حال حاضر در دسترس نیست.',
    'new_comment'         => 'نظر جدید درباره',
    'stats_title'         => 'ده سند برتر',
    'stats_documents'     => 'اسناد منتشرشده',
    'stats_views'         => 'بازدیدها',
    'whatsnew_title'      => 'اسناد اخیر',
    'whatsnew_none'       => 'سند جدیدی وجود ندارد.',
    'more_information'    => 'اطلاعات بیشتر',
    'integrity_audit_title'              => 'ممیزی یکپارچگی داده‌ها',
    'integrity_audit_notice'             => 'این گزارش فقط خواندنی است. هیچ داده یا فایلی تغییر نمی‌کند.',
    'integrity_check'                    => 'بررسی',
    'integrity_result'                   => 'نتیجه',
    'integrity_duplicate_category_slugs' => 'نامک‌های تکراری دسته‌بندی',
    'integrity_duplicate_document_slugs' => 'نامک‌های تکراری سند',
    'integrity_documents_without_values' => 'اسناد بدون مقدار',
    'integrity_values_without_document'  => 'مقادیر بدون سند',
    'integrity_values_without_field'     => 'مقادیر بدون فیلد',
    'integrity_fields_without_category'  => 'فیلدهای بدون دسته‌بندی',
    'integrity_missing_images'           => 'فایل‌های تصویر ارجاع‌شده که روی دیسک موجود نیستند',
    'integrity_unreferenced_images'      => 'فایل‌های تصویری که توسط Documents ارجاع نشده‌اند',
    'integrity_back_admin'               => 'بازگشت به مدیریت Documents'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'اسناد',
    'title' => 'پیکربندی Documents'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'پوشه Documents',
    'documents_main_header' => 'سربرگ اصلی Documents',
    'documents_main_footer' => 'پابرگ اصلی Documents',
    'whatsnew_enabled'      => 'نمایش Documents در بخش تازه‌ها',
    'whatsnew_interval'     => 'بازه تازه‌ها (ثانیه)',
    'whatsnew_limit'        => 'حداکثر تعداد اسناد اخیر',
    'stats_visibility'      => 'نمایانی آمار',
    'max_image_width'       => 'حداکثر عرض تصویر (پیکسل)',
    'max_image_height'      => 'حداکثر ارتفاع تصویر (پیکسل)',
    'max_image_size'        => 'حداکثر اندازه فایل تصویر (بایت)',
    'default_permissions'   => 'مجوزهای پیش‌فرض'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'تنظیمات اصلی'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'تنظیمات اصلی Documents'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'تنظیمات اصلی Documents',
    'fs_integrations' => 'نمایش و یکپارچه‌سازی‌ها',
    'fs_images'       => 'تصاویر',
    'fs_permissions'  => 'مجوزهای پیش‌فرض'
);

$LANG_configselects['documents'] = array(
    0  => array('بله' => 1, 'خیر' => 0),
    1  => array('بله' => true, 'خیر' => false),
    12 => array('بدون دسترسی' => 0, 'فقط خواندنی' => 2, 'خواندن و نوشتن' => 3),
    20 => array(
        'پنهان' => 0,
        'فقط مدیران' => 1,
        'کاربران واردشده و مدیران' => 2,
        'همه، شامل بازدیدکنندگان ناشناس' => 3
    )
);