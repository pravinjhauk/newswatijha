# Local WordPress verification

28 September 2026 · Release 0.1.0 · No live-site tests or changes

## Completed checks

- Node.js 22.23.3 source build completed. Dependency versions and lockfile are committed.
- JavaScript lint, CSS lint and formatting checks pass.
- Five build/migration-boundary tests pass, including local assets, package version pins, asset budgets and the still-pending real migration map.
- Forty integration checks pass on WordPress **7.1.2** and **6.6**, both using PHP 8.3 and SQLite in disposable installations.
- All 17 browser acceptance tests pass in the final combined run.
- Responsive behaviour is checked at 320, 390, 479, 480, 767, 768, 1023, 1024, 1279, 1280, 1440 and 1920 pixels. Tests cover overflow, portrait size, compact-menu operation and Escape/focus return.
- Automated WCAG A/AA scans at 390, 768 and 1440 pixels found no violations on the local homepage.
- Native Gutenberg homepage validation and clinical sidebar controls pass. The image remains a valid native Image block; rendered dimensions and WebP derivatives are applied at output time.
- The frontend dependency check sees no external network requests. Local noindex and disabled enquiry delivery are verified.
- Visual inspection covers the desktop portrait layout and the 390px mobile stack.

Integration evidence is saved under `verification/wordpress/`. Browser and Lighthouse summary evidence is also saved there.

## Clinical workflow coverage

Tests cover new entity UUIDs, initial clinician verification, approved release, blocked unreviewed publication, immutable published narrative/metadata, protected deletion and specialties, isolated change drafts, future-date rejection, stale-approval invalidation, atomic promotion to the original page, approved revision identity, generated graph identifiers, typed relationships, unsafe source URLs, capability separation, private APIs and evidence notes, synthetic import planning/idempotence/rollback, and native REST metadata saves. Test records are explicitly synthetic.

The local database transactions are exercised on SQLite. Native MySQL/MariaDB transaction/cache behaviour still requires hosting-equivalent staging verification.

## Performance observations

A mobile Lighthouse run after fixing a missing generated stylesheet and optimising portrait delivery reported:

| Measure | Local laboratory result |
|---|---:|
| Performance | 98 / 100 |
| Accessibility | 100 / 100 |
| Best practices | 100 / 100 |
| Largest Contentful Paint | 2.3 seconds |
| Cumulative Layout Shift | 0 |
| Total Blocking Time | 10 milliseconds |

These are local lab measurements, not field Core Web Vitals or a promise for migrated pages. Hosting response time, caching/compression, real content, third-party operational integrations and network conditions affect production performance. INP requires representative interaction/field measurements; it is not inferred from TBT.

The owned frontend has no application JavaScript bundle. WordPress core supplies native navigation behaviour. The editor-only bundle is approximately 8KB minified before gzip. Theme CSS and local font files pass the agreed source asset budgets.

## Not yet verified or authorised

- Native PHP/WPCS and Docker/MySQL hosting parity: tooling is configured, but this workstation has no native PHP/Composer/Docker.
- Full migrated content, all destination routes, approved redirects, media reconciliation, real credentials and medical review: awaiting the signed URL map and source inventory.
- Real enquiry delivery and privacy/consent configuration: disabled, requiring verified operational setup.
- Manual screen-reader audit of the complete content, captions/transcripts and production form feedback.
- Authenticated staging deployment, restore drill, server caching/compression and post-launch field metrics.
- Live deployment: not authorised and not performed.

This release is a tested local implementation baseline. It is not a declaration that the complete migrated website is ready to launch.
