# WP Match Free — Technical Stack

> Last Updated: 2025-08-07
> Version: 1.0.0

## Platforms and Compatibility
- WordPress 6.4+ (Classic + Block Editor)
- PHP 7.4 – 8.3 supported; target 8.1+
- MySQL 5.7+ or MariaDB 10.4+
- Multisite and Single-site compatible

## Core Stack
- Language: PHP
- Framework: WordPress Plugin API (hooks, REST API, Settings API)
- Admin UI: Gutenberg (React in WP), Settings API screens
- Frontend: Progressive enhancement, vanilla JS + minimal WP scripts; no heavy SPA
- Styles: WP components, CSS custom properties; prefers system fonts

## Data Layer
- Storage: user_meta for profile fields; custom tables: {prefix}wpmf_messages, {prefix}wpmf_consent_log
- Indexing: BTREE on (to_user, status, sent), (thread_id, sent)
- Migrations: dbDelta with versioned migrations

## Security and Privacy Controls
- Authentication: WP auth cookies; nonces; Application Passwords for integrations
- Authorization: current_user_can with custom capabilities
- Input handling: wp_unslash, sanitize_* functions, wp_kses allowlists
- Output escaping: esc_html, esc_attr, esc_url, esc_js
- Transport security: recommend HTTPS; SameSite=Lax cookies; no third-party trackers
- Data minimization: collect least data; retention policies on messages

## Performance
- Asset strategy: enqueue only on necessary screens; defer where safe
- Caching: transients/object cache for derived data; compatibility with full-page caches
- DB: prepared queries via $wpdb; pagination everywhere; avoid autoload bloat
- Media: image sizes and lazy loading; avoid synchronous remote calls

## Accessibility (a11y)
- WCAG 2.2 AA; keyboard focus traps avoided
- ARIA roles as needed; form labels and descriptions
- Color contrast and prefers-reduced-motion respected

## Internationalization (i18n)
- Text domain: wpmatch-free; POT generated
- RTL styles; localized date/number formatting via WP

## Tooling
- Coding standards: WordPress Coding Standards (PHPCS)
- Testing: PHPUnit with WP test suite; Playwright/puppeteer optional for E2E
- Build: minimal; use WP Scripts only if needed; no required Node build for core
- Linting: PHPCS, PHPStan level 5+ where feasible

## Moderation and Safety
- Rate limiting via transient counters; pluggable strategies
- Word filters hook; block/report flows in core
- Audit/consent logging

## Deployment
- Distributed via WordPress.org; adheres to plugin guidelines
- No external service dependencies; uses wp_mail for notifications
- Updates via WordPress.org; semantic versioning

## Observability
- Admin dashboards for non-PII metrics; debug mode respects privacy

## Extensibility
- Documented actions/filters; service interfaces for matching, messaging, moderation
- Template parts and block filters for theme integration
