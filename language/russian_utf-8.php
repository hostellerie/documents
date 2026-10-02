<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | russian_utf-8.php                                                               |
// |                                                                           |
// | Russian language file                                                     |
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
    'plugin_name'         => 'Документы',
    'categories'          => 'Категории',
    'browse_categories'   => 'Просмотреть практические документы по категориям',
    'browse_documents'    => 'Просмотреть документы',
    'documents'           => 'Документы',
    'category'            => 'Категория',
    'new_cat'             => 'Создать новую категорию',
    'edit_cat'            => 'Редактировать категорию',
    'cat_name'            => 'Название категории',
    'cat_url'             => 'Имя URL (без пробелов)',
    'cat_url_exists'      => 'Такое имя URL уже существует. Выберите другое.',
    'css'                 => 'CSS',
    'template'            => 'Шаблон',
    'cat_order'           => 'Порядок',
    'existing_cat'        => 'Существующие категории (порядок, название и имя URL)',
    'none'                => 'Нет',
    'cat_help'            => 'Справка для формы отправки (может содержать автотег)',
    'list_index'          => 'Показывать эту категорию в списке',
    'submitable'          => 'Пользователи могут отправлять документы в эту категорию',
    'custom_header'       => 'Пользовательский заголовок',
    'custom_footer'       => 'Пользовательский подвал',
    'required_field'      => 'Обозначает обязательное поле',
    'admin'               => 'Администрирование',
    'list_categories'     => 'Категории',
    'edit'                => 'Редактировать',
    'save_button'         => 'Сохранить',
    'delete_button'       => 'Удалить',
    'error'               => 'Ошибка',
    'missing_field'       => 'Не заполнено как минимум одно поле. Проверьте:',
    'save_fail'           => 'Сохранение не удалось',
    'save_success'        => 'Успешно сохранено',
    'delete_fail'         => 'Удаление не удалось',
    'delete_success'      => 'Успешно удалено',
    'message'             => 'Сообщение',
    'validate_button'     => 'ОК',
    'fields'              => 'Поля',
    'new_field'           => 'Создать новое поле',
    'list_fields'         => 'Список полей',
    'field_name'          => 'Название поля',
    'field_order'         => 'Порядок поля',
    'existing_field'      => 'Существующие поля (порядок и название)',
    'var_name'            => 'Имя переменной',
    'field_help'          => 'Справка для формы отправки',
    'type'                => 'Тип',
    'sel_group'           => 'Группа выбора',
    'create_new_doc'      => 'Создать новый документ',
    'field_require'       => 'Сделать это поле обязательным в формах отправки и редактирования',
    'field_on_list'       => 'Показывать это поле столбцом в списках документов',
    'edit_doc'            => 'Редактировать документ',
    'selects'             => 'Варианты выбора',
    'list_groups'         => 'Список групп',
    'new_group'           => 'Создать новую группу',
    'list_selects'        => 'Список вариантов выбора',
    'new_select'          => 'Создать новый вариант выбора',
    'new_option'          => 'Создать новую опцию',
    'group'               => 'Группа',
    'group_name'          => 'Название группы',
    'group_help'          => 'Справка группы',
    'edit_group'          => 'Редактировать группу',
    'select_name'         => 'Название (или значение опции)',
    'select_value'        => 'Значение, показываемое пользователям',
    'existing_select'     => 'Существующие варианты выбора',
    'select_order'        => 'Порядок выбора',
    'doc_submission'      => 'Отправка документа',
    'submission'          => 'Отправка',
    'pending_moderation'  => 'Ожидает модерации',
    'not_active'          => 'Неактивен',
    'draft'               => 'Черновик',
    'active_label'        => 'Статус документа',
    'active'              => 'Активен',
    'submission_recorded' => 'Ваш документ сохранён. Перед публикацией он будет проверен. Спасибо.',
    'submissions_list'    => 'Список отправленных материалов',
    'submissions_list_2'  => 'Список документов на проверке',
    'drafts_list'         => 'Список черновиков документов',
    'documents_list'      => 'Список документов',
    'reserved_to'         => 'Для доступа к этому документу вы должны состоять в группе:',
    'cat_hidden'          => 'Скрытая категория',
    'see_all_docs'        => 'Все документы',
    'use_map'             => 'Использовать карту',
    'use_map_details'     => 'Для геолокации документов выберите карту, на которую следует добавить маркеры.',
    'nonactive'           => 'Неактивен',
    'limited_access'      => 'Ограниченный доступ',
    'private'             => 'Частный',
    'doc_by'              => 'Этот документ от',
    'displayed'           => 'был показан',
    'times'               => 'раз.',
    'select_album'        => 'Выберите альбом:',
    'no_map'              => '-- Нет --',
    'read_more_marker'    => 'Показать документ',
    'document_draft'      => 'Этот документ находится в режиме черновика. Доступ разрешён только владельцу.',
    'document_submit'     => 'Этот документ ещё не одобрен и пока недоступен.',
    'new_comment'         => 'Новый комментарий к',
    'stats_title'         => 'Десять лучших документов',
    'stats_documents'     => 'Опубликованные документы',
    'stats_views'         => 'Просмотры',
    'whatsnew_title'      => 'Недавние документы',
    'whatsnew_none'       => 'Недавних документов нет.',
    'more_information'    => 'Подробнее',
    'integrity_audit_title'              => 'Аудит целостности данных',
    'integrity_audit_notice'             => 'Этот отчёт доступен только для чтения. Данные и файлы не изменяются.',
    'integrity_check'                    => 'Проверка',
    'integrity_result'                   => 'Результат',
    'integrity_duplicate_category_slugs' => 'Дублирующиеся слаги категорий',
    'integrity_duplicate_document_slugs' => 'Дублирующиеся слаги документов',
    'integrity_documents_without_values' => 'Документы без значений',
    'integrity_values_without_document'  => 'Значения без документа',
    'integrity_values_without_field'     => 'Значения без поля',
    'integrity_fields_without_category'  => 'Поля без категории',
    'integrity_missing_images'           => 'Указанные файлы изображений отсутствуют на диске',
    'integrity_unreferenced_images'      => 'Файлы изображений, не используемые Documents',
    'integrity_back_admin'               => 'Вернуться к администрированию Documents'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'Документы',
    'title' => 'Настройка Documents'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Папка Documents',
    'documents_main_header' => 'Основной заголовок Documents',
    'documents_main_footer' => 'Основной подвал Documents',
    'whatsnew_enabled'      => 'Показывать Documents в разделе «Что нового»',
    'whatsnew_interval'     => 'Период «Что нового» (секунды)',
    'whatsnew_limit'        => 'Максимум недавних документов',
    'stats_visibility'      => 'Видимость статистики',
    'max_image_width'       => 'Максимальная ширина изображения (пиксели)',
    'max_image_height'      => 'Максимальная высота изображения (пиксели)',
    'max_image_size'        => 'Максимальный размер файла изображения (байты)',
    'default_permissions'   => 'Разрешения по умолчанию'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Основные настройки'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Основные настройки Documents'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Основные настройки Documents',
    'fs_integrations' => 'Отображение и интеграции',
    'fs_images'       => 'Изображения',
    'fs_permissions'  => 'Разрешения по умолчанию'
);

$LANG_configselects['documents'] = array(
    0  => array('Да' => 1, 'Нет' => 0),
    1  => array('Да' => true, 'Нет' => false),
    12 => array('Нет доступа' => 0, 'Только чтение' => 2, 'Чтение и запись' => 3),
    20 => array(
        'Скрыто' => 0,
        'Только администраторы' => 1,
        'Авторизованные пользователи и администраторы' => 2,
        'Все, включая анонимных посетителей' => 3
    )
);