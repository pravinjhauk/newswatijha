# Swati Jha — portable design handoff

Start with **DESIGN-HANDOFF.md**. Open **preview.html** to see the corrected portrait hero and representative components. It is a specimen, not a finished website.

## Files

- `DESIGN-HANDOFF.md`: complete design specification, 25 component families and implementation examples.
- `design-tokens.css`: canonical tokens and selected component styles.
- `preview.html`, `preview.css`: editable local visual specimen, no build step or JavaScript dependencies.
- `assets/images/professor-swati-jha.jpg`: authentic portrait copied from the existing website, replacing the ribbon art.
- `assets/fonts/`: locally bundled normal Latin/Latin Extended Lora and Plus Jakarta Sans fonts, CSS and OFL licences.
- `assets/icons.svg`: original editable geometric UI symbols; no external icon package required.
- `ASSET-MANIFEST.json`: local asset paths, status, provenance, dimensions and integrity checksums.
- `references/`: historical approved palette concept and font provenance. Not production web assets.
- `DECISIONS.md`: explicit approval record and remaining review status.
- `VERIFICATION.md`: checks completed on this handoff.

## Open, edit and move

Open `preview.html` directly in a modern browser. If a browser restricts local font files, serve this directory using any static web server; for example `python3 -m http.server 8765 --bind 127.0.0.1`. No installation, API key or internet connection is needed. External practice links only navigate when clicked.

Copy the entire folder/repository, including `assets/`, to preserve everything. Keep local relative paths when integrating into a theme. Retain font licences. The portrait is supplied for the user's own website project, not licensed for unrelated reuse; the original photographer's rights remain unchanged.

## Mandatory dependency boundary

Higgsfield was used for early visual exploration only. It is not required to render, edit, compile, test or deploy this package or the future website. Do not add its API, SDK, credentials, service URLs or hosted assets to runtime/build/test/deploy code. Optional future imagery must be exported and stored locally before adoption.

The old concept board is preserved for palette provenance. Its ribbon image and invented logo are excluded from the header, hero and production assets. No generated production images are currently approved. The portrait replacement and selected palette are confirmed; the exact completed handoff remains for user review.

The current parent workspace was not a Git repository. This handoff is kept in a dedicated local repository within this folder; it has no remote and is not connected to production.


## Approval update — WordPress production proposal request

The user has now declared the visual-design phase complete and the approved designs authoritative, retaining the authentic-portrait correction and Higgsfield-independence requirement. Earlier pending visual-sign-off notes are superseded by that instruction. Production architecture is proposed in `wordpress-proposal/WORDPRESS-PRODUCTION-PROPOSAL.md`; coding, migration and deployment remain separate approval gates.

## Native WordPress implementation

Architecture approval has now authorised local implementation. See [WordPress implementation handover](WORDPRESS-IMPLEMENTATION.md) and [verification results](WORDPRESS-VERIFICATION.md). Production source is under `wp-content/`; the original static specimen remains a design reference. Content migration and deployment remain separate approval gates.
