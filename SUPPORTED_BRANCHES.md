# Supported branches

| Plugin branch | Mautic | PHP | Status |
|---|---|---|---|
| `1.x` | `7.2` | `>=8.2` | Released; exact acceptance on Mautic 7.2.1 |

Exact Mautic 7.2.1 acceptance covered normal discovery and migration,
registration/assertion options, invalid-password rejection, the shared login
separator, and native Users-table coexistence with AuthGuard.

The Composer package is intentionally constrained to the verified
`mautic/core-lib` 7.2.x branch (`>=7.2 <7.3`); exact runtime acceptance was on
7.2.1 and later bug-fix releases inherit that branch qualification.
