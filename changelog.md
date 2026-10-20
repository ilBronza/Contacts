# Changelog

All notable changes to `Contacts` will be documented in this file.

## Unreleased

### Fixed
- `addContact()` limits reuse to the current owner and contact type; loaded contacts are refreshed.
- Creation routes require package roles, an allowed owner type and owner view/update permissions.
- Contact icons and tooltips escape untrusted values; all renderers share safe link generation.
- Email extraction preserves subdomains and long TLDs without accepting partial matches.
- Nullable contact types and missing records are handled safely; resolved relations are remembered.
- The href/icon migration rolls back correctly; the contact type index loads contact counts.

### Added
- `ContactEmailVerificationHelper` checks individual mailboxes through the Verifalia API; only an explicit successful mailbox result sets the verification flag. Catch-all and inconclusive results remain unverified.
- Boolean `existence_verified` column and model cast, defaulting to false; address/type changes reset it, including quiet saves. The flag records a positive mailbox result from the provider, never a DNS-only check.
- Configurable Verifalia API credentials, resumable pending jobs in Laravel cache, and an `EmailExistenceVerifier` contract for alternative providers.
- Email verification regression tests cover provider failures, uncertain results, pending jobs, unchanged addresses, concurrent edits and migration of existing contacts.
- Configurable contact normalization and validation on new or changed model values, including quiet saves.
- Configurable allowed URI schemes with dedicated email, phone and web link handling.
- PHPUnit regression tests, explicit dependencies and installation/upgrade documentation.

### Compatibility
- Run `php artisan migrate` to add `existence_verified`; existing contacts remain unverified until explicitly checked. Configure Verifalia credentials and a persistent Laravel cache before invoking the helper.
- Creating contacts now requires update permission on the owner and a configured owner class.
- Existing contacts are not rewritten; invalid new or changed values raise Laravel validation errors.
- Phone format validation accepts 7–15 digits with an optional leading plus and extension.
- Raw SQL and query-builder bulk writes remain the caller's responsibility.

## Version 1.0

### Added
- Everything
