# Appearance studio guide

Open **Administration → Appearance studio** as a clinic administrator or super administrator. Content editors and appointment managers cannot publish appearance changes.

## Create your clinic's visual identity

1. Start in **Theme presets**. Select Modern Clinical, Premium Dental, Minimal Contemporary, Warm Family, Advanced Technology or Elegant Medical. The preview updates without changing the public website.
2. In **Overview**, choose light, dark or the visitor's system preference. Edit the light and dark palettes separately under **Colors**.
3. Adjust typography, layout, buttons, cards and inputs, navigation, footer and animation in their categories. Use the device buttons above the preview to inspect desktop, tablet and mobile compositions.
4. Select a preview page or follow a link inside the preview. Changes refresh the real page with your draft, including its content. The preview keeps your page and scroll position when design settings change.
5. Review any contrast warnings. They identify colors that need adjustment and their measured contrast. These warnings block publication; you can still inspect a draft. Each color has a reset button that restores that role from the selected preset.
6. Select **Publish changes** when ready. A published revision appears beside the title, and visitors then receive the saved configuration.

The preview updates automatically after a short pause in editing. There is no separate Apply step. Only Publish changes saves the visual design to the public website. Contact and booking forms are disabled in the preview; test patient workflows in a separate website tab.

## What each category controls

| Category | Purpose |
| --- | --- |
| Overview | Light/dark/system mode and workflow overview |
| Theme presets | Coordinated color, typography, shape and layout starting points |
| Colors | 26 roles in each palette, pickers, hex values, resets and contrast warnings |
| Typography | Body/heading/navigation families, readable sizes, weights, heading scale, line heights, tracking and reading width; English/Amharic sample |
| Layout & spacing | Content/wide/narrow/sidebar widths, desktop/mobile gutters, density, spacing, gaps, borders, radii and shadow intensity |
| Buttons | Primary/secondary appearance, radius, size, padding, weight, shadow and icons |
| Cards & inputs | Card surfaces, corners, padding, photos, desktop grid density, hover behavior and form fields |
| Navigation | Header layout, height, logo size, navigation spacing/alignment, sticky behavior, shadow and mobile panel/sheet |
| Footer | Expanded/minimal/band composition, columns where relevant, logo size, spacing, contact/branch links, social presentation and copyright |
| Homepage | Existing section visibility and order plus implemented layouts, backgrounds, alignment, widths, spacing, image position and CTA arrangement |
| Animations | Master switch, subtle/expressive intensity, speed, page transitions, scroll reveals and hover effects |
| Backup & restore | Export/import portable JSON and restore the default design into a draft |

The complete keys, defaults, ranges, allowed values and frontend behavior are documented in [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md). Responsive controls live with their corresponding category rather than in a second conflicting set of mobile settings. Desktop dimensions adapt to smaller screens. OS reduced motion always takes precedence.

## Homepage and content

Expand a section under Homepage to show/hide it, move it up/down or adjust its presentation. The real preview reflects these changes. A section also needs published content and an enabled website module before it can appear. Showing an empty section does not invent content.

Use **Homepage sections** to edit section text and translations, and the corresponding CMS pages to edit services, people, articles, gallery items and branches. Use **Branding & settings** for the clinic name, uploaded logo, contact details, languages, hero/clinic photos, appointment behavior and other operational settings. Appearance studio owns visual styling. Existing clinic content and data are retained when switching themes.

The centered hero and compact footer layouts intentionally have fewer applicable controls. The editor hides conflicting image/alignment controls for a centered hero, the column selector for footers with a fixed composition, and contact/branch/social controls when the minimal footer omits those sections. Existing values are retained when switching layouts.

The secondary brand color controls service and clinic-value icon tiles; their foreground contrast adjusts automatically. Button weight and icon settings also apply to text CTAs and service-card links. Selecting **None** for card interaction disables card movement and photograph zoom. Desktop column counts are an upper limit: cards reduce their columns when large text or narrow content needs more room.

## Drafts, reset and conflicts

- **Discard** returns to the loaded published appearance and section order. The confirmation lets you keep editing.
- **Restore design defaults** loads Premium Dental into the draft. It preserves section visibility, order and clinic content. Inspect it before publishing.
- A draft is saved in this browser tab's session storage, scoped to the site and administrator. Returning to the editor or reloading the tab offers **Recover draft**. Recovered drafts are validated before use. Closing the browser tab can remove this temporary recovery data; use an exported backup for long-term storage.
- Leaving the page with edits triggers the browser's unsaved-change warning where supported. If browser storage is unavailable, the editor shows a warning instead of promising recovery.
- If another editor publishes appearance or updates homepage sections, publication returns a conflict. **Load current published settings** refreshes the revision while retaining your draft. Review your draft against the current design or discard it before publishing.
- Preview tokens expire after one hour. **Refresh preview** creates a new one. A copied preview URL is not a public sharing link and does not grant another person access.

## Portable backups

Export creates `clinic-appearance.json` containing the current design and homepage order/visibility. It excludes patient information, email credentials and private clinic settings. Import accepts JSON below 100 KB, validates and normalizes it on the server, and loads the validated values into a draft. Numeric representations of checkboxes are converted to true/false. Importing does not publish. Keep a backup before a substantial redesign or when moving a design between installations.

## Hosting and checks

Published settings live in the existing database and survive application restarts and deployments that preserve that database. Include the database and uploaded media in your normal backups. No additional service, queue worker or production Node process is needed for appearance changes.

After deploying code changes, build frontend assets with `npm run build` before uploading the release. Ordinary appearance edits do not need a build. Check the public site in light/dark mode, on a phone, using keyboard navigation, and with representative clinic content before launch. The supplied Amharic copy and all clinical/legal content should receive the clinic's normal language and content review.
