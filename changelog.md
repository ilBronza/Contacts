# Changelog

All notable changes to `Contacts` will be documented in this file.

## Unreleased

### Fixed
- Email verification command failures always show exception details in the terminal and explicitly call the application logger with contact ID, operation and exception, even for exceptions excluded from reporting. `-v` also shows the logging channel and configuration path; logger failures remain visible without interrupting the batch.
- `addContact()` limits reuse to the current owner and contact type; loaded contacts are refreshed.
- Creation routes require package roles, an allowed owner type and owner view/update permissions.
- Contact icons and tooltips escape untrusted values; all renderers share safe link generation.
- Email extraction preserves subdomains and long TLDs without accepting partial matches.
- Nullable contact types and missing records are handled safely; resolved relations are remembered.
- The href/icon migration rolls back correctly; the contact type index loads contact counts.

### Added
- `contacts:send-email-verifications [contact] --limit=10` sends confirmation emails in batches of at most 25 attempts, skipping verified contacts and live links and reporting per-contact failures.
- Optional `--queue`, `--connection` and `--queue-name` use the host application's Laravel queue; `SendContactEmailVerification` waits for commit, generates links at execution time and skips obsolete or duplicate sends without imposing a driver or worker configuration.
- Stored `email_verification_expires_at` allows expired links to be retried; atomic eligibility checks preserve pending links, and mail transport failures release only their own token for a subsequent attempt.
- Direct email confirmation via `sendVerificationEmail()` or a custom email using `createVerificationUrl()`, with configurable seven-day signed links, single-use hashed tokens and automatic revocation on address/type changes.
- Public signed confirmation route, HTML/plain-text mail templates, Italian/English messages and a confirmation page; no Verifalia credentials are needed for this mode.
- `ContactEmailVerificationHelper` checks individual mailboxes through the Verifalia API; only an explicit successful mailbox result sets the verification flag. Catch-all and inconclusive results remain unverified.
- Boolean `existence_verified` column and model cast, defaulting to false; address/type changes reset it, including quiet saves. The flag records a positive mailbox result from the provider or a successful email-link confirmation, never a DNS-only check.
- Configurable Verifalia API credentials, resumable pending jobs in Laravel cache, and an `EmailExistenceVerifier` contract for alternative providers.
- Email verification regression tests cover provider failures, uncertain results, pending jobs, unchanged addresses, concurrent edits and migration of existing contacts.
- Configurable contact normalization and validation on new or changed model values, including quiet saves.
- Configurable allowed URI schemes with dedicated email, phone and web link handling.
- PHPUnit regression tests, explicit dependencies and installation/upgrade documentation.

### Compatibility
- Run `php artisan migrate` for `email_verification_expires_at` before using the command or updated helpers. Legacy tokens without a stored expiry remain valid and are skipped by batch sending; use the existing helper to explicitly renew them. No queue configuration is required for immediate sends.
- Run the additional token-hash migration for email confirmation, configure the Laravel mailer and public `APP_URL`, and merge `emailVerification.email.expires_days` into published configuration.
- Run `php artisan migrate` to add `existence_verified`; existing contacts remain unverified until explicitly checked. Configure Verifalia credentials and a persistent Laravel cache before invoking the helper.
- Creating contacts now requires update permission on the owner and a configured owner class.
- Existing contacts are not rewritten; invalid new or changed values raise Laravel validation errors.
- Phone format validation accepts 7–15 digits with an optional leading plus and extension.
- Raw SQL and query-builder bulk writes remain the caller's responsibility.

## Version 1.0

### Added
- Everything
