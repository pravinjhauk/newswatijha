# new.swatijha.com — phased build plan

5 October 2026 · Host: GoDaddy (same account as the live site) · Live swatijha.com is not touched until Phase 7.

## Ground rules (every phase)

- **DNS:** add one record for `new` only. Never edit existing A/CNAME/MX/TXT records or nameservers.
- **Isolation:** own document root *outside* `public_html`, own database and database user, different table prefix (`sjn_`). Nothing shared with the live install.
- **No GoDaddy "staging", "clone" or "push to live" tools,** and no migration/clone plugin installed on the live site.
- **Content source is read-only:** the public crawl plus exports from live wp-admin. Never a live database clone pushed back.
- **The GitHub repository is public.** Never commit wp-config.php, passwords, database names, SFTP details or exports containing personal data. Consider making it private before Phase 1.

## Phase 0 — Close pre-staging blockers locally ✅ (v0.2.0)

H1–H4, M1–M5 and M7 from `PRE-STAGING-REVIEW.md` are closed; see the status table there. M6 (enquiry form) and M8–M10 remain, none blocking staging.

## Phase 1 — Provision new.swatijha.com on GoDaddy (locked staging)

First confirm which GoDaddy product hosts the live site: **cPanel Web Hosting** (has File Manager, MySQL Databases, Directory Privacy) or **Managed WordPress** (no cPanel; one-click staging). The steps below are for cPanel. On Managed WordPress, ask before proceeding: basic auth, .htaccess rules and a separate site may not be available on that plan.

1. **Backup first:** in cPanel → Backup (or GoDaddy Website Backup), take and download a full backup of the live site. Nothing below touches it, but this is the rollback baseline.
2. **Subdomain:** cPanel → Domains → Create a New Domain → `new.swatijha.com`. Untick "Share document root" and set the root to `/home/<account>/new.swatijha.com` (not inside `public_html`).
3. **DNS:** if DNS is managed in GoDaddy Domain Manager rather than cPanel, add an **A record** `new` → the hosting IP shown in cPanel. Add nothing else.
4. **SSL:** cPanel → SSL/TLS Status → run AutoSSL for `new.swatijha.com` only.
5. **PHP:** cPanel → MultiPHP Manager → set `new.swatijha.com` to PHP 8.3. Leave the live domain's version alone.
6. **Database:** cPanel → MySQL Databases → new database and new user, all privileges on that database only. Record the credentials in your password manager, not in the repo.
7. **Lock it:** cPanel → Directory Privacy → `new.swatijha.com` folder → password-protect. This is the real protection; noindex is not.
8. **Install WordPress manually** (download from wordpress.org, upload via File Manager or SFTP). Do not use Installatron's clone/staging features. In `wp-config.php` set:
   - `$table_prefix = 'sjn_';`
   - `define('WP_ENVIRONMENT_TYPE','staging');`
   - `define('DISALLOW_FILE_EDIT',true);`
   - `define('SJ_STAGING_MAIL_TO','<your address>');` — all staging mail goes only here; everything is also kept under Practice content → Captured mail.
   - No live keys, no live database details.
9. **Install the release:** wp-admin → Plugins → Upload `dist/swatijha-core-0.2.0.zip`, activate; then Themes → Upload `dist/swatijha-theme-0.2.0.zip`, activate. Check checksums against `dist/release-manifest.json`.
10. **Accounts:** create separate users for editor, clinical reviewer (Professor Jha), publisher and migration operator. In the reviewer's user profile, link the account to her clinician record once that record exists.
11. **Practice settings:** set the production canonical origin to `https://www.swatijha.com` (so staging never leaks into canonicals or schema). Leave "Allow delegated review attestation" off unless you decide otherwise.
12. **Deploy pipeline (optional, later):** GitHub Action deploying tagged releases to staging over SFTP, with staging credentials only, stored as GitHub secrets.
13. Work through `STAGING-ACCEPTANCE-CHECKLIST.md` sections 0–2.

### Phase 1 status — 5 October 2026 (no credentials recorded here)

| Step | Status | Evidence / note |
|---|---|---|
| 1 Backup of live site | ✅ (owner confirmed) | Taken before the WordPress install |
| 2 Subdomain | ✅ | cPanel: `new.swatijha.com`, document root `/home/<account>/new.swatijha.com`, not shared with `public_html` |
| 3 DNS | ✅ no change needed | `new.swatijha.com` already resolved to the hosting IP; no DNS records edited |
| 4 SSL | ✅ | AutoSSL certificate for `new.swatijha.com`, expires 3 Jan 2027 (`www.new` fails DCV; not used) |
| 5 PHP | ⚠️ 8.4, not 8.3 | Host blocks per-domain PHP (site isolation denied); account-wide 8.4, same as production |
| 6 Database | ✅ | Database `newswatijha`; user `pravinjhanew` with privileges on that database only |
| 7 Lock | ✅ | Directory Privacy on `new.swatijha.com`, realm "Staging", one login (`.htpasswds/new.swatijha.com/passwd`) |
| 8 WordPress | ✅ | WordPress 7.1.2 installed manually; `$table_prefix = 'sjn_'`; `WP_ENVIRONMENT_TYPE` staging; `DISALLOW_FILE_EDIT` true; `SJ_STAGING_MAIL_TO` set |
| 9 Release | ✅ | `swatijha-core` and `swatijha-theme` 0.2.0 installed and active; source ZIPs match `dist/release-manifest.json` (SHA-256 and size), tag `v0.2.0` |
| 10 Accounts | 🟡 partial | `pravinjha` (Administrator), `pj-editor` (Clinical content editor), `pj-reviewer` (Clinical reviewer). To do: change their emails to `+editor`/`+reviewer` aliases; rename reviewer to Prof Jha; link to clinician record once created; publisher and migration operator accounts before Phase 4 |
| 11 Practice settings | ✅ | Production canonical origin `https://www.swatijha.com` (verified after reload); delegated review attestation off |
| 12 Deploy pipeline | ⏸ later | Optional |
| 13 Checklist §0–2 | 🟡 in progress | See below |

Checklist §0–2 position:
- §0: H1–H4 closed (55c8a4a) ✅ · URL map signed ❌ (Phase 2) · checksums ✅ · basic auth ✅ · staging env, no production keys ✅ · mail capture test ❌ not yet sent
- §1: PHP 8.4 matches production ✅ (extensions not yet recorded) · remaining items not started
- §2: fresh install with plugin then theme ✅ (debug.log not yet checked) · only `swatijha-core`/`swatijha-theme` present, WordPress defaults removed ✅ (no fallback theme) · network-log and deactivation tests not started

Housekeeping: empty `wordpress/` folder and `wordpress-7.1.2.zip` still in the staging root, to be moved to Trash.

## Phase 2 — Evidence and URL map sign-off (Gate B, parallel with Phase 1)

Read-only on the live site:
1. Search Console page-level export, 16 months.
2. Rank Math → Redirections export (CSV). Add each entry to the map as a new row.
3. Media library listing or `wp-content/uploads` directory listing.
4. Decide `/vaginal-prolapse-signs/` (merge into `/symptoms/` or keep) and the mesh wording.
5. Sign `URL-MAP-FOR-APPROVAL.csv` row by row. Every address row needs an explicit `redirect_code` of 301, 410, 403 or none (S01–S05 are currently blank).
6. Then `npm run redirects` produces `dist/redirects.htaccess`; `npm run redirects -- --origin=https://new.swatijha.com` produces the staging version.

## Phase 3 — Build out the design

C01 treatments menu, C02 breadcrumbs, C18 referrer panel, C25 education/media; then the partial components (footer C19, 404, article cards, pathway, locations, review line), and the reduced editor palette (M8).

## Phase 4 — Migration dry run, then draft import on staging (Gate C)

Build page extraction (Elementor → native blocks), carry Rank Math titles/descriptions into the new SEO fields, download media to the same paths, review the dry-run report, then import drafts only.

## Phase 5 — Clinical and content sign-off

Professor Jha reviews each clinical page on staging with the actual review date. Fees, credentials, mesh statement and review excerpts checked against evidence.

## Phase 6 — Staging acceptance (Gate D)

Rest of the checklist, including `npm run check:redirects -- --base=https://new.swatijha.com` (with `SJ_BASIC_AUTH=user:pass`), accessibility, performance with caching, and enquiry activation only if separately approved (M6 first).

## Phase 7 — Launch (Gate E, separate written go-ahead)

Content freeze → point www.swatijha.com at the new install's document root (old site left intact in `public_html` as rollback) → search-replace `new.swatijha.com` → `www.swatijha.com` → place generated redirect block above the WordPress block in `.htaccess` → remove basic auth → set `WP_ENVIRONMENT_TYPE` to production → submit sitemap → retire `new.` → daily monitoring for two weeks.
