# Staging acceptance checklist (Gate D)

swatijha.com native WordPress rebuild · Release under test: ____ · Staging host: ____ · Tester: ____ · Date: ____

Every item needs **evidence**: a screenshot, log, test output or file reference. "Looked fine" is not evidence. Anything not ticked blocks the launch request. Staging acceptance is not launch approval.

## 0. Entry criteria (must be true before testing starts)

- [ ] Pre-staging review H1–H4 closed, with commits referenced
- [ ] URL map signed row by row (Gate B); signed CSV committed
- [ ] Release ZIPs built from a tagged commit; checksums match `release-manifest.json`
- [ ] Staging is behind authentication (HTTP basic auth or IP allow-list), not just noindex
- [ ] `WP_ENVIRONMENT_TYPE=staging`; no production credentials, payment, booking or mail keys present
- [ ] Mail routed to a capture sink; verify one test message arrives there and nowhere else

## 1. Hosting parity

- [ ] PHP version and extensions match production (record `php -v`, `php -m`)
- [ ] MySQL 8.0+ or MariaDB 10.11+; tables are InnoDB
- [ ] Persistent object cache (if production uses one) enabled on staging too
- [ ] Integration suite (40 checks) passes against this database, not SQLite
- [ ] Release rollback test: force a failure mid-release and confirm the published page and meta are unchanged and caches are consistent
- [ ] Importer rollback test on a synthetic manifest
- [ ] PHPCS/WPCS run clean, or deviations recorded
- [ ] Backup taken, **restored to a separate instance**, and the restored site verified (restore drill)

## 2. Clean installation and independence

- [ ] Fresh WordPress: install plugin ZIP, then theme ZIP; both activate without warnings in `debug.log`
- [ ] No Elementor, Liquid/AIHub, Rank Math, WP Code or Higgsfield present. Absent, not merely deactivated
- [ ] Browser network log on every template shows no third-party requests before consent
- [ ] Deactivating the theme leaves entity records intact; plugin deactivation deletes nothing

## 3. Roles and clinical workflow (run as real, separate accounts)

- [ ] Accounts: content editor, clinical reviewer (linked to Prof Jha's clinician record), publisher, migration operator, administrator
- [ ] Editor drafts a clinical page → requests review. Cannot approve or publish
- [ ] Reviewer approves with the actual review date. Future date rejected. Cannot approve as a different clinician
- [ ] Publisher releases. Cannot release an unapproved or changed revision
- [ ] Editor creates a change draft of a **published entity** and a **published page**; the live version stays unchanged until release
- [ ] Changing the draft after approval invalidates the approval
- [ ] Changing a shared entity flags dependent pages for re-review
- [ ] Published clinical page: title, content, slug, template, featured image and meta cannot be edited directly
- [ ] Audit trail shows who requested, who approved (and as which clinician), who released, and the revision ID
- [ ] Anonymous user: no access to entity REST, evidence notes, audit, catalogue or users list

## 4. Content migration (only after the dry run is approved)

- [ ] Dry-run report reviewed and signed before any write
- [ ] Every retained page: H1, section headings, body text, lists, tables, downloads and internal links present versus the source snapshot
- [ ] Title and meta description equal the approved values (imported from Rank Math unless deliberately changed)
- [ ] No Elementor/Liquid markup, shortcodes or inline positioning in any page body
- [ ] Credentials, roles, GMC number and publications match verified evidence; each has a source and verification date
- [ ] Fees: only the verified current figures appear (£275 new / £160 follow-up as confirmed Sept 2026); no older figures anywhere
- [ ] Mesh statement consistent across homepage, flagship page and schema, as signed off by Prof Jha
- [ ] Every clinical page is clinically reviewed on staging, with the actual review date; none carries the migration date as its review date
- [ ] Reviews: only permitted excerpts; Doctify figure from one maintained source; no review-star markup

## 5. URLs, redirects and SEO

- [ ] Automated request of every row in the signed map: expected status, one hop, correct destination
- [ ] Host/protocol variants: single 301 to `https://www.` form
- [ ] 410 rows return 410 with a useful page, not a generic server error
- [ ] Media: every KEEP row in `MEDIA-INVENTORY.csv` returns 200 at the same path
- [ ] Canonical on every page = production origin + path (no staging hostname leaks in canonical, og:url or JSON-LD `@id`)
- [ ] One JSON-LD graph per page; validates (Schema Markup Validator); Person, MedicalWebPage, conditions/treatments and locations consistent with visible text
- [ ] FAQ markup only where the FAQ is visible; no AggregateRating
- [ ] Sitemap lists only indexable retained pages; no entity types, users, feeds or held routes
- [ ] robots.txt reviewed for production (staging copy is noindex)
- [ ] Noindex exceptions (if any retained) render `noindex` correctly

## 6. Design and accessibility

- [ ] Visual check at 320, 390, 480, 768, 1024, 1280, 1440, 1920px and either side of each breakpoint, on real migrated pages
- [ ] No horizontal page scroll at 320px; 200% and 400% zoom reflow
- [ ] Keyboard only: skip link, navigation, menu open/close with focus return, treatments menu, FAQ, forms
- [ ] Screen reader pass (VoiceOver + NVDA) on homepage, one clinical page, contact and booking: headings, landmarks, link names, current page
- [ ] axe: zero violations on every template type
- [ ] Contrast of actual editor-made content (not just tokens)
- [ ] Reduced motion honoured; images have correct alt; decorative images empty alt
- [ ] Videos have captions and transcripts; no autoplay; external embeds click-to-load

## 7. Enquiries (only if activation is separately approved)

- [ ] Approved privacy wording and consent text in place; privacy notice page approved
- [ ] Recipient confirmed by the practice; transport configured and authenticated (SPF/DKIM/DMARC pass)
- [ ] Validation errors inline, input retained, error summary focused
- [ ] Success shown only after confirmed delivery; failure shows the practice phone number
- [ ] Rate limiting works behind the production proxy/CDN without blocking legitimate users
- [ ] Works on a cached page (nonce not stale)
- [ ] No message bodies stored or logged

## 8. Performance

- [ ] Production caching and compression enabled on staging
- [ ] Lighthouse mobile on homepage, flagship prolapse page, about, contact: LCP < 2.5s, CLS < 0.1, TBT recorded
- [ ] Asset budgets: owned JS ≤ 35KB gz, CSS ≤ 60KB gz, fonts ≤ 120KB, hero ≤ 220KB
- [ ] TTFB recorded uncached and cached

## 9. Launch readiness (for the Gate E request)

- [ ] Content freeze date agreed; delta-sync plan for changes on the live site after the freeze
- [ ] Rollback plan written: triggers (enquiry failure, widespread 404, canonical errors), who decides, how long rollback takes
- [ ] Search Console access confirmed; post-launch checks scheduled (coverage, 404s, sitemap)
- [ ] Sign-off: Pravin (technical/SEO) ____ · Prof Jha (clinical content) ____

Launch remains unauthorised until a separate written instruction.
