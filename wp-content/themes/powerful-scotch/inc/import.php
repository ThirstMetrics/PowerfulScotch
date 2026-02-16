<?php
/**
 * One-Time Data Import: Malt Madness Distilleries → WordPress
 *
 * Usage (WP-CLI):
 *   wp eval-file wp-content/themes/powerful-scotch/inc/import.php
 *
 * Or add ?ps_import=1&ps_import_key=YOUR_SECRET to any admin page URL
 * (requires is_admin() and a secret key for safety).
 *
 * @package PowerfulScotch
 */

defined('ABSPATH') || exit;

/**
 * Run import via admin URL: ?ps_import=1&ps_import_key=powerfulscotch2024
 */
function ps_maybe_run_import() {
    if (!is_admin()) return;
    if (!current_user_can('manage_options')) return;
    if (empty($_GET['ps_import']) || empty($_GET['ps_import_key'])) return;
    if ($_GET['ps_import_key'] !== 'powerfulscotch2024') return;

    ps_run_import();
    wp_die('Import complete. Check the admin for distillery posts.');
}
add_action('admin_init', 'ps_maybe_run_import');

/**
 * WP-CLI command registration
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ps-import', function () {
        ps_run_import();
        WP_CLI::success('Import complete!');
    });
}

/**
 * Main import function
 */
function ps_run_import() {
    // Ensure taxonomies & CPT are registered
    if (!post_type_exists('distillery')) {
        ps_register_distillery_cpt();
    }
    if (!taxonomy_exists('spirit_type')) {
        ps_register_taxonomies();
    }

    // Create default terms
    ps_create_default_terms();

    $distilleries = ps_get_distillery_data();
    $imported = 0;
    $skipped = 0;

    foreach ($distilleries as $d) {
        // Check if already exists
        $existing = get_page_by_title($d['name'], OBJECT, 'distillery');
        if ($existing) {
            $skipped++;
            continue;
        }

        // Create post
        $post_id = wp_insert_post([
            'post_title'  => $d['name'],
            'post_type'   => 'distillery',
            'post_status' => 'publish',
            'post_name'   => sanitize_title($d['name']),
        ]);

        if (is_wp_error($post_id)) {
            if (defined('WP_CLI')) {
                WP_CLI::warning('Failed to create: ' . $d['name']);
            }
            continue;
        }

        // Set meta fields
        update_post_meta($post_id, 'latitude', $d['lat']);
        update_post_meta($post_id, 'longitude', $d['lng']);
        update_post_meta($post_id, 'distillery_status', $d['status']);
        update_post_meta($post_id, 'distillery_type', $d['type']);

        // Parse year range
        if (!empty($d['year'])) {
            $parts = preg_split('/\s*-\s*/', $d['year'], 2);
            update_post_meta($post_id, 'year_founded', trim($parts[0]));
            if (isset($parts[1]) && strtolower(trim($parts[1])) !== 'present') {
                update_post_meta($post_id, 'year_closed', trim($parts[1]));
            }
        }

        // Set spirit type taxonomy
        wp_set_object_terms($post_id, 'Scotch', 'spirit_type');

        // Set region taxonomy - normalize sub-regions
        if (!empty($d['region'])) {
            $region = $d['region'];
            // Clean up sub-region notations like "Speyside (Livet)"
            $base_region = preg_replace('/\s*\(.*\)/', '', $region);
            wp_set_object_terms($post_id, $base_region, 'region');
        }

        $imported++;

        if (defined('WP_CLI')) {
            WP_CLI::log("Imported: {$d['name']} ({$d['lat']}, {$d['lng']})");
        }
    }

    // Flush transients
    delete_transient('ps_distilleries_scotch');
    delete_transient('ps_distilleries_all');

    if (defined('WP_CLI')) {
        WP_CLI::log("Done. Imported: $imported, Skipped: $skipped");
    }

    return ['imported' => $imported, 'skipped' => $skipped];
}

/**
 * Complete dataset: 135 Scotch distilleries with GPS coordinates
 *
 * Coordinates sourced from known distillery locations.
 * Original data from Malt Madness mapdata.js (pixel coords replaced with GPS).
 */
function ps_get_distillery_data() {
    return [
        ['name' => 'Aberfeldy',         'region' => 'Midlands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1896 - present', 'lat' => 56.6210, 'lng' => -3.8710],
        ['name' => 'Aberlour',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1826 - present', 'lat' => 57.4680, 'lng' => -3.2300],
        ['name' => 'Abhainn Dearg',      'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '2009 - present', 'lat' => 58.2030, 'lng' => -6.9230],
        ['name' => 'Ailsa Bay',          'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '2007 - present', 'lat' => 55.4540, 'lng' => -4.6270],
        ['name' => 'Allt A Bhainne',     'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1975 - present', 'lat' => 57.3490, 'lng' => -3.2870],
        ['name' => 'Ardbeg',             'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1815 - present', 'lat' => 55.6400, 'lng' => -6.1080],
        ['name' => 'Ardmore',            'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.3370, 'lng' => -2.7960],
        ['name' => 'Arran',              'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1993 - present', 'lat' => 55.6910, 'lng' => -5.2990],
        ['name' => 'Auchentoshan',       'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1825 - present', 'lat' => 55.9240, 'lng' => -4.4360],
        ['name' => 'Auchroisk',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1974 - present', 'lat' => 57.4870, 'lng' => -3.1050],
        ['name' => 'Aultmore',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1896 - present', 'lat' => 57.5050, 'lng' => -2.9770],
        ['name' => 'Balblair',           'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1790 - present', 'lat' => 57.8020, 'lng' => -4.0710],
        ['name' => 'Balmenach',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 57.3430, 'lng' => -3.4950],
        ['name' => 'Balvenie',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1892 - present', 'lat' => 57.4440, 'lng' => -3.1240],
        ['name' => 'Banff',              'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1863 - 1983',    'lat' => 57.6640, 'lng' => -2.5270],
        ['name' => 'Ben Nevis',          'region' => 'West Highlands',       'type' => 'Malt',         'status' => 'Operating',  'year' => '1825 - present', 'lat' => 56.8180, 'lng' => -5.0940],
        ['name' => 'Benriach',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.5860, 'lng' => -3.2360],
        ['name' => 'Benrinnes',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1835 - present', 'lat' => 57.4370, 'lng' => -3.2390],
        ['name' => 'Benromach',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.6080, 'lng' => -3.6160],
        ['name' => 'Ben Wyvis',          'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Silent',     'year' => '1965 - 1977',    'lat' => 57.6890, 'lng' => -4.3650],
        ['name' => 'Bladnoch',           'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1825 - present', 'lat' => 54.8610, 'lng' => -4.5550],
        ['name' => 'Blair Athol',        'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1798 - present', 'lat' => 56.7650, 'lng' => -3.8490],
        ['name' => 'Bowmore',            'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1779 - present', 'lat' => 55.7560, 'lng' => -6.2900],
        ['name' => 'Braeval',            'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1974 - present', 'lat' => 57.2710, 'lng' => -3.3740],
        ['name' => 'Brora',              'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Silent',     'year' => '1819 - 1983',    'lat' => 58.0110, 'lng' => -3.8530],
        ['name' => 'Bruichladdich',      'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1881 - present', 'lat' => 55.7650, 'lng' => -6.3580],
        ['name' => 'Bunnahabhain',       'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1883 - present', 'lat' => 55.8830, 'lng' => -6.1260],
        ['name' => 'Caol Ila',           'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1846 - present', 'lat' => 55.8540, 'lng' => -6.1060],
        ['name' => 'Cameronbridge',      'region' => 'Eastern Highlands',    'type' => 'Grain',        'status' => 'Operating',  'year' => '1824 - present', 'lat' => 56.2160, 'lng' => -3.0070],
        ['name' => 'Caperdonich',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Mothballed', 'year' => '1898 - 2002',    'lat' => 57.4870, 'lng' => -3.2030],
        ['name' => 'Cardhu',             'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 57.3810, 'lng' => -3.3380],
        ['name' => 'Clynelish',          'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1899 - present', 'lat' => 58.0180, 'lng' => -3.8680],
        ['name' => 'Coleburn',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1897 - 1985',    'lat' => 57.5360, 'lng' => -3.3040],
        ['name' => 'Convalmore',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1894 - 1985',    'lat' => 57.4470, 'lng' => -3.1210],
        ['name' => 'Cragganmore',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1870 - present', 'lat' => 57.3700, 'lng' => -3.3770],
        ['name' => 'Craigellachie',      'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1891 - present', 'lat' => 57.4760, 'lng' => -3.1780],
        ['name' => 'Daftmill',           'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Operating',  'year' => '2005 - present', 'lat' => 56.2800, 'lng' => -3.1080],
        ['name' => 'Dailuaine',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1852 - present', 'lat' => 57.4310, 'lng' => -3.2500],
        ['name' => 'Dallas Dhu',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1899 - 1983',    'lat' => 57.5960, 'lng' => -3.5800],
        ['name' => 'Dalmore',            'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1839 - present', 'lat' => 57.6870, 'lng' => -4.2420],
        ['name' => 'Dalwhinnie',         'region' => 'West Highlands',       'type' => 'Malt',         'status' => 'Operating',  'year' => '1897 - present', 'lat' => 56.9430, 'lng' => -4.2440],
        ['name' => 'Deanston',           'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1966 - present', 'lat' => 56.1880, 'lng' => -4.0720],
        ['name' => 'Dufftown',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1896 - present', 'lat' => 57.4400, 'lng' => -3.1290],
        ['name' => 'Edradour',           'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1837 - present', 'lat' => 56.7630, 'lng' => -3.7130],
        ['name' => 'Fettercairn',        'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 56.8580, 'lng' => -2.5790],
        ['name' => 'Girvan',             'region' => 'Lowlands',             'type' => 'Grain',        'status' => 'Operating',  'year' => '1963 - present', 'lat' => 55.2430, 'lng' => -4.8530],
        ['name' => 'Glen Albyn',         'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Silent',     'year' => '1846 - 1983',    'lat' => 57.4830, 'lng' => -4.2310],
        ['name' => 'Glenallachie',       'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1967 - present', 'lat' => 57.4500, 'lng' => -3.2070],
        ['name' => 'Glenburgie',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1829 - present', 'lat' => 57.5960, 'lng' => -3.5400],
        ['name' => 'Glencadam',          'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Operating',  'year' => '1825 - present', 'lat' => 56.7150, 'lng' => -2.6600],
        ['name' => 'Glencraig',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1958 - 1980s',   'lat' => 57.5960, 'lng' => -3.5400],
        ['name' => 'Glendronach',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1826 - present', 'lat' => 57.3770, 'lng' => -2.6710],
        ['name' => 'Glendullan',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.4440, 'lng' => -3.1090],
        ['name' => 'Glen Elgin',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1900 - present', 'lat' => 57.5600, 'lng' => -3.2820],
        ['name' => 'Glenfarclas',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1836 - present', 'lat' => 57.3680, 'lng' => -3.3540],
        ['name' => 'Glenfiddich',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1887 - present', 'lat' => 57.4540, 'lng' => -3.1280],
        ['name' => 'Glen Flagler',       'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1965 - 1980s',   'lat' => 55.8670, 'lng' => -3.7870],
        ['name' => 'Glen Garioch',       'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Operating',  'year' => '1797 - present', 'lat' => 57.3270, 'lng' => -2.2690],
        ['name' => 'Glenglassaugh',      'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1875 - present', 'lat' => 57.6800, 'lng' => -2.8800],
        ['name' => 'Glengoyne',          'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1833 - present', 'lat' => 56.0360, 'lng' => -4.3120],
        ['name' => 'Glen Grant',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1840 - present', 'lat' => 57.4850, 'lng' => -3.2120],
        ['name' => 'Glengyle',           'region' => 'Campbeltown',          'type' => 'Malt',         'status' => 'Operating',  'year' => '2004 - present', 'lat' => 55.4280, 'lng' => -5.6070],
        ['name' => 'Glen Keith',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Mothballed', 'year' => '1958 - 1999',    'lat' => 57.5350, 'lng' => -2.9570],
        ['name' => 'Glenkinchie',        'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1837 - present', 'lat' => 55.8870, 'lng' => -2.8890],
        ['name' => 'Glenlivet',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 57.3410, 'lng' => -3.3360],
        ['name' => 'Glenlochy',          'region' => 'West Highlands',       'type' => 'Malt',         'status' => 'Silent',     'year' => '1898 - 1983',    'lat' => 56.8210, 'lng' => -5.1090],
        ['name' => 'Glenlossie',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1876 - present', 'lat' => 57.5580, 'lng' => -3.3420],
        ['name' => 'Glen Mhor',          'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Silent',     'year' => '1892 - 1983',    'lat' => 57.4800, 'lng' => -4.2300],
        ['name' => 'Glenmorangie',       'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1843 - present', 'lat' => 57.8200, 'lng' => -3.9890],
        ['name' => 'Glen Moray',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1897 - present', 'lat' => 57.6440, 'lng' => -3.3850],
        ['name' => 'Glen Ord',           'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1838 - present', 'lat' => 57.5060, 'lng' => -4.4990],
        ['name' => 'Glenrothes',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1878 - present', 'lat' => 57.4870, 'lng' => -3.1670],
        ['name' => 'Glen Scotia',        'region' => 'Campbeltown',          'type' => 'Malt',         'status' => 'Operating',  'year' => '1832 - present', 'lat' => 55.4270, 'lng' => -5.6050],
        ['name' => 'Glen Spey',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1878 - present', 'lat' => 57.4840, 'lng' => -3.2050],
        ['name' => 'Glentauchers',       'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.5230, 'lng' => -3.0340],
        ['name' => 'Glenturret',         'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1959 - present', 'lat' => 56.3920, 'lng' => -3.8340],
        ['name' => 'Glenugie',           'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Silent',     'year' => '1834 - 1983',    'lat' => 57.5050, 'lng' => -1.8110],
        ['name' => 'Glenury Royal',      'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Silent',     'year' => '1852 - 1985',    'lat' => 56.9620, 'lng' => -2.1570],
        ['name' => 'Highland Park',      'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1798 - present', 'lat' => 58.9680, 'lng' => -2.9540],
        ['name' => 'Hillside',           'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Silent',     'year' => '1897 - 1985',    'lat' => 56.7270, 'lng' => -2.4480],
        ['name' => 'Huntley',            'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '2008 - present', 'lat' => 57.4490, 'lng' => -2.7740],
        ['name' => 'Imperial',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Mothballed', 'year' => '1897 - 1998',    'lat' => 57.4450, 'lng' => -3.2820],
        ['name' => 'Inchgower',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1871 - present', 'lat' => 57.6560, 'lng' => -2.9180],
        ['name' => 'Invergordon',        'region' => 'North Highlands',      'type' => 'Grain',        'status' => 'Operating',  'year' => '1961 - present', 'lat' => 57.6870, 'lng' => -4.1700],
        ['name' => 'Inverleven',         'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Mothballed', 'year' => '1938 - 1992',    'lat' => 55.9410, 'lng' => -4.5430],
        ['name' => 'Isle of Jura',       'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1963 - present', 'lat' => 55.8330, 'lng' => -5.9510],
        ['name' => 'Kilchoman',          'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '2005 - present', 'lat' => 55.7850, 'lng' => -6.4520],
        ['name' => 'Kinclaith',          'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1958 - 1977',    'lat' => 55.8490, 'lng' => -4.2860],
        ['name' => 'Kininvie',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1990 - present', 'lat' => 57.4530, 'lng' => -3.1250],
        ['name' => 'Knockando',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1898 - present', 'lat' => 57.3870, 'lng' => -3.3490],
        ['name' => 'Knockdhu',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1894 - present', 'lat' => 57.5300, 'lng' => -2.7530],
        ['name' => 'Ladyburn',           'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1966 - 1970s',   'lat' => 55.2430, 'lng' => -4.8530],
        ['name' => 'Lagavulin',          'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1816 - present', 'lat' => 55.6350, 'lng' => -6.1260],
        ['name' => 'Laphroaig',          'region' => 'Islay',                'type' => 'Malt',         'status' => 'Operating',  'year' => '1815 - present', 'lat' => 55.6314, 'lng' => -6.1520],
        ['name' => 'Linkwood',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1821 - present', 'lat' => 57.5940, 'lng' => -3.2960],
        ['name' => 'Littlemill',         'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1772 - 1994',    'lat' => 55.9690, 'lng' => -4.5770],
        ['name' => 'Loch Ewe',           'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '2006 - present', 'lat' => 57.7650, 'lng' => -5.6720],
        ['name' => 'Loch Lomond',        'region' => 'West Highlands',       'type' => 'Malt & Grain', 'status' => 'Operating',  'year' => '1966 - present', 'lat' => 56.0080, 'lng' => -4.5770],
        ['name' => 'Lochside',           'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Silent',     'year' => '1957 - 1992',    'lat' => 56.7240, 'lng' => -2.4550],
        ['name' => 'Longmorn',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1894 - present', 'lat' => 57.5700, 'lng' => -3.2870],
        ['name' => 'Macallan',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 57.4850, 'lng' => -3.2070],
        ['name' => 'MacDuff',            'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1962 - present', 'lat' => 57.6680, 'lng' => -2.4930],
        ['name' => 'Mannochmore',        'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1972 - present', 'lat' => 57.5580, 'lng' => -3.3410],
        ['name' => 'Millburn',           'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Silent',     'year' => '1807 - 1985',    'lat' => 57.4770, 'lng' => -4.2130],
        ['name' => 'Miltonduff',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1824 - present', 'lat' => 57.6230, 'lng' => -3.4530],
        ['name' => 'Mortlach',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1823 - present', 'lat' => 57.4440, 'lng' => -3.1370],
        ['name' => 'Mosstowie',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1964 - 1981',    'lat' => 57.6230, 'lng' => -3.4530],
        ['name' => 'North British',      'region' => 'Lowlands',             'type' => 'Grain',        'status' => 'Operating',  'year' => '1885 - present', 'lat' => 55.9370, 'lng' => -3.2230],
        ['name' => 'North Port',         'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Silent',     'year' => '1820 - 1983',    'lat' => 56.7360, 'lng' => -2.6580],
        ['name' => 'Oban',               'region' => 'West Highlands',       'type' => 'Malt',         'status' => 'Operating',  'year' => '1794 - present', 'lat' => 56.4140, 'lng' => -5.4730],
        ['name' => 'Pittyvaich',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1975 - 1993',    'lat' => 57.4430, 'lng' => -3.1230],
        ['name' => 'Port Dundas',        'region' => 'Lowlands',             'type' => 'Grain',        'status' => 'Operating',  'year' => '1845 - present', 'lat' => 55.8730, 'lng' => -4.2430],
        ['name' => 'Port Ellen',         'region' => 'Islay',                'type' => 'Malt',         'status' => 'Silent',     'year' => '1825 - 1983',    'lat' => 55.6260, 'lng' => -6.1940],
        ['name' => 'Pulteney',           'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1826 - present', 'lat' => 58.4370, 'lng' => -3.0870],
        ['name' => 'Rosebank',           'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1840 - 1993',    'lat' => 55.9780, 'lng' => -3.6960],
        ['name' => 'Roseisle',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '2008 - present', 'lat' => 57.6740, 'lng' => -3.4550],
        ['name' => 'Royal Brackla',      'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1812 - present', 'lat' => 57.5770, 'lng' => -3.8600],
        ['name' => 'Royal Lochnagar',    'region' => 'Eastern Highlands',    'type' => 'Malt',         'status' => 'Operating',  'year' => '1845 - present', 'lat' => 57.0360, 'lng' => -3.2070],
        ['name' => 'Saint Magdalene',    'region' => 'Lowlands',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1798 - 1983',    'lat' => 55.9770, 'lng' => -3.6020],
        ['name' => 'Scapa',              'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1885 - present', 'lat' => 58.9600, 'lng' => -2.9990],
        ['name' => 'Speyburn',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1897 - present', 'lat' => 57.4870, 'lng' => -3.2090],
        ['name' => 'Speyside',           'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1990 - present', 'lat' => 57.0650, 'lng' => -4.0230],
        ['name' => 'Springbank',         'region' => 'Campbeltown',          'type' => 'Malt',         'status' => 'Operating',  'year' => '1828 - present', 'lat' => 55.4260, 'lng' => -5.6070],
        ['name' => 'Strathclyde',        'region' => 'Lowlands',             'type' => 'Grain',        'status' => 'Operating',  'year' => '1927 - present', 'lat' => 55.8530, 'lng' => -4.2750],
        ['name' => 'Strathisla',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1786 - present', 'lat' => 57.5390, 'lng' => -2.9500],
        ['name' => 'Strathmill',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1891 - present', 'lat' => 57.5340, 'lng' => -2.9520],
        ['name' => 'Talisker',           'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1831 - present', 'lat' => 57.3020, 'lng' => -6.3560],
        ['name' => 'Tamdhu',             'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Silent',     'year' => '1896 - 2010',    'lat' => 57.3890, 'lng' => -3.3470],
        ['name' => 'Tamnavulin',         'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1966 - present', 'lat' => 57.2950, 'lng' => -3.3440],
        ['name' => 'Teaninich',          'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1817 - present', 'lat' => 57.6840, 'lng' => -4.2600],
        ['name' => 'Tobermory',          'region' => 'Islands',              'type' => 'Malt',         'status' => 'Operating',  'year' => '1798 - present', 'lat' => 56.6230, 'lng' => -6.0680],
        ['name' => 'Tomatin',            'region' => 'North Highlands',      'type' => 'Malt',         'status' => 'Operating',  'year' => '1897 - present', 'lat' => 57.3380, 'lng' => -4.0090],
        ['name' => 'Tomintoul',          'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1965 - present', 'lat' => 57.2540, 'lng' => -3.3870],
        ['name' => 'Tormore',            'region' => 'Speyside',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1960 - present', 'lat' => 57.3500, 'lng' => -3.3790],
        ['name' => 'Tullibardine',       'region' => 'Midlands',             'type' => 'Malt',         'status' => 'Operating',  'year' => '1949 - present', 'lat' => 56.2360, 'lng' => -3.7610],
    ];
}
