<?php
/**
 * Plugin Name:       Ryokourent Core
 * Plugin URI:        https://github.com/ekokeplek-glitch/sewamotor.git
 * Description:       Core business logic, CPT Armada & Penyewaan, WhatsApp booking engine, and role management for Ryokourent (Rental Motor Malang & Batu).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Ryokourent Dev Team
 * Author URI:        https://github.com/oned250
 * Text Domain:       ryokourent
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// Plugin Constants
// -----------------------------------------------------------------------------
define('RYOKOURENT_VERSION', '1.0.0');
define('RYOKOURENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RYOKOURENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RYOKOURENT_PLUGIN_FILE', __FILE__);

if (!defined('RYOKOURENT_TIMEZONE')) {
    define('RYOKOURENT_TIMEZONE', 'Asia/Jakarta');
}

if (!defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
    define('RYOKOURENT_DEFAULT_WA_NUMBER', '62895384017772');
}

// -----------------------------------------------------------------------------
// Core Bootstrap & Module Loader
// -----------------------------------------------------------------------------

// Load essential helper functions immediately.
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';

/**
 * Activation hook callback.
 * Checks system requirements and sets up initial roles/options safely.
 */
function ryokourent_activate_plugin() {
    // Check PHP version requirement.
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Ryokourent Core memerlukan PHP versi 8.0 atau lebih tinggi.', 'ryokourent'),
            esc_html__('Versi PHP Tidak Kompatibel', 'ryokourent'),
            array('back_link' => true)
        );
    }

    // Set default version option.
    if (!get_option('ryokourent_version')) {
        add_option('ryokourent_version', RYOKOURENT_VERSION);
    }

    // Set default WA number option if not exists.
    if (!get_option('ryokourent_wa_number')) {
        add_option('ryokourent_wa_number', RYOKOURENT_DEFAULT_WA_NUMBER);
    }

    // Register CPTs prior to rewrite flush.
    if (function_exists('ryokourent_register_cpt_motor')) {
        ryokourent_register_cpt_motor();
    }

    // Register operator role (TASK-020). Module belum dimuat pada saat aktivasi.
    require_once RYOKOURENT_PLUGIN_DIR . 'includes/user-roles.php';
    ryokourent_install_operator_role();

    // Grant all custom capabilities to administrator role (reviewOP K1).
    if (function_exists('ryokourent_grant_admin_capabilities')) {
        ryokourent_grant_admin_capabilities();
    }

    // Flush rewrite rules on activation.
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ryokourent_activate_plugin');

/**
 * Ensure administrator role always has Ryokourent custom capabilities.
 */
function ryokourent_ensure_admin_capabilities() {
    if (function_exists('ryokourent_grant_admin_capabilities') && current_user_can('manage_options')) {
        ryokourent_grant_admin_capabilities();
    }
}
add_action('admin_init', 'ryokourent_ensure_admin_capabilities');

/**
 * Deactivation hook callback.
 * Cleans up the operator role safely and flushes rewrite rules.
 */
function ryokourent_deactivate_plugin() {
    // Hapus role operator hanya jika tidak dipakai user manapun (TASK-020).
    require_once RYOKOURENT_PLUGIN_DIR . 'includes/user-roles.php';
    ryokourent_deactivate_operator_role();

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ryokourent_deactivate_plugin');

/**
 * Plugin bootstrap initialization.
 * Loads sub-modules sequentially as they are implemented across tasks.
 */
function ryokourent_init() {
    // Array of core modules to load when present.
    $modules = array(
        // Post types & Taxonomies (FASE 2)
        'includes/post-types.php',
        'includes/taxonomies.php',
        'includes/meta-boxes.php',

        // Business Logic & Booking Engine (FASE 2 & 3)
        'includes/pricing.php',
        'includes/availability.php',
        'includes/booking.php',
        'includes/whatsapp.php',
        'includes/user-roles.php',
        'includes/settings.php',

        // Admin & UI (FASE 2 & 3)
        'admin/dashboard.php',
        'admin/booking-columns.php',
        'admin/admin-settings.php',

        // Public Facing & Shortcodes (FASE 2 & 3)
        'public/shortcodes.php',
        'public/forms.php',
        'public/templates.php',
    );

    foreach ($modules as $module) {
        $file_path = RYOKOURENT_PLUGIN_DIR . $module;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
add_action('plugins_loaded', 'ryokourent_init');
