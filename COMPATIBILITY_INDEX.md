# Compatibility index

Release `1.0.2` is applicable to Mautic `7.2`, inheriting the preceding release's
confirmed compatibility because the change is limited to release packaging and
does not alter runtime compatibility. Exact acceptance remains on Mautic
7.2.1 / PHP 8.4 / MySQL 8.4 on 25 November 2026. Plugin compatibility matrices
omit the patch digit; `7.2` covers all bug-fix releases in that branch.

Acceptance covered normal plugin discovery, Doctrine creation of
`plugin_mautic_passkey_credentials`, idempotent plugin update, published
integration discovery, valid-password registration options, invalid-password
401 rejection, discoverable assertion options with required user verification,
one localized separator with Google/Mail.ru/Yandex, and simultaneous AuthGuard
and Passkey columns/actions in Mautic's native Users table. Provider protocols
for Google, Mail.ru and Yandex were outside this UI-only acceptance.

The 1.0.2 package additionally contains a locked minimal WebAuthn runtime and
loads it after Mautic's own Composer loader. This fixes standalone archive
installations where a source-only package exposed the Passkey button without
providing `web-auth/webauthn-lib`; the release also exposes a stable archive
asset name for MCC latest-release synchronization.
