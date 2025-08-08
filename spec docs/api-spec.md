# WP Match Free — REST API Specification

> Last Updated: 2025-08-07
> Version: 1.0.0
> Namespace: wpmf/v1
> Auth: Cookie (logged-in users), Nonces for write; Application Passwords supported. No public PII endpoints.

## Principles
- Privacy-first: least data, redact sensitive fields, explicit scopes.
- Security: capability checks, nonces, rate limits, prepared SQL, schema validation.
- Performance: pagination, conditional fields (?_fields), ETags/Last-Modified where applicable.
- Accessibility: clear error messages, localized strings; errors map to WP_Error.
- Multisite: endpoints operate per-site; network settings under /network require manage_network.

## Common
- Headers: X-WPMF-Request: v1; X-WP-Nonce for non-GET.
- Errors: JSON {code, message, data{status}}. No stack traces. IDs opaque where possible.
- Rate limits: soft limit 60/min per user for messaging; 30/min for profile mutations.

## Schemas (abridged)
- Profile
  - id (int), user_id (int), display_name (string), bio (string, sanitized), interests (array[string]), photos (array[media_id]), visibility (enum: private, members), preferences (object, redacted), updated (datetime).
- Match
  - id (string), user_id (int), target_user_id (int), score (number 0-1), rationale (array[string]), created (datetime).
- Message
  - id (string), thread_id (string), from_user (int), to_user (int), body (string, filtered), sent (datetime), status (enum: delivered, read, deleted).
- Report
  - id (string), target_user (int), reason (string enum), details (string), created (datetime), status (enum: open, actioned).

## Endpoints

### GET /wpmf/v1/profile/me
- Auth required
- Returns current user profile

### POST /wpmf/v1/profile
- Create or update profile
- Body: {display_name, bio, interests, photos, visibility, preferences}
- Validates length, content safety, media ownership

### GET /wpmf/v1/profile/{user_id}
- Returns public-safe view; requires member auth; redacts private fields

### GET /wpmf/v1/browse
- Query params: page, per_page, interest, region, sort
- Returns paginated minimal cards (id, display_name, age range proxy, interests subset, photo thumb)

### POST /wpmf/v1/match/compute
- Triggers recompute for current user; debounced
- Returns array of Match summaries

### GET /wpmf/v1/match
- Paginated list of current user's matches

### POST /wpmf/v1/message
- Send a message
- Body: {to_user, body, thread_id?}
- Constraints: rate limits, mutual consent required (configurable)

### GET /wpmf/v1/message/threads
- List threads with last message preview, unread counts

### GET /wpmf/v1/message/thread/{thread_id}
- Paginated messages; supports since cursor

### POST /wpmf/v1/message/thread/{thread_id}/read
- Marks messages as read

### POST /wpmf/v1/report
- Body: {target_user, reason, details?}
- Creates a report, notifies moderators

### POST /wpmf/v1/block
- Body: {target_user}
- Blocks target; hides matches and messages

### DELETE /wpmf/v1/block/{target_user}
- Unblock

### GET /wpmf/v1/settings
- Returns plugin settings visible to current user

### GET /wpmf/v1/admin/moderation/queue
- Caps: moderate_wpmf
- Lists reports, abuse flags; paginated

### POST /wpmf/v1/admin/moderation/action
- Body: {report_id, action: warn|mute|ban|dismiss, note}

### GET /wpmf/v1/network/settings
- Multisite only; caps: manage_network

### POST /wpmf/v1/network/settings
- Update network defaults; validation enforced

## Security Details
- Nonces or Application Passwords; capability checks per route.
- Strict type schemas; sanitize_text_field / wp_kses on strings; max lengths.
- No search endpoints exposing arbitrary user queries beyond whitelisted filters.
- Redaction middleware removes email, exact location, IPs.

## Performance and Caching
- Use pagination (per_page <= 50), cursors for threads.
- ETags for read endpoints; 304 support; private caching OK.
- Avoid N+1: batch preloads of user meta and media.

## i18n and Accessibility
- Error strings are translatable (wpmatch-free domain).
- Time and number formats localized.

## Versioning
- Namespace bump for breaking changes (wpmf/v2 when needed).

## Deprecations
- Provide _deprecated field notices one minor before removal.

## Consent and Data Rights
- Endpoints to export/delete personal data use WP core tools; plugin registers exporters/erasers.

## Testing Strategies
- Unit tests for validators and permission callbacks.
- Integration tests via WP REST API test utilities.
- Smoke tests for pagination, rate limiting, and redaction.
