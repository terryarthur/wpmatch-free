# WP Match Free — Architecture Overview

> Last Updated: 2025-08-07
> Version: 1.0.0
> Scope: WordPress.org-compliant dating plugin (free tier)

## 1. Goals and Non‑Goals
- Goals: privacy-first matching, performant user profiles, safe messaging (free-tier throttled), strong moderation, accessibility, i18n, multisite compatibility, minimal data collection.
- Non‑Goals: paid features, external tracking, heavyweight SPA frontends, storing sensitive documents.

## 2. High-Level System Diagram (conceptual)
- WordPress Core (users, roles, options, REST API)
- Plugin Layer (custom post types, taxonomies, REST controllers, shortcodes/blocks)
- Data Storage: WP tables + minimal custom tables (for messages, preferences). No PII beyond necessity.
- Caching: transients/object cache; page cache compatible.
- Integrations: none by default; optional email via wp_mail().

## 3. Key Components
- Domain Model:
  - Profile (user-linked), Preferences, Match, Message, Report/Block, Media (avatars via WP media), Consent log.
- WP Entities:
  - Custom Post Types: wpm_profile (private), wpm_message (private) optional; or custom tables for messages.
  - Taxonomies: wpm_interest, wpm_location (optional lightweight).
  - User Meta: profile fields, privacy flags, preference vectors, consent timestamps.
- REST: namespace wpmf/v1 with endpoints for profile CRUD, browse, match, messaging, report/block.
- UI: Gutenberg blocks + shortcodes for legacy. Templates use WP theming, no front-end build dependency required.

## 4. Data Model and Storage Strategy
- Default: store profile fields in user_meta; profiles are not public posts to reduce leakage.
- Matching: computed vectors derived from preferences; store only derived, non-sensitive signals.
- Messages: custom table wpmf_messages for scalability and privacy; status indexes. Soft-delete with retention policy.
- Audit/Consent: wpmf_consent_log records policy acceptance and key changes.
- Multisite: tables created per-site with $wpdb->prefix; global network options for defaults.

## 5. Security and Privacy
- Data minimization: collect only necessary fields; opt-in visibility controls; private by default.
- Capabilities: custom caps mapped to roles (member, moderator). Nonces on forms; REST uses capability checks and current_user_can.
- Transport: enforce HTTPS-only cookies; HSTS left to hosting.
- Content Security Policy: recommend safe defaults; no third-party scripts by default.
- PII protection: avoid storing exact location, use coarse regions; hash internal matching features when possible.
- Messaging safety: rate limits per user/IP; blocklist and report flows; word filters pluggable.
- Encryption: server-side at-rest relies on host; consider wp_sodium for sensitive transient tokens.
- Logs: no sensitive data in logs; use WP_DEBUG_LOG guards.

## 6. Accessibility (a11y)
- WCAG 2.2 AA targets; keyboard navigable flows.
- Use native form controls, ARIA where needed; color-contrast checked.
- Live regions for async updates; focus management after actions.
- Support reduced motion; alt text for avatars.

## 7. Internationalization (i18n) and Localization (l10n)
- All strings wrapped in i18n functions with text domain wpmatch-free.
- Date/time/number localized via WP helpers; RTL styles included.
- Translation-ready POT; no hardcoded copy.

## 8. Performance
- Avoid autoload bloat: only critical options autoload=yes.
- Lazy-load blocks/assets; enqueue only on plugin pages.
- Use prepared statements and proper indexes; paginate everywhere.
- Cache match results per user with transients; bust on profile change.
- Compatible with page/object caching; REST responses cacheable where public.

## 9. Multisite Strategy
- Network-activatable; creates per-site tables on activation.
- Network settings for defaults (moderation, rate limits); site overrides allowed.
- User data is site-scoped; opt-in network profile sharing not included in free tier.

## 10. Extensibility and Hooks
- Actions/filters around profile save, match compute, message send, moderation events.
- Pluggable service interfaces for Scoring, Messaging, Moderation.
- Block filters for UI customization; REST endpoints filterable.

## 11. Failure Modes and Safeguards
- Graceful degradation if REST blocked; forms post to wp-admin admin-ajax fallback.
- Queue outgoing emails; retry with backoff.
- Strict validation; reject oversized media.
- Feature flags via options; safely disable messaging if abuse detected.

## 12. Compliance and Legal
- Clear privacy policy guidance; consent logging.
- Data export/erase integrated with WP privacy tools.
- Age gating with self-certification; no biometric data.

## 13. Deployment and Updates
- Requires: PHP 7.4+, WP 6.4+; tested up to latest.
- No cron dependencies beyond wp_cron; schedules documented.
- Incremental DB migrations with db version option; rollback safe.

## 14. Observability
- Minimal, privacy-safe metrics (counts, durations) via WP hooks.
- Admin screens surfacing queue health, error rates (non-PII).

## 15. Summary
A lean, privacy-first architecture leveraging WordPress primitives with minimal custom tables, strong moderation, accessibility, performance, i18n, and multisite support.
