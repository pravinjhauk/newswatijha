# Handoff verification — 28 September 2026

## Completed

- Authentic Professor Jha photograph retrieved from the existing public website; inspected visually. Original dimensions 453×600px, retained unchanged.
- Rejected ribbon is absent from the specimen/header/hero. The historical concept PNG is stored only in `references/`.
- Local font files, licences and photo load in the browser specimen. All rendering resources use local relative paths; zero JavaScript files, embeds or form submission endpoints.
- Specimen CSP permits only local fonts/images/styles and prohibits remote resource loads and form submission. Ordinary outbound navigation links do not affect local rendering.
- Calculated palette contrast ratios recorded in the specification, including unsafe white-on-turquoise/orange combinations.
- Desktop (1440px), tablet (768px) and narrow mobile (320px) visually inspected. Portrait crop preserves face; text wraps; mobile CTA stacks.
- Geometry inspected at the additional widths below. No page-level horizontal overflow at the checked widths.

| Viewport | Container width | Portrait width | Horizontal overflow |
|---:|---:|---:|---|
| 320px | 288px | 288px | None |
| 390px | 350px | 350px | None |
| 480px | 432px | 420px | None |
| 768px | 704px | 420px | None |
| 1024px | 944px | 373.33px | None |
| 1280px | 1184px | 453px | None |
| 1440px | 1280px | 453px | None |
| 1920px | 1280px | 453px | None |

- Compact specimen menu opens and closes with native disclosure semantics; links become available. This is a simplified no-JavaScript illustration, not the production modal drawer specified in C01.
- Asset manifest includes SHA-256 checksums. `python3 verify_handoff.py` can check asset integrity and local dependency paths offline, using only the Python standard library.

## Limits and next acceptance steps

This is a design handoff and representative browser specimen, not a production website. The production modal drawer, enquiry submission, search, programme registration and media player are specified but not implemented. The specimen footer is a compact reference footer; C19 defines the full site footer.

No full WCAG conformance claim is made. Screen-reader testing, 200%/400% zoom, all interaction states, full keyboard journeys, every breakpoint boundary and real form errors remain production acceptance tasks. Geometry checks are not substitutes for those tests.

The current portrait cannot supply high-density large-screen detail beyond its original 453×600 resolution. A larger authentic original would improve final image sharpness; no synthetic upscaling or facial generation has been performed.

The design specification is complete for review; final visual phase sign-off remains with the user. No live website was modified or deployed.


## Approval update — WordPress production proposal request

The user has now declared the visual-design phase complete and the approved designs authoritative, retaining the authentic-portrait correction and Higgsfield-independence requirement. Earlier pending visual-sign-off notes are superseded by that instruction. Production architecture is proposed in `wordpress-proposal/WORDPRESS-PRODUCTION-PROPOSAL.md`; coding, migration and deployment remain separate approval gates.
