<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | spanish_utf-8.php                                                               |
// |                                                                           |
// | Spanish language file                                                     |
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
    'plugin_name'         => 'Documentos',
    'categories'          => 'Categorías',
    'browse_categories'   => 'Explorar documentos prácticos por categoría',
    'browse_documents'    => 'Explorar documentos',
    'documents'           => 'Documentos',
    'category'            => 'Categoría',
    'new_cat'             => 'Crear una nueva categoría',
    'edit_cat'            => 'Editar una categoría',
    'cat_name'            => 'Nombre de la categoría',
    'cat_url'             => 'Nombre para la URL (sin espacios)',
    'cat_url_exists'      => 'Este nombre de URL ya existe. Elija otro.',
    'css'                 => 'CSS',
    'template'            => 'Plantilla',
    'cat_order'           => 'Orden',
    'existing_cat'        => 'Categorías existentes (orden, nombre y nombre de URL)',
    'none'                => 'Ninguno',
    'cat_help'            => 'Ayuda para el formulario de envío (puede contener una etiqueta automática)',
    'list_index'          => 'Mostrar esta categoría en la lista',
    'submitable'          => 'Los usuarios pueden enviar documentos a esta categoría',
    'custom_header'       => 'Encabezado personalizado',
    'custom_footer'       => 'Pie de página personalizado',
    'required_field'      => 'Indica un campo obligatorio',
    'admin'               => 'Administración',
    'list_categories'     => 'Categorías',
    'edit'                => 'Editar',
    'save_button'         => 'Guardar',
    'delete_button'       => 'Eliminar',
    'error'               => 'Error',
    'missing_field'       => 'Falta al menos un campo. Compruebe:',
    'save_fail'           => 'Error al guardar',
    'save_success'        => 'Guardado correctamente',
    'delete_fail'         => 'Error al eliminar',
    'delete_success'      => 'Eliminado correctamente',
    'message'             => 'Mensaje',
    'validate_button'     => 'Aceptar',
    'fields'              => 'Campos',
    'new_field'           => 'Crear un nuevo campo',
    'list_fields'         => 'Lista de campos',
    'field_name'          => 'Nombre del campo',
    'field_order'         => 'Orden del campo',
    'existing_field'      => 'Campos existentes (orden y nombre)',
    'var_name'            => 'Nombre de variable',
    'field_help'          => 'Ayuda para el formulario de envío',
    'type'                => 'Tipo',
    'sel_group'           => 'Grupo de selección',
    'create_new_doc'      => 'Crear un nuevo documento',
    'field_require'       => 'Exigir este campo en los formularios de envío y edición',
    'field_on_list'       => 'Mostrar este campo como columna en las listas de documentos',
    'edit_doc'            => 'Editar un documento',
    'selects'             => 'Selecciones',
    'list_groups'         => 'Lista de grupos',
    'new_group'           => 'Crear un nuevo grupo',
    'list_selects'        => 'Lista de selecciones',
    'new_select'          => 'Crear una nueva selección',
    'new_option'          => 'Crear una nueva opción',
    'group'               => 'Grupo',
    'group_name'          => 'Nombre del grupo',
    'group_help'          => 'Ayuda del grupo',
    'edit_group'          => 'Editar grupo',
    'select_name'         => 'Nombre (o valor de la opción)',
    'select_value'        => 'Valor mostrado a los usuarios',
    'existing_select'     => 'Selecciones existentes',
    'select_order'        => 'Orden de selección',
    'doc_submission'      => 'Envío de documento',
    'submission'          => 'Envío',
    'pending_moderation'  => 'Pendiente de moderación',
    'not_active'          => 'Inactivo',
    'draft'               => 'Borrador',
    'active_label'        => 'Estado del documento',
    'active'              => 'Activo',
    'submission_recorded' => 'Su documento se ha guardado. Será revisado antes de publicarse. Gracias.',
    'submissions_list'    => 'Lista de envíos',
    'submissions_list_2'  => 'Lista de documentos en revisión',
    'drafts_list'         => 'Lista de documentos en borrador',
    'documents_list'      => 'Lista de documentos',
    'reserved_to'         => 'Para acceder a este documento, debe pertenecer al grupo:',
    'cat_hidden'          => 'Categoría oculta',
    'see_all_docs'        => 'Todos los documentos',
    'use_map'             => 'Usar mapa',
    'use_map_details'     => 'Para geolocalizar documentos, seleccione el mapa en el que deben añadirse los marcadores.',
    'nonactive'           => 'Inactivo',
    'limited_access'      => 'Acceso limitado',
    'private'             => 'Privado',
    'doc_by'              => 'Este documento de',
    'displayed'           => 'se mostró',
    'times'               => 'veces.',
    'select_album'        => 'Seleccionar álbum:',
    'no_map'              => '-- Ninguno --',
    'read_more_marker'    => 'Mostrar documento',
    'document_draft'      => 'Este documento está en modo borrador. El acceso está reservado a su propietario.',
    'document_submit'     => 'Este documento aún no ha sido aprobado y no está disponible actualmente.',
    'new_comment'         => 'Nuevo comentario en',
    'stats_title'         => 'Los diez documentos principales',
    'stats_documents'     => 'Documentos publicados',
    'stats_views'         => 'Vistas',
    'whatsnew_title'      => 'Documentos recientes',
    'whatsnew_none'       => 'No hay documentos recientes.',
    'more_information'    => 'Más información',
    'integrity_audit_title'              => 'Auditoría de integridad de datos',
    'integrity_audit_notice'             => 'Este informe es de solo lectura. No se modifican datos ni archivos.',
    'integrity_check'                    => 'Comprobación',
    'integrity_result'                   => 'Resultado',
    'integrity_duplicate_category_slugs' => 'Slugs de categoría duplicados',
    'integrity_duplicate_document_slugs' => 'Slugs de documento duplicados',
    'integrity_documents_without_values' => 'Documentos sin valores',
    'integrity_values_without_document'  => 'Valores sin documento',
    'integrity_values_without_field'     => 'Valores sin campo',
    'integrity_fields_without_category'  => 'Campos sin categoría',
    'integrity_missing_images'           => 'Archivos de imagen referenciados que faltan en el disco',
    'integrity_unreferenced_images'      => 'Archivos de imagen no referenciados por Documentos',
    'integrity_back_admin'               => 'Volver a la administración de Documentos'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'Documentos',
    'title' => 'Configuración de Documentos'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Carpeta de Documentos',
    'documents_main_header' => 'Encabezado principal de Documentos',
    'documents_main_footer' => 'Pie de página principal de Documentos',
    'whatsnew_enabled'      => 'Mostrar Documentos en Novedades',
    'whatsnew_interval'     => 'Período de Novedades (segundos)',
    'whatsnew_limit'        => 'Máximo de documentos recientes',
    'stats_visibility'      => 'Visibilidad de estadísticas',
    'max_image_width'       => 'Ancho máximo de imagen (píxeles)',
    'max_image_height'      => 'Alto máximo de imagen (píxeles)',
    'max_image_size'        => 'Tamaño máximo del archivo de imagen (bytes)',
    'default_permissions'   => 'Permisos predeterminados'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Configuración principal'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Configuración principal de Documentos'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Configuración principal de Documentos',
    'fs_integrations' => 'Visualización e integraciones',
    'fs_images'       => 'Imágenes',
    'fs_permissions'  => 'Permisos predeterminados'
);

$LANG_configselects['documents'] = array(
    0  => array('Sí' => 1, 'No' => 0),
    1  => array('Sí' => true, 'No' => false),
    12 => array('Sin acceso' => 0, 'Solo lectura' => 2, 'Lectura y escritura' => 3),
    20 => array(
        'Oculto' => 0,
        'Solo administradores' => 1,
        'Usuarios conectados y administradores' => 2,
        'Todos, incluidos los visitantes anónimos' => 3
    )
);