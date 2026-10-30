# Changelog

## 2026-10-30 — Phase 2 requirements verification

- Verified the existing appearance studio, six coordinated presets, public redesign, private previews, publishing, CMS and appointment workflows against the master prompt.
- Connected secondary palette colors to visible icon tiles with automatically contrasting foregrounds.
- Adapted card columns to content width, configured padding and typography; narrow homepage sections now use appropriate stacked compositions.
- Made card interaction opt-out suppress photo zoom, and applied button weight/icon settings to text CTAs and card links.
- Normalized validated imported/stored appearance values so boolean and numeric representations render consistently; recovery/import now use the server-validated draft.
- Hid inapplicable controls for minimal footers while preserving their values.
- Added targeted PHP/browser regression coverage and refreshed the audit, plan, design system and operator guide. Shared-hosting archives now include the appearance documentation.
- Verified 78 PHP tests (552 assertions), all 24 browser tests, TypeScript, ESLint, production build and an isolated shared-hosting release smoke test. See FRONTEND_AUDIT.md for the run details and limits.

## 2026-10-30 — Phase 2 frontend and appearance system

Completed and hardened the inherited Phase 2 design system rather than replacing the Laravel/Blade public application or React administration.

### Appearance and administration

- Centralized 76 global appearance controls, 26 color roles per light/dark palette and presentation controls for 15 existing homepage sections.
- Six coordinated presets with real typography, geometry, density, header/footer and section composition differences; local multilingual font stacks and comfortable responsive text sizes.
- Categorized Appearance studio with real authenticated page previews, desktop/tablet/mobile frames, color resets, contrast guidance, typography samples and JSON import/export.
- Tab-scoped draft recovery, server validation of recovered/imported drafts, discard/default restoration, private preview expiry, disabled patient forms in previews and revision-protected atomic publication.
- Homepage changes from the separate CMS editor now invalidate stale appearance drafts. Preview state is cleared after rendering, and legacy visual inputs no longer compete with Appearance studio.

### Public experience

- Refined shared typography, buttons, cards, navigation, footer, forms and responsive layouts across all public templates.
- Distinct homepage compositions, service rows, team portraits, quote layouts, editorial introduction, article reading layouts, gallery rhythm, branch layouts and FAQ assistance.
- Booking fields grouped into patient details, visit preferences and preferred time. Field-linked server errors preserve entered values; closed-day guidance uses the chosen branch's actual hours. Sending feedback prevents duplicate submissions and resets on browser return.
- Uploaded footer logos, corrected technology image placement, meaningful information/warning colors, complete section alignment/spacing behavior and improved admin empty-state contrast.
- Lightweight scroll/menu/dialog/hover effects and native page transitions. Motion preferences now suppress their actual effects, including reduced-motion hover displacement, while retaining static layout positioning.

### Engineering and validation

- Existing database, authentication, CMS, consent, patient requests and shared-hosting architecture retained. No new dependencies or database migrations.
- Added regression coverage for appearance authorization, persistence, legacy fallback, invalid configuration, contrast, private preview isolation/expiry, stale revisions, reset and patient error associations.
- Added browser coverage for editor recovery/publish/discard/reset/import/export, private previews, six preset palettes, responsive public pages, motion preferences, mobile sheet navigation and no-JavaScript information/forms.
- Added complete token documentation, appearance workflow guidance, audit findings and verification records. See [FRONTEND_AUDIT.md](FRONTEND_AUDIT.md) for final executed checks and limitations.
