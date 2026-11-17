# Debloat & Harden

[![CI](https://github.com/saizul/wp-debloat/actions/workflows/ci.yml/badge.svg)](https://github.com/saizul/wp-debloat/actions/workflows/ci.yml)
[![Release zip](https://github.com/saizul/wp-debloat/actions/workflows/release-zip.yml/badge.svg)](https://github.com/saizul/wp-debloat/actions/workflows/release-zip.yml)
![PHP](https://img.shields.io/badge/PHP-7.4%20to%208.4-777bb4)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)
[![Good first issues](https://img.shields.io/github/issues/saizul/wp-debloat/good%20first%20issue?label=good%20first%20issues)](https://github.com/saizul/wp-debloat/labels/good%20first%20issue)

Turn off WordPress bloat and close common information leaks with **safe,
individually switchable tweaks**. Every tweak is off until you switch it on,
says what it changes and what can break, and carries a risk badge. Pick a
preset, adjust single tweaks, copy the settings to your other sites, or do it
all from WP-CLI.

Plugin slug: `wp-debloat` - display name: *Debloat & Harden*.

- No tracking, no remote requests, no upsells.
- One option row; tweaks only add or remove hooks at run time, so switching
  a tweak off (or deleting the plugin) restores WordPress defaults at once.
- Each tweak is one small class. Adding one is a great first contribution:
  see [Add a new tweak](#add-a-new-tweak).

## Contents

- [Features](#features)
- [Tweaks](#tweaks)
- [Requirements and installation](#requirements-and-installation)
- [Usage](#usage)
- [Presets](#presets)
- [WP-CLI](#wp-cli)
- [Import and export](#import-and-export)
- [Safe mode](#safe-mode)
- [Hooks for developers](#hooks-for-developers)
- [Add a new tweak](#add-a-new-tweak)
- [FAQ](#faq)
- [Compatibility](#compatibility)
- [Development and testing](#development-and-testing)
- [Limitations](#limitations)
- [Contributing, security and license](#contributing-security-and-license)

## Features

- **41 tweaks** in four groups: performance, security and hardening, content,
  admin.
- Settings screen at **Settings > Debloat**: grouped toggles, risk badges
  (low, medium, high), a search filter, "show only enabled", and notes such as
  the `DISALLOW_FILE_EDIT` hint while the related tweak is on.
- **Presets**: Recommended (low risk only), Aggressive (low and medium risk)
  and Off. High-risk and opt-in tweaks are never switched on by a preset.
- **Import/export** as a small JSON file, from the screen or WP-CLI.
- **WP-CLI**: `wp debloat list | enable | disable | set | preset | status |
  export | import`.
- **Extensible**: register your own tweaks with the `debloat_tweaks` filter,
  skip a tweak per request with `debloat_skip_tweak`.
- **Safe mode**: `define( 'WP_DEBLOAT_DISABLE', true );` stops every tweak.
- A tweak that throws an error is skipped and reported; it never takes the
  site down.
- Capability checks, nonces, strict sanitising against the registry,
  escaped output, translation ready, multisite-aware uninstall.

## Tweaks

The table is built from the tweak classes by `bin/gen-readme-table.php`; CI
fails when it is out of date.

<!-- tweaks:start -->
41 tweaks. Every tweak is **off** until you switch it on.

### Performance (18)

| ID | Tweak | Risk | Presets | Value (default) |
| --- | --- | --- | --- | --- |
| `emojis` | **Remove emoji scripts and styles**<br>Removes the inline emoji detection script and emoji styles from every page, the admin and embeds, and stops converting emoji to images in feeds and e-mails. Modern browsers display emoji on their own. | Low | Recommended, Aggressive | - |
| `embeds` | **Disable WordPress post embeds (oEmbed provider)**<br>Removes the oEmbed discovery links, the oEmbed REST route and the wp-embed script, and stops discovering oEmbed data on unknown sites. Other sites can no longer embed your posts as cards. Embeds from known providers such as YouTube keep working. | Low | Recommended, Aggressive | - |
| `jquery_migrate` | **Remove jQuery Migrate on the front end**<br>Loads jQuery without the jQuery Migrate compatibility layer for visitors. Old themes or plugins that use removed jQuery functions can break; check the browser console after switching it on. The admin is not changed. | Medium | Aggressive | - |
| `dashicons_logged_out` | **Remove Dashicons for logged-out visitors**<br>Dequeues the Dashicons icon font on the front end for visitors who are not logged in (the admin bar needs it only for logged-in users). If a theme or plugin declares Dashicons as a dependency, it is still loaded. | Low | Recommended, Aggressive | - |
| `block_css_classic_pages` | **Remove block library CSS on pages without blocks**<br>On single posts and pages written without blocks (classic editor, page builders), dequeues the block library stylesheets. Only for classic themes; skipped on block themes, on archives and when block widgets are in use. Blocks added by the theme templates or shortcodes can lose their styling. | Medium | Aggressive | - |
| `global_styles` | **Remove global styles and duotone filters (classic themes)**<br>Removes the inline global-styles CSS (colour and font-size preset classes from theme.json) and the duotone SVG filters on the front end. Only applies to classic themes. Blocks that use preset colours, font sizes or duotone can lose that styling. | Medium | Aggressive | - |
| `heartbeat_front_end` | **Disable the Heartbeat API on the front end**<br>Stops the Heartbeat script (regular admin-ajax.php requests) on front-end pages. The admin and the editor keep Heartbeat for autosave and post locking. Front-end features of some plugins (live chat, front-end editors) can rely on it. | Medium | Aggressive | - |
| `heartbeat_interval` | **Slow down the Heartbeat API**<br>Sets how often the browser sends Heartbeat requests (WordPress uses 15 to 60 seconds). Fewer requests mean less server load on busy admin screens. Kept at 120 seconds or less so post locks keep working. | Low | Recommended, Aggressive | `60` (15 to 120 s) |
| `self_pingbacks` | **Disable self pingbacks**<br>Stops WordPress from sending pingbacks to your own site when a post links to another post, which creates pointless pingback comments and requests. | Low | Recommended, Aggressive | - |
| `revisions_limit` | **Limit post revisions**<br>Keeps at most this many revisions per post instead of an unlimited number, which keeps the posts table small. Older revisions are removed the next time a post is saved. A lower WP_POST_REVISIONS value in wp-config.php is respected; 0 turns revisions off. | Low | Recommended, Aggressive | `5` (0 to 100) |
| `autosave_interval` | **Change the autosave interval**<br>Autosaves posts every this many seconds instead of every 60 seconds, which means fewer requests and autosave revisions. If AUTOSAVE_INTERVAL is already defined in wp-config.php, the block editor still uses this value but the classic editor uses the constant. | Low | Recommended, Aggressive | `120` (30 to 3600 s) |
| `asset_query_strings` | **Remove version query strings from scripts and styles**<br>Removes the ver= parameter from front-end CSS and JavaScript URLs. Only useful for proxies that refuse to cache URLs with query strings. Visitors and caches may keep old files after an update until their cache expires, so this is not part of any preset. | Medium | - | - |
| `rsd_link` | **Remove the RSD link**<br>Removes the Really Simple Discovery link from the page head. It only helps old blog editors find the XML-RPC endpoint. | Low | Recommended, Aggressive | - |
| `wlw_manifest` | **Remove the Windows Live Writer manifest link**<br>Removes the wlwmanifest link for the discontinued Windows Live Writer editor. WordPress 6.3 and later no longer print it; the tweak is kept for older versions. | Low | Recommended, Aggressive | - |
| `shortlink` | **Remove the shortlink**<br>Removes the ?p= shortlink from the page head and the Link HTTP header. Search engines use the canonical URL instead. | Low | Recommended, Aggressive | - |
| `rest_api_link` | **Remove the REST API discovery link**<br>Removes the api.w.org link from the page head and the Link HTTP header. The REST API itself keeps working; only the advertisement is removed. | Low | Recommended, Aggressive | - |
| `feed_links_extra` | **Remove comment and archive feed links**<br>Removes the head links to the comments feed and to category, tag, author and search feeds. The main posts feed link stays and all feeds keep working. | Low | Recommended, Aggressive | - |
| `remote_block_patterns` | **Do not load remote block patterns**<br>Stops the block editor from downloading core and featured patterns from the WordPress.org pattern directory. Patterns registered by your theme and plugins are still available. | Low | Recommended, Aggressive | - |

### Security and hardening (11)

| ID | Tweak | Risk | Presets | Value (default) |
| --- | --- | --- | --- | --- |
| `xmlrpc_off` | **Disable XML-RPC completely**<br>Turns off every xmlrpc.php method (the xmlrpc_enabled filter alone only blocks methods that need a login) and removes the X-Pingback header. Blocks brute-force and pingback abuse through XML-RPC. Breaks tools that still use XML-RPC, such as Jetpack connections, very old mobile apps and some desktop editors. | Medium | Aggressive | - |
| `xmlrpc_pingbacks` | **Disable XML-RPC pingback methods**<br>Removes the pingback.ping and pingback.extensions.getPingbacks XML-RPC methods and the X-Pingback header, so your site cannot be used for pingback DDoS amplification. Other XML-RPC methods (Jetpack, apps) keep working. Incoming pingbacks stop. | Low | Recommended, Aggressive | - |
| `rest_user_enumeration` | **Block user enumeration through the REST API**<br>Logged-out visitors get a 401 error from /wp-json/wp/v2/users, which otherwise lists the user names (slugs) of everyone who published a post. Logged-in users and the block editor are not affected. | Low | Recommended, Aggressive | - |
| `author_query_enumeration` | **Block ?author=N user enumeration**<br>Redirects requests such as /?author=1 to the home page before WordPress turns them into /author/user-name/, so bots cannot read user names by counting IDs. Author archive links themselves keep working. | Low | Recommended, Aggressive | - |
| `sitemap_users` | **Remove users from the core XML sitemap**<br>Removes the wp-sitemap-users-1.xml sitemap, which lists the author archive URL (and so the user name) of everyone who published a post. Posts, pages and terms stay in the sitemap. Has no effect when an SEO plugin replaces the core sitemap. | Low | Recommended, Aggressive | - |
| `hide_wp_version` | **Hide the WordPress version**<br>Removes the generator meta tag from the page head and feeds, and replaces ver= values equal to the WordPress version in script and style URLs with a hash, so the version is not shown but caches are still refreshed after an update. | Low | Recommended, Aggressive | - |
| `file_editor` | **Disable the theme and plugin file editor**<br>Removes the Theme File Editor and Plugin File Editor screens for everyone, so a stolen administrator login cannot be used to write PHP code from the dashboard. Installing and updating plugins is not affected. | Low | Recommended, Aggressive | - |
| `login_errors` | **Generic login error message**<br>Shows one generic message on the login screen instead of telling visitors whether the user name or the password was wrong, so bots cannot confirm that an account exists. | Low | Recommended, Aggressive | - |
| `application_passwords` | **Disable application passwords**<br>Turns off application passwords, which let external apps log in to the REST API and XML-RPC with a separate password. Existing application passwords stop working. Breaks integrations that use them (some mobile apps, automation services, remote publishing tools). | Medium | Aggressive | - |
| `security_headers` | **Send basic security headers**<br>Adds X-Content-Type-Options: nosniff, X-Frame-Options: SAMEORIGIN and Referrer-Policy: strict-origin-when-cross-origin to front-end pages, unless another plugin already set them. Other sites can no longer show your pages in a frame. | Medium | Aggressive | - |
| `rest_logged_out_allowlist` | **Restrict the REST API for logged-out visitors**<br>Logged-out visitors get a 401 error from every REST API route except the ones listed below (one namespace or route per line, e.g. /contact-form-7/v1). Contact forms, shops, search and other front-end features that call the REST API break unless their route is listed. Test every form and front-end feature after switching it on. | **High** | - | `/oembed/1.0` (list) |

### Content (6)

| ID | Tweak | Risk | Presets | Value (default) |
| --- | --- | --- | --- | --- |
| `comments` | **Disable comments everywhere**<br>Closes comments and pings on all content, hides existing comments, removes the Comments admin menu, admin bar item, dashboard and discussion screens, the comments feed and its links, the comments REST API routes, the Recent Comments widget and the comment-reply script. Existing comments are not deleted and come back when the tweak is switched off. | Medium | Aggressive | - |
| `attachment_pages` | **Redirect attachment pages**<br>Redirects the thin attachment pages WordPress creates for every upload to the post the file is attached to, or to the file itself when it is not attached or the parent is not public. Also switches the "attachment pages" option of WordPress 6.4+ off. | Low | Recommended, Aggressive | - |
| `author_archives` | **Disable author archives**<br>Author archive pages (/author/name/) return a 404 or redirect to the home page, and author links point to the home page. Useful for single-author sites, where the author archive duplicates the blog. Not part of any preset. | Medium | - | `404` (`404`, `home`) |
| `search` | **Disable the front-end search**<br>Search requests (?s=) on the front end return a 404, the search form and the Search widget are removed and the admin bar search box is hidden. Spam searches no longer cost database queries. Search in the admin and in the REST API is not changed, and the Search block in block themes still renders. Not part of any preset. | Medium | - | - |
| `feeds` | **Disable RSS and Atom feeds**<br>Redirects every feed URL to the home page and removes all feed links from the page head. Feed readers, podcast apps, newsletter tools and services that import your posts through RSS stop working. Not part of any preset. | Medium | - | - |
| `core_sitemaps` | **Disable the core XML sitemaps**<br>Turns off wp-sitemap.xml. Only switch this on when an SEO plugin provides its own sitemap or you do not want search engines to get one. Not part of any preset. | Medium | - | - |

### Admin (6)

| ID | Tweak | Risk | Presets | Value (default) |
| --- | --- | --- | --- | --- |
| `dashboard_widgets` | **Clean up the dashboard**<br>Removes the Welcome panel, the Quick Draft box and the Activity box from the dashboard. At a Glance and Site Health stay. Boxes added by other plugins are not touched. | Low | Recommended, Aggressive | - |
| `dashboard_news` | **Remove the WordPress Events and News widget**<br>Removes the WordPress Events and News box from the dashboard and the network dashboard, so the dashboard no longer fetches remote news feeds and nearby events. | Low | Recommended, Aggressive | - |
| `admin_bar_front_end` | **Hide the admin bar on the front end**<br>Hides the toolbar on the front end for users who cannot manage options (subscribers, customers, authors), or for everyone. The admin is not changed. | Low | Recommended, Aggressive | `non_admins` (`non_admins`, `everyone`) |
| `update_nags` | **Hide update notices from non-administrators**<br>Editors, authors and other users who cannot update WordPress no longer see the "WordPress x.y is available" and maintenance notices. Administrators still see them and updates are not affected. | Low | Recommended, Aggressive | - |
| `block_directory` | **Disable the block directory in the editor**<br>Stops the block editor from suggesting and installing blocks from WordPress.org when a search in the inserter finds nothing. Blocks can still be installed as plugins. | Low | Recommended, Aggressive | - |
| `admin_email_check` | **Disable the admin e-mail confirmation screen**<br>Stops WordPress from asking administrators every six months, right after login, to confirm that the site admin e-mail address is still correct. | Low | Recommended, Aggressive | - |
<!-- tweaks:end -->

Risk levels:

- **Low**: no visible change for visitors; only removes output nobody needs.
- **Medium**: can break a feature some sites use (an integration, a theme
  that relies on old jQuery, framing by another site). Test after switching
  it on.
- **High**: can break forms, shops or the whole front end if misconfigured.
  Only for people who know which REST routes their site uses.

## Requirements and installation

- WordPress 6.0 or newer (tested up to 7.1).
- PHP 7.4 to 8.4.
- No other dependencies and no build step.

**From a release zip.** Download `wp-debloat.zip` from the
[latest release](https://github.com/saizul/wp-debloat/releases), then go to
*Plugins > Add New > Upload Plugin*, upload the zip and activate it.

**With Git.**

```sh
cd wp-content/plugins
git clone https://github.com/saizul/wp-debloat.git
wp plugin activate wp-debloat
```

**With Composer** (`type: wordpress-plugin`, for setups such as Bedrock):

```sh
composer config repositories.wp-debloat vcs https://github.com/saizul/wp-debloat
composer require saizul/wp-debloat
```

Nothing changes on activation: every tweak starts off.

## Usage

1. Go to **Settings > Debloat**.
2. Click **Recommended** for a safe start, or switch on single tweaks.
3. Tweaks with a value (revision limit, heartbeat interval, the REST
   allowlist and so on) show their input under the description. Values are
   kept while a tweak is off.
4. Click **Save changes**. Changes take effect on the next page load.
5. Browse your site, submit a form, open the editor. If something broke,
   switch the last tweak off again (or use [safe mode](#safe-mode)).

Use the search box to filter by title, description or ID, and "Show only
enabled" to review what is on.

## Presets

| Preset | Switches on | Use it when |
| --- | --- | --- |
| Recommended | Every low-risk tweak | Almost any site. Removes head clutter, emoji, oEmbed discovery, enumeration leaks, XML-RPC pingbacks, the file editor and dashboard noise. |
| Aggressive | Low and medium risk tweaks | You can test the site afterwards: also XML-RPC off, comments off, jQuery Migrate off, block CSS on classic pages, security headers and more. |
| Off | Nothing | Back to WordPress defaults. |

A preset **replaces** which tweaks are on; values you entered are kept.
Tweaks marked "-" in the Presets column (high risk or niche: the REST
allowlist, version query strings, search, feeds, author archives, core
sitemaps) are never switched on by a preset.

Each tweak decides which presets include it (`Tweak::presets()`, by default
from its risk level), so new tweaks join the presets automatically.

## WP-CLI

```sh
# All tweaks, with group, risk, status and value.
wp debloat list
wp debloat list --group=security --format=json
wp debloat list --enabled --format=ids

# Switch tweaks on or off (all IDs are checked before anything is saved).
wp debloat enable emojis embeds rsd_link
wp debloat disable jquery_migrate
wp debloat disable all

# Set a value.
wp debloat set revisions_limit 10
wp debloat set rest_logged_out_allowlist /oembed/1.0,/contact-form-7/v1

# Presets.
wp debloat preset recommended
wp debloat preset aggressive --dry-run

# What is on, for scripts and monitoring.
wp debloat status
wp debloat status --format=json

# Copy settings between sites.
wp debloat export > debloat.json
wp --url=other.example.com debloat import debloat.json
```

`wp debloat status --format=json` prints:

```json
{
    "version": "0.1.0",
    "safe_mode": false,
    "total": 41,
    "enabled": ["emojis", "rsd_link", "revisions_limit"],
    "values": {"revisions_limit": 5},
    "applied": ["emojis", "rsd_link", "revisions_limit"],
    "skipped": [],
    "errors": {}
}
```

`applied` lists what ran on the current (CLI) request; `skipped` lists tweaks
turned off by the `debloat_skip_tweak` filter.

## Import and export

*Settings > Debloat > Import and export* downloads the settings as JSON and
imports a file or pasted JSON:

```json
{
    "plugin": "wp-debloat",
    "schema": 1,
    "version": "0.1.0",
    "enabled": ["emojis", "rsd_link", "revisions_limit"],
    "values": {"revisions_limit": 5}
}
```

Imports are validated like the settings form: unknown tweak IDs are skipped
(and counted in the notice), values are sanitised by the tweak, files over
64 KB, files from other plugins and files from a newer schema are refused.
The import replaces the current settings.

## Safe mode

If a tweak breaks your site and you cannot reach the admin, add this line to
`wp-config.php`, above `/* That's all, stop editing! */`:

```php
define( 'WP_DEBLOAT_DISABLE', true );
```

No tweak is applied while the constant is true. The settings are kept, the
settings screen shows a warning and you can still change them; remove the
line to apply them again. `wp debloat status` reports `"safe_mode": true`.

Without file access, you can also rename the `wp-content/plugins/wp-debloat`
folder or run `wp plugin deactivate wp-debloat`.

## Hooks for developers

### `debloat_tweaks`

Filters the list of tweaks. Add instances (or class names) of classes that
extend `Saizul\Debloat\Tweak\Tweak`, or remove built-in ones. It runs on
`plugins_loaded`, so add the filter from a plugin or a must-use plugin (a
theme's `functions.php` loads too late).

```php
add_filter( 'debloat_tweaks', function ( array $tweaks ) {
	$tweaks[] = new My_Plugin\Tweaks\CartFragments();
	return $tweaks;
} );
```

A third-party tweak may use its own group key (for example `woocommerce`);
it is shown after the built-in groups. Invalid tweaks (bad or duplicate ID,
unknown risk level) are ignored with a `_doing_it_wrong()` notice and listed
on the settings screen.

### `debloat_skip_tweak`

Filters whether an enabled tweak is skipped on the current request.

```php
// Keep the REST API open for an internal monitoring IP.
add_filter( 'debloat_skip_tweak', function ( bool $skip, string $id ) {
	if ( 'rest_logged_out_allowlist' === $id && '203.0.113.10' === ( $_SERVER['REMOTE_ADDR'] ?? '' ) ) {
		return true;
	}
	return $skip;
}, 10, 2 );
```

Parameters: `bool $skip`, `string $id`, `Saizul\Debloat\Tweak\Tweak $tweak`.

## Add a new tweak

Tweaks are deliberately small, so a new one is a good first pull request.
Look for issues labelled
[`good first issue`](https://github.com/saizul/wp-debloat/labels/good%20first%20issue)
or [`new tweak`](https://github.com/saizul/wp-debloat/labels/new%20tweak), or
propose your own with the "New tweak" issue template.

**1. Create the class** in `src/Tweaks/<Group>/`, where the group is
`Performance`, `Security`, `Content` or `Admin`:

```php
<?php
/**
 * Tweak: remove the X-Powered-By header.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the PHP version header from responses.
 */
final class PoweredByHeader extends Tweak {

	public function id(): string {
		return 'powered_by_header'; // Never change it after a release.
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Remove the X-Powered-By header', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the X-Powered-By header that reveals the PHP version. Has no effect when the web server adds it.', 'wp-debloat' );
	}

	// Optional: risk() defaults to Risk::LOW, presets() follows the risk.

	public function apply(): void {
		add_action( 'send_headers', array( $this, 'remove_header' ) );
	}

	/**
	 * Remove the header.
	 */
	public function remove_header(): void {
		if ( ! headers_sent() ) {
			header_remove( 'X-Powered-By' );
		}
	}
}
```

Rules that keep tweaks safe:

- `apply()` runs on `plugins_loaded` and must only add or remove hooks. Do the
  work inside the callbacks, where the current user, the query and the theme
  are known.
- Callbacks must be public methods (or core helpers like `__return_false`).
- Hooks that WordPress adds later (for example admin hooks from
  `wp-admin/includes/admin-filters.php`) must be removed from a later hook
  such as `admin_init`; see `Emojis::remove_admin_hooks()`.
- Translate only in `title()`, `description()`, `field_label()`,
  `choice_label()` and `notice()`, which run after `init`.
- The description says what changes **and what can break**, and ends with a
  full stop.
- Pick an honest risk level. Override `presets()` to return `array()` for
  niche tweaks that should never be switched on by a preset.
- Verify every core hook and function name against
  [developer.wordpress.org](https://developer.wordpress.org/reference/) or
  the WordPress source, and mention the version that introduced it if it is
  newer than WordPress 6.0.

Optional extras:

- `field()` returns `Field::int( $default, $min, $max, $unit )`,
  `Field::select( $default, $choices )` or `Field::lines( $default, $pattern )`;
  read the value with `$this->value()`. Add `field_label()` (and
  `choice_label()` for selects).
- `notice()` returns a hint shown while the tweak is on.
- `$this->is_front_end()` and `$this->redirect( $url )` helpers.

**2. Register it** by adding the class to `src/TweakList.php`, in its group.

**3. Test it.** Add a test to `tests/Unit/Tweaks/<Group>TweaksTest.php` that
calls `apply()` and checks the hooks or filter results. The WordPress stubs
in `tests/Stubs/wordpress.php` record hooks, dequeued assets, removed menus
and redirects. Add a stub if your tweak calls a function that is missing.
`RegistryTest` checks the metadata of every tweak automatically.

**4. Regenerate the files** and run the checks:

```sh
composer readme      # update the table in README.md
composer make-pot    # update languages/wp-debloat.pot
composer test
composer phpcs
```

**5. Open a pull request** and fill in the checklist. Mention how you tested
it on a real site.

## FAQ

**Will this plugin break my site?**
Not by itself: nothing is switched on until you do it. The Recommended
preset contains only tweaks that remove output nobody needs. Medium and high
risk tweaks say what they can break. Turn tweaks on one at a time and test.

**Is this a security plugin?**
It closes common information leaks and attack surfaces (XML-RPC, user
enumeration, the file editor, version disclosure). It is not a firewall,
malware scanner or login limiter; use it next to those, and keep WordPress,
themes and plugins updated.

**Does hiding the WordPress version make me safe?**
No. It only removes an easy fingerprint. Updates are what protect you.

**The settings screen says a tweak was "ignored".**
Another plugin added a tweak through `debloat_tweaks` with a missing or
duplicate ID or an unknown risk level. Tell that plugin's author.

**I enabled "Restrict the REST API" and my contact form stopped working.**
Add the form plugin's REST namespace to the allowlist, for example
`/contact-form-7/v1`, `/wc/store/v1` for WooCommerce blocks or `/jetpack/v4`.
The browser's network tab shows which `/wp-json/...` URLs your pages call.

**Does "Disable comments" delete comments?**
No. Comments stay in the database and come back when you switch the tweak
off. Use *Comments > Bulk actions* first if you want to delete them.

**What happens when I delete the plugin?**
`uninstall.php` deletes the `wp_debloat_settings` option on every site of the
network. Because tweaks only add hooks, nothing else needs cleaning up.

**Does it work with caching plugins?**
Yes. Clear the page cache after changing tweaks that alter the HTML (head
links, emoji, block CSS, the admin bar).

**Multisite?**
Settings are per site. Network activation works; configure each site, or use
`wp --url=... debloat import` to roll one file out to many sites.

## Compatibility

| Software | Notes |
| --- | --- |
| Jetpack, WordPress mobile apps, old desktop editors | Need XML-RPC: do not use `xmlrpc_off`. `xmlrpc_pingbacks` is fine. |
| WooCommerce | Block checkout and cart use `/wc/store/v1`: add it to the REST allowlist if you enable it. Hiding the admin bar for "non_admins" also hides it for shop managers who cannot manage options. |
| Contact Form 7, other form plugins | Add their REST namespace to the allowlist (`/contact-form-7/v1`). |
| Yoast SEO, Rank Math, other SEO plugins | Their own attachment redirect and sitemap features overlap with `attachment_pages`, `sitemap_users` and `core_sitemaps`; use one or the other. |
| Page builders (Elementor, Beaver Builder, Divi) | Pages built with them have no blocks, so `block_css_classic_pages` removes block CSS there; switch it off if builder modules use core blocks. |
| Block themes | `global_styles` and `block_css_classic_pages` skip block themes automatically. |
| Classic Editor plugin | Supported; `autosave_interval` uses the `AUTOSAVE_INTERVAL` constant for it. |
| Caching and optimisation plugins | Compatible; avoid enabling the same feature in two plugins. |

Supports WordPress 6.0 to 7.1. Every core hook the tweaks use was checked
against the WordPress source; the unit tests run in CI on PHP 7.4 to 8.4
against WordPress stubs (see [Limitations](#limitations)).

## Development and testing

```sh
composer install
composer test           # PHPUnit on light WordPress stubs, no database needed
composer phpcs          # WordPress Coding Standards and PHP 7.4+ compatibility
composer lint           # php -l on every file
composer readme-check   # README tweak table is current
composer make-pot       # regenerate languages/wp-debloat.pot
```

Project layout:

```
wp-debloat.php           Plugin header and autoloader
uninstall.php            Deletes the option on every site
src/Plugin.php           Wiring: registry, settings, runner, admin, CLI
src/Registry.php         Validated list of tweaks, `debloat_tweaks` filter
src/TweakList.php        Built-in tweak classes
src/Settings.php         Stored settings (one option)
src/Settings/Sanitizer.php
src/Presets.php          Recommended / Aggressive / Off
src/Transfer.php         JSON import and export
src/Runner.php           Applies enabled tweaks, safe mode, `debloat_skip_tweak`
src/Tweak/               Tweak base class, Field, Group, Risk
src/Tweaks/<Group>/      One class per tweak
src/Admin/SettingsPage.php
src/Cli/Command.php
assets/                  Settings screen CSS and JS (no build step)
tests/                   PHPUnit tests and WordPress/WP-CLI stubs
bin/                     README table and POT generators
```

CI runs on PHP 7.4, 8.0, 8.1, 8.2, 8.3 and 8.4: `composer validate`, `php -l`,
phpcs, PHPUnit, the README table check, the POT check and `node --check` for
the admin script. Publishing a GitHub release builds `wp-debloat.zip` with
the files listed in `.distignore` left out and attaches it to the release.

## Limitations

- Tests run against stubs, not a real WordPress install: they prove each
  tweak adds and removes the right hooks, not how every theme and plugin
  reacts. Test on a staging copy of your site.
- Tweaks apply on `plugins_loaded`. Plugins that add the same output later
  (for example their own emoji or oEmbed scripts) are not affected.
- Removing a hook only works if core (or another plugin) added it with the
  default callback and priority. Themes that print RSD or generator tags
  directly keep them.
- `asset_query_strings` and `hide_wp_version` only change URLs printed through
  `wp_enqueue_script()` and `wp_enqueue_style()`.
- `autosave_interval` cannot override an `AUTOSAVE_INTERVAL` constant that is
  already defined in `wp-config.php` for the classic editor.
- `rest_logged_out_allowlist` and `rest_user_enumeration` work at the REST
  API level; a web server or CDN in front can still cache and serve old
  responses.
- `security_headers` adds headers to WordPress page responses only, not to
  static files served directly by the web server.
- Theme `functions.php` cannot register tweaks through `debloat_tweaks`
  (themes load after `plugins_loaded`); use a must-use plugin.

## Contributing, security and license

- [CONTRIBUTING.md](CONTRIBUTING.md): setup, coding standards, pull requests.
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
- [SECURITY.md](SECURITY.md): please report vulnerabilities privately.
- [CHANGELOG.md](CHANGELOG.md).

Debloat & Harden is free software under the
[GPL-2.0-or-later](LICENSE). Copyright (c) 2026 Saizul Amin
([saizul.com](https://saizul.com)).
