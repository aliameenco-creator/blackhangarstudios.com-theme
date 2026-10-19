# Changelog

## 0.4.2

- Header: floating rounded bar (logo left, menu right, cyan Contact pill, dark rounded dropdowns, underline on hover/current page; full-screen menu on phones). Styling only: menu items, links and logo unchanged.
- Homepage: browser tab and search title from the original site ("Black Hangar Studios | UK's Best Film & TV Production Studio") and description when none is set; **Our Studio** button next to Portfolio opens the 360 studio tour on the Videos page (added when the page is shown, so the homepage saved in the Site Editor is not changed; replaces the 0.4.1 popup).
- Homepage on phones: Portfolio and Our Studio buttons at normal size side by side; the "Welcome" studio photo shows the whole wide image instead of a tall crop. Desktop unchanged.
- Production poster rows: "Pause moving posters" removed; rows keep moving and visitors can swipe, drag or scroll them (clicking a poster still opens it).
- Videos page gallery: the studio video is shown as a wide tile.

Validation: homepage text and sections identical to the live page except the Our Studio button; 22 PHP integration checks; 18 pages + homepage template valid in the block editor; Chromium checks for header, dropdowns, phone menu, poster drag/auto-scroll/click and layouts at 1920/768/390/360/320 px.

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
