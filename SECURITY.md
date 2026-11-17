# Security policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 0.1.x   | yes       |

## Reporting a vulnerability

Please do **not** open a public issue. Use GitHub's private vulnerability
reporting ("Security" tab, "Report a vulnerability") on
<https://github.com/saizul/wp-debloat>, or contact the maintainer through
<https://saizul.com>.

Include the affected version, a description and steps to reproduce. You can
expect an acknowledgement within 7 days. Please give us reasonable time to
release a fix before disclosing the issue.

A tweak that does not fully close the leak it describes (for example a way to
list user names while `rest_user_enumeration` is on) is a security bug too;
report it privately.

## How the plugin protects your site

- **Admin**: the settings screen and every form handler require
  `manage_options`. Save, preset, import and export each have their own nonce,
  checked with `check_admin_referer()` before anything changes.
- **Input**: settings from the form, an import file or WP-CLI go through the
  same sanitiser. Only registered tweak IDs are kept; values are clamped
  numbers, one of a fixed list of choices, or lines matching a strict pattern.
  Imports are limited to 64 KB and a JSON depth of 16.
- **Output**: everything printed on the settings screen is escaped with
  `esc_html()`, `esc_attr()`, `esc_url()` or `esc_textarea()`. Redirects use
  `wp_safe_redirect()`.
- **Fail safe**: a tweak that throws an error is skipped and reported. The
  `WP_DEBLOAT_DISABLE` constant switches every tweak off without touching the
  database.
- **No remote requests**, no tracking, no data leaves the site.
- **Uninstall** removes the `wp_debloat_settings` option on every site of a
  network.

The hardening tweaks reduce information leaks and attack surface. They do not
replace updates, strong passwords, two-factor authentication, backups or a
firewall.
