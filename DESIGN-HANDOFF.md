# SWATIJHA.COM — Design Handoff Specification

**Version:** 1.1 · **Date:** 28 September 2026  
**Direction:** 03 — Plum & Turquoise · **Status:** approved visual direction, newly specified implementation details  
**Audience:** designer, WordPress developer, content editor and QA reviewer

## 1. Scope and source of truth

This specification translates the approved vibrant Plum & Turquoise concept into a reusable website design system. The intended character is colourful, welcoming, personal and clinically credible for younger and older patients. The user has explicitly rejected the ribbon image in the header/hero and requested Professor Jha’s real photograph instead; this correction supersedes that part of the concept. Preserve the energy of the existing website while improving hierarchy and readability.

The user approved the concept, not an exact font family, pixel measurement or finished homepage. The numerical values and font choices below are the proposed implementation baseline. They are explicit design decisions, not measurements extracted from the generated image. Build the first homepage prototype against this baseline, then record agreed changes as a new version.

- [Local original concept reference](references/03-plum-turquoise-original-concept.png) — palette and typography direction only; ribbon imagery superseded.
- [Existing website](https://www.swatijha.com/): source for current service names and practice content. Homepage inspected on 28 September 2026.
- `design-tokens.css`: companion reference tokens and illustrative component styles. It is not a production theme.
- The master project brief governs clinical verification, discovery, URL preservation and the eventual WordPress build. This handoff does not replace those deliverables or authorize publishing.

**Precedence:** accessibility and accurate content → this written specification and CSS tokens → approved image for visual character. Generated lettering, icons and incidental marks in the image are not production assets.

## 2. Visual principles

1. Lead with Professor Jha, her specialist care and Sheffield. Make the clinician the main human presence.
2. Use vibrant colour in controlled, substantial areas: service accents, controls and selected section backgrounds. The header/hero contains no ribbon artwork. Keep paragraphs on quiet, opaque surfaces.
3. Combine expressive serif headings with clear sans-serif reading and interface text.
4. Prefer generous spacing and short, understandable sections. Do not compress clinical information to fit fixed-height cards.
5. Use one dominant action in each decision area. Secondary routes remain visibly available.

Suggested first-screen colour balance: 60% warm white/white, 20% lilac/turquoise tint, 15% plum/turquoise and 5% tangerine. This is an art-direction guide, not a measured quota. The photographic area is excluded. Colour balance must not introduce new decorative imagery into the header.

## 3. Colour tokens

All values are opaque sRGB hex unless specified otherwise. Use semantic tokens in components so the palette can be adjusted centrally.

| Token | Value | Intended use |
|---|---|---|
| `--sj-plum` | `#642C68` | Primary brand, main action, selected state |
| `--sj-plum-hover` | `#512255` | Primary hover |
| `--sj-plum-active` | `#421B46` | Primary pressed |
| `--sj-turquoise` | `#27B9B0` | Vivid accents, illustration, dark-text badges |
| `--sj-teal` | `#087A74` | Accessible teal text on white/warm white |
| `--sj-tangerine` | `#EE8B43` | Small energetic accent; dark text only |
| `--sj-lilac` | `#E5DDF1` | Selected chips, soft section accents |
| `--sj-ink` | `#2A2234` | Main reading text and headings |
| `--sj-muted` | `#62596A` | Secondary text, dates, captions |
| `--sj-warm-white` | `#FAFAF8` | Main page background |
| `--sj-white` | `#FFFFFF` | Cards, form fields, reverse text |
| `--sj-plum-tint` | `#F6F0F7` | Quiet plum section surface |
| `--sj-turquoise-tint` | `#E7F7F5` | Quiet turquoise section surface |
| `--sj-orange-tint` | `#FFF1E5` | Quiet warm accent surface |
| `--sj-border` | `#DDD5E2` | Decorative dividers and nonessential card borders |
| `--sj-control-border` | `#817589` | Form/control boundaries on white |
| `--sj-error` | `#AD253F` | Error text and control border |
| `--sj-error-bg` | `#FFF1F3` | Error notice surface |
| `--sj-success` | `#226A45` | Confirmed success text/icon |
| `--sj-success-bg` | `#EEF8F1` | Success notice surface |
| `--sj-warning` | `#805500` | Warning text/icon |
| `--sj-warning-bg` | `#FFF6DA` | Warning surface |
| `--sj-overlay` | `rgb(42 34 52 / 48%)` | Modal/drawer backdrop |
| `--sj-disabled-bg` | `#EAE6ED` | Disabled controls |
| `--sj-disabled-text` | `#62596A` | Disabled label |

Semantic aliases: `text = ink`, `text-secondary = muted`, `surface = white`, `page = warm-white`, `action = plum`, `link = plum`, `focus = plum`. Do not use turquoise as a replacement for every plum action.

### Verified colour pairings

Contrast ratios below were calculated from these exact hex values using relative sRGB luminance. They verify token pairs, not the accessibility of a future page.

| Foreground / background | Ratio | Rule |
|---|---:|---|
| White / plum | 9.99:1 | Primary buttons and reverse plum panels |
| Ink / warm white | 14.59:1 | Default reading pair |
| Muted / warm white | 6.37:1 | Supporting reading text |
| Ink / turquoise | 6.28:1 | Bright turquoise chip labels |
| Ink / tangerine | 6.11:1 | Orange chip labels |
| Teal / warm white | 4.97:1 | Teal text links; still underline |
| Plum / lilac | 7.59:1 | Selected chip text |
| Control border / white | 4.34:1 | Input boundary |
| White / turquoise | 2.43:1 | Do not use for text or essential icons |
| White / tangerine | 2.50:1 | Do not use for text or essential icons |

Keep large body-copy surfaces solid. Never place reading text directly on the ribbon image. Colour must not be the only indication of a selected option, link, error or status. No automatic dark theme in version 1; explicitly use a light colour scheme.

## 4. Typography

### Families, weights and loading

| Role | Family | Weights | Fallback |
|---|---|---|---|
| Display, H1–H3, clinician wordmark | **Lora** | 500, 600 | Georgia, Times New Roman, serif |
| Body, H4–H6, navigation, controls, captions | **Plus Jakarta Sans** | 400, 500, 600, 700 | system-ui, Segoe UI, sans-serif |

Use Lora 600 for main headings; Plus Jakarta Sans 400 for paragraphs, 600 for labels/buttons, 700 sparingly for emphasis. The families are selected to reproduce the concept’s serif/sans relationship; the raster board does not identify its own font files.

Self-host WOFF2 assets from the official font distributions, retain licence files, and use `font-display: swap`. Deliver normal styles first; include actual italic files only when editorial content needs italics. Do not use synthetic bold/italic as the final typography. Preload only the above-the-fold font files actually used. Normal Latin and Latin Extended WOFF2 binaries, local font-face CSS and both OFL licence files are bundled under `assets/fonts/`. No font CDN is needed. Additional language coverage or italic styles must be bundled locally if later required. Sources: [Lora](https://github.com/google/fonts/tree/main/ofl/lora), [Plus Jakarta Sans](https://github.com/google/fonts/tree/main/ofl/plusjakartasans).

### Type scale

Pixels below are the nominal result with a 16px browser root. Implement with rem; preserve user font preferences. Use the fluid formulas in the CSS for intermediate widths.

| Style | Small screens | Wide screens | Line height | Tracking | Weight/family |
|---|---:|---:|---:|---:|---|
| Hero display | 36px | 64px | 1.10 | −0.025em | 600 Lora |
| Standard page H1 | 34px | 52px | 1.15 | −0.020em | 600 Lora |
| H2 | 28px | 40px | 1.20 | −0.015em | 600 Lora |
| H3 | 24px | 28px | 1.25 | −0.010em | 600 Lora |
| H4 | 20px | 22px | 1.35 | 0 | 600 Jakarta |
| H5 / H6 | 18px | 18px | 1.40 | 0 | 700 Jakarta |
| Lead paragraph | 20px | 22px | 1.55 | 0 | 400 Jakarta |
| Body | 18px | 20px | 1.65 | 0 | 400 Jakarta |
| Card description / form helper | 16px | 18px | 1.60 | 0 | 400 Jakarta |
| Navigation, labels, buttons | 16px | 16px | 1.50 | 0 | 600 Jakarta |
| Caption / breadcrumb / metadata | 14px | 14px | 1.50 | 0 | 400–500 Jakarta |
| Eyebrow | 13px | 13px | 1.50 | +0.08em | 700 Jakarta, uppercase |
| Wordmark name | 20px | 24px | 1.20 | −0.015em | 600 Lora |

Use only one H1 per page. Heading appearance is independent of semantic level. Do not make every card H2 simply to obtain the right size.

Reading width: 65ch maximum for articles and paragraphs; hero copy 50ch; heading 22ch maximum. Hero heading may use 18ch to create three natural lines on desktop. No manual `<br>` elements to force desktop wrapping on mobile. Use `text-wrap: balance` for headings and natural wrapping as fallback. Avoid full justification, all-capital paragraphs and tiny legal text.

Paragraph-to-paragraph gap: 16px. Heading-to-introduction: 16px mobile / 24px desktop. In article flow, H2 top margin 48px, H3 32px; following paragraph 16px. Lists use 8px between items and 24px indentation. Support user text-spacing overrides without clipping.

## 5. Spacing and density

Base unit: 4px. The token scale is `0, 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96, 128px` (tokens `--sj-space-0` through `--sj-space-32`, named by multiples of four).

| Application | Mobile <768px | Tablet 768–1023px | Desktop ≥1024px |
|---|---:|---:|---:|
| Standard section top/bottom | 48px | 64px | 96px |
| Compact section top/bottom | 32px | 40px | 48px |
| Hero top/bottom | 40px | 64px | 80px |
| Section title to component grid | 24px | 32px | 40px |
| Card padding | 24px | 24px | 32px |
| Card/grid gap | 24px | 24px | 32px |
| Form field group gap | 24px | 24px | 24px |
| Label to input | 8px | 8px | 8px |
| Input to helper/error | 8px | 8px | 8px |
| Icon to adjacent label | 8px | 8px | 8px |
| Related CTA gap | 12px | 16px | 16px |

Each section owns its padding. Do not add large bottom margins to its last child. Use grid/flex gap instead of arbitrary child margins. Dense tables are the only compact reading treatment; patient instructions retain the normal body scale.

## 6. Layout widths and breakpoints

Use mobile-first CSS. Breakpoints refer to viewport width, not device names. Content must work at all widths between the named checks.

| Range/token | Width | Page gutter each side | Grid | Header |
|---|---|---:|---|---|
| Base | <480px | 20px; 16px at ≤359px | 4 conceptual columns; one-column content | Compact navigation |
| `sm` | ≥480px / 30rem | 24px | 4 columns; one-column service cards | Compact navigation |
| `md` | ≥768px / 48rem | 32px | 8 columns; two-column service cards | Compact navigation |
| `lg` | ≥1024px / 64rem | 40px | 12 columns; three-column service cards | Compact navigation |
| `xl` | ≥1280px / 80rem | 48px | 12 columns | Full navigation |
| `2xl` | ≥1536px / 96rem | 48px minimum | Same capped content width | Full navigation |

- Main container content width: **1280px maximum**, centered. Gutters are outside that width: `width: min(1280px, 100% - 2 × gutter)`.
- Reading container: `min(720px, 65ch, 100% - 2 × gutter)`.
- Form container: 640px maximum. Wide CTA copy: 760px maximum.
- Grid gap: 24px under 1024px; 32px from 1024px.
- Hero: single column below 1024px; **7/12 text + 5/12 visual** from 1024px, with a 48px gap. Allocate remaining width in the 7:5 ratio after the gap.
- Biography split: single column below 1024px; 5:7 image/text above, 48px gap.
- Article: single column below 1024px; 240px contents rail + 48px gap + up to 720px reading area above. Keep rail nonsticky in version 1.
- Full-bleed section colour may reach the viewport edge; content always aligns to the shared container.

Widths at examples: 390px viewport → 350px content; 768px → 704px; 1024px → 944px; 1440px → 1280px; 1920px → 1280px. Never stretch typography or cards to fill an ultrawide monitor.

## 7. Shape, borders, elevation and layers

| Token | Value | Use |
|---|---|---|
| Radius none | 0 | Tables, inline rules |
| Radius small | 8px | Inline notices, small media |
| Radius control | 12px | Inputs, menus |
| Radius card | 20px | Service/article cards, accordions as a group |
| Radius feature | 32px | Hero portrait, feature panels, large CTA |
| Radius pill | 999px | Buttons, topic chips |
| Border hairline | 1px solid decorative border | Dividers, passive cards |
| Border control | 1px solid control border | Inputs |
| Border emphasis | 2px solid plum | Secondary button, selected control |
| Shadow 0 | none | Default cards |
| Shadow 1 | `0 2px 8px rgb(42 34 52 / 6%)` | Header, subtle raised control |
| Shadow 2 | `0 8px 24px rgb(42 34 52 / 10%)` | Hovered cards, dropdown |
| Shadow 3 | `0 20px 56px rgb(42 34 52 / 16%)` | Modal or navigation drawer |

Do not apply shadows to every surface. Feature corners reduce to 20px below 768px. Photos have no decorative heavy border.

Layer tokens: base 0, decoration 1 within its own isolated visual, content 2, dropdown 20, optional sticky interface 30, modal backdrop 40, modal/drawer 50, toast 60, focused skip link 100. The default header is not sticky. Do not introduce decorative ribbon imagery into the header/hero. Elsewhere, never place ornament over interactive content.

Motion: fast 120ms, standard 180ms, panel 240ms; easing `cubic-bezier(.2, 0, 0, 1)`. Animate colour, shadow and at most 2px vertical card movement. No looping hero animation, parallax or entrance animation hiding content. Reduced-motion mode removes transitions, movement and smooth scrolling.

## 8. Shared interaction states

| State | Required treatment |
|---|---|
| Default | Label and boundary readable; action clear without hover |
| Hover | Darken plum action; card border becomes plum with shadow 2; only apply movement on hover-capable devices |
| Focus | 3px plum outer ring with 3px white separation; never remove outline without equivalent |
| Focus on dark panels | White outline with a dark separating halo |
| Pressed | Plum-active fill; no scale animation that moves surrounding content |
| Selected/current | Lilac surface, plum text/border, plus tick/underline and semantic current/selected state |
| Disabled | Disabled background/text; no shadow or pointer response; native disabled where supported; adjacent reason if relevant |
| Loading | Retain control width and accessible label; add 20px spinner and “Sending…”; set busy state and prevent duplicate submission |
| Error | Error border, icon and explicit textual explanation connected to field; retain entered content |
| Success | Success icon and text; show only after confirmed server response |

Minimum project target: 48×48px for standalone interactive controls. Inline text links are underlined and follow reading flow. Do not dim entire controls with opacity to create their disabled state. Navigation uses links; actions that change state use buttons.

## 9. Component specifications

### C01 — Header, wordmark and navigation

- Surface white; bottom 1px decorative border. Content uses main container. Padding 16px vertical desktop, 12px compact. Minimum height 96px desktop / 80px compact; allow height to grow with text.
- Wordmark: text “Professor Swati Jha”, Lora 600, 24px desktop / 20px compact. Specialty subtitle 14px desktop. Compact header can omit subtitle because the hero repeats it visibly. Never truncate the clinician’s name.
- Use an approved existing vector logo if suitable. The small generated ribbon mark in the concept is exploratory and must not silently replace the current logo. A text-only wordmark is the implementation default until the logo asset is approved.
- Full navigation from 1280px: About, Conditions & treatments, Patient information, Contact; one plum “Book a consultation” action. Navigation item gap 24px; link hit area at least 48px high.
- Current page: 2px plum underline, 6px below label, and `aria-current="page"`.
- Compact: wordmark left, 48px menu button right. At 320px, allow the name to wrap; keep the menu button fixed-width.
- Menu drawer: width `min(400px, 100vw)`, right aligned; white surface, shadow 3, padding 24px, height 100dvh, internal scroll. Links 18px/1.5, minimum 48px tall, 8px gaps. Full-width booking button after links.
- Drawer is modal: move focus inside, trap focus, inert background, Escape and backdrop close, return focus to trigger. Include labelled close button. When switching to full navigation, close drawer and release scroll lock.
- Desktop treatment menu opens on click and keyboard, not hover alone. Trigger is a button with expanded state. Panel max width 640px, padding 24px, radius 12px, shadow 2. If its title also needs a landing page, provide a separate “All treatments” link.

### C02 — Breadcrumbs

14px Jakarta, muted; 8px gaps; minimum 24px vertical padding. Wrap naturally. Current page is plain ink text. Use a labelled navigation landmark and an ordered list. Never show breadcrumbs on the homepage.

### C03 — Homepage hero

- Main-container grid and spacing follow section 6. Background warm white. Eyebrow → H1 → lead → optional service routes → CTA group → concise location line.
- Eyebrow: “CONSULTANT UROGYNAECOLOGIST · SHEFFIELD”. H1: “Specialist care for prolapse & pelvic floor problems”. These are proposed display copy based on the brief.
- Lead: 50ch maximum. Use current-site content to describe assessment and treatment; clinical claims require content review.
- Spacing: eyebrow–H1 12px; H1–lead 24px; lead–routes 24px; routes–CTA 24px; CTA–locations 16px.
- Primary action “Book a consultation”; secondary “Explore prolapse treatment”. Use existing destination URLs until migration decisions are approved.
- Main image: authentic Professor Jha portrait. Use the portrait alone on a clean surface. Do not place ribbon artwork behind, beside, over or around it. Colour comes from the plum CTA, lilac chips and turquoise/orange supporting accents.
- Desktop portrait 4:5, max width 453px for the supplied 453×600 original; text and portrait vertically centered. No fixed hero height.
- Mobile/tablet: text and CTAs first, portrait next, 32px gap. Portrait width up to 420px centered, with 4:5 default crop. Do not hide useful content to fit “above the fold”.
- At <480px CTA group stacks and buttons fill available width; from 480px it wraps as a row. The ribbon artwork is excluded at every breakpoint. All copy remains live HTML.

### C04 — Buttons and text links

| Variant | Dimensions | Visual |
|---|---|---|
| Primary | Min height 52px; 12px vertical / 24px horizontal padding | Plum fill, white label, pill, 2px transparent border |
| Secondary | Same dimensions | White fill, plum label and 2px border |
| Compact | Min height 48px; 10px / 20px padding | Same variants |
| Icon-only | 48×48px | 20–24px icon, accessible text name |
| Inline link | Inherits surrounding type | Plum, underline 1px with 3px offset |

Labels 16px/1.5, 600 weight; icon 20px, gap 8px. Allow long labels to wrap and control height to grow. Secondary hover uses plum-tint; pressed uses lilac. Inline hover thickens underline to 2px. Do not underline button labels. Reverse CTA on plum: white filled primary with plum label; secondary white text and border.

### C05 — Topic chips and service shortcuts

48px minimum height; padding 8px 16px; radius pill; text 14px/1.5, 600. Optional icon well 32px; icon 20px. Default lilac surface/ink text. Active plum text plus 2px plum border and check marker. Use dark ink on turquoise/orange accents, never white glyphs.

Wrap onto multiple lines with 12px gap at every width; no horizontal chip carousel. A chip that navigates is an anchor. A real filter is a button with pressed state; do not apply filter semantics to navigation links. Service colour is secondary to the label and carries no diagnostic meaning.

### C06 — Service and symptom cards

- Grid: 1 / 2 / 3 columns at base / 768 / 1024px. White surface, decorative border, radius 20px; 24/32px padding, shadow 0.
- Structure: 48px icon well → 16px gap → H3 → 12px gap → description → 24px gap → descriptive link. Icon 24px, dark ink on coloured well.
- Service accents: prolapse plum/lilac, urinary symptoms turquoise/turquoise-tint, menopause tangerine/orange-tint. Other services rotate tint surfaces without implying clinical priority.
- Description 16–18px; suggested length 25–45 words; do not line-clamp clinical copy. Equal height within each row; link aligned toward bottom with flexible space, never a fixed card height.
- Hover: plum border, shadow 2, translateY(−2px). Focus visible on its anchor.
- Simple one-destination shortcut can be one whole-card anchor with no nested interactive controls. Rich cards use a normal title/action link; do not use a pseudo-element overlay that blocks text selection or other links.
- Symptom example: “I have been told I have a prolapse” → useful next-step information. Wording must not imply an automatic diagnosis.

### C07 — Credentials strip

Quiet white or plum-tint surface, 24px vertical padding. Text 16px/1.5 with 20px icons; up to four concise facts. Four columns ≥1024px, two ≥768px, stacked below. Gap 24px. Use only verified credentials. No decorative hospital/RCOG logos implying endorsement; use licensed logos only where permitted. No hardcoded counts copied from old snapshots.

### C08 — Treatment pathway

Numbered sequence, 1–5 steps. Desktop ≥1024px horizontal equal columns with 24px gaps; smaller widths vertical list with 24px gaps. Number disc 40px, plum fill/white 16px text; H4 and description 16–18px. Connector lines decorative only, 2px, do not cross text. Preserve source order. Use an ordered list. Example labels: Enquiry → Consultation → Assessment → Treatment plan → Follow-up.

### C09 — Treatment options / decision cards

Two columns ≥768px, one below. Use equal visual weight for surgical and non-surgical options. Radius 20px, padding 24/32px, quiet tinted surfaces. Heading H3, description and 3–5 short bullets, explicit learn-more link. Avoid “best option” badges or sales-style price tables. Clinical decision aids need clinician-reviewed content.

### C10 — Professor profile / academic section

Biography uses the 5:7 split; authentic 4:5 portrait left on desktop, above text on smaller screens. Name H2; role lead text; biography body limited to 65ch. Credentials are a list, followed by one biography link. Selected publications are rows with title, authors/year/source and DOI or publisher link. Academic metadata 14px; publication title 18px/1.4, 600. Separate current, past and honorary appointments in content.

### C11 — Patient information / article cards

Grid 1/2/3 as service cards. Image 3:2, radius 20px at top corners; card content 24px. Category 13px eyebrow, title H3, short summary, date/reviewer metadata 14px. Image and title link to the same article without duplicating verbose accessible names. No fixed excerpt height; no fabricated review dates. Single archive pagination: 48px controls, wrapping row, visible current page.

### C12 — Clinical answer box, key points and review metadata

Answer box: turquoise-tint background, 4px teal left border, radius 8px, 24/32px padding. Question H3, answer body, optional source link. Key points: unordered list, 8px item gaps. Warning/urgent-help content uses warning or error treatment only when clinically appropriate.

Reviewer block: top decorative border, 24px padding, name/role 16px, dates 14px. Place before or after article body consistently. Distinguish published, updated and medically reviewed dates. References use a numbered list, 16px/1.6, DOI/URL wraps with `overflow-wrap:anywhere`.

### C13 — Reviews

White cards, 24px padding, radius 20px; quotation 18px/1.65, attribution 14px. Use real authorized review text and original source link. Do not generate patient identities or portraits. Display up to three static cards in a responsive grid; avoid auto-advancing sliders. Rating and count come from one maintained source. If unavailable, show a “Read patient reviews” link rather than stale numbers. Reserve space for any third-party embed to reduce layout shifts.

### C14 — Clinic locations

Two columns ≥768px, one below; 4:3 authentic hospital exterior image, H3 location name, address 16–18px, telephone link and booking link. Card padding 24/32px. Location names from current site: Spire Claremont Hospital and Circle Thornbury Hospital, Sheffield. Verify contact details before implementation. Use a click-to-load map or external directions link; photograph and text remain useful if the map is unavailable.

### C15 — FAQ accordion

Reading-container width; 1px separators; group radius 20px optional. Header is a real button within a heading, min height 64px, padding 20px 24px, label 18px/1.4 weight 600, plus/minus icon 20px. Answer padding 0 24px 24px, body scale. Whole header activates; icon is decorative. Support expanded state and panel relationship; Enter/Space toggle. Multiple items may remain open. Keep all answers in HTML. Avoid height animation; no automatic collapse of another answer.

### C16 — Enquiry and booking form

- Maximum width 640px, white surface. Visible label above each field. Field min height 56px, padding 14px 16px, radius 12px, 1px control border, 18px text. Placeholder is optional and never replaces label.
- Textarea minimum 160px, resizable vertically. Select same dimensions. Checkbox/radio visual 24px, total clickable label area at least 48px high, 12px gap to text.
- Single column below 768px. Above, only related short fields such as first/last name may share two columns with 24px gap. Email and message remain full width.
- Helper 16px, 8px below input. Error 16px with icon, tied by described-by and invalid state. Error summary at top with field links; focus summary on failed submission. Do not clear patient-entered text.
- Validating on submit is the baseline; after first failure, revalidate corrected fields on blur. Avoid errors before the patient has interacted.
- Submit 52px min height, full width <480px. Loading and confirmed-success states follow section 8. Network/server failure keeps data and shows retry guidance. Never display success after a click alone.
- Use native input types/autocomplete; mark required fields in text. A consent/privacy control must have accurate purpose and approved wording. Do not preselect marketing consent.
- Avoid collecting unnecessary clinical detail. Any privacy notice and urgent-care wording must be supplied or approved by the practice; link to the exact existing policy URL during prototyping.

### C17 — CTA feature panel

Plum background, white heading/body, radius 32px desktop / 20px mobile; padding 48px desktop / 32px mobile. Heading H2, paragraph max 50ch. Copy/action split ≥1024px; stacked below, 24px gap. Primary white button with plum text; reverse secondary style. Optional solid turquoise or orange corner accents are CSS shapes only; no generated ribbon asset is specified. Never add urgency countdowns or unverified outcomes.

### C18 — Referrer panel

White or turquoise-tint, radius 20px, 24/32px padding, H3, concise explanatory copy and “Refer a patient” link. Place separately from patient booking. Do not imply a referral portal exists unless implemented. Files/downloads show format and size when known.

### C19 — Footer

Ink background with white text and white underlined links. Four columns ≥1024px; two ≥768px; one below. Gap 32px; section padding 64px desktop / 48px mobile. Name 24px Lora; subheadings 16px Jakarta 700; links 16px with 48px interactive line boxes where practical. Legal line 14px with readable line height. Preserve privacy/contact/registration content and real hospital details. No collapsed footer accordions in version 1.

### C20 — Notices, empty states and errors

Notice padding 20px, radius 8px, left icon 24px and text; use semantic foreground/background pairs. Status must be stated in text. Announce newly inserted status politely, errors assertively only when necessary. Persistent notices stay visible until resolved. Optional dismiss button 48px with explicit accessible name. Empty list: plain explanation and useful next action. 404 page: H1, concise explanation, homepage/treatment routes and contact option; no dead search field.

### C21 — Tables, lists and downloads

Table headings 16px/1.5, 600; cells 16–18px/1.6; padding 16px; 1px decorative row lines. Caption above table; real row/column headers. At narrow widths, preserve meaningful comparison in a labelled horizontally scrollable region with keyboard access and visible overflow hint. Prefer a stacked list if the data is not inherently comparative. Downloads have descriptive link text, format/size and no forced new tab.

### C22 — Search, pagination and overlay components

If search is implemented: labelled input follows C16; clear button 48px; submit button follows C04. Results are a list with H3 title, excerpt, type, 24px gaps; query remains visible. No-results state suggests clinical topic routes. Do not add decorative search UI without a working search.

Generic modal: width `min(640px, 100% - 32px)`, max-height `calc(100dvh - 32px)`, internal scroll, radius 20px, padding 24px mobile / 32px desktop, shadow 3. Labelled title, explicit close, focus management and Escape as C01. A cookie consent panel, if required, must use equally clear choice buttons and cannot cover focused controls; do not build a tracking interface solely because it is in a template.

### C23 — Programme components

These are reusable patterns for future verified educational programmes or structured services. Their inclusion is not a statement that Professor Jha currently sells courses or runs a particular programme. Do not publish placeholders as actual offerings.

**Programme card:** reuse C06 grid, padding and radius. Optional 3:2 editorial image; audience/type eyebrow; H3 title; 16–18px summary; metadata list (format, duration, location/date only when confirmed); one primary link. Metadata uses 16px labels and 20px icons, 8px gaps. Title 2–3 lines as an editorial target, no truncation. A draft/unavailable offering is omitted from the public site, not presented as bookable.

**Programme header:** use C03 hierarchy with standard page H1, optional 3:2 image and a 48px metadata row below the lead. Desktop 7:5 split; stacked below 1024px. Enrolment/booking control appears only when a functioning, approved destination exists. A date cancellation uses warning notice and explicit text.

**Programme overview:** two-column learning-outcomes and intended-audience cards ≥768px, stacked below. H3, body text, 8px bullet gaps. No promise of accreditation or CPD points without verification.

**Module/schedule list:** ordered list or C15 accordion. Each row min 72px, 24px padding, 1px divider, numbered 40px disc, H4 title, duration 14px metadata, optional description. Multiple modules may expand. Mobile duration wraps under title; no horizontal timeline. Do not imply completion tracking if there is no learning system.

**Enrolment panel:** C17 visual treatment; price, availability, terms and status must come from confirmed structured data. Loading/error/closed/waitlist states use section 8; do not silently replace a closed course with an active booking CTA. Registration and payment are separate implementation projects, not functions of Higgsfield.

### C24 — Faculty and contributor components

Use for a genuinely confirmed teaching faculty or article contributors; do not create fictional colleagues or imply a larger clinical team. For the current website, Professor Jha’s profile is the primary instance.

**Faculty card:** one/two/three-column grid at base/768/1024px. 4:5 authentic headshot above content, radius 20px top corners, maximum image display width 360px; padding 24px; name H3; current role 16px/1.5; organisation/discipline metadata 14px; bio excerpt 40–60 words at 16–18px; profile link. Avoid circular crops that cut faces. If no approved photograph exists, use a labelled text-only card; never generate a substitute face.

**Faculty profile:** C10 biography split; professional title and qualification text separate from name. Verified biography, teaching topics, selected publications and declared affiliations each have their own heading. Distinguish historical roles. Contact details shown only with consent and actual purpose.

**Contributor byline:** optional 48×48px authentic headshot with 8px radius, name 16px/600, role and review date 14px, 12px gap; wraps on mobile. Linking the name to an actual profile is sufficient; avoid duplicate image links. Publication ownership and clinical review remain explicit fields.

### C25 — Educational content and media components

**Resource library:** C11 cards for articles, patient leaflets, recorded talks and verified teaching resources. Filter labels use C05; optional search C22; results count 16px and polite announcement after user-triggered filtering. Controls stack below 768px, wrap in a row above. Preserve the selected query in the URL if implemented; no filter UI without functionality.

**Learning/article page:** use reading width and C12 metadata. Desktop nonsticky contents rail; mobile contents disclosure with 48px trigger. Educational level/audience label, title, introduction, optional objectives, body, resources and references. Headings remain a meaningful hierarchy. No paywall/progress/login styling unless the product actually supports it.

**Video or recorded talk:** 16:9 player frame, radius 12px, caption and speaker attribution below at 14px. User-triggered play; no autoplay. Controls keyboard-operable, captions for speech, transcript below with C15 disclosure, and an accessible alternative for relevant visual content. Load a local poster and consent-gated external media only if needed. Unavailable media shows a useful explanation/transcript; no empty black rectangle. Poster max 1280×720px, target ≤120KB.

**Audio:** native accessible controls across reading width; visible episode title, duration and transcript. No autoplay. **Download row:** 24px file icon, title 18px/600, format/size 14px, 16px padding, minimum 64px row height; stacks metadata under title on mobile.

**Educational callout:** C12 style with explicit label such as “Key points” or “For healthcare professionals”. Clinical diagrams use intrinsic proportions and a full textual explanation. Knowledge checks or assessments require a separate agreed interaction specification; they are not implied by a static education component.

## 10. Imagery and asset requirements

| Asset | Aspect ratio | Suggested source export | Responsive behaviour |
|---|---|---|---|
| Professor hero portrait | 4:5 display | Supplied original: 453×600px | Max 453px rendered width; 4:5 cover crop verified on specimen; higher-resolution original useful for high-density displays |
| Biography portrait | 4:5 | 1000×1250px | Preserve face/shoulders; do not upscale a low-resolution original |
| Generated concept board | 1024:688 | Local original PNG under `references/` | Historical visual reference only; never load it in site pages |
| Patient-information thumbnail | 3:2 | 1200×800px | Same ratio across a card row |
| Hospital exterior | 4:3 | 1200×900px | Show identifiable architecture; no fabricated facility |
| Wide article editorial image | 16:9 | 1600×900px | Retain ratio unless meaningful detail would be cut |
| Clinical diagram | Intrinsic | SVG preferred, or crisp raster | Contain, not cover; no cropping of labels |
| Social sharing image | 1.91:1 | 1200×630px | Separate composition; do not crop screenshot of homepage |

Use AVIF/WebP with suitable fallback where needed; preserve originals. Candidate responsive widths: 400, 640, 960, 1280 and 1600px, bounded by source resolution. Set intrinsic width/height to reserve space and provide accurate `sizes`. Hero is eager/high-priority only when it is the likely largest-content image; below-fold assets lazy-load. Initial image budgets: hero ≤220KB, card ≤100KB, decorative assets ≤150KB if separately approved; these are targets subject to visible quality, not permission to blur faces.

Portrait focal point must be set after reviewing the actual asset; use `50% 35%` for the supplied original, then review if the image changes. If the face cannot survive a 4:5 crop, use an approved alternate photograph or uncropped treatment. Do not generate, reshape or beautify Professor Jha’s face. Never fabricate patients, operations or hospital facilities.

The Higgsfield concept is a moodboard, not an export-ready hero. Extract neither its lettering nor its invented logo for production. Its ribbon art has been rejected for the header and is not an approved production asset. Decorative imagery, if separately approved later, has empty alt text; meaningful portraits describe the subject; diagrams need a caption and textual equivalent. Never use SEO keyword lists as alt text.

Icons: consistent 24px viewBox, 1.75px stroke, round joins/caps; 20px for controls and 24px for cards. Use one licensed icon family and retain licence. No emoji as UI icons. Avoid decorative anatomical icons that could misrepresent a service.

## 11. Responsive rules by component

| Component | <480px | 480–767px | 768–1023px | ≥1024px |
|---|---|---|---|---|
| Header | Compact, name can wrap | Compact | Compact | Compact until 1280px, then full |
| Hero | Single column; full-width CTA | Single column; wrapping CTA row | Single column | 7:5 split |
| Services/articles | 1 column | 1 column | 2 columns | 3 columns |
| Treatment options/locations | 1 column | 1 column | 2 columns | 2 columns |
| Credentials | Stacked | Stacked | 2 columns | 4 columns |
| Pathway | Vertical | Vertical | Vertical | Horizontal |
| Biography | Image above copy | Image above copy | Image above copy | 5:7 split |
| Form | All full width | All full width | Related short fields can pair | Same, capped at 640px |
| Article contents | Disclosure before body | Disclosure before body | Disclosure before body | Nonsticky 240px side rail |
| Footer | 1 column | 1 column | 2 columns | 4 columns |

At 320px there must be no page-level horizontal scrolling. Tables may use their own labelled scroll regions. Content order remains the same in the DOM and visual layout. At 200% zoom navigation must collapse as available width reduces. At 400% zoom and equivalent 320px layout width, content must reflow. Do not rely on hover to reveal essential information. Respect safe-area insets for full-height mobile drawers.

## 12. Worked examples

### Example A — First-screen content hierarchy

```text
CONSULTANT UROGYNAECOLOGIST · SHEFFIELD         [Authentic Professor Jha portrait]
Specialist care for prolapse &                 [4:5 frame; no ribbon artwork]
pelvic floor problems

Professor Swati Jha provides specialist care
for prolapse, urinary incontinence and
pelvic-floor conditions in Sheffield.

[Book a consultation]  Explore prolapse treatment →
Spire Claremont Hospital · Circle Thornbury Hospital
```

On mobile, keep the entire text/action block above the portrait. This is a hierarchy example, not an approved complete homepage layout or final clinical copy.

### Example B — Buttons using existing destinations

```html
<div class="sj-actions">
  <a class="sj-button" href="https://www.swatijha.com/book-consultation/">
    Book a consultation
  </a>
  <a class="sj-button sj-button--secondary"
     href="https://www.swatijha.com/vaginal-prolapse-treatment-sheffield/">
    Explore prolapse treatment
  </a>
</div>
```

### Example C — Service card

```html
<article class="sj-card">
  <span class="sj-icon-well" aria-hidden="true"><!-- approved 24px SVG --></span>
  <h3>Vaginal prolapse</h3>
  <p>Explore assessment and treatment options for pelvic organ prolapse.</p>
  <a href="https://www.swatijha.com/vaginal-prolapse-treatment-sheffield/">
    Explore prolapse care
  </a>
</article>
```

### Example D — Responsive portrait markup pattern

```html
<!-- This path exists in the handoff package. -->
<img class="sj-portrait"
     src="assets/images/professor-swati-jha.jpg"
     width="453" height="600"
     alt="Professor Swati Jha"
     fetchpriority="high">
```

The supplied photograph is 453×600px. Do not invent higher-resolution source-set files or upscale it. For production, generate smaller WebP/AVIF derivatives locally if useful, and add higher-density candidates only when a larger authentic original is supplied. The CSS display frame is 4:5; the encoded file retains its original ratio.

### Example E — Field/error relationship

```html
<div class="sj-field">
  <label for="email">Email address (required)</label>
  <input id="email" name="email" type="email" autocomplete="email"
         required aria-invalid="true" aria-describedby="email-error">
  <p id="email-error" class="sj-field-error">
    Enter an email address in the format name@example.com.
  </p>
</div>
```

The invalid attributes/error text appear only after validation failure; they are included here to demonstrate the error state.

## 13. WordPress implementation mapping

Keep WordPress as the CMS. Map palette, typography, spacing, content widths and radii into `theme.json` and shared CSS variables. Use the same public names in the editor and frontend. Editors choose curated component variants, not arbitrary font sizes or colours.

Reusable patterns/blocks: site header; hero; credentials; service grid; symptoms; answer box; treatment options; pathway; profile; publication list; article grid; review panel; locations; FAQ; form; referrer panel; final CTA; footer.

Each component supports optional heading/copy/action/media fields without leaving empty gaps. Use one responsive component instance; avoid maintaining duplicate desktop/mobile text. Keep clinical metadata, practice details and review counts in central structured fields. Ordinary page editing must not require writing HTML. The core plugin owns structured business data; theme styles own presentation.

All links in this specification are existing-site references, not a new sitemap. Do not rename URLs or replace the theme as part of accepting this handoff. The earlier master brief’s discovery and migration work remains required before production implementation.

## 14. Accessibility and acceptance checks

Target WCAG 2.2 AA. Normal text needs 4.5:1 contrast; large text 3:1; essential component boundaries/icons need 3:1 against adjacent colours. WCAG AA target-size minimum is 24 CSS px with exceptions; this project intentionally specifies 48px standalone controls. Ensure visible keyboard focus and that focused content is not obscured. A stronger focus ring is a project choice, not a claim that the AAA Focus Appearance criterion is part of AA. [Source: W3C WCAG 2.2](https://www.w3.org/TR/WCAG22/).

Before approving the coded prototype:

- Inspect 320, 390, 480, 768, 1024, 1280, 1440 and 1920px; additionally inspect either side of each breakpoint.
- Check typography, portrait crop, CTA order, menu behaviour and long copy at each layout change.
- Test keyboard-only navigation, drawer focus/close, form errors, accordions and skip link.
- Test 200% and 400% zoom, enlarged default font size and text-spacing overrides.
- Check actual text/background pairs, hover/focus states, form boundaries and graphics; do not infer page compliance from this palette table.
- Check screen reader names, heading order, landmarks, expanded/current states and error announcements.
- Check reduced motion, unavailable images, blocked third-party widgets and failed form submission.
- Verify real image sources, live contact information, review data and clinical wording before release.
- Keep text selectable, images dimensioned and navigation available with progressive enhancement.

The browser specimen demonstrates responsive styling and the portrait replacement, but contains no working appointment submission or production homepage. Those behaviours are specified here for implementation and acceptance.

## 15. Higgsfield independence and asset ownership

**Mandatory architecture constraint:** Higgsfield is a temporary design-generation tool only. It is not a runtime, development, build, test, editing or deployment dependency. Do not add its SDK, API credentials, project IDs, hosted asset URLs or generation calls to the theme, plugin, editor, build scripts, tests or deployment configuration. New imagery may optionally be commissioned through it and then exported as ordinary local files.

Every approved production image must live under repository-managed `assets/` (or be copied to the site's own controlled media storage at deployment). The manifest records filenames, source, dimensions, status, intended use and checksums. Preserve originals and derivation details. No production asset should require opening a Higgsfield account, recovering a job ID, querying a generation API or fetching a vendor-hosted image.

The original generated board is stored in `references/03-plum-turquoise-original-concept.png`. It preserves the approved colour direction, but the pictured ribbon and invented mark are **not approved production imagery**. It is deliberately excluded from `assets/` and from the specimen. There are currently **zero approved generated production images**. The user's requested replacement is the authentic, locally stored portrait. Thus no separate ribbon export is required or permitted in the header.

The exact corrected portrait composition and the expanded component set are supplied for review in this handoff. Palette selection and the portrait-replacement instruction are confirmed; unreviewed programme/faculty examples and newly specified measurements are not retroactively labelled user-approved. Record final prototype approval in `DECISIONS.md` before declaring the visual phase signed off.

### Offline contract

1. `preview.html`, `design-tokens.css`, `preview.css`, local font CSS/binaries, portrait and SVG sprite are sufficient to render the specimen. No package install, build service, API key, account login or internet access is needed.
2. All specimen images/fonts/styles are relative local files. Its policy blocks remote resources. External links are ordinary optional navigation, not render/build dependencies.
3. Edit the Markdown/CSS/HTML in any editor. Open `preview.html` in a browser or serve the directory with any static server. Copying the repository to another machine preserves the design reference.
4. The eventual WordPress implementation may use WordPress, PHP and the project’s agreed Node toolchain; it must not require Higgsfield. Build outputs must contain locally controlled asset paths and no Higgsfield credentials or calls.
5. CI for the eventual site should reject references to Higgsfield services in runtime/build files, validate the local asset manifest, and build/test with those services unreachable. Source-history notes in documentation are allowed.

### Exit checklist for the design phase

- Approved language recorded: vibrant Plum & Turquoise; clear serif/sans hierarchy; real clinician photography; no ribbon header.
- All requested foundation and component families defined in this document, including programme/faculty/education variants.
- Local source assets, font licences, manifests and editable tokens included; no cloud-only assets required.
- Corrected portrait specimen reviewed at desktop, tablet and mobile; results recorded in `VERIFICATION.md`.
- Any remaining user review clearly marked; do not claim that the complete website or clinical content has been approved.

## 16. Delivery and remaining asset decisions

**Included:** this complete specification, canonical CSS token sheet, component style examples, a portable browser specimen (`preview.html`), the authentic local portrait, bundled fonts/licences, the local original concept reference and an asset manifest.

**For the homepage prototype:** use the bundled Professor Jha portrait (or a larger authentic original); use the supplied editable SVG icon sprite; retain the text-only wordmark until a separate logo is approved; load the bundled fonts. Do not add the rejected ribbon image. All exact design values are defined here so prototyping can proceed without inventing a new visual system.

**Before launch:** complete the master brief’s discovery/migration work, verify clinical and professional claims, approve final copy and photography, and test the implemented pages. No public site has been changed by this handoff.


## Approval update — WordPress production proposal request

The user has now declared the visual-design phase complete and the approved designs authoritative, retaining the authentic-portrait correction and Higgsfield-independence requirement. Earlier pending visual-sign-off notes are superseded by that instruction. Production architecture is proposed in `wordpress-proposal/WORDPRESS-PRODUCTION-PROPOSAL.md`; coding, migration and deployment remain separate approval gates.
