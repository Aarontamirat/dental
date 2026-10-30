# Assets and third-party licenses

The demo uses locally stored photographs from Unsplash. Exact downloaded asset URLs and intended uses are recorded in `public/images/SOURCES.json`; those URLs are the reliable identifiers. They depict stock subjects/facilities, not the installed clinic or its clinicians. Replace them with clinic-owned or appropriately licensed, approved photographs before a customer launch.

480/960-pixel variants are resized from these same images using `tools/optimize-demo-images.php`. They share the original source/permission requirements.

Unsplash's license is described at <https://unsplash.com/license>. Review current terms and any additional permissions for recognizable people, property, trademarks, or clinical use. No endorsement, qualification, patient outcome, or actual clinic identity is asserted by the demonstration. Attribution/photographer identity should be established from the source before commercial use where required; asset records do not assert unknown authorship.

DM Sans, DM Serif Display, and Noto Sans Ethiopic are self-hosted Google Fonts under the SIL Open Font License. The original notices/licenses are included in `public/fonts/licenses`. Upstream sources:

- <https://github.com/google/fonts/tree/main/ofl/dmsans>
- <https://github.com/google/fonts/tree/main/ofl/dmserifdisplay>
- <https://github.com/google/fonts/tree/main/ofl/notosansethiopic>

Runtime font CSS references WOFF2 subsets, including Ethiopic. Legacy downloaded TTF copies are unused and excluded from releases. Preserve license notices when redistributing fonts. No browser request to Google Fonts or Unsplash is required for normal page rendering.

Lucide icons are provided through the installed Lucide package; public SVG icons are small project-rendered shapes. PHP/JavaScript dependency license declarations/notices remain with their respective packages. Production vendor includes its upstream licenses. Inspect the lockfiles and installed packages when preparing any commercial distribution; this document does not assign third-party ownership to the product.
