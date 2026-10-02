<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | hebrew_utf-8.php                                                               |
// |                                                                           |
// | Hebrew language file                                                     |
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
    'plugin_name'         => 'מסמכים',
    'categories'          => 'קטגוריות',
    'browse_categories'   => 'עיון במסמכים שימושיים לפי קטגוריה',
    'browse_documents'    => 'עיון במסמכים',
    'documents'           => 'מסמכים',
    'category'            => 'קטגוריה',
    'new_cat'             => 'יצירת קטגוריה חדשה',
    'edit_cat'            => 'עריכת קטגוריה',
    'cat_name'            => 'שם הקטגוריה',
    'cat_url'             => 'שם URL (ללא רווחים)',
    'cat_url_exists'      => 'שם URL זה כבר קיים. בחרו שם אחר.',
    'css'                 => 'CSS',
    'template'            => 'תבנית',
    'cat_order'           => 'סדר',
    'existing_cat'        => 'קטגוריות קיימות (סדר, שם ושם URL)',
    'none'                => 'ללא',
    'cat_help'            => 'עזרה לטופס ההגשה (עשויה לכלול תג אוטומטי)',
    'list_index'          => 'הצגת קטגוריה זו ברשימה',
    'submitable'          => 'משתמשים יכולים להגיש מסמכים לקטגוריה זו',
    'custom_header'       => 'כותרת עליונה מותאמת',
    'custom_footer'       => 'כותרת תחתונה מותאמת',
    'required_field'      => 'מציין שדה חובה',
    'admin'               => 'ניהול',
    'list_categories'     => 'קטגוריות',
    'edit'                => 'עריכה',
    'save_button'         => 'שמירה',
    'delete_button'       => 'מחיקה',
    'error'               => 'שגיאה',
    'missing_field'       => 'חסר לפחות שדה אחד. בדקו:',
    'save_fail'           => 'השמירה נכשלה',
    'save_success'        => 'נשמר בהצלחה',
    'delete_fail'         => 'המחיקה נכשלה',
    'delete_success'      => 'נמחק בהצלחה',
    'message'             => 'הודעה',
    'validate_button'     => 'אישור',
    'fields'              => 'שדות',
    'new_field'           => 'יצירת שדה חדש',
    'list_fields'         => 'רשימת שדות',
    'field_name'          => 'שם השדה',
    'field_order'         => 'סדר השדה',
    'existing_field'      => 'שדות קיימים (סדר ושם)',
    'var_name'            => 'שם המשתנה',
    'field_help'          => 'עזרה לטופס ההגשה',
    'type'                => 'סוג',
    'sel_group'           => 'קבוצת בחירה',
    'create_new_doc'      => 'יצירת מסמך חדש',
    'field_require'       => 'דרישת שדה זה בטפסי הגשה ועריכה',
    'field_on_list'       => 'הצגת שדה זה כעמודה ברשימות מסמכים',
    'edit_doc'            => 'עריכת מסמך',
    'selects'             => 'בחירות',
    'list_groups'         => 'רשימת קבוצות',
    'new_group'           => 'יצירת קבוצה חדשה',
    'list_selects'        => 'רשימת בחירות',
    'new_select'          => 'יצירת בחירה חדשה',
    'new_option'          => 'יצירת אפשרות חדשה',
    'group'               => 'קבוצה',
    'group_name'          => 'שם הקבוצה',
    'group_help'          => 'עזרה לקבוצה',
    'edit_group'          => 'עריכת קבוצה',
    'select_name'         => 'שם (או ערך אפשרות)',
    'select_value'        => 'ערך המוצג למשתמשים',
    'existing_select'     => 'בחירות קיימות',
    'select_order'        => 'סדר הבחירה',
    'doc_submission'      => 'הגשת מסמך',
    'submission'          => 'הגשה',
    'pending_moderation'  => 'ממתין לאישור',
    'not_active'          => 'לא פעיל',
    'draft'               => 'טיוטה',
    'active_label'        => 'מצב המסמך',
    'active'              => 'פעיל',
    'submission_recorded' => 'המסמך נשמר. הוא ייבדק לפני הפרסום. תודה.',
    'submissions_list'    => 'רשימת הגשות',
    'submissions_list_2'  => 'רשימת מסמכים בבדיקה',
    'drafts_list'         => 'רשימת מסמכי טיוטה',
    'documents_list'      => 'רשימת מסמכים',
    'reserved_to'         => 'כדי לגשת למסמך זה, עליכם להשתייך לקבוצה:',
    'cat_hidden'          => 'קטגוריה מוסתרת',
    'see_all_docs'        => 'כל המסמכים',
    'use_map'             => 'שימוש במפה',
    'use_map_details'     => 'כדי למקם מסמכים גאוגרפית, בחרו את המפה שאליה יתווספו הסמנים.',
    'nonactive'           => 'לא פעיל',
    'limited_access'      => 'גישה מוגבלת',
    'private'             => 'פרטי',
    'doc_by'              => 'מסמך זה מאת',
    'displayed'           => 'הוצג',
    'times'               => 'פעמים.',
    'select_album'        => 'בחירת אלבום:',
    'no_map'              => '-- ללא --',
    'read_more_marker'    => 'הצגת מסמך',
    'document_draft'      => 'מסמך זה במצב טיוטה. הגישה שמורה לבעליו.',
    'document_submit'     => 'מסמך זה עדיין לא אושר ואינו זמין כעת.',
    'new_comment'         => 'תגובה חדשה על',
    'stats_title'         => 'עשרת המסמכים המובילים',
    'stats_documents'     => 'מסמכים שפורסמו',
    'stats_views'         => 'צפיות',
    'whatsnew_title'      => 'מסמכים אחרונים',
    'whatsnew_none'       => 'אין מסמכים אחרונים.',
    'more_information'    => 'מידע נוסף',
    'integrity_audit_title'              => 'בדיקת שלמות נתונים',
    'integrity_audit_notice'             => 'דוח זה לקריאה בלבד. לא משתנים נתונים או קבצים.',
    'integrity_check'                    => 'בדיקה',
    'integrity_result'                   => 'תוצאה',
    'integrity_duplicate_category_slugs' => 'מזהי קטגוריה כפולים',
    'integrity_duplicate_document_slugs' => 'מזהי מסמך כפולים',
    'integrity_documents_without_values' => 'מסמכים ללא ערכים',
    'integrity_values_without_document'  => 'ערכים ללא מסמך',
    'integrity_values_without_field'     => 'ערכים ללא שדה',
    'integrity_fields_without_category'  => 'שדות ללא קטגוריה',
    'integrity_missing_images'           => 'קובצי תמונה מקושרים החסרים בדיסק',
    'integrity_unreferenced_images'      => 'קובצי תמונה שאינם מקושרים על ידי Documents',
    'integrity_back_admin'               => 'חזרה לניהול Documents'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'מסמכים',
    'title' => 'הגדרות Documents'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'תיקיית Documents',
    'documents_main_header' => 'כותרת ראשית של Documents',
    'documents_main_footer' => 'כותרת תחתונה ראשית של Documents',
    'whatsnew_enabled'      => 'הצגת Documents במה חדש',
    'whatsnew_interval'     => 'תקופת מה חדש (שניות)',
    'whatsnew_limit'        => 'מספר מרבי של מסמכים אחרונים',
    'stats_visibility'      => 'נראות סטטיסטיקות',
    'max_image_width'       => 'רוחב תמונה מרבי (פיקסלים)',
    'max_image_height'      => 'גובה תמונה מרבי (פיקסלים)',
    'max_image_size'        => 'גודל מרבי של קובץ תמונה (בתים)',
    'default_permissions'   => 'הרשאות ברירת מחדל'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'הגדרות ראשיות'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'הגדרות ראשיות של Documents'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'הגדרות ראשיות של Documents',
    'fs_integrations' => 'תצוגה ושילובים',
    'fs_images'       => 'תמונות',
    'fs_permissions'  => 'הרשאות ברירת מחדל'
);

$LANG_configselects['documents'] = array(
    0  => array('כן' => 1, 'לא' => 0),
    1  => array('כן' => true, 'לא' => false),
    12 => array('ללא גישה' => 0, 'קריאה בלבד' => 2, 'קריאה וכתיבה' => 3),
    20 => array(
        'מוסתר' => 0,
        'מנהלים בלבד' => 1,
        'משתמשים מחוברים ומנהלים' => 2,
        'כולם, כולל מבקרים אנונימיים' => 3
    )
);