<?php
/**
 * Template Name: Distillery Map
 *
 * Full-screen interactive map page
 *
 * @package PowerfulScotch
 */

get_header(); ?>

<div class="map-page">
    <div class="map-toolbar">
        <div class="map-toolbar__left">
            <div class="region-filter" id="region-filter">
                <button class="filter-toggle" id="filter-toggle" aria-expanded="false">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M1 3h14M4 8h8M6 13h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <span>Filter by Region</span>
                </button>
                <div class="filter-dropdown" id="filter-dropdown" hidden>
                    <button class="filter-chip active" data-region="all">All Regions</button>
                </div>
            </div>
        </div>
        <div class="map-toolbar__right">
            <div class="map-search">
                <input type="text" id="map-search" class="map-search__input" placeholder="Search distilleries..." autocomplete="off">
                <div class="map-search__results" id="search-results" hidden></div>
            </div>
            <span class="distillery-count" id="distillery-count"></span>
        </div>
    </div>

    <div class="map-container">
        <div class="map-wrap" id="distillery-map"></div>

        <aside class="map-sidebar" id="map-sidebar" aria-label="Distillery details">
            <button class="sidebar-close" id="sidebar-close" aria-label="Close panel">&times;</button>
            <div class="sidebar-content" id="sidebar-content">
                <p class="sidebar-hint">Click a distillery pin to see details here.</p>
            </div>
        </aside>

        <!-- Mobile bottom sheet -->
        <div class="map-bottomsheet" id="map-bottomsheet" hidden>
            <div class="bottomsheet-handle"></div>
            <div class="bottomsheet-content" id="bottomsheet-content"></div>
        </div>
    </div>

    <!-- Coming soon overlay (injected by JS when needed) -->
    <div class="map-coming-soon" id="map-coming-soon"></div>
</div>

<?php get_footer();
