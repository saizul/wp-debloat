=== Debloat & Harden ===
Contributors: saizul
Tags: performance, security, xml-rpc, emoji, disable comments
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn off WordPress bloat and close common information leaks with safe, individually switchable tweaks. Presets, import/export and WP-CLI.

== Description ==

Debloat & Harden collects 41 small, well-known WordPress tweaks in one place. Every tweak is off until you switch it on, explains what it changes and what can break, and shows a risk badge (low, medium or high).

**Performance**: remove emoji scripts, oEmbed discovery and the embed script, jQuery Migrate on the front end, Dashicons for logged-out visitors, block library CSS on pages without blocks, global styles and duotone filters on classic themes; slow down or disable Heartbeat; disable self pingbacks; limit revisions; change the autosave interval; remove RSD, Windows Live Writer, shortlink, REST API and extra feed links; stop loading remote block patterns; remove version query strings (optional).

**Security and hardening**: disable XML-RPC completely or only its pingback methods, block user enumeration through the REST API and ?author=N, remove users from the core sitemap, hide the WordPress version, disable the theme and plugin file editor, use a generic login error, disable application passwords, send basic security headers, and (advanced) restrict the REST API for logged-out visitors to an allowlist.

**Content**: disable comments everywhere (front end, admin menus, admin bar, REST API, feeds, XML-RPC), redirect attachment pages, disable author archives, the front-end search, RSS feeds or the core sitemaps.

**Admin**: clean up the dashboard, remove the Events and News widget, hide the admin bar on the front end, hide update notices from non-administrators, disable the block directory and the admin e-mail confirmation screen.

**Features**

* Settings > Debloat: grouped toggles, risk badges, search filter, "show only enabled".
* Presets: Recommended (low risk only), Aggressive (low and medium risk), Off.
* Import and export of settings as JSON.
* WP-CLI: wp debloat list, enable, disable, set, preset, status, export, import.
* Safe mode: define( 'WP_DEBLOAT_DISABLE', true ); in wp-config.php stops every tweak.
* Developers can add their own tweaks with the debloat_tweaks filter and skip tweaks per request with debloat_skip_tweak.
* No tracking, no remote requests. One option, removed on uninstall.

Development happens on GitHub: https://github.com/saizul/wp-debloat. New tweaks are welcome; each tweak is one small class.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate the plugin. Nothing changes until you switch tweaks on.
3. Go to Settings > Debloat and click Recommended, or switch on single tweaks.
4. Test your site.

== Frequently Asked Questions ==

= Will this plugin break my site? =

Nothing is switched on until you do it. The Recommended preset only contains low-risk tweaks. Medium and high risk tweaks explain what they can break. Turn tweaks on one at a time and test.

= Something broke and I cannot reach the admin. =

Add define( 'WP_DEBLOAT_DISABLE', true ); to wp-config.php. No tweak is applied while the constant is set; your settings are kept.

= Does "Disable comments" delete comments? =

No. Existing comments stay in the database and come back when you switch the tweak off.

= My contact form stopped working after restricting the REST API. =

Add the form plugin's REST namespace to the allowlist, for example /contact-form-7/v1.

= Is this a security plugin? =

It closes common information leaks and attack surfaces. It is not a firewall or malware scanner; use it next to those and keep everything updated.

= What is removed when I delete the plugin? =

The wp_debloat_settings option, on every site of a network.

== Screenshots ==

1. Settings > Debloat with presets, search and grouped tweaks.
2. A tweak with a value and a risk badge.
3. Import and export.

== Changelog ==

= 0.1.0 =
* First release: 41 tweaks in four groups, settings screen with presets, search and import/export, WP-CLI command, debloat_tweaks and debloat_skip_tweak filters, safe mode.

== Upgrade Notice ==

= 0.1.0 =
First release.
