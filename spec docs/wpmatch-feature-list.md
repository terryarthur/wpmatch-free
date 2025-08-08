# WPMatch Feature Specification

## Baseline Features to Match (Parity)

### User-facing
- Profiles with custom fields (checkbox, selects, numbers, dates), retina-ready images, galleries, privacy controls, friend lists, “Viewed me / I viewed,” status updates, winks/gifts, and user blocking.
- Advanced & basic search (including searching in profile fields, ranges) and “Near me”/geolocation browsing with map view.
- Real-time text chat; optional audio/video calls (WebRTC) and virtual date booking via Zoom/Meet.
- Simple/rapid registration, optional social login; multilingual readiness.
- Couple profiles (shared account) and success stories module.

### Monetization
- Membership tiers (free/paid), recurring subscriptions, credit/points wallet, pay-per-minute voice/video options.
- WooCommerce payment gateway support + built-in PayPal/bank transfer, promotions (happy hour, open days, gender-based access).
- Private photo/video galleries with access rules.

### Admin, moderation & ops
- Member management with filters, CSV import/export, blacklists (email domain, IP/country), image wall moderation, message supervision on reported members.
- GDPR tools; daily cleanup/perf optimizations; unlimited members.

### Add-ons ecosystem (examples to mirror)
- Instant chat, AI/explicit-image moderation, SMS verification, affiliate program, demo content/profiles, mobile app branding/PWA.

## Differentiators for WPMatch (Go Beyond Parity)

### Matching & discovery
- **Hybrid semantic matching**: combine rules (age, distance, fields) with vector embeddings from profiles/messages for “People like you also liked…” surfacing.
- **Event & community layer**: built-in “Speed Dating Live,” audio rooms, and RSVPable local events with capacity, ticketing (via WooCommerce) and auto-match follow-ups.

### Safety & trust
- **AI Trust & Safety Suite**:
  - On-device or server-side nudity/explicit detection with adjustable thresholds, queue, and appeal workflow.
  - Scam/fraud heuristics: message patterns (WhatsApp ask, crypto pitch, cash app), link shields, and supervised escalation.
  - Selfie + liveness + document check via optional KYC providers (pluggable adapter).
  - Consent receipts for media sharing & profile visibility changes (auditable logs).
- **Anti-ghosting nudges**: humane prompts and “close loop” UX (quick reply chips, snooze/decline with reason, auto-unmatch timers).

### Monetization 2.0
- **A/B price testing** per segment and built-in promotions scheduler.
- **Micro-features as IAP**: Boost, Super-Like, Read Receipts, Profile Spotlight, Stealth mode—tied to credits or subscription tier.
- **Creator/Host payouts for events**, plus affiliate tracking baked in.

### Content & UX
- **Gutenberg/FSE blocks** for profiles, search, carousels, and event lists; design tokens for instant theming.
- **Guided profile builder** with AI prompts for bio/interests and profanity/tone guardrails.
- **Accessibility first** (WCAG 2.2 AA), RTL, and inclusive gender/orientation matrix presets.

### Performance & architecture
- **Headless-ready REST & Webhooks** (match events, payment events, moderation actions); native WP Cron replacement with queue.
- **Search that scales**: MySQL with generated indices, optional Elastic/Meilisearch adapter.
- **Media pipeline**: serverless image/video processing, signed URLs, and per-tier quality caps.

### Operations & analytics
- **Funnel & cohort analytics**: registration → profile completion → first message → match → subscription.
- **Moderation analytics**: reasons, time to resolution, reoffense rate; export to CSV/BigQuery.
- **Playbooks**: prebuilt email/SMS drips (onboarding, win-back, “first match” celebration).

### Developer experience
- **Add-on SDK**: actions/filters, typed PHP interfaces, JS events, schema registry for profile fields.
- **One-click demo**: seed users, messages, events, media; safe reset.

## Concrete WPMatch Feature Checklist (Ship Order)

### MVP (Parity + Quick Wins)
- Profiles & custom fields; basic/advanced search; Near Me map; real-time chat; galleries; winks/gifts; blocking; privacy.
- Memberships (free/paid), recurring + credits; WooCommerce gateways; promotions; private galleries.
- Admin moderation (image wall, reported messages), blacklists, CSV import/export; GDPR tools.

### Differentiators (Phase 2)
- Semantic recommendations; Events/speed-dating; Anti-ghosting nudges.
- AI Trust & Safety Suite (explicit filter, scam heuristics, liveness/KYC adapters).

### Scale & Pro polish (Phase 3)
- Headless API/webhooks; Search adapter; Add-on SDK; analytics dashboards.

## Competitor Positioning Notes
- **WP Dating**: heavy on add-ons, mobile apps, Near Me, virtual date booking, AI operator chat.
- **Rencontre**: solid free core with Premium upgrades for monetization, geolocation, astrology, moderation tools.
