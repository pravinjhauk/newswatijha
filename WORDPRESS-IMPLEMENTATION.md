# WordPress implementation handover

28 September 2026 · Local implementation release 0.1.0 · Architecture approval received in this task

## What is built

The approved Plum & Turquoise visual language is implemented as a native WordPress block theme. The hero uses Professor Jha’s authentic portrait; the rejected ribbon artwork is absent. Fonts, images, icons and compiled assets are local files. Higgsfield, Elementor and proprietary page builders are not dependencies.

| Deliverable | Location | Behaviour |
|---|---|---|
| Native block theme | `wp-content/themes/swatijha-theme` | Theme.json v3, approved tokens, local fonts, responsive CSS, 10 templates, header/footer and curated patterns |
| Owned content plugin | `wp-content/plugins/swatijha-core` | Ten internal entity types, specialties, validated metadata, relationships, review workflow, schema and editor controls |
| Native component library | Theme patterns + 14 plugin blocks | Core blocks handle narrative/layout; record selectors supply credentials, profiles, treatments, resources, publications, research, reviews, locations and references |
| Development environment | `.nvmrc`, `package-lock.json`, `tooling/` | Node 22.23.3; reproducible builds; disposable WordPress/PHP 8.3 local runtime; Docker WordPress environment configuration |
| Migration controls | Plugin Migration module + existing proposed URL map | Approved-map guard, synthetic dry-run/import tests, UUID upserts, relationship resolution, draft-only import and transaction rollback |
| Installable packages | `dist/` | Theme and plugin ZIPs with compiled assets and a checksum manifest |

The project is in Git on `feature/native-wordpress`. There is no configured deployment remote and no live-site modification.

## Editing without code

Use **Pages** for public content and existing addresses. Use **Practice content** for reusable records. In a page, insert a pattern from the **Swati Jha** category, then edit normal headings, paragraphs, images, lists, details and buttons. Structured blocks expose record selectors in block settings. Empty selectors do not manufacture clinical facts.

The page sidebar provides **Practice content and clinical review**, including related records, section citations, actual review dates and private evidence notes. Credentials and professional roles use repeatable labelled fields. Practice settings centralise public contact details and canonical identity.

For clinical content:

1. Mark a Page as clinical information, or create an internal clinical entity.
2. Save the draft and request review.
3. A user with the clinical-review capability selects the verified clinician and enters the actual review date, then approves the saved revision.
4. A user with the clinical-publishing capability releases that approved revision.
5. For later changes, choose **Create clinical change draft**. The published version stays intact. Changed content invalidates its draft’s approval. Release checks both the approved hash and the unchanged source before promotion.

Published clinical records reject direct content/meta/deletion/specialty changes. Shared entity changes flag dependent pages for re-review. A revision identifier and private audit trail record the released narrative. The first clinician’s verified identity can be approved by an authorised reviewer during initial setup; this is not automatic verification of Professor Jha’s real-world facts.

Private notes and audit records are excluded from anonymous APIs and structured data. This is a public content system, not a patient-record database.

## Local development

With the pinned Node version selected:

```sh
npm ci
npm run build
npm run dev
```

The preview is at `http://127.0.0.1:8881`. It binds to loopback only, discourages indexing and blocks outgoing WordPress email. Its database is deliberately disposable. Restarting the preview recreates its sample pages. The local development fixture uses WordPress Playground’s standard test account; never reuse that configuration on staging or production.

The homepage contains the approved design specimen and explicit local preview text. Other local pages are labelled placeholders. They are **not migrated clinical content**. Do not use this disposable database as the eventual production database.

The Node runtime downloaded for this workstation is in ignored `.tools/node`. Another machine can use `.nvmrc` with its usual version manager. Node is required for source compilation/testing only. Installed release ZIPs run and edit under WordPress/PHP without Node or any design-generation service.

Available checks:

```sh
npm run build
npm run lint
npm run lint:css
npm run format:check
npm test
npm run test:integration
npm run test:e2e
npm run test:performance
npm run package
```

`npm run assets:build` regenerates bounded WebP derivatives without changing the original portrait. Theme.json owns design tokens and named breakpoints; the build generates CSS aliases. The editor bundle uses WordPress-provided packages. No owned frontend application bundle is shipped.

`npm run env:start` provides the configured Docker environment on ports 8890/8891 when Docker is available. The current workstation has no Docker or native PHP/Composer, so verification here uses PHP 8.3 WebAssembly and SQLite. Native PHP/WPCS configuration is included in `composer.json` and `phpcs.xml.dist`; it has not been executed on this workstation. MySQL/MariaDB and actual hosting parity remain staging checks, not passed local claims.

## Installation and independence

Install `swatijha-core-0.1.0.zip` as a plugin and `swatijha-theme-0.1.0.zip` as a theme on an isolated WordPress installation. Activate the plugin before the theme. Both ZIPs contain the needed compiled assets. The source repository remains the authoritative history for template/style changes.

No vendor credentials, external generated-image links or builder database are needed. New artwork must be exported into local version-controlled assets before it is used. The original rejected concept remains historical documentation only.

WordPress core still supplies its native navigation interaction and editor. The owned plugin is necessary for its entity-backed features; changing themes does not remove records. Plugin deactivation/uninstall does not delete content.

## Migration and launch boundaries

The 29-row OLD → NEW map remains **PENDING**, including its four hold routes. Architecture approval did not approve migration. The real content has not been imported and no redirects have been activated. Only synthetic test manifests were applied to disposable test databases.

The first importer handles structured entity drafts. Public-page extraction, clinical section mapping, media reconciliation and redirect application must be completed against the signed inventory in the migration phase. It does not blindly ingest Elementor HTML. Rollback manifests include created IDs and prior snapshots; no automated production rollback is claimed.

Before staging acceptance and launch:

- Reconcile the full CMS/media/SEO inventory and resolve/approve the URL map.
- Migrate and clinically review actual records, pages, credentials, citations, roles, locations and permitted reviews.
- Verify hosting/PHP/database compatibility, native PHP standards, database transaction behaviour, backups and restore.
- Verify privacy wording, consent, recipient and delivery transport before enabling enquiries. No real booking/email service is active here.
- Test the completed content, screen-reader use, caption/transcript coverage, template exports, canonical/schema ownership and redirects in authenticated staging.
- Apply production caching/compression, then measure representative migrated pages. Local Lighthouse results are laboratory observations, not field Core Web Vitals.
- Obtain separate launch approval. The live website must remain unchanged until then.

See `WORDPRESS-VERIFICATION.md` for measured results and limitations.
