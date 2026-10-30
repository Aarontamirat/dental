# Clinic administration guide

## Sign in and permissions

Visit your clinic domain followed by `/login`. Use your individual account, then open `/admin`. Sign out using the icon beside your account at the bottom of the navigation. On mobile, Open navigation reveals the menu. Escape closes dialogs and navigation.

Passwords require at least 12 characters, mixed case, a number, and a symbol. Forgot password sends recovery email when email is configured. Otherwise contact the administrator or hosting operator for private account recovery.

| Role | Access |
| --- | --- |
| Clinic administrator | Content, media, branding, patient requests, users, activity |
| Super administrator | Same modules, plus management of privileged administrator accounts |
| Content editor | Homepage, website content, media; no patient inquiries or users |
| Appointment manager | Appointment requests and contact messages; no content/branding/users |

Clinic administrators cannot create or modify super administrators. Inactive accounts cannot access the workspace. Keep one active administrator, assign the smallest useful role, and deactivate former employees. Changes are logged without patient message text.

## Dashboard

Overview shows actual database counts and recent requests. Counts depend on your permissions. Pending requests need follow-up; a submitted request is not a confirmed visit. Use the quick actions to open the relevant module.

## Website content

Choose a module, then Add item. Provide a title and a unique lowercase URL slug using hyphens. Add an introduction, rich content, imagery, and SEO fields. Use the formatting toolbar for headings, emphasis, and lists. Unsafe HTML is removed on the server.

Save as Draft while preparing content. Published content becomes visible when its module is enabled. Featured items populate supported homepage collections; Display order uses smaller numbers first. Search, publication filtering, and pagination help locate records. Changing a slug changes the public URL; update navigation and incoming links when doing so.

Use the English/Amharic tabs to enter reviewed translations. Empty translated text falls back to primary English content. Clinical guidance and qualifications require clinic review before publication. The administration workspace uses English labels; patient navigation and forms support both languages.

Editing an item saves its relationships as well as its content. Deletion requires confirmation and is permanent. Deleted branch/service/professional relationships are cleared from historical requests; remaining request details and history persist. Keep business records or backups when a historic relationship must be retained.

## Services, team, and branches

Create service categories with Category type set to Service, then assign services. Services support benefits, procedure steps, treatment FAQs, related services, optional ETB prices, and branch availability. Link gallery items to services for a treatment gallery. Publish only treatments actually offered, with individual suitability and outcomes explained responsibly.

Doctors & staff supports dentists, specialists, hygienists, and other staff. Add approved portraits, titles, biography, verified qualifications/experience, languages, affiliations, and professional links. Assign branches.

Branches contain address, city, region, locality/sub-city, phone, email, optional branch notification email, maps link, photo, opening hours, services, and professionals. Enter each weekday once; open days need a closing time after opening. Mark closed days explicitly. A missing weekday uses the global appointment hours, so enter all seven days when a branch has a regular weekly schedule.

## Appointment requests

Open Appointment requests to search, filter by status/branch/date, and view details. Contact the patient to agree the visit. Then choose Pending, Confirmed, Rescheduled, Completed, Cancelled, or Rejected as appropriate. Rescheduling requires a future date/time and an explanation. Internal notes stay private; no diagnosis or treatment history should be entered.

Status changes record the administrator and history. Concurrent edits are rejected if another staff member saved first; reload the request before editing again. Configured patient email receives the status/date/time, while internal notes remain private. A failed email does not discard the request. Follow up by phone when delivery fails or the patient has no email.

CSV export includes the active filters and patient contact information. Save it only on an authorized device; the export protects spreadsheet formulas but is still confidential.

## Contact messages

Open Contact messages, review the inquiry, and reply using the patient's contact details. Mark New, Read, or Resolved and add internal notes. The system alerts the clinic when configured, but does not send a contact reply from the workspace. Use the clinic's email or telephone.

## Media library and gallery

Upload JPEG, PNG, or WebP up to 5 MB, 6000 pixels per dimension, and 20 megapixels. Name the image, describe it for screen readers, and set a category. Images are resized to at most 1600 pixels on their longer side and converted to WebP. Choose images from the library in content/branding forms.

Edit / replace keeps the same image URL, updating every use. Referenced images cannot be deleted until their uses are removed. Public image URLs may remain cached for up to an hour after replacement. Originals stay private, but optimized uploads are publicly retrievable by URL. Upload only material approved for website use; do not upload patient documents or confidential clinical photographs.

Gallery categories organize clinic, equipment, team, treatment, and before/after images. Team/treatment/before-after publication requires verified consent and a private reference to the consent record. Before/after needs both images. Keep the signed consent record securely at the clinic, never in a public image caption. Testimonials require consent and approval; optional ratings must reflect actual feedback. The demo testimonial is labelled as a sample.

## Articles and pages

Create Article categories and tags, supply an author if appropriate, add clinically reviewed content, and set a publication date. Future articles stay hidden until their date. Related articles share the category.

Pages & announcements supports ordinary pages, announcements, and offers. Create `privacy`, `terms`, `cookies`, and `payment-information` pages for the standard footer/form links. A page with slug `about` supplies the clinic's about content. Use Navigation to add custom header/footer links with a local path or HTTPS destination. Offer content should state conditions and dates accurately.

## Branding and homepage

Branding & settings contains clinic identity, logo/favicon, colors, fonts, header/footer/hero presets, images, contact details, social links, language, SEO, modules, requests, notifications, and retention. Dark primary colors/light backgrounds preserve readable contrast; invalid colors are rejected. SMTP credentials are configured privately by the hosting operator.

Trust messages support an icon, title, and description. Optional statistics appear only when Verified is checked and the Statistics homepage section is visible. Check every figure with the clinic; there are no fabricated defaults.

Homepage sections lets you edit headings/introductions, reviewed Amharic text, visibility, and ordering using the arrows. Hero/about edits also update the corresponding branding settings. Save homepage to publish the arrangement. Sections backed by records use those records' content/images; the technology section uses the configured clinic image/gallery. Supported presets provide structured customization, not arbitrary drag-and-drop page building.

Disable demonstration mode only after replacing, approving, and clearing the Demonstration content flag on every record you intend to publish. Unreplaced demo records become hidden, including sample branches and reviews. Replace the default branding and bundled photos as well. Preview the public site in both languages after every substantial change.
