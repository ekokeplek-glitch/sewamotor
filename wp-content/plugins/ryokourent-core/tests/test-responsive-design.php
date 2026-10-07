<?php
/**
 * Test Suite for Responsive Design & Mobile-First Optimization (TASK-026)
 *
 * Verifies:
 * - Floating Mobile Action Bar rendering (ryokourent_render_floating_mobile_bar):
 *   - Semantic markup (<nav class="ryokou-floating-mobile-bar" ... role="navigation">).
 *   - Direct WhatsApp integration with clean phone digits & message payload.
 *   - Direct Booking Form trigger anchor.
 *   - Dynamic operating hours (07:00 - 23:00 WIB) retrieved from settings.
 *   - Custom arguments override (wa_message, booking_anchor).
 * - Shortcode registration and execution ([ryokou_mobile_bar]):
 *   - Enqueues ryokourent-public stylesheet.
 *   - Returns valid HTML string.
 * - Mobile CSS specifications in ryokourent-public.css:
 *   - Floating mobile bar strictly bounded to max-height: 15vh (<= 15% viewport).
 *   - Touch targets designed with minimum 44px height (min-height: 44px).
 *   - Safe area inset support (calc(0.5rem + env(safe-area-inset-bottom, 0px))).
 *   - Thumb zone ergonomics and bottom padding clearance (padding-bottom on body).
 *   - Zero horizontal overflow rules (overflow-x: hidden on narrow viewports).
 *   - Narrow viewport media queries (max-width: 430px and max-width: 768px).
 * - Child theme style.css responsive rules:
 *   - Single motor layout unstacking / sticky override on small screens.
 *   - Minimum touch target 44px on buttons.
 * - Template integrations:
 *   - templates/template-faq-pool.php calls ryokourent_render_floating_mobile_bar().
 *   - templates/single-motor.php calls ryokourent_render_floating_mobile_bar() with unit context.
 *
 * Jalankan: php tests/test-responsive-design.php
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

// Define ABSPATH and plugin constants for mock environment.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}
if (!defined('RYOKOURENT_PLUGIN_DIR')) {
    define('RYOKOURENT_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
    define('RYOKOURENT_DEFAULT_WA_NUMBER', '62895384017772');
}

// -----------------------------------------------------------------------------
// Global Test State & Mocks
// -----------------------------------------------------------------------------
$GLOBALS['mock_options']          = array();
$GLOBALS['mock_shortcodes']       = array();
$GLOBALS['mock_enqueued_styles']  = array();
$GLOBALS['mock_enqueued_scripts'] = array();

function get_option($key, $default = false) {
    return isset($GLOBALS['mock_options'][$key]) ? $GLOBALS['mock_options'][$key] : $default;
}

function update_option($key, $value) {
    $GLOBALS['mock_options'][$key] = $value;
    return true;
}

function esc_html($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function esc_attr($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function esc_html_e($text, $domain = 'ryokourent') {
    echo esc_html($text);
}

function esc_attr_e($text, $domain = 'ryokourent') {
    echo esc_attr($text);
}

function __($text, $domain = 'ryokourent') {
    return $text;
}

function add_shortcode($tag, $callback) {
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

function shortcode_atts($pairs, $atts, $shortcode = '') {
    $atts = (array) $atts;
    $out  = array();
    foreach ($pairs as $name => $default) {
        if (array_key_exists($name, $atts)) {
            $out[$name] = $atts[$name];
        } else {
            $out[$name] = $default;
        }
    }
    return $out;
}

function wp_enqueue_style($handle) {
    $GLOBALS['mock_enqueued_styles'][] = $handle;
}

function wp_enqueue_script($handle) {
    $GLOBALS['mock_enqueued_scripts'][] = $handle;
}

function home_url($path = '') {
    return 'https://ryokourent.test' . $path;
}

// Load settings and public modules
require_once RYOKOURENT_PLUGIN_DIR . 'includes/settings.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/templates.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/shortcodes.php';

// -----------------------------------------------------------------------------
// Test Harness
// -----------------------------------------------------------------------------
$test_count = 0;
$pass_count = 0;
$fail_count = 0;

function run_test($name, $condition, $details = '') {
    global $test_count, $pass_count, $fail_count;
    $test_count++;
    if ($condition) {
        $pass_count++;
        echo "  [PASS] {$name}\n";
    } else {
        $fail_count++;
        echo "  [FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
    }
}

echo "=================================================================\n";
echo "RYOKOURENT TEST SUITE: RESPONSIVE DESIGN & MOBILE-FIRST (TASK-026)\n";
echo "=================================================================\n\n";

// -----------------------------------------------------------------------------
// Test 1: Floating Mobile Bar HTML Rendering & Semantics
// -----------------------------------------------------------------------------
echo "1. Pengujian Markup Semantik & Struktur Floating Mobile Bar:\n";

$bar_html = ryokourent_render_floating_mobile_bar();
run_test('Floating bar memiliki elemen kontainer nav dengan class ryokou-floating-mobile-bar', strpos($bar_html, '<nav class="ryokou-floating-mobile-bar"') !== false);
run_test('Floating bar memiliki attribute role="navigation"', strpos($bar_html, 'role="navigation"') !== false);
run_test('Floating bar memiliki aria-label aksesibel', strpos($bar_html, 'aria-label="Aksi Cepat Mobile Ryokourent"') !== false);
run_test('Floating bar menampilkan indikator pulse dot animasi', strpos($bar_html, 'ryokou-pulse-dot') !== false);
run_test('Floating bar menampilkan status Buka', strpos($bar_html, 'Buka') !== false);
run_test('Floating bar menampilkan jam operasional default 07:00 - 23:00 WIB', strpos($bar_html, '07:00 - 23:00 WIB') !== false);
run_test('Floating bar memuat tombol Form Sewa', strpos($bar_html, 'Form Sewa') !== false);
run_test('Floating bar memuat tombol Chat WA', strpos($bar_html, 'Chat WA') !== false);

// -----------------------------------------------------------------------------
// Test 2: Dynamic Settings Synchronization
// -----------------------------------------------------------------------------
echo "\n2. Pengujian Sinkronisasi Pengaturan Dinamis (Settings Fallback):\n";

update_option('ryokourent_general_settings', array(
    'wa_primary' => '081234567890',
    'pool_open'  => '06:00',
    'pool_close' => '22:00',
));

$dynamic_bar = ryokourent_render_floating_mobile_bar();
run_test('Floating bar mengupdate jam buka-tutup dinamis 06:00 - 22:00 WIB', strpos($dynamic_bar, '06:00 - 22:00 WIB') !== false);
run_test('Floating bar membersihkan nomor WA ke format internasional 6281234567890', strpos($dynamic_bar, 'phone=6281234567890') !== false);

// Custom parameter override
$custom_bar = ryokourent_render_floating_mobile_bar(array(
    'booking_anchor' => 'https://ryokourent.test/#booking-form?motor_id=42',
    'wa_message'     => 'Saya ingin pesan unit CRF 150L',
));
run_test('Floating bar mendukung kustomisasi booking anchor via argumen', strpos($custom_bar, 'href="https://ryokourent.test/#booking-form?motor_id=42"') !== false);
run_test('Floating bar mendukung kustomisasi pesan WA via argumen', strpos($custom_bar, rawurlencode('Saya ingin pesan unit CRF 150L')) !== false);

// -----------------------------------------------------------------------------
// Test 3: Shortcode Registration & Execution
// -----------------------------------------------------------------------------
echo "\n3. Pengujian Shortcode [ryokou_mobile_bar]:\n";

run_test('Shortcode [ryokou_mobile_bar] terdaftar di WordPress', isset($GLOBALS['mock_shortcodes']['ryokou_mobile_bar']));

$GLOBALS['mock_enqueued_styles'] = array();
$sc_bar_output = call_user_func($GLOBALS['mock_shortcodes']['ryokou_mobile_bar'], array(
    'booking_anchor' => '#form-pesan',
));
run_test('Shortcode callback meng-enqueue stylesheet ryokourent-public', in_array('ryokourent-public', $GLOBALS['mock_enqueued_styles'], true));
run_test('Shortcode callback menghasilkan markup floating bar yang valid', strpos($sc_bar_output, 'ryokou-floating-mobile-bar') !== false);
run_test('Shortcode callback meneruskan atribut kustom booking_anchor', strpos($sc_bar_output, 'href="#form-pesan"') !== false);

// -----------------------------------------------------------------------------
// Test 4: CSS Mobile-First Specifications & Touch Target Bounding
// -----------------------------------------------------------------------------
echo "\n4. Pengujian Spesifikasi CSS Mobile-First di ryokourent-public.css:\n";

$public_css_path = RYOKOURENT_PLUGIN_DIR . 'assets/css/ryokourent-public.css';
run_test('File ryokourent-public.css tersedia', file_exists($public_css_path));

$public_css = file_get_contents($public_css_path);
run_test('CSS mendefinisikan batas tinggi maksimum 15% viewport (max-height: 15vh)', strpos($public_css, 'max-height: 15vh;') !== false);
run_test('CSS mendefinisikan touch target minimum 44px (min-height: 44px)', strpos($public_css, 'min-height: 44px;') !== false);
run_test('CSS mendukung iOS safe-area-inset-bottom', strpos($public_css, 'env(safe-area-inset-bottom') !== false);
run_test('CSS memberikan padding bawah pada body agar konten tidak tertutup', strpos($public_css, 'padding-bottom: calc(4.25rem') !== false);
run_test('CSS menerapkan pencegahan horizontal scroll (overflow-x: hidden)', strpos($public_css, 'overflow-x: hidden;') !== false);
run_test('CSS memiliki breakpoint sempit untuk smartphone 430px (@media (max-width: 430px))', strpos($public_css, '@media (max-width: 430px)') !== false);
run_test('CSS memiliki breakpoint tablet/mobile (@media (max-width: 768px))', strpos($public_css, '@media (max-width: 768px)') !== false);

// -----------------------------------------------------------------------------
// Test 5: Child Theme style.css Mobile Optimizations
// -----------------------------------------------------------------------------
echo "\n5. Pengujian Spesifikasi Responsif di Child Theme style.css:\n";

$child_theme_css_path = dirname(dirname(RYOKOURENT_PLUGIN_DIR)) . '/themes/generatepress-child/style.css';
run_test('File GeneratePress Child style.css tersedia', file_exists($child_theme_css_path));

$child_css = file_get_contents($child_theme_css_path);
run_test('Child theme CSS menerapkan breakpoint mobile @media (max-width: 640px)', strpos($child_css, '@media (max-width: 640px)') !== false);
run_test('Child theme CSS mengoverride posisi sticky pricing card menjadi static di mobile', strpos($child_css, 'position: static;') !== false);
run_test('Child theme CSS menetapkan touch target 44px pada tombol tombol', strpos($child_css, 'min-height: 44px;') !== false);
run_test('Child theme CSS memberikan ruang clearance footer untuk floating bar', strpos($child_css, 'padding-bottom: calc(4.5rem') !== false);

// -----------------------------------------------------------------------------
// Test 6: Template Integrations
// -----------------------------------------------------------------------------
echo "\n6. Pengujian Integrasi Template Child Theme:\n";

$single_motor_path = dirname(dirname(RYOKOURENT_PLUGIN_DIR)) . '/themes/generatepress-child/templates/single-motor.php';
$faq_pool_path     = dirname(dirname(RYOKOURENT_PLUGIN_DIR)) . '/themes/generatepress-child/templates/template-faq-pool.php';

run_test('Template single-motor.php tersedia', file_exists($single_motor_path));
run_test('Template template-faq-pool.php tersedia', file_exists($faq_pool_path));

$single_motor_content = file_get_contents($single_motor_path);
run_test('single-motor.php mengintegrasikan pemanggilan ryokourent_render_floating_mobile_bar()', strpos($single_motor_content, 'ryokourent_render_floating_mobile_bar') !== false);
run_test('single-motor.php meneruskan konteks judul unit dan anchor motor_id ke floating bar', strpos($single_motor_content, 'booking_anchor') !== false && strpos($single_motor_content, 'wa_message') !== false);

$faq_pool_content = file_get_contents($faq_pool_path);
run_test('template-faq-pool.php mengintegrasikan pemanggilan ryokourent_render_floating_mobile_bar()', strpos($faq_pool_content, 'ryokourent_render_floating_mobile_bar') !== false);

echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
if ($fail_count > 0) {
    echo "PERINGATAN: {$fail_count} pengujian gagal!\n";
}
echo "-----------------------------------------------------------------\n";

exit($fail_count === 0 ? 0 : 1);
