# Implementation plan

## Phase 2 — frontend appearance and experience

1. Audit all templates/components/settings and document findings in FRONTEND_AUDIT.md.
2. Add a validated appearance registry and coordinated presets; merge legacy values safely without changing existing database rows.
3. Render centralized responsive typography, palettes, spacing, components and motion tokens from request-scoped settings.
4. Build categorized Appearance editing with private unsaved previews, viewport/page controls, draft recovery, discard/reset and optimistic publication.
5. Refine every public page, navigation, footer, cards and forms; implement real homepage and header/footer compositions.
6. Verify persistence, preview authorization/isolation, contrast, modes, responsive behavior, workflows and reduced motion with backend and browser tests.
7. Publish accurate design/operator documentation, screenshots and a refreshed shared-hosting release artifact.

Status: implementation complete; final local verification and reporting are recorded in FRONTEND_AUDIT.md. Existing content/database and Phase 1 modules are preserved.

One independently deployed clinic per installation. No SaaS tenancy or required cloud services.

1. Foundation: Laravel 13, PHP 8.3+, MySQL 8.0+/MariaDB 10.6+, SQLite for local tests; session authentication, four roles, migrations, clinic configuration.
2. Public experience: server-rendered, accessible English/Amharic pages, premium responsive design, local photographs, services, professionals, branches, education, gallery, custom pages, SEO.
3. Administration: React/TypeScript CMS, validated CRUD, media, homepage ordering, branding, navigation, actual database dashboard metrics, users and activity log.
4. Patient workflows: database-backed appointment requests and contact messages; consent, spam/rate controls, reference numbers, auditable status transitions, optional email notifications.
5. Hardening: sanitized rich content, safe image processing, role enforcement, retention, backups, feature flags and localization.
6. Verification and handover: automated backend and browser tests, lint/type checks, production build, dependency audit, hosting and clinic operator documentation.

## Progress

- Initial Phase 1 workspace was empty; Phase 2 extends the resulting working application.
- Hosting constraints evaluated; Laravel monolith selected.
- Local runtime available: PHP 8.5, Composer 2, Node 24. Local PHP needs SQLite, GD and ZIP extensions enabled through a project-specific configuration.
- Phases 1–6 implemented: foundation, public pages, administration, patient workflows, localization, and clinic customization.
- Phase 7 implemented: authorization, rich-content sanitization, upload processing, privacy/consent, throttling, CSP, SEO, and responsive accessibility corrections.
- Phase 8 implemented: isolated SQLite/MySQL suites, real-browser journeys, production asset/dependency build, release packaging, documentation, and deployment smoke verification. Executed results and their limits are recorded in TESTING.md.

## Delivered modules

- Services/categories, professional categories/branches, branches/hours, articles/categories/tags, gallery/consent, testimonials/approval, FAQs, custom pages/offers/announcements, navigation.
- Branding, images, colors/typography/presets, reviewed English/Amharic text, homepage ordering/visibility, optional modules, trust messages, verified statistics, SEO and contact preferences.
- Secure individual staff accounts, four roles, real dashboard metrics, appointment/contact inboxes, auditable status updates, concurrent-edit protection, CSV export, optional email, and private recovery/setup commands.
- Local WebP media, private originals, URL-preserving replacement, reference-aware deletion, deployment readiness and retention preview commands.
- Nine required documents plus asset notices; production release builder and separate-public-root smoke tool.

## Deployment acceptance remains per customer

The application is completed and locally verified. Each customer must supply approved clinic assets/content/credentials, review translations and privacy text, verify actual hosting PHP/Apache/SSL and SMTP delivery, configure backups, and complete the launch checklist. No customer hosting account was deployed in this workspace. Real-time scheduling, SMS, EMR, payments, SaaS tenancy, and Ethiopian-calendar conversion are outside the initial product scope.


## Phase 2 completion — 30 October 2026

1. Audit every public template, shared component, token consumer, appearance endpoint, and admin flow.
2. Complete token wiring and correct visual variants, motion preferences, readable responsive layouts, and footer identity.
3. Complete draft recovery, private preview, revision-safe publication, and centralized appearance navigation.
4. Refine patient forms, accessible errors, loading feedback, service discovery, and page-specific compositions.
5. Add meaningful backend and browser coverage for appearance persistence, privacy, configuration validation, presets, responsive rendering, and reduced motion.
6. Run production build, type checks, lint, affected/full PHP suites, browser tests and axe checks; inspect screenshots and correct findings.
7. Document the actual token catalog, admin workflow, verification evidence, and remaining deployment considerations.

The requirements recheck also completes secondary-color consumers, typed imported settings, density-aware card columns, narrow section compositions, text-button token consistency, card motion opt-out, applicable footer controls and inclusion of appearance documentation in shared-hosting releases. No application dependency or database schema changes are needed.
