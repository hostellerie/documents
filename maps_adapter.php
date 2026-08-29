<?php

/* Documents -> Maps interoperability adapter. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'maps_adapter.php') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Ask Maps to create or update a marker for a Documents item.
 * Documents never writes to Maps tables and never allocates marker IDs.
 *
 * @return array array(success, marker id, error message)
 */
function DOCUMENTS_mapsSaveMarker($documentId, $categorySlug, $field, $request, $ownerId, $groupId, $permissions, $active)
{
    global $_DOCUMENTS_CONF;

    if (!DOCUMENTS_hasMaps() || !function_exists('PLG_invokeService')) {
        return array(false, '', 'Maps marker service is unavailable.');
    }
    if (!is_array($field) || !is_array($request)) {
        return array(false, '', 'Invalid Maps marker request.');
    }

    $fieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
    $mapId = isset($field['sel_id']) ? (int) $field['sel_id'] : 0;
    $varName = isset($field['var_name']) ? (string) $field['var_name'] : '';
    if ($fieldId <= 0 || $mapId <= 0 || $varName === '') {
        return array(false, '', 'Maps marker field is not configured correctly.');
    }

    $markerId = isset($request[$varName]) ? DOCUMENTS_normalizeFieldInput('marker', $request[$varName]) : '';
    $name = isset($request['name']) ? DOCUMENTS_plainTextInput($request['name']) : '';
    if ($name === '') {
        foreach ($request as $key => $value) {
            if ($key === $varName || is_array($value) || is_object($value)) {
                continue;
            }
            $candidate = DOCUMENTS_plainTextInput($value);
            if ($candidate !== '') {
                $name = $candidate;
                break;
            }
        }
    }

    $address = isset($request['address']) ? DOCUMENTS_plainTextInput($request['address']) : '';
    $lat = isset($request['lat']) ? trim((string) $request['lat']) : '';
    $lng = isset($request['lng']) ? trim((string) $request['lng']) : '';
    $sourceUrl = DOCUMENTS_interopCanonicalUrl($categorySlug, $documentId);

    $args = array(
        'source' => 'documents',
        'source_id' => (string) $documentId,
        'source_url' => $sourceUrl,
        'marker_id' => $markerId,
        'map_id' => $mapId,
        'name' => $name,
        'address' => $address,
        'lat' => $lat,
        'lng' => $lng,
        'active' => ((int) $active === DOCUMENTS_STATUS_ACTIVE) ? 1 : 0,
        'hidden' => 0,
        'owner_id' => (int) $ownerId,
        'group_id' => (int) $groupId,
        'perm_owner' => (int) $permissions[0],
        'perm_group' => (int) $permissions[1],
        'perm_members' => (int) $permissions[2],
        'perm_anon' => (int) $permissions[3],
        'operation_id' => 'documents:' . (string) $documentId . ':field:' . $fieldId . ':' . md5(serialize(array(
            $markerId, $mapId, $name, $address, $lat, $lng, (int) $active,
            (int) $ownerId, (int) $groupId, (int) $permissions[0],
            (int) $permissions[1], (int) $permissions[2], (int) $permissions[3]
        )))
    );

    $output = array();
    $svcMsg = array();
    $result = PLG_invokeService('maps', 'marker_save', $args, $output, $svcMsg);
    if ($result !== PLG_RET_OK) {
        $message = isset($svcMsg['error_desc']) ? (string) $svcMsg['error_desc'] : 'Maps marker save failed.';
        return array(false, '', $message);
    }

    $savedId = isset($output['id']) ? preg_replace('/[^0-9]/', '', (string) $output['id']) : '';
    if ($savedId === '') {
        return array(false, '', 'Maps did not return a marker id.');
    }

    return array(true, $savedId, '');
}
