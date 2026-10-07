<?php
/**
 * Test Suite for FAQ, Pool Locations, and Bromo Rules (TASK-025)
 *
 * Verifies:
 * - 7 official FAQ points defined according to the Ryokourent blueprint:
 *   1. Dokumen persyaratan (e-KTP asli + 2 pendukung sah).
 *   2. Alasan matik dilarang keras ke Lautan Pasir Bromo (CVT debu, overheat, slip).
 *   3. Kewajiban Honda Trail CRF 150L untuk rute Bromo.
 *   4. Fasilitas antar-jemput stasiun/hotel fleksibel sikon.
 *   5. Jam operasional pelayanan (07:00 - 23:00 WIB, terintegrasi dinamis dari ryokourent_get_settings).
 *   6. Aturan 24 jam dan batas toleransi keterlambatan (overtime grace period 2 jam).
 *   7. Batas wilayah Malang Raya & Batu, izin tertulis untuk keluar kota.
 * - 2 Lokasi Pool resmi (Dinoyo Malang & Diponegoro Batu) beserta alamat, jam operasional, dan link Google Maps.
 * - Dynamic settings fallback integration: perubahan jam operasional dan link Google Maps di option memengaruhi FAQ dan Pool cards.
 * - Render HTML output untuk:
 *   * ryokourent_render_faq_section()
 *   * ryokourent_render_pool_locations_section()
 *   * ryokourent_render_bromo_advisory_banner()
 * - Shortcodes registration and output:
 *   * [ryokou_faq]
 *   * [ryokou_pools]
 *   * [ryokou_bromo_advisory]
 * - Child theme template files presence (templates/template-faq-pool.php and template-faq-pool.php).
 * - Output escaping and XSS safety (esc_html, esc_attr, esc_url).
 *
 * Jalankan: php tests/test-faq-pool.php
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
$GLOBALS['mock_options']      = array();
$GLOBALS['mock_shortcodes']   = array();
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

function esc_html__($text, $domain = 'default') {
    return esc_html($text);
}

function esc_attr__($text, $domain = 'default') {
    return esc_attr($text);
}

function __($text, $domain = 'default') {
    return $text;
}

function _e($text, $domain = 'default') {
    echo $text;
}

function esc_html_e($text, $domain = 'default') {
    echo esc_html($text);
}

function esc_attr_e($text, $domain = 'default') {
    echo esc_attr($text);
}

function wp_parse_args($args, $defaults = array()) {
    return array_merge($defaults, (array) $args);
}

function add_shortcode($tag, $callback) {
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

function shortcode_atts($pairs, $atts, $shortcode = '') {
    $atts = (array) $atts;
    $out = array();
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

// -----------------------------------------------------------------------------
// Load Application Modules Under Test
// -----------------------------------------------------------------------------
require_once RYOKOURENT_PLUGIN_DIR . 'includes/settings.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/templates.php';
require_once RYOKOURENT_PLUGIN_DIR . 'public/shortcodes.php';

// -----------------------------------------------------------------------------
// Test Runner Harness
// -----------------------------------------------------------------------------
$test_count = 0;
$pass_count = 0;
$fail_count = 0;

function run_test($name, $assertion, $details = '') {
    global $test_count, $pass_count, $fail_count;
    $test_count++;
    if ($assertion) {
        $pass_count++;
        echo "  [PASS] {$name}\n";
    } else {
        $fail_count++;
        echo "  [FAIL] {$name}\n";
        if (!empty($details)) {
            echo "         Details: {$details}\n";
        }
    }
}

echo "=================================================================\n";
echo "RYOKOURENT TEST SUITE: FAQ, POOL LOCATIONS & BROMO RULES (TASK-025)\n";
echo "=================================================================\n\n";

// -----------------------------------------------------------------------------
// Test 1: 7 Official FAQ Points Retrieval and Content Integrity
// -----------------------------------------------------------------------------
echo "1. Pengujian 7 Poin FAQ Resmi Sesuai Blueprint:\n";

$faqs = ryokourent_get_faq_items();
run_test('Jumlah item FAQ tepat 7 poin sesuai spesifikasi', count($faqs) === 7, 'Count: ' . count($faqs));

// 1.1 Persyaratan dokumen e-KTP + 2 pendukung
$faq_dokumen = isset($faqs[0]) ? $faqs[0] : array();
run_test('FAQ 1 adalah tentang dokumen persyaratan e-KTP', strpos($faq_dokumen['question'], 'dokumen persyaratan') !== false);
run_test('FAQ 1 jawaban memuat e-KTP Asli dan 2 dokumen pendukung', strpos($faq_dokumen['answer'], 'e-KTP Asli') !== false && strpos($faq_dokumen['answer'], '2 (dua) dokumen identitas pendukung') !== false);

// 1.2 Larangan keras matik ke Lautan Pasir Bromo
$faq_larangan = isset($faqs[1]) ? $faqs[1] : array();
run_test('FAQ 2 adalah tentang larangan motor matik ke Bromo', strpos($faq_larangan['question'], 'DILARANG KERAS ke Lautan Pasir Bromo') !== false);
run_test('FAQ 2 menjelaskan alasan transmisi CVT overheat & selip', strpos($faq_larangan['answer'], 'CVT') !== false && strpos($faq_larangan['answer'], 'selip') !== false);

// 1.3 Kewajiban Trail CRF 150L untuk Bromo
$faq_crf = isset($faqs[2]) ? $faqs[2] : array();
run_test('FAQ 3 adalah tentang motor wajib untuk trip Bromo', strpos($faq_crf['question'], 'WAJIB digunakan') !== false && strpos($faq_crf['question'], 'Bromo') !== false);
run_test('FAQ 3 menyebutkan Honda Trail CRF 150L, Showa, dan ban pacul', strpos($faq_crf['answer'], 'Honda Trail CRF 150L') !== false && strpos($faq_crf['answer'], 'Showa') !== false && strpos($faq_crf['answer'], 'dual-purpose') !== false);

// 1.4 Layanan antar-jemput stasiun/hotel fleksibel sikon
$faq_antar = isset($faqs[3]) ? $faqs[3] : array();
run_test('FAQ 4 adalah tentang antar-jemput stasiun atau hotel', strpos($faq_antar['question'], 'diantar ke stasiun atau tempat menginap') !== false);
run_test('FAQ 4 menyebutkan 2 pool, Stasiun Malang, dan fleksibilitas sikon', strpos($faq_antar['answer'], 'Stasiun Malang Kota Baru') !== false && strpos($faq_antar['answer'], 'situasi dan kondisi (sikon)') !== false);

// 1.5 Jam operasional (07:00 - 23:00 WIB)
$faq_jam = isset($faqs[4]) ? $faqs[4] : array();
run_test('FAQ 5 adalah tentang jam operasional pelayanan', strpos($faq_jam['question'], 'jam operasional') !== false);
run_test('FAQ 5 memuat jam buka dan tutup 07:00 – 23:00 WIB', strpos($faq_jam['answer'], '07:00 – 23:00 WIB') !== false);

// 1.6 Overtime grace period 2 jam
$faq_overtime = isset($faqs[5]) ? $faqs[5] : array();
run_test('FAQ 6 adalah tentang toleransi overtime sewa 24 jam', strpos($faq_overtime['question'], 'durasi sewa 24 jam') !== false);
run_test('FAQ 6 menyebutkan grace period gratis hingga 2 jam', strpos($faq_overtime['answer'], 'toleransi keterlambatan') !== false && strpos($faq_overtime['answer'], '2 jam') !== false);

// 1.7 Batas wilayah Malang Raya & Batu, izin tertulis luar kota
$faq_wilayah = isset($faqs[6]) ? $faqs[6] : array();
run_test('FAQ 7 adalah tentang batas wilayah dan keluar kota', strpos($faq_wilayah['question'], 'keluar wilayah Malang Raya') !== false);
run_test('FAQ 7 menegaskan kewajiban izin tertulis dari Admin', strpos($faq_wilayah['answer'], 'izin tertulis') !== false);

// -----------------------------------------------------------------------------
// Test 2: Dynamic Settings Integration on FAQ Hours
// -----------------------------------------------------------------------------
echo "\n2. Pengujian Sinkronisasi Dinamis Pengaturan Jam Operasional ke FAQ:\n";

update_option('ryokourent_operational_open', '06:30');
update_option('ryokourent_operational_close', '22:30');

$dynamic_faqs = ryokourent_get_faq_items();
run_test('Pertanyaan FAQ 5 otomatis terupdate dengan jam kustom 06:30 dan 22:30', strpos($dynamic_faqs[4]['question'], '06:30') !== false || strpos($dynamic_faqs[4]['answer'], '06:30 – 22:30 WIB') !== false);

// Reset options back to defaults
update_option('ryokourent_operational_open', '07:00');
update_option('ryokourent_operational_close', '23:00');

// -----------------------------------------------------------------------------
// Test 3: Dua Pool Resmi (Dinoyo Malang & Diponegoro Batu)
// -----------------------------------------------------------------------------
echo "\n3. Pengujian Detail 2 Lokasi Pool Resmi:\n";

$pools = ryokourent_get_pool_details();
run_test('Jumlah pool resmi tepat 2 lokasi', count($pools) === 2);
run_test('Pool 1 terdaftar dengan ID pool_dinoyo', isset($pools['pool_dinoyo']));
run_test('Pool 2 terdaftar dengan ID pool_batu', isset($pools['pool_batu']));

// Verifikasi Pool Dinoyo
$dinoyo = $pools['pool_dinoyo'];
run_test('Pool Dinoyo berlokasi di Lowokwaru Kota Malang', strpos($dinoyo['address'], 'Lowokwaru') !== false && strpos($dinoyo['address'], 'Kota Malang') !== false);
run_test('Pool Dinoyo memiliki link Google Maps aktif', strpos($dinoyo['maps_url'], 'maps.google.com') !== false);
run_test('Pool Dinoyo memiliki highlight dekat kampus UB/UIN dan stasiun', count($dinoyo['highlights']) >= 3);

// Verifikasi Pool Diponegoro Batu
$batu = $pools['pool_batu'];
run_test('Pool Batu berlokasi di Diponegoro Kota Wisata Batu', strpos($batu['address'], 'Diponegoro') !== false && strpos($batu['address'], 'Batu') !== false);
run_test('Pool Batu memiliki link Google Maps aktif', strpos($batu['maps_url'], 'maps.google.com') !== false);
run_test('Pool Batu memiliki highlight dekat Jatim Park & Alun-Alun Batu', count($batu['highlights']) >= 3);

// -----------------------------------------------------------------------------
// Test 4: Dynamic Settings Override for Pool Maps URL and Address
// -----------------------------------------------------------------------------
echo "\n4. Pengujian Custom Options pada Pool Details:\n";

update_option('ryokourent_pool_dinoyo_address', 'Jl. MT Haryono No. 999, Malang');
update_option('ryokourent_pool_dinoyo_maps', 'https://maps.google.com/?cid=123456');

$custom_pools = ryokourent_get_pool_details();
run_test('Alamat Pool Dinoyo dinamis mengambil nilai dari get_option', $custom_pools['pool_dinoyo']['address'] === 'Jl. MT Haryono No. 999, Malang');
run_test('URL Google Maps Pool Dinoyo dinamis mengambil nilai dari get_option', $custom_pools['pool_dinoyo']['maps_url'] === 'https://maps.google.com/?cid=123456');

// Reset options back
unset($GLOBALS['mock_options']['ryokourent_pool_dinoyo_address']);
unset($GLOBALS['mock_options']['ryokourent_pool_dinoyo_maps']);

// -----------------------------------------------------------------------------
// Test 5: Render HTML Output for FAQ Accordion
// -----------------------------------------------------------------------------
echo "\n5. Pengujian Render Komponen HTML FAQ Accordion:\n";

$faq_html = ryokourent_render_faq_section();
run_test('HTML FAQ memiliki kontainer section ber-id syarat-faq', strpos($faq_html, 'id="syarat-faq"') !== false);
run_test('HTML FAQ memuat banner syarat dokumen e-KTP', strpos($faq_html, 'ryokou-req-banner') !== false && strpos($faq_html, 'Wajib e-KTP') !== false);
run_test('HTML FAQ memuat tombol trigger accordion ber-atribut ARIA aria-expanded="false"', strpos($faq_html, 'aria-expanded="false"') !== false);
run_test('HTML FAQ memuat aria-controls yang merujuk ke id konten', strpos($faq_html, 'aria-controls="faq-dokumen-content"') !== false);
run_test('HTML FAQ merender seluruh 7 pertanyaan', substr_count($faq_html, 'ryokou-accordion-item') === 7);
run_test('HTML FAQ lolos escaping tanpa tag berbahaya', strpos($faq_html, '<script>') === false);

// -----------------------------------------------------------------------------
// Test 6: Render HTML Output for Pool Locations
// -----------------------------------------------------------------------------
echo "\n6. Pengujian Render Komponen HTML Pool Locations:\n";

$pools_html = ryokourent_render_pool_locations_section();
run_test('HTML Pools memiliki kontainer section ber-id lokasi-pool', strpos($pools_html, 'id="lokasi-pool"') !== false);
run_test('HTML Pools merender kartu artikel untuk kedua pool', strpos($pools_html, 'id="pool-dinoyo"') !== false && strpos($pools_html, 'id="pool-batu"') !== false);
run_test('HTML Pools memuat tombol Google Maps dengan rel noopener noreferrer', strpos($pools_html, 'target="_blank"') !== false && strpos($pools_html, 'rel="noopener noreferrer"') !== false);
run_test('HTML Pools memuat catatan layanan antar-jemput sikon', strpos($pools_html, 'ryokou-delivery-notice') !== false && strpos($pools_html, 'Situasi & Kondisi (Sikon)') !== false);

// -----------------------------------------------------------------------------
// Test 7: Render HTML Output for Bromo Mandatory Advisory Banner
// -----------------------------------------------------------------------------
echo "\n7. Pengujian Render Bromo Mandatory Advisory Banner:\n";

$bromo_html = ryokourent_render_bromo_advisory_banner();
run_test('Banner Bromo memiliki peran role="alert"', strpos($bromo_html, 'role="alert"') !== false);
run_test('Banner Bromo menegaskan unit matik dilarang ke lautan pasir', strpos($bromo_html, 'Unit Matik Dilarang ke Lautan Pasir Bromo') !== false);
run_test('Banner Bromo mewajibkan Honda Trail CRF 150L', strpos($bromo_html, 'Wajib Honda Trail CRF 150L') !== false);
run_test('Banner Bromo memiliki tombol quick trigger filter data-filter="trail-adventure"', strpos($bromo_html, 'data-filter="trail-adventure"') !== false);

// -----------------------------------------------------------------------------
// Test 8: Shortcodes Registration & Execution
// -----------------------------------------------------------------------------
echo "\n8. Pengujian Pendaftaran dan Eksekusi Shortcode:\n";

run_test('Shortcode [ryokou_faq] terdaftar di WordPress', isset($GLOBALS['mock_shortcodes']['ryokou_faq']));
run_test('Shortcode [ryokou_pools] terdaftar di WordPress', isset($GLOBALS['mock_shortcodes']['ryokou_pools']));
run_test('Shortcode [ryokou_bromo_advisory] terdaftar di WordPress', isset($GLOBALS['mock_shortcodes']['ryokou_bromo_advisory']));

// Eksekusi shortcode callback
$GLOBALS['mock_enqueued_styles']  = array();
$GLOBALS['mock_enqueued_scripts'] = array();

$sc_faq_output = call_user_func($GLOBALS['mock_shortcodes']['ryokou_faq'], array('title' => 'FAQ Kustom'));
run_test('Callback shortcode [ryokou_faq] meng-enqueue stylesheet ryokourent-public', in_array('ryokourent-public', $GLOBALS['mock_enqueued_styles'], true));
run_test('Callback shortcode [ryokou_faq] meng-enqueue script ryokourent-filter', in_array('ryokourent-filter', $GLOBALS['mock_enqueued_scripts'], true));
run_test('Callback shortcode [ryokou_faq] merender judul kustom', strpos($sc_faq_output, 'FAQ Kustom') !== false);

$sc_pools_output = call_user_func($GLOBALS['mock_shortcodes']['ryokou_pools'], array('title' => 'Pool Kustom'));
run_test('Callback shortcode [ryokou_pools] merender output valid', strpos($sc_pools_output, 'Pool Kustom') !== false);

$sc_bromo_output = call_user_func($GLOBALS['mock_shortcodes']['ryokou_bromo_advisory']);
run_test('Callback shortcode [ryokou_bromo_advisory] merender banner peringatan Bromo', strpos($sc_bromo_output, 'ATURAN WAJIB TRIP BROMO') !== false);

// -----------------------------------------------------------------------------
// Test 9: Child Theme Template File Integrity
// -----------------------------------------------------------------------------
echo "\n9. Pengujian Integritas Template GeneratePress Child Theme:\n";

$child_theme_dir = dirname(dirname(RYOKOURENT_PLUGIN_DIR)) . '/themes/generatepress-child';
$template_partial = $child_theme_dir . '/templates/template-faq-pool.php';
$template_root    = $child_theme_dir . '/template-faq-pool.php';

run_test('Berkas template partial templates/template-faq-pool.php tersedia', file_exists($template_partial));
run_test('Berkas root template pointer template-faq-pool.php tersedia', file_exists($template_root));

$template_content = file_get_contents($template_partial);
run_test('Template partial memanggil ryokourent_render_pool_locations_section()', strpos($template_content, 'ryokourent_render_pool_locations_section') !== false);
run_test('Template partial memanggil ryokourent_render_faq_section()', strpos($template_content, 'ryokourent_render_faq_section') !== false);
run_test('Template partial memanggil ryokourent_render_bromo_advisory_banner()', strpos($template_content, 'ryokourent_render_bromo_advisory_banner') !== false);

echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
if ($fail_count > 0) {
    echo "PERINGATAN: {$fail_count} pengujian gagal!\n";
}
echo "-----------------------------------------------------------------\n";

exit($fail_count === 0 ? 0 : 1);
