# Changelog

## 1.0.4 - 2026-11-27

- Add Packagist-ready Composer metadata with the verified Mautic 7.2.x
  constraint (`>=7.2 <7.3`) and project support links.
- Keep the release metadata and Composer package version aligned at `1.0.4`.

## 1.0.3 - 2026-11-25

- Fix the plugin metadata version so MCC Sync detects the release as `1.0.3`
  instead of retaining the previous `1.0.1` value from `Config/config.php`.

## 1.0.2 - 2026-11-25

- Fix the published release package so standalone installations receive the
  plugin-local `vendor/autoload.php` and locked WebAuthn runtime dependencies.
- Keep the installable archive available under a stable release-asset name for
  MCC latest-release synchronization.

## 1.0.1 - 2026-11-25

- Ship an installable release archive with locked production Composer
  dependencies, including `web-auth/webauthn-lib`.
- Load the plugin-local production autoloader when the Mautic root project does
  not already provide WebAuthn classes.
- Prevent standalone MCC/MCD installations from exposing a Passkey button that
  fails at runtime because the WebAuthn library is absent.
- Register the ceremony and challenge-store services under explicit Mautic
  container IDs so the authentication subscriber receives service objects
  instead of literal class-name strings.
- Reduce integration settings to Mautic's single global Active switch. The
  enabled plugin exposes all Passkey behavior; the disabled plugin exposes no
  login button, Users-table columns, or Passkey controls.
- Replace the Plugins-page tile artwork with the approved Sales Snap Passkey
  PNG asset.

## 1.0.0 - 2026-11-25

- Add passwordless, discoverable WebAuthn passkey registration and sign-in.
- Add per-user Passkey status and clear controls to Mautic's Users table.
- Add safe Users-table composition with AuthGuard.
- Add the shared single-separator contract for alternative login providers.
- Require user verification, exact RP/origin validation, one-time challenges,
  and HTTPS outside localhost fixtures.
- Add English, Russian, and Serbian translations.
- Fix the canonical Mautic integration service ID so a normal plugin install
  discovers and persists the Passkey integration without manual repair.
