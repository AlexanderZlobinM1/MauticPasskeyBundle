# Changelog

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
