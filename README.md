# Artbooks Publishing Website (WordPress)

A WordPress-powered website for **Artbooks** publishing house: a scalable **book catalog** with dedicated **book pages**, **authors**, and **events**, designed so a non-technical admin can manage content via WP admin.

## Current Build Priority (MVP)

Implementation should be done in this order:

1. **Home page** (`/`)
2. **About page** (`/about/`)
3. **Books catalog** (`/books/`)

Everything else (single book pages, authors, events, contacts, advanced filtering) is a follow-up stage after the first three pages are stable.

## Goals

- Fast, clean public website for a publishing house
- Large catalog of books, each with a **custom visual background**
- Structured content (Books, Authors, Events) instead of “random pages”
- Simple content editing for administrators

## MVP Scope Details

### 1) Home (`/`)
- Hero section with brand positioning and primary CTA
- Short publisher introduction
- Featured or latest books preview (cards linking to catalog or book pages)
- Navigation links to About and Books

### 2) About (`/about/`)
- Mission / story content block
- Optional team or imprint information
- Optional partner/contact links
- Editable via WordPress admin (page content + optional ACF fields)

### 3) Books Catalog (`/books/`)
- Archive grid/list of books (cover, title, author)
- Pagination
- Basic search and/or simple filters (can start minimal)
- Optimized listing: do not load heavy per-book background assets in catalog cards

## Information Architecture (IA)

- `/` — Home
- `/about/` — About Artbooks
- `/books/` — Books catalog (search / filters / pagination)
- `/books/{book-slug}/` — Book page (single page with sections)
    - `#about` — Description
    - `#author` — Author block + link
    - `#illustrations` — Gallery
    - `#buy` — Where to buy (store links)
- `/authors/` — Authors list (optional but recommended)
- `/authors/{author-slug}/` — Author page (bio + author’s books)
- `/events/` — Events listing
- `/events/{event-slug}/` — Event details
- `/contacts/` — Contacts

## Tech Stack

- WordPress (classic theme, not a page builder)
- ACF (Advanced Custom Fields) for structured content
- Custom Post Types:
    - `book`
    - `author`
    - `event`
- Optional:
    - FacetWP (advanced filters for large catalogs)
    - RankMath / Yoast (SEO)
    - Caching (host-level or plugin)

## Content Model

### Book (`book`)
Typical fields (ACF):

- Cover image
- Background image (desktop)
- Background image (mobile)
- Overlay opacity / theme (light/dark)
- Accent color
- Author reference (`author`)
- Description (WYSIWYG)
- Illustrations gallery
- “Where to buy” links (repeater: store name + URL + optional note)

### Author (`author`)
- Photo
- Bio (WYSIWYG)
- Links (repeater)
- Books list is generated automatically from the Book → Author relation

### Event (`event`)
- Start date/time
- Location
- Registration / external URL
- Content (WYSIWYG)
- Optional gallery

## Repository Structure (example)

> Adjust to your actual structure.

- `wp-content/themes/artbooks/` — Custom theme
    - `assets/` — CSS/JS/images source
    - `templates/` — Template parts/partials
    - `functions.php` — Theme setup, hooks
    - `single-book.php`, `archive-book.php`
    - `single-author.php`, `archive-author.php`
    - `single-event.php`, `archive-event.php`
- `wp-content/mu-plugins/` (optional) — small “must-use” plugins for CPT registration, helpers, etc.

## Local Development

### Requirements
- PHP 8.x
- MySQL/MariaDB
- Node.js (only if theme assets are built locally)
- Docker (optional, recommended)

### Quick start (generic)
1. Install WordPress locally (Docker / LocalWP / MAMP / custom stack)
2. Put the theme into:
    - `wp-content/themes/artbooks/`
3. Activate the theme in WP Admin:
    - Appearance → Themes → **Artbooks**
4. Install and activate required plugins:
    - ACF (and ACF Pro if used)
5. Import ACF field groups (if provided) or create fields manually
6. Add sample content:
    - Authors → Add New
    - Books → Add New (attach author + background + gallery + buy links)
    - Events → Add New

## Deployment Notes

- Use managed WP hosting (recommended) + CDN for media
- Enable caching (host-level or plugin)
- Use WebP/AVIF for cover/background where possible
- Avoid loading book background images in the catalog listing
- Add `rel="noopener noreferrer nofollow"` + `target="_blank"` for store links

## SEO / Performance Guidelines

- Keep book pages as **one URL** with anchor sections (better SEO, fewer templates)
- Catalog should load:
    - cover thumbnails only (lazy-loaded)
    - pagination and/or infinite scroll with care
- Consider FacetWP / SearchWP for large-scale filtering and search

## Roadmap (optional)

- [ ] Home page (`/`) implementation
- [ ] About page (`/about/`) implementation
- [ ] Books catalog (`/books/`) implementation
    - [ ] **Phase 1: Data + URL structure**
        - [ ] Register/verify `book` CPT archive slug as `/books/`
        - [ ] Confirm permalink settings and clean archive URL
        - [ ] Define minimal fields for catalog card: cover, title, author, year (optional)
    - [ ] **Phase 2: Archive template UI**
        - [ ] Create `archive-book.php` with responsive grid/list
        - [ ] Add reusable book card partial (`templates/`)
        - [ ] Add empty-state block when no books are found
    - [ ] **Phase 3: Query, pagination, sorting**
        - [ ] Implement `WP_Query` for published books only
        - [ ] Add pagination (default), keep infinite scroll optional
        - [ ] Add basic sorting (newest first by default)
    - [ ] **Phase 4: Search and basic filters (MVP)**
        - [ ] Add keyword search (`s` query param)
        - [ ] Add basic filter by author and/or year
        - [ ] Keep filters URL-driven for sharable links
    - [ ] **Phase 5: Performance + media rules**
        - [ ] Load thumbnail-size covers only in catalog
        - [ ] Enable lazy-loading for images
        - [ ] Avoid per-book background images on listing page
        - [ ] Validate page weight and first render performance
    - [ ] **Phase 6: SEO + accessibility**
        - [ ] Add semantic heading structure (`h1` + card titles)
        - [ ] Ensure alt text and keyboard-accessible controls
        - [ ] Add canonical-safe pagination and indexable archive pages
    - [ ] **Phase 7: Content/admin workflow**
        - [ ] Document required fields for editors
        - [ ] Add fallback behavior for missing cover/author metadata
        - [ ] Add sample content set for QA
    - [ ] **Phase 8: QA checklist**
        - [ ] Verify desktop/mobile layouts
        - [ ] Verify filter + pagination combinations
        - [ ] Verify no broken links to single book pages
- [ ] Book filters (genre/series/year/author)
- [ ] Multilingual support (WPML / Polylang)
- [ ] Accessibility pass (contrast, focus states, keyboard navigation)
- [ ] Editorial workflow (draft/review roles)
- [ ] Media optimization pipeline (compression + responsive sizes)

## License

TBD (add your license here).
