# Pre-staging review — theme, plugin and design conformance

28 September 2026 · Release 0.1.0 · Branch `feature/native-wordpress` @ `30f8edd`
Static code review only. No code changed, no live site touched, no migration run.

## Status update — 5 October 2026 (release 0.2.0)

| Item | Status | Resolution |
|---|---|---|
| H1 | Closed | Every primitive capability of the ten entity types maps to an owned capability; editor/reviewer/publisher run the full change → request → approve → release cycle as separate non-admin accounts in the integration suite. |
| H2 | Closed | Accounts are linked to a clinician record by an administrator (logged in `sj_identity_log`). Approval counts only as the reviewer's own linked clinician. Delegated attestation exists but is **off by default** (Practice settings) and needs a written attestation. The approver cannot release the same revision; an administrator override needs a recorded reason. Audit entries record user name, linked clinician, reviewer entity, mode, attestation and override reason. |
| H3 | Closed | `seo_title`, `robots` (index/noindex) and `social_image_id` fields; document title, robots, Open Graph image/locale/site name, `og:type` article for posts, Twitter card; noindex pages excluded from the sitemap; the importer carries `seo_title`/`seo_description`/`robots` but never review dates or reviewers. |
| H4 | Closed | `tooling/redirects.mjs` generates `.htaccess` rules from the signed map and refuses any unapproved row; single hop to the canonical origin; host/protocol rules apply to production hostnames only. `tooling/check-redirects.mjs` requests every old URL and checks status, destination and one hop. |
| M1 | Closed | Clinical template or a bound entity makes a page clinical automatically; only a reviewer can clear the flag. |
| M2 | Closed | Featured image, template and menu order are locked, hashed and carried through change drafts. |
| M3 | Closed | Release locks older than five minutes are treated as abandoned. |
| M4 | Closed | Versioned role reconciliation runs on `init`; plugin ZIP replacement now reaches existing installs. |
| M5 | Closed | Registered meta is edit-context only in REST; anonymous users endpoint, users sitemap, author archives and oEmbed author data removed; XML-RPC disabled (and 403 at server via the URL map). |
| M7 | Closed | Non-production mail is captured as administrator-only "Captured mail" (14-day retention); on staging, `SJ_STAGING_MAIL_TO` sends copies to one nominated address only. |
| M6, M8–M10, Low | Open | Not blocking staging. M6 before any enquiry activation; M10 at staging. |

Verification: 77/77 integration checks on WordPress 7.1.2 and 6.6, 12/12 unit tests, 17/17 browser tests.

## Verdict (original, 28 September)

The architecture is sound and the clinical workflow is more rigorous than most commercial builds. It is **not ready for staging** until the four high-severity items below are closed. H1–H2 affect the medicolegal integrity of clinical review; H3–H4 are prerequisites for any migration dry run.

Visual conformance: the homepage hero, tokens, typography and responsive shell are faithful to the handoff. Of the 25 component families, 7 are fully implemented, 14 partially and 4 are missing (table in section 4). The gaps are mostly unbuilt patterns rather than wrong ones.

## 1. High — fix before staging

**H1. Custom roles cannot edit published entities; change drafts only work for administrators.**
Entity post types map `edit_posts`, `publish_posts` etc. to `sj_*` capabilities but leave `edit_published_posts`, `delete_published_posts`, `edit_private_posts` and `delete_others_posts` at the core defaults. None of the custom roles hold those core caps. `current_user_can('edit_post', $published_entity)` therefore fails for Clinical content editor, Clinical reviewer and Clinical publisher, so **Create clinical change draft** fails for every published condition, treatment, clinician or location unless the user is an administrator. (Pages are unaffected: the content editor holds `edit_published_pages`, and review and release act on the draft.)
The integration suite runs change/approve/release as user 1 (administrator), so this path is untested.
*Fix:* complete the capability map for all ten entity types; add integration tests that run change → request → approve → release as three distinct non-admin users.

**H2. No separation of duties, and the approver is not bound to the reviewing clinician.**
The approve endpoint accepts any `reviewer_id` for any verified clinician, so a user with `sj_review_clinical` can record Professor Jha as reviewer without being her. Administrators hold every capability, so one account can draft, request, approve and release the same revision. The audit trail records the WordPress user but not an attestation that the named clinician reviewed it.
For a medical site under GMC and CQC scrutiny, "Reviewed by Professor Swati Jha, [date]" must be something she did.
*Fix:* link each reviewer WordPress account to its `sj_clinician` record, and allow approval only as the logged-in user's own clinician (or record an explicit delegated attestation with the delegate's name). Block approver = releaser by default, with an audited override. Store reviewer entity, approving user and attestation text in the audit entry.

**H3. No per-page SEO title, robots or social image controls.**
The model has `seo_description` only. Titles fall back to `Post title – Site name`, so the Rank Math titles now ranking (e.g. "Prolapse Surgeon Sheffield | Professor Swati Jha") have nowhere to migrate to. There is no per-page `noindex`, although the URL map retains two noindex routes. No `og:image` or Twitter card is emitted; `og:type` is always `website`.
*Fix:* add `_sj_seo_title`, `_sj_robots` (index/noindex), `_sj_social_image_id`; filter `pre_get_document_title` and `wp_robots`; add them to the clinical sidebar, graph and importer. Required before the migration dry run.

**H4. No redirect implementation.**
The importer validates 301/410 rows in the manifest but nothing applies them. Rank Math Pro's Redirections module on the live site may also hold historical redirects that will be lost when Rank Math is removed.
*Fix:* decide where redirects live (recommended: server rules generated from the signed CSV, with a test that requests every old URL and asserts one hop and the right status). Export Rank Math redirections into the URL map inventory first.

## 2. Medium — fix before staging acceptance

| # | Finding | Fix |
|---|---|---|
| M1 | **Clinical classification is optional.** A Page is only workflow-controlled if the editor ticks "clinical". Clinical copy published without the flag bypasses review entirely. | Pages using the Clinical template, or bound to a condition/treatment entity, become clinical automatically; only a reviewer can clear the flag. |
| M2 | **Lock on published clinical pages is incomplete.** Title, content, slug, status, parent and dates are locked; featured image, page template and menu order are not. | Add `_thumbnail_id`, `_wp_page_template`, `menu_order` to the guard and snapshot hash. |
| M3 | **Stale release lock.** The release mutex is an option row. A PHP fatal between `add_option` and `finally` leaves it in place and blocks all future releases of that record. | Store a timestamp and treat locks older than ~5 minutes as stale; surface a clear editor message. |
| M4 | **Roles are created only on activation.** Replacing the plugin ZIP does not re-run activation, so role/capability fixes (H1) will not reach an existing install. | Versioned upgrade routine on `plugins_loaded` comparing `sj_schema_version`. |
| M5 | **Anonymous exposure.** `_sj_*` page meta (reviewer IDs, review-due dates, citation sections) is visible at `/wp/v2/pages`. Core `/wp/v2/users` and the users sitemap disclose staff login names. `xmlrpc.php` is reachable by default. | Set meta REST context to `edit`; remove the users sitemap provider; restrict the users endpoint to authenticated users; disable XML-RPC. |
| M6 | **Enquiry form not production-safe (currently disabled).** Rate limit keyed on `REMOTE_ADDR` will throttle all patients together behind a CDN or proxy. Nonces on full-page-cached pages will expire and fail. Errors use `wp_die`, which loses entered text and has no error summary, contrary to C16. | Rate-limit on a trusted forwarded IP header plus a hashed email; exclude the form page from the full-page cache or fetch the nonce by REST; inline validation with a retained-value error summary. |
| M7 | **All non-production email is discarded.** This includes password resets, so staging user onboarding will fail silently. | Route non-production mail to a capture sink (Mailpit or similar) instead of dropping it. |
| M8 | **Editor colour palette exposes all 25 tokens**, including white, overlay and error colours. Editors can create white-on-turquoise text (2.43:1). | Expose an 8–10 colour editor palette; keep semantic tokens CSS-only. |
| M9 | **Block rendering gaps.** Credentials block outputs an empty `<section><ul>` when nothing is verified. Credential schema emits an empty `recognizedBy` name when organisation is blank. Location cards lack photograph and booking link (C14). The medical-review line does not distinguish published / updated / reviewed dates (C12). References ignore section anchors. | Render nothing when empty; omit empty schema properties; complete C12/C14 output. |
| M10 | **Hosting assumptions untested.** Release and import transactions assume InnoDB and no persistent object cache. With Redis/Memcached, a rollback can leave stale cached meta. | Staging check (see checklist); flush affected keys in the rollback path. |

## 3. Low

- Hero figcaption repeats the image alt text, so screen readers announce "Professor Swati Jha" twice. Drop the caption or make the alt text empty when captioned.
- Hero topic "chips" are one styled paragraph with `·` separators, not linked chips (C05). The location line under the CTAs (C03) is missing.
- Header CTA uses a 12px radius; buttons elsewhere are pills (C04).
- The mobile menu is WordPress core's full-screen overlay, not the 400px right-hand drawer in C01. It is accessible, so this is acceptable, but record it in `DECISIONS.md` as a deliberate deviation.
- Footer is a single column without the registration/legal line or hospital details (C19).
- The 404 page offers search and home only; C20 asks for treatment routes and a contact option.
- The archive/search templates list title and excerpt only; there is no C11 card grid.
- The pathway pattern is a plain ordered list; the C08 numbered discs and horizontal desktop layout are not styled.
- `Model::boot()` switches off avatars and emoji. That is presentation and belongs in the theme.
- The importer cannot resolve `landing_page_id` bindings because Pages have no UUID. This is expected until the page-extraction stage is built, but the stage is not yet designed.

## 4. Design conformance, C01–C25

| Component | Status | Note |
|---|---|---|
| C01 Header / navigation | Partial | No "Conditions & treatments" menu, so 14 clinical pages are not reachable from the header. Full navigation from 1280px is correct. |
| C02 Breadcrumbs | **Missing** | Schema emits BreadcrumbList (always Home › Page) with no visible breadcrumb. |
| C03 Homepage hero | Implemented | Portrait, 7:5 split, mobile order and tokens match. Minor copy/caption issues above. |
| C04 Buttons / links | Implemented | Header CTA radius differs. |
| C05 Topic chips | Partial | Styled text only; no link or filter semantics. |
| C06 Service / symptom cards | Partial | Entity-cards block complete; no native symptom-card pattern. |
| C07 Credentials strip | Partial | Block only; no strip pattern or 4/2/1 layout. |
| C08 Treatment pathway | Partial | Ordered list; disc and horizontal styling not built. |
| C09 Treatment options | Implemented | Equal weight, entity-driven. |
| C10 Profile / academic | Partial | Blocks exist; no 5:7 biography pattern; publication rows lack C10 format. |
| C11 Article cards | Partial | Archive list only. |
| C12 Answer box / review metadata | Partial | Answer box done; review line and references incomplete. |
| C13 Reviews | Implemented | Correctly gated on permission and moderation. |
| C14 Clinic locations | Partial | No image or booking link. |
| C15 FAQ | Implemented | Core Details; multiple open. FAQ schema only from visible content. |
| C16 Enquiry form | Partial | Disabled; see M6. |
| C17 CTA panel | Implemented | |
| C18 Referrer panel | **Missing** | |
| C19 Footer | Partial | Single column; legal line missing. |
| C20 Notices / 404 | Partial | Notice style exists; 404 incomplete. |
| C21 Tables / downloads | Partial | Table styling done; download row missing. |
| C22 Search / overlays | Implemented | Core search; no modal needed yet. |
| C23 Programme components | Partial | Overview/module only. Correctly not published. |
| C24 Faculty / contributors | Partial | Block only; byline variant missing. |
| C25 Education / media | **Missing** | No contents rail, video/transcript or download-row pattern. |

## 5. What is good and should not change

- Entity/page separation, UUIDs, typed relationships and the one-primary-entity binding rule.
- Hash-bound approvals, stale-approval invalidation and isolated change drafts.
- Schema emitted only from published, verified records; no review stars; FAQ only from visible content.
- Migration hard-blocked on production and on any unapproved URL-map row, including dry runs.
- No external runtime requests; local fonts; noindex outside production.

## 6. Recommended order

1. H1, M4 (capabilities and upgrade routine), with non-admin workflow tests.
2. H2 (reviewer binding and separation of duties). Needs your decision on whether delegated attestation is acceptable.
3. H3 and H4 (SEO fields and redirect mechanism), then M1–M3, M5.
4. Missing patterns: C01 treatments menu, C02 breadcrumbs, C18, C25, then the partials.
5. M6–M7 before any enquiry activation; M10 at staging.
