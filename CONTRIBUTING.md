# Contributing

Thanks for helping improve Debloat & Harden. New tweaks, bug reports, fixes,
documentation and translations are all welcome, and first-time contributors
are especially welcome: most tweaks are 30 to 80 lines.

By taking part you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).

## Ways to help

- **Add a tweak.** Pick an issue labelled
  [`good first issue`](https://github.com/saizul/wp-debloat/labels/good%20first%20issue)
  or [`new tweak`](https://github.com/saizul/wp-debloat/labels/new%20tweak),
  or propose one with the "New tweak" issue template. The step-by-step guide
  is in the README: [Add a new tweak](README.md#add-a-new-tweak).
- **Report a tweak that breaks something.** Use the "Bug report" template and
  include the tweak ID and the theme or plugin it conflicts with. Sometimes
  the fix is a better description or a higher risk level.
- **Improve a description.** Clear "what can break" notes are as valuable as
  code.
- **Translate** the plugin: `languages/wp-debloat.pot` is the template.

Security problems go to the private channel in [SECURITY.md](SECURITY.md),
not to public issues.

## Development setup

```bash
git clone https://github.com/saizul/wp-debloat.git
cd wp-debloat
composer install
composer test           # PHPUnit on WordPress stubs
composer phpcs          # WordPress Coding Standards + PHPCompatibilityWP
composer lint           # php -l
composer readme         # regenerate the tweak table in README.md
composer make-pot       # regenerate the translations template
```

PHP 7.4 or newer is required. Dependencies are resolved for PHP 7.4
(`config.platform`), so the same lock file works across the CI matrix. There
is no JavaScript build: `assets/admin.js` is plain ES5 without dependencies.

To try the plugin in a real site, symlink or copy the folder into
`wp-content/plugins/` of a local WordPress install (wp-env, LocalWP, DDEV or
similar) and activate it.

## Where things go

- `src/Tweaks/<Group>/` - one class per tweak, registered in
  `src/TweakList.php`.
- `src/Tweak/` - the `Tweak` base class, `Field`, `Group` and `Risk`.
- `src/Registry.php`, `src/Settings.php`, `src/Settings/Sanitizer.php`,
  `src/Presets.php`, `src/Transfer.php`, `src/Runner.php` - the core. Cover
  every change with tests.
- `src/Admin/SettingsPage.php` and `assets/` - the settings screen.
- `src/Cli/Command.php` - `wp debloat`.
- `tests/Stubs/` - light WordPress and WP-CLI stubs. Add a stub when you use a
  new WordPress function; keep it minimal and record calls in `WpStubs` so
  tests can assert them.

## Guidelines for tweaks

- One tweak does one thing. If it needs a long "and also" list, consider two
  tweaks.
- Never change an existing tweak ID; it is stored in settings and export files.
- `apply()` only adds or removes hooks. No output, no database writes, no
  remote requests.
- Be honest about risk. If a tweak can break a popular plugin, say which one
  in the description and use medium or high risk.
- Check hook and function names against the WordPress source or
  developer.wordpress.org, and note in the pull request which WordPress
  versions you tested.

## Coding standards

- WordPress Coding Standards (`composer phpcs` must pass) and PHP 7.4
  compatibility: no union types, no `match`, no named arguments, no
  constructor promotion, and no `...$args` unpacking of arrays with string
  keys.
- Escape all output, sanitise all input, check capabilities and nonces.
- Translatable strings use the `wp-debloat` text domain and are only called
  after `init` (in `title()`, `description()` and the other label methods).
- Keep pull requests focused. Update `CHANGELOG.md` under "Unreleased".

## Pull requests

1. Fork the repository and create a branch (`tweak/powered-by-header`,
   `fix/comments-admin-bar`).
2. Make your change with tests.
3. Run `composer test`, `composer phpcs`, `composer readme` and
   `composer make-pot`, and commit the regenerated files.
4. Open the pull request and fill in the checklist.

CI must pass before a pull request is merged. Maintainers may ask for a
different risk level, a clearer description or a smaller scope; that is part
of keeping every tweak safe to switch on.

## Releases

Maintainers bump the version in `wp-debloat.php` (header and
`WP_DEBLOAT_VERSION`), `src/Plugin.php` (`VERSION`), `readme.txt`
(`Stable tag`) and `CHANGELOG.md`, then publish a GitHub release tagged
`vX.Y.Z`. The release workflow checks that the versions match, builds
`wp-debloat.zip` and attaches it to the release.
