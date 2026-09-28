# Design decisions and approval record

| Decision | Status | Evidence |
|---|---|---|
| Preserve a vibrant, colourful and welcoming character | User requested | Feedback that current website attracts young and adult patients |
| Direction 03 — Plum & Turquoise | User selected | “3” following the three revised concepts |
| Remove ribbon artwork from header/hero | User required | Screenshot of rejected ribbon artwork and “dont want this image in header” |
| Use Professor Jha’s photograph instead | User required | “Insert Prof Jha image in place of ribbon design” |
| Higgsfield is temporary design tooling only | User required | Explicit prohibition on runtime and development dependency |
| Exact Lora/Jakarta font pairing and numeric tokens | Production proposal baseline | Design Handoff v1.1 operationalises approved visual direction; do not reimagine styling |
| Corrected portrait composition | Authoritative corrected design baseline | Local `preview.html`; latest user instruction declares visual phase complete |
| Programme/faculty/educational component family | Specified as requested | Future patterns; not claims of current offerings |
| Visual-design phase | Complete per user instruction | “The visual-design phase for swatijha.com is now complete”; approved direction authoritative with portrait correction |

Only the selected concept is retained locally as a historical reference. Its ribbon imagery and generated logo are excluded from production assets. The other five exploratory concepts were not approved and are not part of the production handoff.

Production coding, migration and launch remain separate approval gates. The architecture proposal is documentation only.

## Architecture approved; local implementation

The user's “Yes” approves Gate A of the WordPress proposal. Build the owned theme, core plugin and Gutenberg library locally. It does not approve the pending URL map, real content migration, enquiry activation or live deployment. The workstation uses Node 22.23.3 and a disposable WordPress Playground/PHP 8.3 environment because native PHP/Docker are absent. Docker/MySQL staging parity remains to be verified.
