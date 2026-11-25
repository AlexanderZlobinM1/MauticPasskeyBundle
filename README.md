# MauticPasskeyBundle

Passwordless WebAuthn/passkey sign-in for Mautic 7. The plugin is licensed
under GPL-3.0-or-later and maintained by Alexander Zlobin for Sales Snap.

## Login behavior

- With no registered credentials, the alternative login button registers a
  passkey after Mautic validates the entered username and password.
- Once credentials exist, the button starts a discoverable passkey ceremony.
  The account bound to the selected passkey wins over any text left in the
  username and password fields.
- The ordinary Mautic **Sign in** button remains the native password flow.
- A user may register more than one passkey. An administrator can clear only
  the selected user's passkeys from Mautic's Users table.
- When AuthGuard is also enabled, AuthGuard protects the native password flow.
  Successful plugin authentication (Passkey, Google, Yandex, Mail.ru, or a
  future Mautic `PluginToken` provider) is not challenged a second time.

## Security model

The plugin uses `web-auth/webauthn-lib` and requires user verification and a
discoverable credential. Challenges are random, one-time, session-bound,
origin/RP-bound, and expire after five minutes. Production use requires HTTPS.
Mautic stores the public credential record; biometric data remains inside the
device authenticator and is never sent to Mautic.

## Installation

Install the packaged release archive as `plugins/MauticPasskeyBundle`, then run
Mautic's normal plugin reload and migration/update workflow. Do not use
GitHub's automatically generated source archive: the packaged release contains
the locked production dependencies required by WebAuthn and loads them locally
when Mautic does not already provide them. The migration creates the plugin-owned
`plugin_mautic_passkey_credentials` table; no Mautic core table is modified.

Enable **Passkey Login** in Plugins. It can coexist with AuthGuard and with the
Sales Snap Google, Yandex, and Mail.ru login plugins.

## Shared alternative-login separator

All participating Sales Snap login plugins expose the same DOM contract. A
singleton coordinator leaves exactly one localized `or` separator immediately
before the topmost alternative-login button, regardless of plugin load order or
AJAX/DOM reinitialization.

## Development

```bash
composer install --ignore-platform-req=ext-imap
composer test
```
