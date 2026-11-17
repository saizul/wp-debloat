# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-11-17

### Added

- 41 individually switchable tweaks, all off by default, each with an ID,
  group, title, description, risk level and default presets:
  - Performance (18): emoji, oEmbed provider and embed script, jQuery Migrate
    on the front end, Dashicons for logged-out visitors, block library CSS on
    pages without blocks, global styles and duotone on classic themes,
    Heartbeat on the front end and its interval, self pingbacks, revision
    limit, autosave interval, version query strings, RSD, Windows Live
    Writer, shortlink, REST API link, extra feed links, remote block
    patterns.
  - Security and hardening (11): XML-RPC off, XML-RPC pingback methods, REST
    API user enumeration, `?author=N` enumeration, users in the core sitemap,
    WordPress version, file editor (with a `DISALLOW_FILE_EDIT` hint), generic
    login errors, application passwords, basic security headers, REST API
    allowlist for logged-out visitors.
  - Content (6): comments everywhere, attachment pages, author archives,
    front-end search, feeds, core sitemaps.
  - Admin (6): dashboard clean-up, Events and News widget, admin bar on the
    front end, update notices for non-administrators, block directory, admin
    e-mail confirmation.
- Settings > Debloat screen with grouped toggles, risk badges, search and
  "show only enabled" filters, value inputs, preset buttons and JSON import
  and export. Every form checks `manage_options` and its own nonce.
- Presets: Recommended, Aggressive and Off, defined by each tweak.
- WP-CLI command `wp debloat` with `list`, `enable`, `disable`, `set`,
  `preset`, `status`, `export` and `import`.
- `debloat_tweaks` filter to add or remove tweaks and `debloat_skip_tweak`
  filter to skip a tweak per request.
- Safe mode with the `WP_DEBLOAT_DISABLE` constant; failing tweaks are caught
  and reported instead of breaking the site.
- Translation template, uninstall clean-up on every site of a network.
- PHPUnit tests on WordPress stubs, phpcs (WordPress Coding Standards), CI on
  PHP 7.4 to 8.4, README table check and a release workflow that builds the
  plugin zip.

[Unreleased]: https://github.com/saizul/wp-debloat/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/saizul/wp-debloat/releases/tag/v0.1.0
