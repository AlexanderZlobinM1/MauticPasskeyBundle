# Compatibility index

Release `1.0.0` is applicable to Mautic `7.2.x`, with exact acceptance on
Mautic 7.2.1 / PHP 8.4 / MySQL 8.4 on 25 November 2026.

Acceptance covered normal plugin discovery, Doctrine creation of
`plugin_mautic_passkey_credentials`, idempotent plugin update, published
integration discovery, valid-password registration options, invalid-password
401 rejection, discoverable assertion options with required user verification,
one localized separator with Google/Mail.ru/Yandex, and simultaneous AuthGuard
and Passkey columns/actions in Mautic's native Users table. Provider protocols
for Google, Mail.ru and Yandex were outside this UI-only acceptance.
