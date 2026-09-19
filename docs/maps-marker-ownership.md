# Maps marker ownership contract

Documents may reference a Maps marker, but it does not own the marker.

This boundary is mandatory for Documents 1.2.0 and later.

## Ownership

Maps is the sole owner of:

- marker creation;
- marker identifiers (`mkid`);
- marker editing;
- marker activation, hiding and withdrawal;
- marker storage and database schema;
- coordinate validation and geocoding;
- map refresh/rebuild operations;
- marker lifecycle events;
- public marker rendering.

Documents owns only its document content and stores the `mkid` returned by Maps as the value of a `marker` field.

## Allowed Documents → Maps calls

Documents communicates with Maps only through Geeklog's plugin service contract:

```php
PLG_invokeService('maps', 'marker_save', ...);
PLG_invokeService('maps', 'marker_render', ...);
```

A source identity is supplied with marker mutations:

```text
source    = documents
source_id = <Documents document id>
```

Maps validates the request, allocates or resolves the marker, persists it, refreshes its map and emits its own lifecycle notifications.

## Forbidden inside Documents

Documents code must not:

- query or mutate `maps_markers`;
- query or mutate `maps_maps` to manage markers;
- allocate a Maps `mkid`;
- call `updateMap()`;
- duplicate Maps coordinate/geocoding logic;
- generate Maps marker JavaScript directly;
- silently fall back to legacy marker SQL when the Maps service is unavailable.

If a category contains a `marker` field but cannot use the Maps service contract, the mutation must fail rather than bypass this ownership boundary.

## Save flow

```text
Documents form
    ↓
Documents validates its document fields and permissions
    ↓
PLG_invokeService('maps', 'marker_save', ...)
    ↓
Maps creates/updates marker
    ↓
Maps returns mkid
    ↓
Documents stores mkid in documents_values
```

## Delete flow

Deleting a Documents item does not directly delete a Maps row.

Documents asks Maps to withdraw the referenced marker. Only after Maps accepts the operation does Documents delete its own document/value rows.

This prevents public orphan markers without giving Documents ownership of Maps storage.

## Public rendering

The Documents default renderer calls Maps `marker_render`. Documents does not read coordinates or marker storage and does not enqueue the Google Maps implementation itself.

## Compatibility fallback

Legacy fallback remains temporarily available for integrations that do not yet expose an ownership-preserving service contract, such as historical MediaGallery album handling.

A category containing a `marker` field is explicitly excluded from this fallback. Maps ownership is mandatory even when the category also contains another legacy field type.
