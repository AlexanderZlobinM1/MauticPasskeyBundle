# MauticPasskeyBundle — convenient passwordless sign-in for Mautic

I am presenting `MauticPasskeyBundle`, a free open-source Mautic plugin that
adds modern sign-in through Passkey, WebAuthn, Touch ID, Face ID, Windows
Hello, and other system authentication methods.

I created this plugin to make Mautic sign-in more convenient and secure,
without requiring users to type their password every time. After a one-time
registration, a user can confirm sign-in directly on their device.

The plugin is released under the **GPL-3.0-or-later** license and is available
for free use, inspection, and modification.

I welcome feedback, bug reports, improvement ideas, and pull requests. If the
plugin is useful in your project, I would be glad to hear how it works for you
on different browsers, devices, and Mautic configurations.

If a translation for your language is not available yet, pull requests with a
localization are very welcome. I will review the proposed changes and add a
ready translation to the project.

## Features

- Sign in with Touch ID, Face ID, Windows Hello, or a security key.
- Confirm sign-in with the device PIN when supported by the platform.
- Register a Passkey directly on Mautic's standard login page.
- Sign in without manually entering a username and password.
- Register multiple Passkeys for one Mautic user.
- Show Passkey status in Mautic's Users table.
- Let an administrator clear Passkeys for a selected user.
- Register a new Passkey after the previous credentials have been cleared.
- Work without an external paid authentication service.
- Store only public WebAuthn credential data in Mautic.
- Include the required production WebAuthn dependencies in the installable
  package.
- Provide English, Russian, and Serbian translations.

## Requirements

- Mautic `7.2.x`;
- PHP `8.2` or newer;
- HTTPS on the production domain;
- a modern browser with Passkey/WebAuthn support;
- Touch ID, Face ID, Windows Hello, a device PIN, or another compatible
  authenticator.

Passkeys should be used over HTTPS in production. A browser may allow a local
development exception for localhost fixtures.

Biometric data is not sent to Mautic. It is processed by the operating system
or the device authenticator.

## Download

Use the current moving package for normal installation:

ZIP:

```text
https://github.com/AlexanderZlobinM1/MauticPasskeyBundle/releases/latest/download/MauticPasskeyBundle.zip
```

TAR.GZ:

```text
https://github.com/AlexanderZlobinM1/MauticPasskeyBundle/releases/latest/download/MauticPasskeyBundle.tar.gz
```

## Important archive note

Use the prepared installable archives from the release Assets section.

Do not use GitHub's automatically generated archives:

```text
Source code (zip)
Source code (tar.gz)
```

GitHub's automatically generated source archives do not contain the required
production WebAuthn dependencies. The prepared plugin package contains:

```text
vendor/autoload.php
```

and the locked `web-auth/webauthn-lib` runtime.

## Installation

### Manual installation

1. Back up the Mautic files and database.
2. Download the prepared ZIP or TAR.GZ package.
3. Extract it into:

   ```text
   plugins/MauticPasskeyBundle
   ```

   The resulting layout should include:

   ```text
   plugins/
   └── MauticPasskeyBundle/
       ├── Config/
       ├── Controller/
       ├── Entity/
       ├── Migrations/
       ├── Resources/
       ├── Service/
       ├── Translations/
       ├── vendor/
       │   └── autoload.php
       ├── MauticPasskeyBundle.php
       └── composer.json
   ```

4. Make sure file ownership and permissions match the rest of the Mautic
   installation.
5. From the Mautic root directory, run the normal plugin reload and migration
   workflow:

   ```bash
   php bin/console mautic:plugins:reload
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console cache:clear
   ```

   If your deployment uses a different standard Mautic update workflow, use
   that workflow instead.

6. Open:

   ```text
   Settings → Plugins
   ```

7. Find **Passkey Login**.
8. Enable the plugin with its standard switch.
9. Save the settings.
10. Sign out of Mautic and open the login page.

The migration creates the plugin-owned table:

```text
plugin_mautic_passkey_credentials
```

If your Mautic installation uses a table prefix, Mautic applies it
automatically. Manual table creation and manual SQL are not required.

### Installation through a catalog or MCC

If your catalog or MCC installation supports an archive URL, use:

```text
https://github.com/AlexanderZlobinM1/MauticPasskeyBundle/releases/latest/download/MauticPasskeyBundle.tar.gz
```

After downloading the package, use Mautic's standard plugin installation or
update workflow. Do not replace the installable package with a GitHub
Source-code archive.

## First Passkey registration

1. Open the Mautic login page.
2. Enter the normal Mautic username and password.
3. Click **Register passkey**.
4. Confirm the operation in the device system dialog, for example with:

   - Touch ID;
   - Face ID;
   - Windows Hello;
   - a device PIN;
   - a security key;
   - another available authenticator.

5. After successful verification, the Passkey is linked to the Mautic user.
6. The user is signed in to Mautic.

The username and password are required only to verify the account before the
Passkey is created.

## Signing in with a registered Passkey

After registration, the button is shown as:

```text
Continue with passkey
```

To sign in:

1. Open the Mautic login page.
2. Click **Continue with passkey**.
3. Confirm your identity using the device authenticator.
4. Wait for the verification to complete.

The username and password fields may be left empty. Mautic identifies the
user from the registered Passkey.

If the username and password fields contain values, the registered Passkey
still takes priority when the Passkey button is used.

The regular username-and-password sign-in button continues to work separately.

## Registering a Passkey for another user

After at least one Passkey exists in the system, the main button is normally
shown as **Continue with passkey**.

A user who does not have a Passkey yet can:

1. enter their Mautic username and password;
2. choose the option to register a Passkey for that account;
3. confirm the operation on the device;
4. complete the registration.

If no Passkey is selected, the page offers the registration fallback after the
username and password have been entered.

## Multiple Passkeys

One user may register multiple Passkeys:

- on different computers;
- on a phone or tablet;
- in a security key;
- in the operating system or browser Passkey manager.

If the operating system or Passkey manager supports synchronization, a
registered Passkey may also be available on the user's other devices.

## Administrator management

The Mautic Users table adds two columns:

- **Passkey** — shows whether Passkeys are registered and how many records
  exist;
- **Clear Passkey** — clears Passkeys for the selected user.

Clicking **Clear Passkey** opens a standard confirmation dialog.

After confirmation, all registered Passkeys for the selected user are removed.

Clearing Passkeys:

- does not delete the user;
- does not change the password;
- does not change user permissions;
- does not disable the user account;
- does not disable the plugin;
- does not change other user settings;
- allows the user to register a new Passkey.

After the reset, the user signs in with the normal username and password and
can then register a new Passkey.

## Working with GoogleAuthBundle

`MauticPasskeyBundle` can work alongside my open-source
[`GoogleAuthBundle`](https://github.com/AlexanderZlobinM1/GoogleAuthBundle).

Both plugins belong to the same family of free alternative-login extensions
for Mautic and can be installed together. The user can choose among:

- Passkey sign-in;
- Google sign-in;
- regular Mautic authentication.

When both plugins are installed, both buttons are displayed, but the **or**
separator appears only once. Its text is selected automatically according to
the current Mautic interface language.

Both projects are available for free use, inspection, and modification:

- [MauticPasskeyBundle](https://github.com/AlexanderZlobinM1/MauticPasskeyBundle)
- [GoogleAuthBundle](https://github.com/AlexanderZlobinM1/GoogleAuthBundle)

## Updating the plugin

1. Back up the Mautic files and database.
2. Download the current installable package.
3. Update the directory:

   ```text
   plugins/MauticPasskeyBundle
   ```

4. Do not use a GitHub Source-code archive.
5. Run the standard update workflow:

   ```bash
   php bin/console mautic:plugins:reload
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console cache:clear
   ```

6. Open:

   ```text
   Settings → Plugins → Passkey Login
   ```

7. Make sure the plugin is enabled.
8. Test the login page.
9. Test a user with an already registered Passkey.

Existing Passkeys should continue to work after an update.

Do not delete the following table before updating:

```text
plugin_mautic_passkey_credentials
```

## Disabling the plugin

Disable the plugin at:

```text
Settings → Plugins → Passkey Login
```

When the plugin is disabled:

- the Passkey button disappears from the login page;
- the Passkey columns disappear from the Users table;
- Passkey management controls are hidden;
- Passkey authentication is not performed;
- regular Mautic authentication remains unchanged.

Enabling the plugin again restores its behavior and the previously stored
Passkeys.

## Removing the plugin

Before removing the plugin:

1. Back up the database.
2. If required, clear users' Passkeys through the Users table.
3. Disable the plugin.
4. Use Mautic's standard plugin removal workflow.

Do not remove plugin tables manually without a backup and a clear data-retention
decision.

## Troubleshooting

### The Passkey button is missing

Check that:

- the plugin is enabled;
- `mautic:plugins:reload` has been executed;
- the Mautic cache has been cleared;
- the site uses HTTPS;
- the installable package was used;
- `vendor/autoload.php` exists;
- `plugins/MauticPasskeyBundle` was extracted correctly.

### The browser or connection does not support Passkeys

Check:

- the browser version;
- HTTPS availability;
- the device authenticator;
- browser permissions;
- the configured domain;
- the operating system Passkey settings.

### The page asks for a username and password

This is expected during the first Passkey registration. The credentials are
required to verify the Mautic account before the Passkey is created.

They are not required for a normal Passkey sign-in after registration.

### The Passkey cannot be verified

Try the following:

1. Repeat the sign-in attempt.
2. Use the same domain where the Passkey was registered.
3. Check the date and time on the device.
4. Make sure the correct Passkey is selected.
5. Check that the device authenticator is available.
6. Ask an administrator to clear the user's Passkeys and register again.

### A WebAuthn class is missing

This usually means that an incomplete GitHub source archive was installed
instead of the prepared installable package.

To fix it:

1. Remove the incomplete installed package.
2. Download the ZIP or TAR.GZ package from the release Assets.
3. Install the prepared package again.
4. Reload the plugins.
5. Clear the Mautic cache.

### An error appears after an update

Run:

```bash
php bin/console mautic:plugins:reload
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
```

Also check:

- file ownership and permissions;
- the presence of `vendor/autoload.php`;
- the presence of `plugin_mautic_passkey_credentials`;
- Mautic and PHP logs;
- HTTPS availability.

## Security

The plugin uses the WebAuthn standard and requires user verification on the
device.

- challenges are one-time values;
- challenges are bound to the session;
- challenges are restricted to the configured origin and relying party;
- challenges expire after five minutes;
- Mautic stores only public credential data;
- biometric data is never sent to the server;
- the user's password is not deleted or changed;
- HTTPS is required for production use.

## Feedback and contribution

This is an open-source project. Contributions are welcome, including:

- bug reports;
- improvement ideas;
- browser and device compatibility reports;
- localization pull requests;
- documentation improvements;
- code pull requests.

If your language is not yet available, send a pull request with the
translation. I will review it and add the completed localization to the
project.

## Links

- [MauticPasskeyBundle repository](https://github.com/AlexanderZlobinM1/MauticPasskeyBundle)
- [Current latest publication](https://github.com/AlexanderZlobinM1/MauticPasskeyBundle/releases/tag/latest)
- [GoogleAuthBundle](https://github.com/AlexanderZlobinM1/GoogleAuthBundle)
- [GPL-3.0-or-later license](https://github.com/AlexanderZlobinM1/MauticPasskeyBundle/blob/main/LICENSE)
