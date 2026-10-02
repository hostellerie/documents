<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | japanese_utf-8.php                                                               |
// |                                                                           |
// | Japanese language file                                                     |
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
    'plugin_name'         => 'ドキュメント',
    'categories'          => 'カテゴリー',
    'browse_categories'   => 'カテゴリー別に実用的なドキュメントを探す',
    'browse_documents'    => 'ドキュメントを探す',
    'documents'           => 'ドキュメント',
    'category'            => 'カテゴリー',
    'new_cat'             => '新しいカテゴリーを作成',
    'edit_cat'            => 'カテゴリーを編集',
    'cat_name'            => 'カテゴリー名',
    'cat_url'             => 'URL 名（空白なし）',
    'cat_url_exists'      => 'この URL 名は既に存在します。別の名前を選択してください。',
    'css'                 => 'CSS',
    'template'            => 'テンプレート',
    'cat_order'           => '順序',
    'existing_cat'        => '既存のカテゴリー（順序、名前、URL 名）',
    'none'                => 'なし',
    'cat_help'            => '投稿フォームのヘルプ（自動タグを含めることができます）',
    'list_index'          => 'このカテゴリーを一覧に表示',
    'submitable'          => 'ユーザーはこのカテゴリーにドキュメントを投稿できます',
    'custom_header'       => 'カスタムヘッダー',
    'custom_footer'       => 'カスタムフッター',
    'required_field'      => '必須項目を示します',
    'admin'               => '管理',
    'list_categories'     => 'カテゴリー',
    'edit'                => '編集',
    'save_button'         => '保存',
    'delete_button'       => '削除',
    'error'               => 'エラー',
    'missing_field'       => '少なくとも 1 つの項目が不足しています。確認してください:',
    'save_fail'           => '保存に失敗しました',
    'save_success'        => '保存しました',
    'delete_fail'         => '削除に失敗しました',
    'delete_success'      => '削除しました',
    'message'             => 'メッセージ',
    'validate_button'     => 'OK',
    'fields'              => 'フィールド',
    'new_field'           => '新しいフィールドを作成',
    'list_fields'         => 'フィールド一覧',
    'field_name'          => 'フィールド名',
    'field_order'         => 'フィールド順序',
    'existing_field'      => '既存のフィールド（順序と名前）',
    'var_name'            => '変数名',
    'field_help'          => '投稿フォームのヘルプ',
    'type'                => '種類',
    'sel_group'           => '選択グループ',
    'create_new_doc'      => '新しいドキュメントを作成',
    'field_require'       => '投稿・編集フォームでこのフィールドを必須にする',
    'field_on_list'       => 'ドキュメント一覧でこのフィールドを列として表示',
    'edit_doc'            => 'ドキュメントを編集',
    'selects'             => '選択肢',
    'list_groups'         => 'グループ一覧',
    'new_group'           => '新しいグループを作成',
    'list_selects'        => '選択肢一覧',
    'new_select'          => '新しい選択肢を作成',
    'new_option'          => '新しいオプションを作成',
    'group'               => 'グループ',
    'group_name'          => 'グループ名',
    'group_help'          => 'グループのヘルプ',
    'edit_group'          => 'グループを編集',
    'select_name'         => '名前（またはオプション値）',
    'select_value'        => 'ユーザーに表示する値',
    'existing_select'     => '既存の選択肢',
    'select_order'        => '選択順序',
    'doc_submission'      => 'ドキュメント投稿',
    'submission'          => '投稿',
    'pending_moderation'  => '承認待ち',
    'not_active'          => '無効',
    'draft'               => '下書き',
    'active_label'        => 'ドキュメントの状態',
    'active'              => '有効',
    'submission_recorded' => 'ドキュメントを保存しました。公開前に確認されます。ありがとうございます。',
    'submissions_list'    => '投稿一覧',
    'submissions_list_2'  => '確認中のドキュメント一覧',
    'drafts_list'         => '下書きドキュメント一覧',
    'documents_list'      => 'ドキュメント一覧',
    'reserved_to'         => 'このドキュメントにアクセスするには、次のグループに所属する必要があります:',
    'cat_hidden'          => '非表示カテゴリー',
    'see_all_docs'        => 'すべてのドキュメント',
    'use_map'             => '地図を使用',
    'use_map_details'     => 'ドキュメントの位置を表示するには、マーカーを追加する地図を選択してください。',
    'nonactive'           => '無効',
    'limited_access'      => 'アクセス制限',
    'private'             => '非公開',
    'doc_by'              => 'このドキュメントの作成者',
    'displayed'           => '表示回数',
    'times'               => '回。',
    'select_album'        => 'アルバムを選択:',
    'no_map'              => '-- なし --',
    'read_more_marker'    => 'ドキュメントを表示',
    'document_draft'      => 'このドキュメントは下書きです。所有者のみアクセスできます。',
    'document_submit'     => 'このドキュメントはまだ承認されておらず、現在利用できません。',
    'new_comment'         => '新しいコメント:',
    'stats_title'         => '上位 10 件のドキュメント',
    'stats_documents'     => '公開済みドキュメント',
    'stats_views'         => '閲覧数',
    'whatsnew_title'      => '最近のドキュメント',
    'whatsnew_none'       => '最近のドキュメントはありません。',
    'more_information'    => '詳細情報',
    'integrity_audit_title'              => 'データ整合性監査',
    'integrity_audit_notice'             => 'このレポートは読み取り専用です。データやファイルは変更されません。',
    'integrity_check'                    => 'チェック',
    'integrity_result'                   => '結果',
    'integrity_duplicate_category_slugs' => '重複するカテゴリースラッグ',
    'integrity_duplicate_document_slugs' => '重複するドキュメントスラッグ',
    'integrity_documents_without_values' => '値のないドキュメント',
    'integrity_values_without_document'  => 'ドキュメントのない値',
    'integrity_values_without_field'     => 'フィールドのない値',
    'integrity_fields_without_category'  => 'カテゴリーのないフィールド',
    'integrity_missing_images'           => '参照されているがディスクに存在しない画像ファイル',
    'integrity_unreferenced_images'      => 'Documents から参照されていない画像ファイル',
    'integrity_back_admin'               => 'Documents 管理に戻る'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'ドキュメント',
    'title' => 'Documents 設定'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Documents フォルダー',
    'documents_main_header' => 'Documents メインヘッダー',
    'documents_main_footer' => 'Documents メインフッター',
    'whatsnew_enabled'      => '新着情報に Documents を表示',
    'whatsnew_interval'     => '新着情報の期間（秒）',
    'whatsnew_limit'        => '最近のドキュメントの最大数',
    'stats_visibility'      => '統計の表示範囲',
    'max_image_width'       => '画像の最大幅（ピクセル）',
    'max_image_height'      => '画像の最大高さ（ピクセル）',
    'max_image_size'        => '画像ファイルの最大サイズ（バイト）',
    'default_permissions'   => '既定の権限'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'メイン設定'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents メイン設定'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Documents メイン設定',
    'fs_integrations' => '表示と連携',
    'fs_images'       => '画像',
    'fs_permissions'  => '既定の権限'
);

$LANG_configselects['documents'] = array(
    0  => array('はい' => 1, 'いいえ' => 0),
    1  => array('はい' => true, 'いいえ' => false),
    12 => array('アクセスなし' => 0, '読み取り専用' => 2, '読み書き' => 3),
    20 => array(
        '非表示' => 0,
        '管理者のみ' => 1,
        'ログインユーザーと管理者' => 2,
        '匿名訪問者を含む全員' => 3
    )
);