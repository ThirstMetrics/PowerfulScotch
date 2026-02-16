# PowerfulScotch — Project Handoff

## Current State (Feb 16, 2026)

**LIVE** at: https://wordpress-1449472-6215096.cloudwaysapps.com/
- Map page: `/map/?spirit=scotch` — 135 Scotch distilleries with interactive Leaflet map
- Detail pages: `/distillery/{slug}/` — individual distillery profiles
- Archive: `/distillery/` — browsable grid of all distilleries
- REST API: `/wp-json/powerful-scotch/v1/distilleries?spirit_type=scotch` — GeoJSON endpoint

**Target domain**: PowerfulScotch.PowerfulThirst.com (DNS not yet configured)

---

## Infrastructure

| Component | Details |
|-----------|---------|
| **Hosting** | Cloudways (Lightstack, Optimized WordPress) |
| **Server IP** | 167.71.242.157 |
| **App ID** | ujcwjsspzd |
| **SSH User** | master_nrbudqgaus |
| **SSH Access** | `ssh master_nrbudqgaus@167.71.242.157` |
| **App Path** | `/home/master/applications/ujcwjsspzd/public_html/` |
| **Theme Path** | `wp-content/themes/powerful-scotch/` |
| **WP-CLI** | Available on server |
| **Caching** | Breeze (active) + Varnish + Object Cache Pro (Redis) |
| **GitHub Repo** | git@github.com:ThirstMetrics/PowerfulScotch.git |

---

## Theme: powerful-scotch (v1.0.1)

### File Structure
```
wp-content/themes/powerful-scotch/
├── style.css                    # Theme declaration
├── functions.php                # CPT, enqueues, REST API, cache invalidation
├── header.php                   # Fixed header with spirit selector nav
├── footer.php                   # Site footer
├── index.php                    # Fallback template
├── front-page.php               # Homepage: hero + spirit cards + map preview
├── page-map.php                 # Full-screen interactive map
├── single-distillery.php        # Distillery detail page
├── archive-distillery.php       # Distillery grid listing
├── 404.php                      # Error page
├── assets/
│   ├── css/main.css             # Complete design system (~800 lines)
│   ├── js/map.js                # Leaflet map controller
│   ├── js/spirit-switcher.js    # Mobile menu + spirit tab logic
│   ├── js/detail-map.js         # Small map on detail pages
│   └── img/marker-*.svg         # Status-colored SVG markers
├── inc/
│   ├── cpt-distillery.php       # Custom post type registration
│   ├── taxonomies.php           # spirit_type + region taxonomies
│   ├── rest-api.php             # GeoJSON REST endpoint with transient caching
│   ├── acf-fields.php           # ACF field groups + native meta box fallback
│   └── import.php               # One-time import script (135 distilleries + GPS)
└── template-parts/
    ├── distillery-card.php      # Reusable card component
    └── map-filters.php          # Region filter chips
```

### Key Technical Details

- **Map tiles**: CartoDB dark (`dark_all`) for main map, CartoDB light for minimap contrast
- **Plugins used**: Leaflet.js 1.9.4, MarkerCluster 1.5.3, MiniMap 3.6.1 (all via CDN)
- **Markers**: CircleMarker (malt) and DivIcon square (grain), color-coded by status
  - Operating: `#4ade80` (green)
  - Silent: `#a0845c` (brown)
  - Mothballed: `#6b7280` (gray)
- **REST caching**: WordPress transients, 1 hour TTL, invalidated on post save
- **Coming Soon**: Dynamically injected by JS when spirit has no data (class-based toggle)
- **Deep linking**: `?spirit=scotch&distillery=laphroaig` centers map and opens sidebar
- **Responsive**: Mobile bottom sheet (<768px), collapsible sidebar (tablet), persistent sidebar (desktop)

### Custom Fields (per distillery post)

| Field | Meta Key | Type |
|-------|----------|------|
| Latitude | `latitude` | float |
| Longitude | `longitude` | float |
| Status | `distillery_status` | Operating / Silent / Mothballed |
| Type | `distillery_type` | Malt / Grain / Malt & Grain |
| Year Founded | `year_founded` | text |
| Year Closed | `year_closed` | text (blank if active) |
| Official Website | `official_website` | URL |
| Owner | `owner` | text |
| Water Source | `water_source` | text |
| Still Count | `still_count` | number |

ACF plugin is **not installed** — native meta boxes handle all fields.

---

## Deployment Workflow

### Making changes locally → server:
```bash
# Edit files locally in wp-content/themes/powerful-scotch/

# Upload changed files:
scp -O <local-file> master_nrbudqgaus@167.71.242.157:/home/master/applications/ujcwjsspzd/public_html/wp-content/themes/powerful-scotch/<path>

# Purge caches (REQUIRED after every change):
ssh master_nrbudqgaus@167.71.242.157 "cd /home/master/applications/ujcwjsspzd/public_html && wp breeze purge --cache=all && wp cache flush"

# Commit + push:
git add <files> && git commit -m "message" && git push https://github.com/ThirstMetrics/PowerfulScotch.git master
```

### SSH note:
SSH keys don't work from Claude Code's shell (agent issue). Use HTTPS for git push:
```bash
git push https://github.com/ThirstMetrics/PowerfulScotch.git master
```
Remote is set to SSH (`git@github.com:ThirstMetrics/PowerfulScotch.git`) for normal terminal use.

---

## Remaining TODO

### Domain Setup
- [ ] Create DNS A record: `PowerfulScotch.PowerfulThirst.com` → `167.71.242.157`
- [ ] Update WordPress URLs: `wp option update siteurl https://powerfulscotch.powerfulthirst.com && wp option update home https://powerfulscotch.powerfulthirst.com`
- [ ] Install SSL via Cloudways dashboard
- [ ] Verify site loads on new domain

### Content & Data
- [ ] Review/spot-check GPS pin placements for accuracy (Laphroaig, Glenfiddich, Highland Park, Talisker, Macallan recommended)
- [ ] Add distillery descriptions via WP admin (post editor content)
- [ ] Add featured images to distillery posts
- [ ] Populate owner field for major distilleries
- [ ] Add official website URLs where known

### Features Not Yet Built
- [ ] Homepage: set up a proper front page (currently uses sample page)
- [ ] Tequila / Rum / Sake data import (spirit selector tabs ready, shows "Coming Soon")
- [ ] About page content
- [ ] SEO: Install Yoast SEO, configure meta descriptions
- [ ] Security: Install Wordfence or similar
- [ ] Analytics: Add tracking code

### Polish
- [ ] Cross-browser test (Chrome, Firefox, Safari, Edge)
- [ ] Mobile testing on real devices
- [ ] Performance audit (target <3s mobile load)
- [ ] Delete default WordPress posts/pages (Sample Page, Hello World)

---

## Known Issues Fixed

1. **Coming-soon overlay always visible** (v1.0.0 → v1.0.1): CSS `display: flex` overrode HTML `hidden` attribute. Fixed by switching to class-based toggling with `display: none` default.
2. **Map container zero height**: Leaflet needs explicit height. Fixed by adding `height: 100%` to `.map-wrap` and `#distillery-map`.
3. **Varnish cache**: Cloudways serves pages through Varnish. Must run `wp breeze purge --cache=all` after any template/CSS/JS changes.

---

## Data Source

Original distillery data recovered from the Malt Madness whisky map via the Wayback Machine:
- `mapdata_full.js` — 135 distilleries with pixel coordinates (original)
- GPS coordinates were mapped for all 135 distilleries in `inc/import.php`
- Import has already been run; all 135 are live as WordPress posts
