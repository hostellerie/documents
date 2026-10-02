<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | chinese_traditional_utf-8.php                                                               |
// |                                                                           |
// | Chinese Traditional language file                                                     |
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
    'plugin_name'         => '文件',
    'categories'          => '分類',
    'browse_categories'   => '依分類瀏覽實用文件',
    'browse_documents'    => '瀏覽文件',
    'documents'           => '文件',
    'category'            => '分類',
    'new_cat'             => '建立新分類',
    'edit_cat'            => '編輯分類',
    'cat_name'            => '分類名稱',
    'cat_url'             => 'URL 名稱（不可有空格）',
    'cat_url_exists'      => '此 URL 名稱已存在。請選擇其他名稱。',
    'css'                 => 'CSS',
    'template'            => '範本',
    'cat_order'           => '順序',
    'existing_cat'        => '現有分類（順序、名稱和 URL 名稱）',
    'none'                => '無',
    'cat_help'            => '提交表單說明（可包含自動標籤）',
    'list_index'          => '在清單中顯示此分類',
    'submitable'          => '使用者可提交文件到此分類',
    'custom_header'       => '自訂頁首',
    'custom_footer'       => '自訂頁尾',
    'required_field'      => '表示必填欄位',
    'admin'               => '管理',
    'list_categories'     => '分類',
    'edit'                => '編輯',
    'save_button'         => '儲存',
    'delete_button'       => '刪除',
    'error'               => '錯誤',
    'missing_field'       => '至少缺少一個欄位。請檢查：',
    'save_fail'           => '儲存失敗',
    'save_success'        => '儲存成功',
    'delete_fail'         => '刪除失敗',
    'delete_success'      => '刪除成功',
    'message'             => '訊息',
    'validate_button'     => '確定',
    'fields'              => '欄位',
    'new_field'           => '建立新欄位',
    'list_fields'         => '欄位清單',
    'field_name'          => '欄位名稱',
    'field_order'         => '欄位順序',
    'existing_field'      => '現有欄位（順序和名稱）',
    'var_name'            => '變數名稱',
    'field_help'          => '提交表單說明',
    'type'                => '類型',
    'sel_group'           => '選擇群組',
    'create_new_doc'      => '建立新文件',
    'field_require'       => '在提交和編輯表單中要求此欄位',
    'field_on_list'       => '在文件清單中將此欄位顯示為一欄',
    'edit_doc'            => '編輯文件',
    'selects'             => '選項',
    'list_groups'         => '群組清單',
    'new_group'           => '建立新群組',
    'list_selects'        => '選項清單',
    'new_select'          => '建立新選項',
    'new_option'          => '建立新選擇項',
    'group'               => '群組',
    'group_name'          => '群組名稱',
    'group_help'          => '群組說明',
    'edit_group'          => '編輯群組',
    'select_name'         => '名稱（或選項值）',
    'select_value'        => '顯示給使用者的值',
    'existing_select'     => '現有選項',
    'select_order'        => '選項順序',
    'doc_submission'      => '文件提交',
    'submission'          => '提交',
    'pending_moderation'  => '等待審核',
    'not_active'          => '未啟用',
    'draft'               => '草稿',
    'active_label'        => '文件狀態',
    'active'              => '啟用',
    'submission_recorded' => '您的文件已儲存。發布前將進行審核。謝謝。',
    'submissions_list'    => '提交清單',
    'submissions_list_2'  => '審核中文件清單',
    'drafts_list'         => '草稿文件清單',
    'documents_list'      => '文件清單',
    'reserved_to'         => '若要存取此文件，您必須屬於以下群組：',
    'cat_hidden'          => '隱藏分類',
    'see_all_docs'        => '所有文件',
    'use_map'             => '使用地圖',
    'use_map_details'     => '若要為文件加入地理位置，請選擇要新增標記的地圖。',
    'nonactive'           => '未啟用',
    'limited_access'      => '受限存取',
    'private'             => '私人',
    'doc_by'              => '此文件作者',
    'displayed'           => '已顯示',
    'times'               => '次。',
    'select_album'        => '選擇相簿：',
    'no_map'              => '-- 無 --',
    'read_more_marker'    => '顯示文件',
    'document_draft'      => '此文件為草稿模式。僅擁有者可存取。',
    'document_submit'     => '此文件尚未核准，目前無法使用。',
    'new_comment'         => '新留言：',
    'stats_title'         => '前十大文件',
    'stats_documents'     => '已發布文件',
    'stats_views'         => '瀏覽次數',
    'whatsnew_title'      => '最近文件',
    'whatsnew_none'       => '沒有最近文件。',
    'more_information'    => '更多資訊',
    'integrity_audit_title'              => '資料完整性稽核',
    'integrity_audit_notice'             => '此報告為唯讀。不會修改任何資料或檔案。',
    'integrity_check'                    => '檢查',
    'integrity_result'                   => '結果',
    'integrity_duplicate_category_slugs' => '重複的分類別名',
    'integrity_duplicate_document_slugs' => '重複的文件別名',
    'integrity_documents_without_values' => '沒有值的文件',
    'integrity_values_without_document'  => '沒有文件的值',
    'integrity_values_without_field'     => '沒有欄位的值',
    'integrity_fields_without_category'  => '沒有分類的欄位',
    'integrity_missing_images'           => '磁碟上缺少已引用的圖片檔案',
    'integrity_unreferenced_images'      => '未被 Documents 引用的圖片檔案',
    'integrity_back_admin'               => '返回 Documents 管理'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => '文件',
    'title' => 'Documents 設定'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Documents 資料夾',
    'documents_main_header' => 'Documents 主要頁首',
    'documents_main_footer' => 'Documents 主要頁尾',
    'whatsnew_enabled'      => '在「最新內容」中顯示 Documents',
    'whatsnew_interval'     => '「最新內容」期間（秒）',
    'whatsnew_limit'        => '最近文件最大數量',
    'stats_visibility'      => '統計資訊可見性',
    'max_image_width'       => '圖片最大寬度（像素）',
    'max_image_height'      => '圖片最大高度（像素）',
    'max_image_size'        => '圖片檔案最大大小（位元組）',
    'default_permissions'   => '預設權限'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => '主要設定'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents 主要設定'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Documents 主要設定',
    'fs_integrations' => '顯示與整合',
    'fs_images'       => '圖片',
    'fs_permissions'  => '預設權限'
);

$LANG_configselects['documents'] = array(
    0  => array('是' => 1, '否' => 0),
    1  => array('是' => true, '否' => false),
    12 => array('無存取權' => 0, '唯讀' => 2, '讀寫' => 3),
    20 => array(
        '隱藏' => 0,
        '僅管理員' => 1,
        '已登入使用者與管理員' => 2,
        '所有人，包括匿名訪客' => 3
    )
);