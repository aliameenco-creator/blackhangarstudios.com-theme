# Black Hangar WordPress theme

Custom editable block theme for Black Hangar Studios, version 0.2.0. Includes Homepage and Film Studio designs, Poppins typography, responsive navigation, production display blocks, article styles and progressive scroll animations.

## Install or update

1. Back up your site and export the current theme.
2. Build the ZIP with `python scripts/package.py`, or use the supplied reviewed ZIP.
3. In WordPress, open Appearance → Themes → Add New Theme → Upload Theme.
4. Upload the ZIP and choose Replace current with uploaded. Keep the `black-hangar` theme folder name.
5. For the reviewed two-page redesign, open Appearance → Black Hangar Page Designs → Apply both page designs. This replaces only the two saved template layouts and stores their initial saved content for restoration.
6. Assign the Film studio template to your existing Film Studio page. Clear hosting/page cache.

Headings, text, pictures, FAQ answers and buttons remain editable in the Site Editor. Replace the homepage Cover media with your image/video. Production posters use published `bh_portfolio` entries selected for the homepage; CPT UI must continue registering that content type.

## What GitHub controls

This repository tracks theme source files. WordPress page content, Media Library records, menus, Additional CSS, site settings and saved Site Editor templates live in the database and need separate backups. Uploading this theme does not overwrite those settings. Saved templates can override file templates; use the apply tool only when you intentionally want the reviewed layouts.

## Change and release process

Work on a branch, review the change, let automated syntax/JSON checks pass, and check the pages on a staging WordPress site at desktop and mobile widths. Then merge and release a numbered theme ZIP. Syntax checks alone do not validate WordPress behavior or block serialization. Keep the previous ZIP and a database backup for rollback.

No automatic hosting deployment is configured. GitHub pushes do not change the live site. Hosting credentials must be configured separately through a secure deployment mechanism; never commit them or paste them into theme files.

## Assets and licensing

Theme code is GPL-2.0-or-later. Google Fonts are provided with license notices under assets/fonts and assets/design. Black Hangar images and the studio brochure are included for the authorized site's migration and design; their presence does not grant third-party reuse rights.
