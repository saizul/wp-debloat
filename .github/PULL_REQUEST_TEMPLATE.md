## What does this change?

<!-- One or two sentences. Link the issue: "Closes #123". -->

## Type

- [ ] New tweak
- [ ] Fix to an existing tweak
- [ ] Settings screen, presets, WP-CLI or hooks
- [ ] Documentation or translations
- [ ] Tests or tooling

## New tweak checklist

<!-- Delete this section if the pull request does not add a tweak. -->

- [ ] Class in `src/Tweaks/<Group>/`, `final`, registered in `src/TweakList.php`.
- [ ] New, stable ID (lower case and underscores).
- [ ] Description says what changes **and what can break**.
- [ ] Honest risk level; `presets()` overridden if it should not be in a preset.
- [ ] `apply()` only adds or removes hooks.
- [ ] Hook and function names checked against the WordPress source or developer.wordpress.org.
- [ ] Test added in `tests/Unit/Tweaks/`.

## Checks

- [ ] `composer test` passes.
- [ ] `composer phpcs` passes.
- [ ] `composer readme` and `composer make-pot` run, regenerated files committed.
- [ ] `CHANGELOG.md` updated under "Unreleased".

## How did you test it on a real site?

<!-- WordPress and PHP versions, theme, plugins, what you checked. -->
