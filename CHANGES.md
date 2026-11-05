# Changelog

## [1.0.2] - 2026-06-23

### Added
- Primary navigation now appears for users via a new `before_http_headers` hook callback that injects custom page links into `$CFG->custommenuitems`
- Defensive upgrade step that re-applies the `depth`, `path`, `sortthread`, `iscontainer` and `showinprimarynav` schema fields for sites that skipped intermediate upgrades

### Fixed
- Removed duplicate `sortthread` field selection in the Report Builder `custompages` entity
- Fixed `sortorder` Report Builder column using the wrong `lang_string` component (`core` instead of `local_custompage`)

## [1.0.1] - 2026-05-01

### Fixed
- Fixed `CONTEXT_CUSTOMPAGE` undefined constant error in namespaced context class
- Fixed `$PAGE->context` ordering in `view.php` to prevent filter warnings
- Fixed missing hook registrations in `db/hooks.php`
- Removed deprecated legacy callback causing Moodle 4.4+ warnings

## [1.0.0] - 2026-03-27

Initial stable release targeting Moodle 4.5+.

### Added
- Hierarchical custom page management with container and content page types
- Custom context (CONTEXT_CUSTOMPAGE) for full Moodle block support
- 5 audience types for access control: allusers, guests, admins, systemrole, manual
- Primary navigation integration via custom menu items
- Vancode-based sortthread ordering for efficient hierarchical sorting
- Report Builder integration with pages_list and page_access_list system reports
- 5 AJAX web services for page and audience management
- 7 event classes for page and audience lifecycle tracking
- Inline editing for page names, titles, and audience headings
- Privacy API implementation (GDPR compliant)
- Moodle 4.3+ hooks support with legacy callback fallback
- Caching infrastructure for page data and audiences
- PHPUnit test suite and Behat acceptance tests
