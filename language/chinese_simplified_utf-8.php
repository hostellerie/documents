<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | chinese_simplified_utf-8.php                                                               |
// |                                                                           |
// | Chinese Simplified language file                                                     |
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
    'plugin_name'         => '文档',
    'categories'          => '分类',
    'browse_categories'   => '按分类浏览实用文档',
    'browse_documents'    => '浏览文档',
    'documents'           => '文档',
    'category'            => '分类',
    'new_cat'             => '创建新分类',
    'edit_cat'            => '编辑分类',
    'cat_name'            => '分类名称',
    'cat_url'             => 'URL 名称（无空格）',
    'cat_url_exists'      => '此 URL 名称已存在。请选择其他名称。',
    'css'                 => 'CSS',
    'template'            => '模板',
    'cat_order'           => '顺序',
    'existing_cat'        => '现有分类（顺序、名称和 URL 名称）',
    'none'                => '无',
    'cat_help'            => '提交表单帮助（可包含自动标签）',
    'list_index'          => '在列表中显示此分类',
    'submitable'          => '用户可以向此分类提交文档',
    'custom_header'       => '自定义页眉',
    'custom_footer'       => '自定义页脚',
    'required_field'      => '表示必填字段',
    'admin'               => '管理',
    'list_categories'     => '分类',
    'edit'                => '编辑',
    'save_button'         => '保存',
    'delete_button'       => '删除',
    'error'               => '错误',
    'missing_field'       => '至少缺少一个字段。请检查：',
    'save_fail'           => '保存失败',
    'save_success'        => '保存成功',
    'delete_fail'         => '删除失败',
    'delete_success'      => '删除成功',
    'message'             => '消息',
    'validate_button'     => '确定',
    'fields'              => '字段',
    'new_field'           => '创建新字段',
    'list_fields'         => '字段列表',
    'field_name'          => '字段名称',
    'field_order'         => '字段顺序',
    'existing_field'      => '现有字段（顺序和名称）',
    'var_name'            => '变量名称',
    'field_help'          => '提交表单帮助',
    'type'                => '类型',
    'sel_group'           => '选择组',
    'create_new_doc'      => '创建新文档',
    'field_require'       => '在提交和编辑表单中要求此字段',
    'field_on_list'       => '在文档列表中将此字段显示为一列',
    'edit_doc'            => '编辑文档',
    'selects'             => '选项',
    'list_groups'         => '组列表',
    'new_group'           => '创建新组',
    'list_selects'        => '选项列表',
    'new_select'          => '创建新选项',
    'new_option'          => '创建新选择项',
    'group'               => '组',
    'group_name'          => '组名称',
    'group_help'          => '组帮助',
    'edit_group'          => '编辑组',
    'select_name'         => '名称（或选项值）',
    'select_value'        => '向用户显示的值',
    'existing_select'     => '现有选项',
    'select_order'        => '选项顺序',
    'doc_submission'      => '文档提交',
    'submission'          => '提交',
    'pending_moderation'  => '等待审核',
    'not_active'          => '未启用',
    'draft'               => '草稿',
    'active_label'        => '文档状态',
    'active'              => '启用',
    'submission_recorded' => '您的文档已保存。发布前将进行审核。谢谢。',
    'submissions_list'    => '提交列表',
    'submissions_list_2'  => '审核中文档列表',
    'drafts_list'         => '草稿文档列表',
    'documents_list'      => '文档列表',
    'reserved_to'         => '要访问此文档，您必须属于以下组：',
    'cat_hidden'          => '隐藏分类',
    'see_all_docs'        => '所有文档',
    'use_map'             => '使用地图',
    'use_map_details'     => '要为文档添加地理位置，请选择要添加标记的地图。',
    'nonactive'           => '未启用',
    'limited_access'      => '受限访问',
    'private'             => '私有',
    'doc_by'              => '此文档作者',
    'displayed'           => '已显示',
    'times'               => '次。',
    'select_album'        => '选择相册：',
    'no_map'              => '-- 无 --',
    'read_more_marker'    => '显示文档',
    'document_draft'      => '此文档处于草稿模式。仅所有者可访问。',
    'document_submit'     => '此文档尚未批准，目前不可用。',
    'new_comment'         => '新评论：',
    'stats_title'         => '十大文档',
    'stats_documents'     => '已发布文档',
    'stats_views'         => '浏览量',
    'whatsnew_title'      => '最近文档',
    'whatsnew_none'       => '没有最近文档。',
    'more_information'    => '更多信息',
    'integrity_audit_title'              => '数据完整性审核',
    'integrity_audit_notice'             => '此报告为只读。不会修改任何数据或文件。',
    'integrity_check'                    => '检查',
    'integrity_result'                   => '结果',
    'integrity_duplicate_category_slugs' => '重复的分类别名',
    'integrity_duplicate_document_slugs' => '重复的文档别名',
    'integrity_documents_without_values' => '没有值的文档',
    'integrity_values_without_document'  => '没有文档的值',
    'integrity_values_without_field'     => '没有字段的值',
    'integrity_fields_without_category'  => '没有分类的字段',
    'integrity_missing_images'           => '磁盘上缺少已引用的图像文件',
    'integrity_unreferenced_images'      => '未被 Documents 引用的图像文件',
    'integrity_back_admin'               => '返回 Documents 管理'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => '文档',
    'title' => 'Documents 配置'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Documents 文件夹',
    'documents_main_header' => 'Documents 主页眉',
    'documents_main_footer' => 'Documents 主页脚',
    'whatsnew_enabled'      => '在“最新内容”中显示 Documents',
    'whatsnew_interval'     => '“最新内容”周期（秒）',
    'whatsnew_limit'        => '最近文档最大数量',
    'stats_visibility'      => '统计信息可见性',
    'max_image_width'       => '图像最大宽度（像素）',
    'max_image_height'      => '图像最大高度（像素）',
    'max_image_size'        => '图像文件最大大小（字节）',
    'default_permissions'   => '默认权限'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => '主要设置'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents 主要设置'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Documents 主要设置',
    'fs_integrations' => '显示和集成',
    'fs_images'       => '图像',
    'fs_permissions'  => '默认权限'
);

$LANG_configselects['documents'] = array(
    0  => array('是' => 1, '否' => 0),
    1  => array('是' => true, '否' => false),
    12 => array('无访问权限' => 0, '只读' => 2, '读写' => 3),
    20 => array(
        '隐藏' => 0,
        '仅管理员' => 1,
        '已登录用户和管理员' => 2,
        '所有人，包括匿名访客' => 3
    )
);