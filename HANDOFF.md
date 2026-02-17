# PowerfulSpirits — Project Handoff

## Current State (Feb 16, 2026)

**LIVE** at: https://wordpress-1449472-6215096.cloudwaysapps.com/
**Target domain**: PowerfulSpirits.PowerfulThirst.com (DNS not yet configured)

### What's Live
- **Scotch map**: `/map/?spirit=scotch` — 135 distilleries across Scotland
- **Rum map**: `/map/?spirit=rum` — 173 distilleries across 54 countries, 7 geographic regions
- **Tequila**: Coming Soon overlay (tab exists, no data)
- **Sake**: Coming Soon overlay (tab exists, no data) — **NEXT SESSION TARGET**
- **Detail pages**: `/distillery/{slug}/` — full profiles with map, facts table, production process
- **Archive**: `/distillery/` — browsable grid of all distilleries
- **REST API**: `/wp-json/powerful-spirits/v1/distilleries?spirit_type=scotch|rum` — GeoJSON endpoint
- **Homepage**: Hero + spirit cards (scotch active, rum active with 173 count, tequila/sake Coming Soon)

### Brand
- Theme branded as **PowerfulSpirits** (v1.1.0)
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

## Theme: powerful-scotch/ (branded PowerfulSpirits v1.1.0)

### File Structure
```
wp-content/themes/powerful-scotch/
├── style.css                    # Theme declaration (Powerful Spirits v1.1.0)
├── functions.php                # Setup, enqueues, REST URL, cache invalidation
├── header.php                   # Fixed header: logo + spirit nav + favicon links
├── footer.php                   # Footer with logo + spirit links
├── index.php                    # Fallback template
├── front-page.php               # Homepage: hero + spirit cards (scotch/rum active) + map preview
├── page-map.php                 # Full-screen interactive map with toolbar, sidebar, bottomsheet
├── single-distillery.php        # Detail page: map + facts table + rum/scotch fields
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
│   ├── taxonomies.php           # spirit_type + region taxonomies (scotch regions + 7 rum regions)
│   ├── rest-api.php             # GeoJSON REST endpoint (powerful-spirits/v1) with transient caching
│   ├── acf-fields.php           # ACF field groups + native meta box fallback (all fields)
│   ├── import.php               # Scotch import: 135 distilleries (already run)
│   └── import-rum.php           # Rum import: 173 distilleries (already run)
└── template-parts/
    ├── distillery-card.php      # Reusable card component
    └── map-filters.php          # Region filter chips
```

### Key Technical Patterns

- **Adding a new spirit** requires changes to:
  1. `assets/js/map.js` — add to `AVAILABLE_SPIRITS` array, set default view in `SPIRIT_VIEWS`
  2. `inc/taxonomies.php` — add region terms in `ps_create_default_terms()`
  3. `inc/import-{spirit}.php` — new import script (follow `import-rum.php` pattern)
  4. `front-page.php` — convert Coming Soon `<div>` to active `<a>` with dynamic count
  5. `functions.php` — add transient key to `ps_invalidate_cache()` (already has sake)

- **map.js popup behavior**: Hover opens a popup (not tooltip) with `interactive: true` so users can click the Visit link. Click opens the sidebar. `getSpiritLabel()` returns "Rum" or "Rhum" based on French-tradition countries.

- **REST API namespace**: `powerful-spirits/v1` — referenced in `functions.php` (line 130), `inc/rest-api.php` (line 11), `front-page.php` (inline script)

- **Transient cache keys**: `ps_distilleries_{spirit}` and `ps_distilleries_all` — invalidated on post save, 1 hour TTL

- **SCP requires `-O` flag** (legacy protocol) on this Cloudways server

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
| Still Count | `still_count` | All spirits | number or text ("Multiple") |
| Still Types | `still_types` | Rum | text (e.g., "Column stills", "Pot and column stills") |
| Expressions | `expressions` | Rum | textarea (notable brands/expressions) |
| Barrel Sources | `barrel_sources` | Rum | text (e.g., "Oak", "Ex-bourbon") |
| Raw Material | `raw_material` | Rum | text (e.g., "Molasses", "Cane juice") |
| Country | `country` | Rum | text (country name) |

ACF plugin is **not installed** — native meta boxes handle all fields.

### Taxonomies

- **spirit_type**: Scotch, Rum, Tequila, Sake (terms pre-created)
- **region**: Scotch regions (Speyside, Islay, Highlands, etc.) + Rum regions (Caribbean, Central America, South America, North America, Europe, Africa, Asia-Pacific)

---

## Deployment Workflow

```bash
# 1. Edit files locally in wp-content/themes/powerful-scotch/

# 2. Upload changed files (note: -O flag required):
scp -O <local-file> master_nrbudqgaus@167.71.242.157:/home/master/applications/ujcwjsspzd/public_html/wp-content/themes/powerful-scotch/<path>

# 3. Run import (for new spirit data):
ssh master_nrbudqgaus@167.71.242.157 "cd /home/master/applications/ujcwjsspzd/public_html && wp eval-file wp-content/themes/powerful-scotch/inc/import-sake.php"

# 4. Purge caches (REQUIRED after every change):
ssh master_nrbudqgaus@167.71.242.157 "cd /home/master/applications/ujcwjsspzd/public_html && wp breeze purge --cache=all && wp cache flush"

# 5. Commit + push:
git add <files> && git commit -m "message" && git push
```

---

## Next Session: Sake Map

### What Needs to Happen

1. **Source sake data** — Need an Excel/CSV or data source for Japanese sake breweries with:
   - Brewery name, Prefecture/region, GPS coordinates
   - Sake-specific fields: rice type (酒米), water source, yeast strain, brewing method (ginjo/daiginjo/junmai/etc.), toji (master brewer) school, notable brands/expressions
   - Website

2. **Decide on regions** — Japan's sake regions could be:
   - By traditional region: Tohoku, Hokuriku, Kanto, Chubu, Kinki, Chugoku, Shikoku, Kyushu
   - Or by prefecture: Niigata, Hyogo (Nada), Kyoto (Fushimi), Akita, Yamagata, etc.

3. **Create `inc/import-sake.php`** — follow `import-rum.php` pattern:
   - Parse data, GPS conversion if needed
   - Map to region terms
   - Set `spirit_type` = "Sake"
   - Set sake-specific meta fields

4. **Add sake-specific ACF fields** to `inc/acf-fields.php`:
   - Potential fields: `rice_type`, `yeast_strain`, `brewing_method`, `toji_school`, `prefecture`
   - Add to native meta box fallback save array

5. **Add sake regions** to `inc/taxonomies.php`

6. **Update `assets/js/map.js`**:
   - Add `'sake'` to `AVAILABLE_SPIRITS` array
   - Verify `SPIRIT_VIEWS.sake` center/zoom (currently `{ center: [36.2, 138.2], zoom: 6 }`)
   - Add sake-specific fields to `buildDetailPanel()` and hover popup
   - Consider: sake breweries don't have "Rum/Rhum" distinction — `getSpiritLabel()` already handles non-rum spirits

7. **Update `single-distillery.php`** — add conditional rows for sake fields

8. **Update `front-page.php`** — convert sake card from Coming Soon `<div>` to active `<a>` with count

9. **Update REST API** (`inc/rest-api.php`) — add sake meta fields to GeoJSON properties

10. **Deploy + import + cache flush**

### Files That Will Change
- `inc/acf-fields.php` — new sake fields
- `inc/taxonomies.php` — sake region terms
- `inc/rest-api.php` — sake fields in GeoJSON
- `inc/import-sake.php` — **new file**
- `assets/js/map.js` — AVAILABLE_SPIRITS, detail panel, popup
- `single-distillery.php` — sake field rows
- `front-page.php` — activate sake card

### Data Needed Before Starting
- **A sake brewery dataset** (equivalent to the "Rum Distilleries.xlsx" used for rum)
- If no Excel is available, data can be compiled from resources like:
  - Japan Sake and Shochu Makers Association brewery registry
  - Sake breweries by prefecture databases
  - Major brewery websites (Dassai/Asahi Shuzo, Kubota/Asahi-Shuzo Niigata, Hakkaisan, Juyondai, etc.)

---

## Known Issues & Notes

1. **3 rum distilleries with zero coordinates**: Ron Millonario (Peru), Zhumir (Ecuador), Three Counties Spirits Co. (UK) — GPS was unparseable from Excel. They're imported but won't show on the map. Can be manually fixed in WP admin.

2. **Varnish cache**: Cloudways serves through Varnish. Always run `wp breeze purge --cache=all` after template/CSS/JS changes.

3. **Browser favicon cache**: After favicon changes, users may need hard-refresh (Ctrl+Shift+R) to see updates.

4. **Theme directory name**: Stays `powerful-scotch/` on disk to avoid breaking the active theme registration on the server. All branding in files says "PowerfulSpirits".

---

## Data Sources

| Spirit | Source | Count | Import Script |
|--------|--------|-------|---------------|
| Scotch | Malt Madness (Wayback Machine) + GPS mapping | 135 | `inc/import.php` (run) |
| Rum | `Rum Distilleries.xlsx` (54 countries) | 173 | `inc/import-rum.php` (run) |
| Tequila | Not yet sourced | — | — |
| Sake | Not yet sourced | — | — |
