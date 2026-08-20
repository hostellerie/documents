# Documents for Geeklog

Documents is a configurable structured-content plugin for Geeklog. A category defines fields and presentation rules, while individual documents store values for those fields.

## Development status

The current development line is **1.1.x**, focused on stabilizing the historical 1.1.0 codebase before the 1.2.0 stable release.

See [ROADMAP.md](ROADMAP.md) for the complete stabilization, architecture and feature roadmap.

## Compatibility target

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**

The code must remain syntactically compatible with PHP 5.6 throughout the 1.x modernization work unless this policy is explicitly changed in a future major release.

## Optional integrations

### Maps

The Geeklog Maps plugin is optional.

When Maps is missing or inactive, Documents must:

- not include Maps PHP files;
- not query Maps tables;
- not enqueue Google Maps or Maps-specific JavaScript;
- not render map or marker fields/controls;
- continue to operate normally for all other field types.

### MediaGallery

The Geeklog MediaGallery plugin is optional.

When MediaGallery is missing or inactive, Documents must:

- not include MediaGallery PHP files;
- not instantiate MediaGallery classes;
- not query MediaGallery data;
- not render album-related controls;
- continue to operate normally for all other field types.

## 1.1.1 development goals

The first stabilization release focuses on:

- removal of unsolicited installation/upgrade telemetry;
- stronger compatibility checks;
- protection of administrative AJAX actions;
- optional Maps and MediaGallery behavior;
- legacy image-processing replacement planning;
- input/output hardening without changing the historical data model.

## Upgrade policy

The 1.1.x series must preserve existing Documents installations and data. Major database restructuring is intentionally deferred until 1.3.0.

Before installing a development build on a production site, back up both the Geeklog database and the Documents image/data directories.

## License

GNU General Public License v2.0 or later, consistent with the existing plugin source headers and LICENSE file.
