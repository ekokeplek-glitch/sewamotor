<?php
/**
 * Test Suite for Admin Settings & Bulk Price Adjustment (TASK-024)
 *
 * Verifies:
 * - Default settings retrieval and fallback mechanisms.
 * - Server-side access restriction: Operator blocked with HTTP 403 Forbidden via ryokourent_check_settings_permission_or_die().
 * - Administrator access authorization: manage_ryokourent_settings granted.
 * - Validation & persistence of official admin WhatsApp phone number.
 * - Validation of pool operating hours (07:00 - 23:00 WIB, format and chronological order).
 * - Multi-tier bulk price adjustment calculation (nominal and percentage).
 * - Boundary protection rules (ADR-012): no price <= 0, percentage bounded to -50%..+200%, zero adjustment rejected.
 * - Fleet-wide bulk update by motorcycle category with two-pass atomic validation.
 * - Menu registration and nonces verification.
 *
 * Jalankan: php tests/test-admin-settings.php
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

// -----------------------------------------------------------------------------
// Global Test State & Mocks
// -----------------------------------------------------------------------------
$GLOBALS['mock_options']           = array();
$GLOBALS['mock_post_meta']         = array();
$GLOBALS['mock_posts']             = array();
$GLOBALS['mock_terms']             = array();
$GLOBALS['mock_current_caps']      = array();
$GLOBALS['mock_admin_submenus']    = array();
$GLOBALS['wp_die_called']          = false;
$GLOBALS['wp_die_response']        = 0;
$GLOBALS['wp_die_message']         = '';
$GLOBALS['mock_nonce_ok']          = true;

class WP_Error {
    public $errors = array();
    public function __construct($code = '', $message = '') {
        if ($code) {
            $this->errors[$code] = array($message);
        }
    }
}

class WP_Query {
    public $posts = array();
    public function __construct($args = array()) {
        $found = array();
        $target_cat = '';
        if (!empty($args['tax_query'][0]['terms'])) {
            $target_cat = $args['tax_query'][0]['terms'];
        }

        foreach ($GLOBALS['mock_posts'] as $id => $post) {
            if ($post['post_type'] !== ($args['post_type'] ?? 'post')) {
                continue;
            }
            if ($target_cat && ($post['category'] ?? '') !== $target_cat) {
                continue;
            }
            $found[] = $id;
        }

        $this->posts = $found;
    }
}

// Mock WordPress functions
function __($text, $domain = 'default') { return $text; }
function _x($text, $context, $domain = 'default') { return $text; }
function esc_html__($text, $domain = 'default') { return $text; }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url) { return filter_var($url, FILTER_SANITIZE_URL); }
function esc_url_raw($url) { return filter_var($url, FILTER_SANITIZE_URL); }
function sanitize_text_field($str) { return trim(strip_tags((string) $str)); }
function sanitize_textarea_field($str) { return trim(strip_tags((string) $str)); }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)); }
function absint($n) { return abs((int) $n); }
function wp_unslash($val) { return $val; }
function is_wp_error($thing) { return $thing instanceof WP_Error; }

function get_option($option, $default = false) {
    return isset($GLOBALS['mock_options'][$option]) ? $GLOBALS['mock_options'][$option] : $default;
}
function update_option($option, $value, $autoload = null) {
    $GLOBALS['mock_options'][$option] = $value;
    return true;
}
function get_post_meta($post_id, $key = '', $single = false) {
    return isset($GLOBALS['mock_post_meta'][$post_id][$key]) ? $GLOBALS['mock_post_meta'][$post_id][$key] : ($single ? '' : array());
}
function update_post_meta($post_id, $key, $value) {
    $GLOBALS['mock_post_meta'][$post_id][$key] = $value;
    return true;
}
function get_the_title($post_id) {
    return isset($GLOBALS['mock_posts'][$post_id]['title']) ? $GLOBALS['mock_posts'][$post_id]['title'] : ('Motor #' . $post_id);
}
function get_terms($args = array()) {
    return $GLOBALS['mock_terms'];
}
function current_user_can($cap) {
    return !empty($GLOBALS['mock_current_caps'][$cap]);
}
function wp_die($message = '', $title = '', $args = array()) {
    $GLOBALS['wp_die_called']   = true;
    $GLOBALS['wp_die_message']  = (string) $message;
    $GLOBALS['wp_die_response'] = isset($args['response']) ? (int) $args['response'] : 500;
    throw new Exception('WP_DIE: ' . $GLOBALS['wp_die_response']);
}
function check_admin_referer($action, $name = '_wpnonce') {
    if (!$GLOBALS['mock_nonce_ok']) {
        throw new Exception('NONCE_VERIFICATION_FAILED');
    }
    return true;
}
function wp_create_nonce($action = -1) { return 'mock_nonce_' . $action; }
function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
    $html = '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr(wp_create_nonce($action)) . '" />';
    if ($echo) { echo $html; }
    return $html;
}
function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '') {
    $GLOBALS['mock_admin_submenus'][] = array(
        'parent'     => $parent_slug,
        'page_title' => $page_title,
        'capability' => $capability,
        'menu_slug'  => $menu_slug,
    );
}
function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
function admin_url($path = '') { return 'https://example.com/wp-admin/' . $path; }
function add_query_arg($key, $value, $url = '') { return $url . '&' . $key . '=' . $value; }

// Load necessary plugin source files
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/user-roles.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/pricing.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/settings.php';
require_once RYOKOURENT_PLUGIN_DIR . 'admin/admin-settings.php';

// Test runner counters
$test_count = 0;
$pass_count = 0;

function run_test($description, $assertion) {
    global $test_count, $pass_count;
    $test_count++;
    if ($assertion) {
        $pass_count++;
        echo "  [PASS] " . $description . PHP_EOL;
    } else {
        echo "  [FAIL] " . $description . PHP_EOL;
    }
}

// Fixture setup helper
function reset_test_environment() {
    $GLOBALS['mock_options']        = array();
    $GLOBALS['mock_post_meta']      = array();
    $GLOBALS['mock_posts']          = array();
    $GLOBALS['mock_current_caps']   = array();
    $GLOBALS['wp_die_called']       = false;
    $GLOBALS['wp_die_response']     = 0;
    $GLOBALS['wp_die_message']      = '';
    $GLOBALS['mock_nonce_ok']       = true;

    // Seed mock terms
    $GLOBALS['mock_terms'] = array(
        (object) array('slug' => 'beat-series', 'name' => 'Honda BeAT Series', 'count' => 3),
        (object) array('slug' => 'scoopy-vario', 'name' => 'Honda Scoopy & Vario', 'count' => 3),
        (object) array('slug' => 'trail-adventure', 'name' => 'Trail Adventure (Bromo)', 'count' => 1),
    );

    // Seed 7 standard fleet motorcycles per blueprint
    $fleet = array(
        1 => array('title' => 'Honda BeAT Deluxe', 'category' => 'beat-series', 'daily' => 85000, 'weekly' => 500000, 'monthly' => 1600000),
        2 => array('title' => 'Honda BeAT CBS', 'category' => 'beat-series', 'daily' => 80000, 'weekly' => 480000, 'monthly' => 1500000),
        3 => array('title' => 'Honda BeAT Street', 'category' => 'beat-series', 'daily' => 85000, 'weekly' => 500000, 'monthly' => 1600000),
        4 => array('title' => 'Honda Scoopy', 'category' => 'scoopy-vario', 'daily' => 95000, 'weekly' => 570000, 'monthly' => 1800000),
        5 => array('title' => 'Honda Vario 125', 'category' => 'scoopy-vario', 'daily' => 100000, 'weekly' => 600000, 'monthly' => 1900000),
        6 => array('title' => 'Honda Vario 160', 'category' => 'scoopy-vario', 'daily' => 130000, 'weekly' => 780000, 'monthly' => 2400000),
        7 => array('title' => 'Trail CRF 150L', 'category' => 'trail-adventure', 'daily' => 250000, 'weekly' => 1500000, 'monthly' => 4500000),
    );

    foreach ($fleet as $id => $data) {
        $GLOBALS['mock_posts'][$id] = array(
            'post_type' => 'motor',
            'title'     => $data['title'],
            'category'  => $data['category'],
        );
        $GLOBALS['mock_post_meta'][$id] = array(
            '_ryokou_price_daily'   => $data['daily'],
            '_ryokou_price_weekly'  => $data['weekly'],
            '_ryokou_price_monthly' => $data['monthly'],
        );
    }
}

echo "=================================================================" . PHP_EOL;
echo "RUNNING ADMIN SETTINGS & BULK PRICING TEST SUITE (TASK-024)" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

// -----------------------------------------------------------------------------
echo "\n1. Konfigurasi Default & Opsi Pengaturan:\n";
reset_test_environment();

$defaults = ryokourent_get_default_settings();
run_test('Default WA number terdefinisi sesuai blueprint', !empty($defaults['wa_number']) && '62895384017772' === $defaults['wa_number']);
run_test('Default jam operasional buka adalah 07:00 WIB', '07:00' === $defaults['operational_open']);
run_test('Default jam operasional tutup adalah 23:00 WIB', '23:00' === $defaults['operational_close']);
run_test('Default pool Dinoyo dan Batu terdefinisi', !empty($defaults['pool_dinoyo_name']) && !empty($defaults['pool_batu_name']));

$settings = ryokourent_get_settings();
run_test('ryokourent_get_settings() mengambil nilai default saat opsi database kosong', $settings['wa_number'] === $defaults['wa_number']);

$hours = ryokourent_get_operating_hours();
run_test('ryokourent_get_operating_hours() mengembalikan jam buka 07:00 dan tutup 23:00', '07:00' === $hours['open'] && '23:00' === $hours['close']);

// -----------------------------------------------------------------------------
echo "\n2. Keamanan & Otorisasi RBAC (Operator vs Administrator):\n";
reset_test_environment();

// Set user sebagai Operator (hanya manage_ryokourent_bookings)
$GLOBALS['mock_current_caps'] = array(
    'read'                        => true,
    'manage_ryokourent_bookings'  => true,
);

run_test('Operator tidak memiliki wewenang manage_settings', !ryokourent_current_user_can_manage_settings());

$caught_die = false;
try {
    ryokourent_check_settings_permission_or_die();
} catch (Exception $e) {
    $caught_die = true;
}
run_test('Penjaga akses server-side memicu HTTP 403 wp_die untuk Operator', $caught_die && 403 === $GLOBALS['wp_die_response']);

// Operator mencoba menyimpan pengaturan umum
$save_attempt = ryokourent_save_general_settings(array('wa_number' => '0895384017772'));
run_test('Operator ditolak saat memanggil ryokourent_save_general_settings()', !$save_attempt['success']);

// Operator mencoba mengeksekusi bulk price update
$bulk_attempt = ryokourent_apply_bulk_price_adjustment(array('category' => 'all', 'type' => 'nominal', 'amount' => 10000));
run_test('Operator ditolak saat memanggil ryokourent_apply_bulk_price_adjustment()', !$bulk_attempt['success']);

// Beralih ke Administrator (manage_ryokourent_settings)
$GLOBALS['mock_current_caps'] = array(
    'manage_ryokourent_settings' => true,
    'manage_options'             => true,
);
run_test('Administrator memiliki wewenang manage_settings', ryokourent_current_user_can_manage_settings());

$caught_admin_die = false;
try {
    ryokourent_check_settings_permission_or_die();
} catch (Exception $e) {
    $caught_admin_die = true;
}
run_test('Administrator lolos penjaga akses server-side tanpa 403', !$caught_admin_die);

// -----------------------------------------------------------------------------
echo "\n3. Validasi & Penyimpanan Pengaturan Umum (General Settings):\n";
reset_test_environment();
$GLOBALS['mock_current_caps']['manage_ryokourent_settings'] = true;

// Valid input
$valid_input = array(
    'wa_number'             => '0895384017772',
    'wa_number_secondary'   => '081234567890',
    'wa_default_template'   => 'Halo Admin, booking motor baru...',
    'operational_open'      => '08:00',
    'operational_close'     => '22:00',
    'pool_dinoyo_name'      => 'Pool Dinoyo Baru',
    'pool_dinoyo_address'   => 'Jl. MT Haryono No. 128',
    'pool_dinoyo_maps'      => 'https://maps.google.com/?q=Dinoyo',
    'pool_batu_name'        => 'Pool Batu Baru',
    'pool_batu_address'     => 'Jl. Diponegoro No. 45',
    'pool_batu_maps'        => 'https://maps.google.com/?q=Batu',
);

$res = ryokourent_save_general_settings($valid_input);
run_test('Penyimpanan input valid berhasil', $res['success']);
run_test('Nomor WA utama dinormalisasi menjadi format 628...', '62895384017772' === get_option('ryokourent_wa_number'));
run_test('Nomor WA sekunder dinormalisasi menjadi format 628...', '6281234567890' === get_option('ryokourent_wa_number_secondary'));
run_test('Jam buka operasional tersimpan 08:00', '08:00' === get_option('ryokourent_operational_open'));
run_test('Jam tutup operasional tersimpan 22:00', '22:00' === get_option('ryokourent_operational_close'));
run_test('Helper ryokourent_get_default_wa_number() mencerminkan opsi baru', '62895384017772' === ryokourent_get_default_wa_number());

// Invalid phone number
$invalid_wa = $valid_input;
$invalid_wa['wa_number'] = '12345';
$res_wa = ryokourent_save_general_settings($invalid_wa);
run_test('Nomor WA tidak valid ditolak', !$res_wa['success'] && isset($res_wa['errors']['wa_number']));

// Invalid operating hours format
$invalid_hours_fmt = $valid_input;
$invalid_hours_fmt['operational_open'] = '25:00';
$res_hours_fmt = ryokourent_save_general_settings($invalid_hours_fmt);
run_test('Format jam operasional salah ditolak', !$res_hours_fmt['success'] && isset($res_hours_fmt['errors']['operational_open']));

// Invalid chronological hours (close <= open)
$invalid_chron = $valid_input;
$invalid_chron['operational_open']  = '20:00';
$invalid_chron['operational_close'] = '08:00';
$res_chron = ryokourent_save_general_settings($invalid_chron);
run_test('Jam tutup sebelum jam buka ditolak', !$res_chron['success'] && isset($res_chron['errors']['operational_close']));

// -----------------------------------------------------------------------------
echo "\n4. Validasi Perhitungan Penyesuaian Tarif (ADR-012 Batasan Keamanan):\n";
reset_test_environment();

// Nominal positif
$calc1 = ryokourent_calculate_adjusted_price(85000, 'nominal', 10000);
run_test('Nominal positif +10.000: 85.000 -> 95.000', $calc1['is_valid'] && 95000 === $calc1['new_price']);

// Nominal negatif sah
$calc2 = ryokourent_calculate_adjusted_price(85000, 'nominal', -5000);
run_test('Nominal negatif sah -5.000: 85.000 -> 80.000', $calc2['is_valid'] && 80000 === $calc2['new_price']);

// Nominal menghasilkan harga <= 0
$calc3 = ryokourent_calculate_adjusted_price(85000, 'nominal', -85000);
run_test('Nominal menghasilkan harga Rp 0 ditolak', !$calc3['is_valid'] && 0 === $calc3['new_price']);

$calc4 = ryokourent_calculate_adjusted_price(85000, 'nominal', -100000);
run_test('Nominal menghasilkan harga minus ditolak', !$calc4['is_valid']);

// Persentase positif sah dengan pembulatan kelipatan seribu
$calc5 = ryokourent_calculate_adjusted_price(85000, 'percentage', 10); // 85.000 + 8.500 = 93.500 -> dibulatkan ke 94.000
run_test('Persentase +10% dibulatkan ke kelipatan seribu: 85.000 -> 94.000', $calc5['is_valid'] && 94000 === $calc5['new_price']);

$calc6 = ryokourent_calculate_adjusted_price(100000, 'percentage', 20); // 100.000 + 20.000 = 120.000
run_test('Persentase +20%: 100.000 -> 120.000', $calc6['is_valid'] && 120000 === $calc6['new_price']);

// Persentase diskon negatif sah
$calc7 = ryokourent_calculate_adjusted_price(100000, 'percentage', -10); // 90.000
run_test('Persentase diskon -10%: 100.000 -> 90.000', $calc7['is_valid'] && 90000 === $calc7['new_price']);

// Persentase ekstrem > 200% ditolak
$calc8 = ryokourent_calculate_adjusted_price(100000, 'percentage', 250);
run_test('Persentase kenaikan ekstrem > 200% ditolak', !$calc8['is_valid']);

// Persentase diskon ekstrem < -50% ditolak
$calc9 = ryokourent_calculate_adjusted_price(100000, 'percentage', -60);
run_test('Persentase diskon ekstrem < -50% ditolak', !$calc9['is_valid']);

// Nilai penyesuaian nol ditolak
$calc10 = ryokourent_calculate_adjusted_price(100000, 'nominal', 0);
run_test('Penyesuaian nilai 0 ditolak', !$calc10['is_valid']);

// -----------------------------------------------------------------------------
echo "\n5. Penerapan Multi-Update Tarif Massal Armada (Bulk Update per Kategori):\n";
reset_test_environment();
$GLOBALS['mock_current_caps']['manage_ryokourent_settings'] = true;

// Uji coba kenaikan harga kategori BeAT +10.000 (sesuai spesifikasi task)
$bulk1 = ryokourent_apply_bulk_price_adjustment(array(
    'category'   => 'beat-series',
    'type'       => 'nominal',
    'amount'     => 10000,
    'rate_types' => array('daily'),
));

run_test('Bulk update BeAT Series +10.000 berhasil', $bulk1['success']);
run_test('3 model motor BeAT ter-update', 3 === $bulk1['updated_count']);

// Verifikasi harga baru motor BeAT (ID 1, 2, 3)
run_test('BeAT Deluxe harian naik dari 85.000 ke 95.000', 95000 === (int) get_post_meta(1, '_ryokou_price_daily', true));
run_test('BeAT CBS harian naik dari 80.000 ke 90.000', 90000 === (int) get_post_meta(2, '_ryokou_price_daily', true));
run_test('BeAT Street harian naik dari 85.000 ke 95.000', 95000 === (int) get_post_meta(3, '_ryokou_price_daily', true));

// Verifikasi motor kategori lain TIDAK berubah (Scoopy & Vario tetap)
run_test('Scoopy (kategori lain) tidak tersentuh (tetap 95.000)', 95000 === (int) get_post_meta(4, '_ryokou_price_daily', true));
run_test('CRF 150L (kategori lain) tidak tersentuh (tetap 250.000)', 250000 === (int) get_post_meta(7, '_ryokou_price_daily', true));

// Uji coba bulk update ke SEMUA armada ('all') persentase +15% untuk Harian & Mingguan
$bulk2 = ryokourent_apply_bulk_price_adjustment(array(
    'category'   => 'all',
    'type'       => 'percentage',
    'amount'     => 15,
    'rate_types' => array('daily', 'weekly'),
));

run_test('Bulk update semua armada +15% berhasil', $bulk2['success']);
run_test('Seluruh 7 armada motor diperbarui', 7 === $bulk2['updated_count']);

// -----------------------------------------------------------------------------
echo "\n6. Atomisitas Two-Pass Validation (Rollback jika ada 1 unit <= 0):\n";
reset_test_environment();
$GLOBALS['mock_current_caps']['manage_ryokourent_settings'] = true;

// Buat satu motor BeAT berharga murah (misal 50.000)
$GLOBALS['mock_post_meta'][2]['_ryokou_price_daily'] = 50000;

// Coba diskon nominal -60.000 pada BeAT Series (ID 1 & 3 sah, tapi ID 2 menjadi -10.000)
$bulk_fail = ryokourent_apply_bulk_price_adjustment(array(
    'category'   => 'beat-series',
    'type'       => 'nominal',
    'amount'     => -60000,
    'rate_types' => array('daily'),
));

run_test('Two-pass mendeteksi pelanggaran batas dan membatalkan seluruh operasi', !$bulk_fail['success']);
run_test('Unit ID 1 tetap pada harga asli 85.000 (tidak ada partial update)', 85000 === (int) get_post_meta(1, '_ryokou_price_daily', true));
run_test('Unit ID 2 tetap pada harga asli 50.000', 50000 === (int) get_post_meta(2, '_ryokou_price_daily', true));
run_test('Unit ID 3 tetap pada harga asli 85.000', 85000 === (int) get_post_meta(3, '_ryokou_price_daily', true));

// -----------------------------------------------------------------------------
echo "\n7. Integrasi Helper Pricing ryokourent_update_motor_pricing():\n";
reset_test_environment();

$up1 = ryokourent_update_motor_pricing(1, 110000, 650000, 2000000);
run_test('ryokourent_update_motor_pricing() memperbarui harian, mingguan, bulanan', $up1);
run_test('Tarif harian ID 1 tersimpan 110.000', 110000 === (int) get_post_meta(1, '_ryokou_price_daily', true));
run_test('Tarif mingguan ID 1 tersimpan 650.000', 650000 === (int) get_post_meta(1, '_ryokou_price_weekly', true));
run_test('Tarif bulanan ID 1 tersimpan 2.000.000', 2000000 === (int) get_post_meta(1, '_ryokou_price_monthly', true));

$up_invalid = ryokourent_update_motor_pricing(1, -5000);
run_test('ryokourent_update_motor_pricing() menolak tarif harian <= 0', !$up_invalid);

// -----------------------------------------------------------------------------
echo "\n8. Pendaftaran Menu Admin & Submenu:\n";
$GLOBALS['mock_admin_submenus'] = array();
ryokourent_register_settings_menu();

$found_penyewaan_sub = false;
$found_motor_sub     = false;

foreach ($GLOBALS['mock_admin_submenus'] as $sub) {
    if ('edit.php?post_type=penyewaan' === $sub['parent'] && 'manage_ryokourent_settings' === $sub['capability']) {
        $found_penyewaan_sub = true;
    }
    if ('edit.php?post_type=motor' === $sub['parent'] && 'manage_ryokourent_settings' === $sub['capability']) {
        $found_motor_sub = true;
    }
}

run_test('Submenu Pengaturan terdaftar di bawah menu Penyewaan dengan capability manage_ryokourent_settings', $found_penyewaan_sub);
run_test('Submenu Pengaturan Tarif terdaftar di bawah menu Motor dengan capability manage_ryokourent_settings', $found_motor_sub);

// -----------------------------------------------------------------------------
echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
echo "-----------------------------------------------------------------\n";
exit($pass_count === $test_count ? 0 : 1);
