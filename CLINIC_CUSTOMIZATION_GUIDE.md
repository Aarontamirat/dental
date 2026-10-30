# Clinic customization and handover

One maintained codebase supports independent clinic installations. Every customer receives its own environment/key, database, media, staff accounts, and content. Do not copy patient databases or credentials between clinics. Branding changes do not require rebuilding assets.

## Collect approved material

Ask the clinic for its legal/display name, logo, preferred colors, contact channels, branch addresses/maps/hours, actual services, approved staff portraits/biographies/credentials, clinic photographs, language preferences, and privacy/retention wording. Confirm who approves clinical articles and translations. Use only genuine consented testimonials and results; leave these modules empty when material is unavailable.

## Configure a new customer

1. Deploy a clean release using [SHARED_HOSTING_DEPLOYMENT.md](SHARED_HOSTING_DEPLOYMENT.md). Create the dedicated database and first administrator.
2. In Branding & settings enter the name/tagline, logo/favicon, colors, typography, header/footer/hero preset, hero text/photo, and about text/photo. The primary color must support readable white text; backgrounds must stay light.
3. Enter public phone/email/address, WhatsApp, social accounts, SEO defaults, default language, enabled languages, and request/notification settings. Keep emergency contact blank unless the clinic actually provides that service.
4. Add branches with full address/locality, seven-day hours, available services, assigned professionals, photo, and optional branch notification email. Select the default branch after it exists.
5. Add actual service categories/services and real doctors/staff. Verify titles, affiliations, experience, and treatment descriptions. Prices are optional; show them only if approved. The initial currency choice is ETB.
6. Create the `about`, `privacy`, `terms`, `cookies`, and `payment-information` pages. Publish approved legal/contact content. The cookie notice should accurately describe essential sessions; the product includes no analytics/tracking by default.
7. Upload approved gallery material, articles, FAQs, testimonials, announcements, and offers as desired. Use drafts until reviewed. Assign gallery categories and service/branch relationships.
8. Add reviewed Amharic translations. The UI supports English/Amharic; clinical content is never automatically translated. English fills missing translated text.
9. Arrange Homepage sections and select enabled modules. Record-backed sections populate from published/featured records. Enter trust messages and optional verified statistics; enable Statistics only when useful figures exist.
10. Configure Navigation with working local paths or HTTPS destinations. Disable irrelevant links/modules and preview both desktop/mobile layouts.
11. Replace all default marketing text and bundled demo photos. Replace/edit demo records, then clear their Demonstration content flags only after approval. Disable demonstration mode to hide all remaining sample records.
12. Complete production readiness checks, test patient journeys, email delivery, backups, and staff training before launch.

Default/demo content is not evidence of clinical qualifications, treatment availability, patient feedback, or results. Hiding demo records does not automatically replace the default brand text/photos; review both database content and branding settings.

## Supported presets and scope

The CMS offers editorial/modern typography, classic/centered headers, expanded/minimal footers, and split/immersive heroes. Homepage sections have fixed, designed templates with configurable headings, introductions, visibility, and ordering. Images/content come from branding or the relevant content records. This keeps new clinics consistent and manageable without creating an arbitrary visual page builder.

English and Amharic are available now. Another language can be added by introducing its UI translation file, extending the allowed-language/translation validation and editor tabs, and providing an appropriate local font. The same relational/JSON translation structure supports this extension; deployment infrastructure does not change. Ethiopian-calendar display, SMS providers, real availability scheduling, additional currencies, and uncommon layouts require deliberate extensions. They are not exposed as inactive product buttons.

For additional structured sections, add a template in `resources/views/public/sections`, register its key in the foundation seeder, and expose the necessary validated settings/content fields. For content entities update migrations, models, `ContentRegistry`, public routes/templates, and authorization/tests. Keep a shared product branch; avoid a separate copied source tree for each clinic as the maintenance strategy.

## Customer handover

- Give the owner the public URL and `/login`, an individual administrator account through a private channel, and [ADMIN_USER_GUIDE.md](ADMIN_USER_GUIDE.md).
- Demonstrate name/logo/colors, opening hours, service/team editing, article publication, branch assignments, SEO, and homepage visibility.
- Submit a consented test appointment, show the reference/Pending state, and demonstrate confirmation, rescheduling, notes, email status, and CSV privacy.
- Create individual editor/appointment-manager accounts and explain their boundaries. Record the approved support/recovery contact.
- Agree who checks requests, manages mail, approves translations, maintains backups, reviews retention, and installs security updates.
- Record domain/hosting ownership, PHP/database versions, release identity, backup schedule/location, and the last successful restoration drill. Keep credentials in an appropriate password manager.

Use a clean test request and agree its removal/retention separately. Avoid clinical records during demonstrations.
