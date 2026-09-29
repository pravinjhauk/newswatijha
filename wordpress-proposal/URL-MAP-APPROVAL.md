# URL map — approval pack (Gate B)

28 September 2026 · **Status: PENDING. Nothing here is approved, applied or migrated.**

Files: `URL-MAP-FOR-APPROVAL.csv` (41 rows) and `MEDIA-INVENTORY.csv` (192 upload URLs). The original `OLD-TO-NEW-URL-MAP-PROPOSED.csv` is left unchanged as the historical proposal.

## What the map says

| Group | Rows | Recommendation |
|---|---:|---|
| Retained pages | 24 + root | Same URL, no redirect. Content rebuilt natively; live titles/descriptions carried as SEO fields. |
| Root without slash | 1 | Already resolves to `/`; no rule. |
| Liquid theme routes | 2 | `?liquid-footer=home` and `/liquid-archives/blog/` → **410**, unless Search Console shows traffic or links. |
| `/uncategorized/` | 1 | **410**. It is a noindex default category archive. |
| `/vaginal-prolapse-signs/` | 1 | **Merge into `/symptoms/` and 301.** It is a noindex post that overlaps `/symptoms/`, with a broken title and meta description. Keep it only if it is meant to be the first article of the guides hub. |
| Host / protocol | 3 | Replicate the current http→https and non-www→www 301s in a single hop. |
| Feeds, XML-RPC, REST | 6 | Retire unused feeds (410), block XML-RPC, no action on REST IDs. |
| Media | 1 group | Carry `/wp-content/uploads/` verbatim: 144 content files kept at the same path, 48 Elementor/Liquid CSS and font artefacts not carried. |
| Rank Math redirects | 1 | Export and carry forward after review. The crawl cannot see them. |
| New-page URL convention | 1 decision | **Flat root** (e.g. `/sacrocolpopexy-sheffield/`) for all future procedure pages. This closes the open silo-versus-flat question. |

## Evidence still missing (needed before signature)

1. **Search Console export, 16 months, page level.** Your SEO audit agent already pulls this, so a CSV of clicks and impressions per URL is enough. It decides P25, P26 and P28 between 410 and 301.
2. **Rank Math → Redirections export** (CSV) from wp-admin.
3. **Media library export**, or a directory listing of `wp-content/uploads`, to catch files not linked from the 29 crawled pages. The crawl saw 192; the library will hold more.
4. **Content decision on `/vaginal-prolapse-signs/`**: fold its unique content into `/symptoms/`, or keep it as a guide.
5. **Mesh wording.** The flagship page's clinical sign-off still depends on resolving the homepage/page-spec conflict with Professor Jha. It does not block the URL map, but it does block migrating that page.

## To approve

Fill `approval` = `APPROVED` (or your amended disposition), `approved_by` and `approved_on` on every row. A single blanket "approved" is not enough: the importer refuses any manifest in which a row is not individually approved. Signing the map authorises a **dry run on local/staging only**. Applying content and redirects remains a separate instruction.
