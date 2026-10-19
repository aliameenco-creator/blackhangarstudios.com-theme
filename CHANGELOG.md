# Changelog

## 0.4.1

Built on the live site's theme (the hand-made homepage, header and film studio design are kept exactly). Replaces the withdrawn 0.4.0.

- Homepage: only change is an **Our Studio** button next to Portfolio. It opens a popup with the studio Showreel and Studio tour videos (Media Library); without JavaScript it opens the Videos page.
- **Appearance -> Black Hangar Pages**: applies the approved designs to the 18 inner pages at their original addresses (backup and restore per page; homepage and News never touched), restores the original blog publication dates, and adds The Fallow Few, The Chair and Frost to Portfolio as drafts.
- Contact page: original form (First Name, Last Name, Email*, Message), saved under Enquiries and emailed (Settings -> General -> Contact form recipient; default sales@blackhangarstudios.com), map and 360 tour.
- Redirects for /services, /faq, /privacy-policy, /terms-and-conditions and /production-facilities to the original pages.
- Footer: "A project by ORWO Studio" removed; copyright always shows the current year.

Validation: homepage file = live export + only the Our Studio button (byte comparison); 20 PHP integration checks; 18 pages + homepage template valid in the block editor; copy completeness; Chromium at 1920/768/390/360/320 px with no overflow, broken images or script errors.

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
