# Design system

Updated 30 October 2026. The public site remains server-rendered Laravel 13/Blade with small TypeScript enhancements. React 19 is restricted to administration. No new packages, schema migrations, hosted font services or production Node server are required.

## Configuration and persistence

`App\Support\Appearance` is the source of truth for the version 1 configuration, defaults, presets, validation, contrast checks, section variants and CSS rendering. `ClinicSettings` resolves it from the existing `settings` table under `appearance`. The separate `appearance_revision` row protects publication from concurrent edits. The homepage continues to use `homepage_sections`; there is no duplicate section store.

All configuration arrays use allowed-key validation. Numbers have bounded ranges, fonts and variants use allowlists, colors require six-digit hex, and copyright is limited to 200 characters and escaped in HTML. Validated values are normalized to numbers, booleans and strings before previewing, publishing or resolving stored configuration. Imported `0`/`1` values therefore render correctly in both CSS attributes and editor checkboxes. Missing tokens receive defaults. Malformed stored configuration falls back to safe defaults. Existing legacy branding seeds the initial appearance until the first publication. Legacy fields stay in storage for compatibility; visual editing is centralized in Appearance studio.

Settings and resolved appearance are memoized within the request. An update invalidates both in-memory values immediately. The next request reads the database, so no long-lived stale theme cache needs flushing. No private notification, mail or patient configuration enters preview payloads or appearance exports.

## Rendering contract

`Appearance::css()` emits allowlisted `--color-*`, `--typography-*`, `--layout-*`, `--buttons-*`, `--cards-*`, `--header-*`, `--footer-*` and `--motion-*` custom properties. The public layout supplies categorical `data-*` attributes. Components consume these tokens through `resources/css/app.css`; section templates consume `ClinicSettings::sectionAttributes()`.

The names `--brand`, `--paper`, `--ink`, `--muted`, `--line`, `--soft`, `--space`, `--gap`, `--section-space`, `--h1`, `--h2` and `--h3` are derived semantic aliases. Density scales section, element, grid and card spacing. Shadows derive from the global intensity. Grid columns use the configured count as an upper limit and reduce when card padding, heading size or available width require it; tablet/mobile limits apply at 800px and 540px. Narrow homepage sections use stacked compositions and readable service rows even on a wide monitor. Width limits protect readable text and usable mobile content rather than forcing desktop dimensions onto phones.

## Typography and fonts

DM Sans, DM Serif Display and Noto Sans Ethiopic are hosted under `public/fonts`, with source attribution in `ASSET_LICENSES.md`. System Humanist and Georgia are optional local system stacks. Every stack includes Ethiopic fallback. Amharic headings use Noto Sans Ethiopic with appropriate line height and neutral tracking. Display-serif families have their native weight availability; the browser may synthesize a requested heavier weight.

Default body and paragraphs reach 18px on desktop and stay above 16px on phones. Inputs default to 17px and cannot be configured below 16px. Supporting text defaults to 14px. Headings are fluid; the configured H1/H2 numbers are desktop maxima. Text width is measured in characters. Body, heading and navigation families are separate semantic roles; there are no duplicate primary/secondary family switches with ambiguous precedence.

## Color roles

Light and dark are independent, deliberately composed palettes. `mode` accepts `light`, `dark` or `system`. System mode uses a CSS media query and needs no startup JavaScript. Colors are validated in both palettes before publication, even if only one mode is currently active.

Text combinations must meet 4.5:1 and input borders 3:1. Warnings identify the foreground, surface and measured ratio; unsafe drafts remain previewable but publication is blocked. These checks supplement browser accessibility review; they cannot guarantee contrast inside arbitrary uploaded images or CMS-authored rich content.

| Token in `colors.light` / `colors.dark` | Light default | Dark default |
| --- | --- | --- |
| `primary` | `#176d67` | `#92d5c4` |
| `secondary` | `#153d3a` | `#152e2b` |
| `accent` | `#dfb77b` | `#e7be87` |
| `background` | `#fcfbf8` | `#101d1b` |
| `surface` | `#eef3ed` | `#192b27` |
| `card` | `#ffffff` | `#20342f` |
| `heading` | `#183d36` | `#f1f5ef` |
| `text` | `#304a40` | `#dde7df` |
| `muted` | `#53675b` | `#b4c6bb` |
| `border` | `#c7d3c9` | `#4e685f` |
| `link` | `#176d67` | `#a4dfcf` |
| `button` | `#176d67` | `#92d5c4` |
| `button_text` | `#ffffff` | `#102d26` |
| `button_hover` | `#124c47` | `#b5e8d9` |
| `nav_background` | `#fcfbf8` | `#152622` |
| `nav_text` | `#334f43` | `#e1ebe3` |
| `sticky_background` | `#fcfbf8` | `#152622` |
| `footer_background` | `#153d3a` | `#0c1715` |
| `footer_text` | `#d7e5da` | `#d2e2d7` |
| `footer_link` | `#e2ece1` | `#d8e9dc` |
| `input_background` | `#ffffff` | `#14241f` |
| `input_border` | `#6e8879` | `#77978a` |
| `success` | `#27653b` | `#9ad5ae` |
| `warning` | `#775208` | `#f2cd89` |
| `error` | `#98392b` | `#ffb6ac` |
| `info` | `#235d85` | `#a5cee9` |

Primary is used for brand accents; link and button roles remain independently configurable. Secondary colors service and clinic-value icon tiles independently of the footer. The derived `--color-on-secondary` chooses whichever of black/white has higher contrast, keeping those icons readable for any valid secondary color in either palette. Secondary also seeds the initial footer color. Surface and card are distinct. Footer/nav text use their own roles. Success appears in confirmations, warning in the request notice, error in validation, and information in treatment guidance. Accent is decorative and does not carry essential text.

## Complete global token catalog

The tables below are generated from the authoritative registry. Select options are fixed; numeric ranges are enforced by the server. Pixel values describe desktop maxima or base dimensions where responsive rules apply.

### Typography

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `typography.body_font` | Body font | `"dm-sans"` | dm-sans, dm-serif, humanist, classic, ethiopic |
| `typography.heading_font` | Heading font | `"dm-serif"` | dm-sans, dm-serif, humanist, classic, ethiopic |
| `typography.nav_font` | Navigation font | `"dm-sans"` | dm-sans, dm-serif, humanist, classic, ethiopic |
| `typography.body_size` | Base font size | `18` | 16–22 px |
| `typography.paragraph_size` | Paragraph size | `18` | 16–24 px |
| `typography.small_size` | Supporting text | `14` | 13–16 px |
| `typography.nav_size` | Navigation size | `16` | 15–19 px |
| `typography.button_size` | Button text | `16` | 15–20 px |
| `typography.label_size` | Form labels | `16` | 15–20 px |
| `typography.input_size` | Input text | `17` | 16–22 px |
| `typography.h1` | H1 maximum size | `80` | 44–100 px |
| `typography.h2` | H2 maximum size | `48` | 32–64 px |
| `typography.h3` | H3 size | `25` | 21–34 px |
| `typography.h4` | H4 size | `21` | 18–28 px |
| `typography.heading_scale` | Heading scale | `1` | 0.85–1.15 × |
| `typography.heading_weight` | Heading weight | `"400"` | 400, 500, 600, 700 |
| `typography.body_weight` | Body weight | `"400"` | 400, 500 |
| `typography.line_height` | Body line height | `1.75` | 1.5–2 × |
| `typography.heading_line` | Heading line height | `1.12` | 1.05–1.5 × |
| `typography.letter_spacing` | Heading letter spacing | `-0.035` | -0.05–0.03 em |
| `typography.text_width` | Reading width | `65` | 48–80 ch |

### Layout

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `layout.density` | Density | `"balanced"` | compact, balanced, spacious |
| `layout.content_width` | Content width | `1240` | 1000–1440 px |
| `layout.wide_width` | Wide width | `1400` | 1200–1600 px |
| `layout.narrow_width` | Narrow width | `820` | 640–960 px |
| `layout.sidebar_width` | Sidebar width | `320` | 260–380 px |
| `layout.section_space` | Section vertical space | `96` | 56–140 px |
| `layout.page_gutter` | Desktop horizontal padding | `48` | 24–80 px |
| `layout.mobile_gutter` | Mobile horizontal padding | `20` | 16–32 px |
| `layout.element_space` | Element spacing | `24` | 16–40 px |
| `layout.grid_gap` | Grid gap | `28` | 16–48 px |
| `layout.radius` | Panel radius | `16` | 0–32 px |
| `layout.border_width` | Border thickness | `1` | 1–2 px |
| `layout.shadow` | Shadow intensity | `12` | 0–24 % |
| `layout.background_style` | Background detail | `"soft"` | plain, soft |

### Buttons

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `buttons.style` | Primary style | `"solid"` | solid, outline |
| `buttons.secondary` | Secondary style | `"outline"` | outline, soft |
| `buttons.radius` | Button radius | `8` | 0–32 px |
| `buttons.height` | Minimum height | `52` | 44–64 px |
| `buttons.padding_x` | Horizontal padding | `24` | 16–36 px |
| `buttons.padding_y` | Vertical padding | `14` | 10–20 px |
| `buttons.weight` | Button weight | `"600"` | 500, 600, 700 |
| `buttons.shadow` | Button shadow | `false` | true / false |
| `buttons.icon_size` | Button icon size | `20` | 16–24 px |
| `buttons.icon_position` | Icon position | `"end"` | start, end |

### Cards

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `cards.style` | Card surface | `"bordered"` | bordered, elevated, flat |
| `cards.radius` | Card radius | `14` | 0–28 px |
| `cards.padding` | Card padding | `30` | 20–44 px |
| `cards.image_radius` | Image radius | `12` | 0–24 px |
| `cards.image_ratio` | Card photograph ratio | `"landscape"` | landscape, square, portrait |
| `cards.columns` | Desktop grid columns | `"3"` | 2, 3, 4 |
| `cards.hover` | Card interaction | `"lift"` | lift, border, none |
| `cards.input_style` | Input style | `"outlined"` | outlined, filled |
| `cards.input_radius` | Input radius | `8` | 0–20 px |

### Header

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `header.style` | Header layout | `"classic"` | classic, centered, compact |
| `header.height` | Header height | `96` | 76–120 px |
| `header.logo_size` | Logo height | `48` | 32–64 px |
| `header.nav_gap` | Navigation gap | `24` | 12–36 px |
| `header.alignment` | Menu alignment | `"center"` | start, center, end |
| `header.sticky` | Sticky header | `true` | true / false |
| `header.shadow` | Header shadow | `false` | true / false |
| `header.mobile_style` | Mobile menu | `"panel"` | panel, sheet |

### Footer

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `footer.style` | Footer layout | `"expanded"` | expanded, minimal, band |
| `footer.columns` | Footer columns | `"4"` | 2, 3, 4 |
| `footer.space` | Footer spacing | `64` | 40–100 px |
| `footer.logo_size` | Footer logo height | `44` | 32–64 px |
| `footer.contact` | Show contact details | `true` | true / false |
| `footer.branches` | Show branch link | `true` | true / false |
| `footer.social_style` | Social link presentation | `"text"` | text, pills |
| `footer.copyright` | Copyright text (blank uses translated default) | `""` | 0–200 characters |

### Motion

| Key | Control | Default | Allowed values / units |
| --- | --- | --- | --- |
| `motion.enabled` | Enable animation | `true` | true / false |
| `motion.intensity` | Motion intensity | `"subtle"` | subtle, expressive |
| `motion.duration` | Transition duration | `220` | 100–400 ms |
| `motion.page_transitions` | Page transitions | `true` | true / false |
| `motion.scroll_reveal` | Scroll reveals | `true` | true / false |
| `motion.hover` | Hover effects | `true` | true / false |

Footer columns apply to the expanded layout; minimal and band layouts use their own compositions, so the editor hides that control for those layouts. Header height and logo controls scale down safely on mobile. Input style uses either the input background or the coordinated surface. Button icons follow the selected size and placement.

## Homepage presentation

Visibility and ordering remain in the existing homepage records. Presentation is under `sections.<key>`. Background can inherit its section's designed default or use paper, surface or card. Heading alignment, compact/normal/generous spacing and content/wide/narrow width are shared. The trust strip retains its compact scale. Image placement applies only to image-bearing sections. Centered hero composition fixes its own alignment and image position, and the editor hides conflicting controls.

| Section | Layouts | Additional controls |
| --- | --- | --- |
| `hero` | split, centered, immersive | image: left, right; cta: inline, stacked |
| `trust` | default | Shared presentation controls |
| `services` | default, rows | Shared presentation controls |
| `about` | default, editorial | image: left, right |
| `why` | default | Shared presentation controls |
| `doctors` | default, portrait | Shared presentation controls |
| `technology` | default | image: left, right |
| `testimonials` | default, quotes | Shared presentation controls |
| `appointment` | default, centered | cta: inline, stacked |
| `faq` | default | Shared presentation controls |
| `articles` | default | Shared presentation controls |
| `branches` | default | image: left, right |
| `before_after` | default | Shared presentation controls |
| `statistics` | default | Shared presentation controls |
| `announcements` | default | Shared presentation controls |

Hero split pairs copy and image; centered stacks a broad photograph below centered copy; immersive uses the footer palette and a distinct image composition. Services offer cards or editorial rows. Team portrait mode changes image proportions and caption alignment. Testimonials offer a lead quotation or a multi-quote composition. About editorial changes column balance and photograph treatment. Appointment centered removes the decorative illustration and centers the call to action.

## Presets

| Preset | Composition |
| --- | --- |
| Modern Clinical | Blue, sans-serif headings, crisp panels and balanced spacing |
| Premium Dental | Green, editorial serif, warm accents and a split hero |
| Minimal Contemporary | Sharp corners, spacious density, flat panels, centered hero, service rows and a band footer |
| Warm Family | Terracotta, classic typography and softer button/panel geometry |
| Advanced Technology | Indigo, elevated cards and an immersive hero |
| Elegant Medical | Violet, classic typography, centered masthead and editorial introduction |

Preset selection replaces visual tokens in the draft and preserves clinic content, homepage visibility and order. Individual settings can be changed afterward.

## Motion and progressive enhancement

The base duration controls transitions; reveal uses twice that duration. Intensity changes hover displacement. CSS handles button, card, image, focus, dialog and menu feedback. IntersectionObserver triggers short, visible-to-visible reveals; essential content is never hidden awaiting JavaScript. CSS cross-document View Transitions progressively enhance normal links. Unsupported browsers retain native navigation, SEO, history and scroll behavior.

The master switch, hover switch, reveal switch and page-transition switch all affect their actual consumers. OS reduced motion overrides nonessential animation and hover displacement while preserving static layout transforms. There are no animation dependencies, parallax loops or artificial navigation delays. Booking submission announces a sending state, blocks duplicates, and restores the button on back/forward return.

## Private previews and publication

The authenticated preview endpoint validates the full draft and stores at most four short-lived preview tokens in the administrator's session. Tokens expire after one hour and are tied to that administrator. A GET with `appearance_preview` renders the real public templates using request-only overrides; the middleware clears overrides after rendering. Preview responses are private/no-store/noindex and permit only same-origin framing. Public responses retain frame denial.

POST requests with a preview token are rejected, and booking/contact submit buttons are disabled in preview HTML. Preview navigation and search retain the token. The editor refreshes after a short debounce, keeps the inspected page and scroll position on setting changes, and offers desktop/tablet/mobile frame widths.

Publication validates the complete configuration, checks contrast, locks the revision row, checks the submitted revision, and atomically saves appearance plus homepage order/visibility and audit activity. The separate homepage editor also advances this revision. A conflict preserves the draft and asks the administrator to load current published settings before deciding how to proceed.

## Verification and limits

Automated coverage is in `tests/Feature/AppearanceTest.php` and `tests/browser/appearance.spec.ts`, alongside the existing patient/CMS tests. Browser screenshots are stored under `.tools/previews/phase2-*`. See `FRONTEND_AUDIT.md` and `CHANGELOG.md` for actual run results. Chromium automation and axe supplement visual inspection; they are not a WCAG certification or a substitute for real assistive-technology and device testing.
