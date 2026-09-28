# WordPress production proposal — approval gate

Read [WORDPRESS-PRODUCTION-PROPOSAL.md](WORDPRESS-PRODUCTION-PROPOSAL.md) for the proposed data model, theme/plugin architectures, Gutenberg map, entity graph, tooling and migration strategy.

Review [OLD-TO-NEW-URL-MAP-PROPOSED.csv](OLD-TO-NEW-URL-MAP-PROPOSED.csv) alongside the [public URL inventory](evidence/url-inventory.csv).

The public read-only crawl completed on 28 September 2026. Its 29 fetched URLs include 26 sitemap entries and three discovered URLs, one of which is the alternate root spelling. Raw HTML/XML and detailed extracted metadata are in `evidence/`. This is public website evidence, not a WordPress database backup, complete historical URL inventory or search-performance audit.

No production code, migration or live website changes are included. Architecture approval is required before coding. A completed approved URL map is required before migration. Launch requires separate authorization.
