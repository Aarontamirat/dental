# Frontend audit — Phase 2

Audit date: 30 October 2026. Scope: all public templates, shared components, authentication screens, CMS settings, public JavaScript, CSS, persistence, security headers, and existing browser coverage.

| Area | Finding before Phase 2 | Priority / response |
| --- | --- | --- |
| Readability | Body 15px; navigation 10px; supporting text frequently 8–12px; desktop inputs 13px. Mobile overrides do not fix the majority of labels. | Critical: semantic fluid typography with safe minimum sizes. |
| Customization | Four injected color variables; hardcoded surfaces, text, shadows, spacing and font sizes. Legacy presets change only a few properties. | Critical: one typed appearance registry, database persistence, token rendering, complete coordinated presets. |
| Administration | Branding/settings is an endless schema form. No unsaved preview, change recovery, contrast guidance for individual surfaces, or concurrent publication protection. | Critical: categorized editor, authenticated private preview, local draft recovery, versioned publication and discard/reset. |
| Navigation | Desktop sizing hides the real space needed for readable labels. Mobile menu abruptly toggles; no outside-click/focus-return handling. | High: responsive header layouts, accessible transitions, active links and visible touch targets. |
| Homepage | Good editorial starting point, but nearly every section uses similar spacing; hero variants mainly recolor. Testimonials show only one approved quote. | High: actual hero compositions, services/team/quote variants, per-section presentation tokens. |
| Services | Search/category discovery exists. Cards have tiny excerpts and weak hover/focus equivalence. | High: readable cards, useful search feedback, consistent linked interaction and CTA treatment. |
| Professionals | Photograph-led cards are appropriate, but detail pages use the same image/content/sidebar layout as treatment information. | High: profile-specific composition and readable qualification panels. |
| Branches | Listing has useful hours/details but repeats identical rows; dense small hours text. | High: alternating location compositions and prominent practical information. |
| Articles | Listing mirrors other grids; article detail has a booking sidebar competing with reading. | High: featured lead article, constrained reading width and supporting article metadata. |
| Appointment / contact | Working server submission, consent, errors, success and spam protection. Form text is small and appointment groups are visually undifferentiated. | Critical: comfortable input/label sizes, clear grouped steps, retained one-page/no-JS workflow. |
| Gallery / testimonials | Working native dialog/comparison and approval/consent enforcement. Uniform grids, small captions. | Medium: editorial gallery rhythm, dialog keyboard handling, readable quote panels. |
| FAQ | Native details is robust without JS; narrow repetitive list lacks surrounding reassurance. | Medium: composed question/help layout with clear expanded state. |
| About / custom / legal | About already has a split introduction. Legal content inherits a generic booking sidebar. | High: distinct reading layout for policy pages, stronger clinic narrative. |
| Motion | Scattered transition durations; hover-only image effects; no centralized reveal or navigation transitions. | Medium: small CSS/IntersectionObserver system with reduced-motion enforcement and visible content without JS. |
| Dark mode | Unsupported; legacy validation assumes white text and a light background. | High: separately designed light/dark palettes and system preference support. |
| Data / hosting | Request-scoped settings use one database read; PHP/Blade architecture works on shared hosting. No design draft storage. CSP forbids frames. | Preserve storage and hosting; permit same-origin preview frames only under authorized preview conditions. |

Existing asset provenance, multilingual local fonts, CMS authorization, patient workflows and SEO remain valuable foundations. Phase 2 verification must cover all public routes at representative widths, both modes, settings persistence/privacy, preset/layout variants, reduced motion and actual rendered contrast.

## Completion audit of the inherited Phase 2 work

The workspace arrived with an incomplete Phase 2 implementation. Rechecked all public templates, the appearance registry, admin editor, legacy settings, preview middleware, and existing tests before extending them. The inherited design system is retained.

Confirmed defects: placeholder appearance coverage; draft-recovery lint errors; duplicate legacy visual controls; no revision protection against the separate homepage editor; preview state not cleared after rendering; unused appointment step styling; missing field error associations; a footer that ignored uploaded logos; unimplemented technology image positioning; root view transitions ignoring the disable switch; reduced-motion rules changing static layout transforms; and insufficient contrast in an admin empty state. Initial browser checks passed public rendering and mobile navigation but failed that admin contrast check.

The installed PHP CLI needs the existing `.tools/php.ini` to enable SQLite for isolated tests. Tests use an in-memory or temporary database; application data is preserved. No dependency changes or schema migration are needed.

## Requirements verification — 30 October 2026

The inherited implementation passes 76 PHP tests (531 assertions), TypeScript, lint, production compilation, and 22 Playwright journeys. The browser coverage includes 18 public routes/locales at 320, 390, 768, 1024 and 1440px, all six presets in both palettes, private drafts, publication, booking and CMS workflows. Screenshots confirm the public redesign and categorized appearance editor exist.

Additional checks found gaps that the original suite did not cover:

- Secondary brand color was persisted but had no independent visible consumer after appearance publication.
- Selecting no card interaction still zoomed doctor/article photographs on hover.
- Narrow homepage sections combined with four columns, larger typography and spacious padding overflowed cards and retained unsuitable desktop compositions.
- Accepted scalar representations of booleans/numbers were not normalized to the typed configuration required by CSS attributes and editor checkboxes.
- Phase 2 status and verification documentation were stale; the release packager omitted the new appearance guides.

These gaps are corrected in the existing architecture. Text CTAs now also consume button weight/icon settings, and minimal footer layouts hide inapplicable controls without discarding their saved values.

## Final local verification

| Check | Executed result |
| --- | --- |
| PHP suite, isolated SQLite | 78 tests, 552 assertions passed |
| Real-browser coverage | All 24 Playwright tests passed: 23 in the full run, then the studio test passed after correcting an ambiguous test selector |
| Appearance persistence | Preview privacy, authorization/expiry, validated import/recovery, publication, reset/discard, revision conflicts and normalized values verified |
| Responsive rendering | 18 public route/locale combinations at 320, 390, 768, 1024 and 1440px; additional narrow-section/large-text checks passed |
| Themes and accessibility | All six presets in light/dark, system preference, reduced motion, keyboard/mobile navigation and automated WCAG A/AA checks passed on the tested pages |
| Existing workflows | Real-browser booking/confirmation, contact resolution, CMS edits, media and role/CSRF checks passed |
| Static checks and assets | TypeScript, ESLint and production Vite build passed |
| PHP formatting | Pint passed on the three changed PHP files; `--dirty` requires Git metadata, which this workspace does not contain |
| Rendered review | Inspected generated homepage, studio, mobile appointment, service listing and professional-profile screenshots; route/preset screenshots are under `.tools/previews` |
| Shared-hosting release | Built `.tools/releases/dental-clinic-20261030-203023.zip` with all five requested documents; isolated release smoke passed setup, caching, routes, assets and private-file isolation |

The five requested Phase 2 documents are updated. Appearance settings remain in the existing database; no application dependency changes, destructive database operations, production Node server or external font service were introduced. Tests use disposable databases and accounts.

This local recheck uses Chromium and SQLite. It does not claim fresh Lighthouse/Core Web Vitals measurements, Safari/Firefox coverage, manual screen-reader certification or live customer hosting verification. Historical Phase 1 MySQL/Lighthouse results remain labelled as historical in TESTING.md. Newsletter and dropdown-specific controls are omitted because those components are not implemented; responsive controls remain in their relevant categories rather than duplicating them in a separate panel.
