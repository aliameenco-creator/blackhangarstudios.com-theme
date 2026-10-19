# Changelog

## 0.4.0

All remaining Black Hangar pages, designed to match the homepage. The homepage design itself is unchanged apart from the client-requested hero buttons.

- **Appearance → Black Hangar Pages** (new): applies editable designs to the 18 existing pages at their original addresses (The Studio, About Jake Seal, Virtual Tour, Contact, Covid-19, Event Space, Sustainability, Film Festivals, Film Studio, FAQ, Privacy Policy, Terms & Conditions, The History of Lasham, Location, On the Lot, Post Production, Production Services, Videos). Titles, slugs, SEO settings and status stay the same; previous content is backed up and can be restored per page; pages edited after a design was applied are not preselected. Homepage and News are never touched.
- Pages are native blocks (editable in Pages) using the homepage design language: photo heroes, image/text splits, cards, stats, logo grids, festival calendar, EV charger accordions, video rows and a shared call to action, with scroll reveals, counters and hover motion (reduced-motion respected). Images reuse the Media Library files from the migration import; two Videos gallery photos missing from that import ship with the theme.
- Contact: the original form (First Name, Last Name, Email*, Message, Send, "Thanks for submitting!"), saved under Enquiries and emailed to Settings → General → Contact form recipient (default sales@blackhangarstudios.com); spam honeypot, timing and rate limit. Map and the original CloudPano 360° tour are embedded.
- Original URL structure: header/footer links now use original addresses (/general-5 for FAQ, /general-5-1 and /general-5-2 for Privacy/Terms, /production-services for Services, no trailing slashes); 301 redirects for /services, /faq, /privacy-policy, /terms-and-conditions and /production-facilities; one-click repair for menus saved in the Site Editor.
- Blog: one click restores the original publication dates of the 66 imported posts (they showed the import day).
- Footer: "A project by ORWO Studio" removed; the copyright line always shows the current year (also for a footer saved in the Site Editor).
- Client requests: "Studio Video" and "Services" buttons next to Portfolio in the homepage hero (one click for a homepage saved in the Site Editor, with restore); The Fallow Few, The Chair and Frost added to Portfolio as drafts (posters/years to be added before publishing).
- Production pages: adjustable title size (Production detail block setting).

Validation: 23 PHP integration checks (apply/restore/edit detection/homepage protection, post dates, link repair, footer year/credit, productions, hero buttons), all 18 pages valid in the block editor with exact re-serialization, original-copy completeness check, contact form submission/validation/spam checks, redirects, and Chromium checks at 1920/768/390/360/320 px (no horizontal overflow, no broken images, one H1, no JavaScript errors).

## 0.3.1

- Homepage releases use the full page width with narrow responsive side margins.
- Selected productions render once in a static grid, replacing moving rows and clones.
- Entire poster cards link to the production page.
- Cards retain upward, once-only scroll reveals, with visible content when JavaScript is disabled or reduced motion is enabled.
- Existing saved homepage blocks work without resetting the template; database settings and Additional CSS are preserved.

Validation: 48 theme integration checks; 18 updater checks including native WordPress installation; Chromium checks at 1920, 1440, 768 and 390 pixels, including full-width grid bounds, unique card links, no horizontal movement/overflow and reveal behavior.

## 0.3.0

- Added administrator-controlled Check for updates and Update theme actions using stable GitHub releases.
- Added package integrity, path, version and WordPress/PHP compatibility checks.
