# PowerfulSpirits — Project Handoff

## Current State (Feb 17, 2026)

**LIVE** at: https://wordpress-1449472-6215096.cloudwaysapps.com/
**Target domain**: PowerfulSpirits.PowerfulThirst.com (DNS not yet configured)

### What's Live
- **Scotch map**: `/map/?spirit=scotch` — 135 distilleries across Scotland
- **Rum map**: `/map/?spirit=rum` — 173 distilleries across 54 countries, 7 geographic regions
- **Tequila map**: `/map/?spirit=tequila` — 135 distilleries across 5 Mexican regions
- **Sake map**: `/map/?spirit=sake` — 798 breweries across Japan
- **Detail pages**: `/distillery/{slug}/` — full profiles with map, facts table, production details
- **Archive**: `/distillery/` — browsable grid of all distilleries
- **REST API**: `/wp-json/powerful-spirits/v1/distilleries?spirit_type=scotch|rum|tequila|sake` — GeoJSON endpoint
- **Homepage**: Hero + spirit cards — all 4 spirits active with dynamic counts (1,241 total distilleries)

### Brand
- Theme branded as **PowerfulSpirits** (v1.3.0)
- PowerfulThirst Atlas logo in header/footer
- Custom favicon: Atlas graphic only (text stripped), served as .ico + 32px + 192px PNG
- Dark theme: `#0f0f1a` bg, `#d4a574` gold accent, `#1b3a4b` teal (brand guide in main.css header)

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
| **Theme Path** | `wp-content/themes/powerful-scotch/` (directory name unchanged to avoid breaking active theme) |
| **WP-CLI** | Available on server |
| **Caching** | Breeze (active) + Varnish + Object Cache Pro (Redis) |
| **GitHub Repo** | https://github.com/ThirstMetrics/PowerfulScotch.git |
| **Git remote** | HTTPS (`https://github.com/...`) — SSH keys don't work from Claude Code's shell |

---

## Theme: powerful-scotch/ (branded PowerfulSpirits v1.3.0)

### File Structure
```
wp-content/themes/powerful-scotch/
├── style.css                    # Theme declaration (Powerful Spirits v1.3.0)
├── functions.php                # Setup, enqueues, REST URL, cache invalidation
├── header.php                   # Fixed header: logo + spirit nav + favicon links
├── footer.php                   # Footer with logo + spirit links
├── index.php                    # Fallback template
├── front-page.php               # Homepage: hero + spirit cards (all 4 active) + map preview
├── page-map.php                 # Full-screen interactive map with toolbar, sidebar, bottomsheet
├── single-distillery.php        # Detail page: map + facts table + spirit-specific fields
├── archive-distillery.php       # Distillery grid listing
├── 404.php                      # Error page
├── assets/
│   ├── css/main.css             # Complete design system (~1350 lines, brand guide in header)
│   ├── js/map.js                # Leaflet map: clustering, popups, region filters, search, deep-linking
│   ├── js/spirit-switcher.js    # Mobile menu + spirit tab logic
│   ├── js/detail-map.js         # Small map on single distillery pages
│   └── img/
│       ├── marker-*.svg         # Status-colored SVG markers (operating, silent, mothballed, grain)
│       ├── powerfulthirst-logo.png  # Full logo (header/footer)
│       ├── favicon.ico          # Multi-size ICO (16/32/48px)
│       ├── favicon-32.png       # 32px PNG favicon
│       └── favicon-192.png      # 192px PNG (Android/Apple touch)
├── inc/
│   ├── cpt-distillery.php       # Custom post type: distillery
│   ├── taxonomies.php           # spirit_type + region taxonomies (scotch/rum/tequila regions)
│   ├── rest-api.php             # GeoJSON REST endpoint (powerful-spirits/v1) with transient caching
│   ├── acf-fields.php           # ACF field groups + native meta box fallback (all fields)
│   ├── import.php               # Scotch import: 135 distilleries (already run)
│   ├── import-rum.php           # Rum import: 173 distilleries (already run)
│   ├── import-tequila.php       # Tequila import: 135 distilleries (already run)
│   └── tequila-data.php         # Tequila distillery data array (auto-generated from Excel)
└── template-parts/
    ├── distillery-card.php      # Reusable card component
    └── map-filters.php          # Region filter chips
```

### Key Technical Patterns

- **All 4 spirits are fully live.** No more Coming Soon overlays.

- **Adding a new spirit** requires changes to:
  1. `assets/js/map.js` — add to `AVAILABLE_SPIRITS` array, set default view in `SPIRIT_VIEWS`
  2. `inc/taxonomies.php` — add region terms in `ps_create_default_terms()`
  3. `inc/import-{spirit}.php` — new import script (follow `import-rum.php` pattern)
  4. `front-page.php` — add active `<a>` card with dynamic count query
  5. `inc/acf-fields.php` — add spirit-specific fields to ACF group, meta box, and save handler
  6. `inc/rest-api.php` — expose spirit-specific fields in GeoJSON properties
  7. `single-distillery.php` — add conditional rows for spirit-specific fields
  8. `functions.php` — bump `PS_VERSION` for cache busting, add transient key to `ps_invalidate_cache()`

- **map.js popup behavior**: Hover opens a popup (not tooltip) with `interactive: true` so users can click the Visit link. Click opens the sidebar. `getSpiritLabel()` returns "Rum" or "Rhum" based on French-tradition countries.

- **REST API namespace**: `powerful-spirits/v1` — referenced in `functions.php` (line 130), `inc/rest-api.php` (line 11), `front-page.php` (inline script)

- **Transient cache keys**: `ps_distilleries_{spirit}` and `ps_distilleries_all` — invalidated on post save, 1 hour TTL

- **SCP requires `-O` flag** (legacy protocol) on this Cloudways server

- **SCP path matters**: When uploading JS/CSS assets, always SCP to the correct subdirectory (e.g., `assets/js/map.js`), not the theme root. SCP to the wrong path was the cause of a search bug that took debugging to find.

- **Version bumping**: Always bump `PS_VERSION` in `functions.php` after changing JS/CSS. Without this, Varnish and browser caches serve stale assets.

### Custom Fields (per distillery post)

| Field | Meta Key | Used By | Notes |
|-------|----------|---------|-------|
| Latitude | `latitude` | All spirits | float |
| Longitude | `longitude` | All spirits | float |
| Status | `distillery_status` | All spirits | Operating / Silent / Mothballed |
| Type | `distillery_type` | Scotch | Malt / Grain / Malt & Grain |
| Year Founded | `year_founded` | All spirits | text |
| Year Closed | `year_closed` | All spirits | text (blank if active) |
| Official Website | `official_website` | All spirits | URL |
| Owner | `owner` | All spirits | text |
| Water Source | `water_source` | All spirits | text |
| Still Count | `still_count` | Scotch, Rum, Tequila | number or text ("Multiple") |
| Still Types | `still_types` | Rum, Tequila | text (e.g., "Column stills", "Copper pot stills") |
| Expressions | `expressions` | Rum, Sake | textarea (notable brands/expressions) |
| Barrel Sources | `barrel_sources` | Rum, Tequila | text (e.g., "Oak", "Ex-bourbon") |
| Raw Material | `raw_material` | Rum, Tequila | text (e.g., "Molasses", "Highland agave") |
| Country | `country` | Rum, Tequila, Sake | text (country name) |
| NOM Number | `nom_number` | Tequila | text (NOM registration number) |
| Cooking Method | `cooking_method` | Tequila | text (e.g., "Brick oven", "Autoclave", "Diffuser") |
| Production Capacity | `production_capacity` | Tequila | text (e.g., "Large-scale", "Artisanal") |
| Japanese Name | `name_japanese` | Sake | text (kanji/kana name) |
| Prefecture | `prefecture` | Sake | text (Japanese prefecture) |
| Key Brands | `key_brands` | Sake | text (notable sake brands) |
| Rice Varieties | `rice_varieties` | Sake | text (e.g., "Yamadanishiki", "Gohyakumangoku") |
| Toji School | `toji_school` | Sake | text (e.g., "Nanbu Toji", "Echigo Toji") |
| Production Size | `production_size` | Sake | text (Small / Medium / Large) |

ACF plugin is **not installed** — native meta boxes handle all fields.

### Taxonomies

- **spirit_type**: Scotch, Rum, Tequila, Sake (terms pre-created)
- **region**:
  - Scotch: Speyside, Islay, Highlands, North Highlands, West Highlands, Eastern Highlands, Lowlands, Campbeltown, Islands, Midlands
  - Rum: Caribbean, Central America, South America, North America, Europe, Africa, Asia-Pacific
  - Tequila: Los Altos (Highlands), Tequila Valley (Lowlands), Central Jalisco, Guanajuato, Tamaulipas

---

## Deployment Workflow

```bash
# 1. Edit files locally in wp-content/themes/powerful-scotch/

# 2. Bump PS_VERSION in functions.php (REQUIRED for JS/CSS changes)

# 3. Upload changed files (note: -O flag required, use correct subdirectory paths):
scp -O wp-content/themes/powerful-scotch/assets/js/map.js master_nrbudqgaus@167.71.242.157:/home/master/applications/ujcwjsspzd/public_html/wp-content/themes/powerful-scotch/assets/js/map.js
scp -O wp-content/themes/powerful-scotch/inc/rest-api.php master_nrbudqgaus@167.71.242.157:/home/master/applications/ujcwjsspzd/public_html/wp-content/themes/powerful-scotch/inc/rest-api.php

# 4. Run import (for new spirit data):
ssh master_nrbudqgaus@167.71.242.157 "cd /home/master/applications/ujcwjsspzd/public_html && wp eval-file wp-content/themes/powerful-scotch/inc/import-{spirit}.php"

# 5. Purge caches (REQUIRED after every change):
ssh master_nrbudqgaus@167.71.242.157 "cd /home/master/applications/ujcwjsspzd/public_html && wp breeze purge --cache=all && wp cache flush"

# 6. Commit + push:
git add <files> && git commit -m "message" && git push
```

**Important**: SSH command output is not visible in Claude Code's terminal. To capture output, redirect to a remote file and SCP it back:
```bash
ssh user@host 'command > /tmp/output.log 2>&1'
scp -O user@host:/tmp/output.log ./output.log
cat output.log
```

---

## Known Issues & Notes

1. **3 rum distilleries with zero coordinates**: Ron Millonario (Peru), Zhumir (Ecuador), Three Counties Spirits Co. (UK) — GPS was unparseable from Excel. They're imported but won't show on the map. Can be manually fixed in WP admin.

2. **Varnish cache**: Cloudways serves through Varnish. Always run `wp breeze purge --cache=all` after template/CSS/JS changes. Always bump `PS_VERSION` for JS/CSS changes.

3. **Browser favicon cache**: After favicon changes, users may need hard-refresh (Ctrl+Shift+R) to see updates.

4. **Theme directory name**: Stays `powerful-scotch/` on disk to avoid breaking the active theme registration on the server. All branding in files says "PowerfulSpirits".

5. **Search dropdown z-index**: Fixed in v1.3.0. The `.map-toolbar` now has `position: relative; z-index: 500` to ensure search results appear above the Leaflet map container.

6. **Tequila data source**: `Tequila Distilleries.xlsx` in project root, processed by `gen_tequila_php.py` into `inc/tequila-data.php`. Region classification is based on city names in the address field.

---

## Data Sources

| Spirit | Source | Count | Import Script | Regions |
|--------|--------|-------|---------------|---------|
| Scotch | Malt Madness (Wayback Machine) + GPS mapping | 135 | `inc/import.php` (run) | 10 Scottish regions |
| Rum | `Rum Distilleries.xlsx` (54 countries) | 173 | `inc/import-rum.php` (run) | 7 geographic regions |
| Tequila | `Tequila Distilleries.xlsx` + agavematchmaker.com | 135 | `inc/import-tequila.php` (run) | 5 Mexican regions |
| Sake | Sake brewery dataset | 798 | (previously run) | By prefecture |
