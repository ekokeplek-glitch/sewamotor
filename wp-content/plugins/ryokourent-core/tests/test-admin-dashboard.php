<?php
/**
 * Test Suite Dashboard Operasional & Kolom Booking (TASK-022).
 *
 * Jalankan: php tests/test-admin-dashboard.php
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}
if (!defined('RYOKOURENT_PLUGIN_DIR')) {
    define('RYOKOURENT_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

$GLOBALS['mock_transients'] = array();
$GLOBALS['mock_meta']       = array();
$GLOBALS['mock_fixture']    = array();
$GLOBALS['mock_query_args'] = array();
$GLOBALS['mock_query_count'] = 0;
$GLOBALS['mock_can']        = true;
$GLOBALS['mock_actions']    = array();
$GLOBALS['mock_filters']    = array();
$GLOBALS['mock_submenu']    = null;

function add_action($tag, $cb, $priority = 10, $args = 1) { $GLOBALS['mock_actions'][] = array($tag, $cb); }
function add_filter($tag, $cb, $priority = 10, $args = 1) { $GLOBALS['mock_filters'][] = array($tag, $cb); }
function add_submenu_page($parent, $title, $menu, $cap, $slug, $cb) { $GLOBALS['mock_submenu'] = compact('parent', 'cap', 'slug', 'cb'); }
function __($t, $d = 'default') { return $t; }
function esc_html__($t, $d = 'default') { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_url($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function admin_url($p = '') { return 'http://example.test/wp-admin/' . $p; }
function add_query_arg($a, $u) { return $u . '?' . http_build_query($a); }
function number_format_i18n($n) { return (string) $n; }
function absint($n) { return abs((int) $n); }
function get_the_title($id) { return 'Motor #' . $id . ' <b>'; }
function get_transient($k) { return $GLOBALS['mock_transients'][$k] ?? false; }
function set_transient($k, $v, $e = 0) { $GLOBALS['mock_transients'][$k] = $v; return true; }
function delete_transient($k) { unset($GLOBALS['mock_transients'][$k]); return true; }
function get_post_meta($id, $k, $single = false) { return $GLOBALS['mock_meta'][$id][$k] ?? ''; }
function get_post_type($id) { return 'penyewaan'; }
function current_user_can($cap) { return $GLOBALS['mock_can']; }
function wp_die($m, $t = '', $a = array()) { throw new Exception('WP_DIE_INVOKED: ' . ($a['response'] ?? 0)); }
function ryokourent_get_timezone() { return new DateTimeZone('Asia/Jakarta'); }
function ryokourent_get_now_wib($f = 'Y-m-d H:i:s') { return (new DateTime('now', ryokourent_get_timezone()))->format($f); }
function ryokourent_get_pool_locations() {
    return array(
        'pool_dinoyo'    => 'Pool Malang Dinoyo',
        'pool_batu'      => 'Pool <i>Batu</i>',
        'stasiun_malang' => 'Stasiun Malang',
        'antar_hotel'    => 'Antar ke Hotel',
    );
}
class WP_Query {
    public $posts = array();
    public function __construct($args) {
        $GLOBALS['mock_query_count']++;
        $s   = $args['post_status'];
        $key = is_array($s) ? 'today' : ('status_menunggu' === $s ? 'pending' : 'running');
        $GLOBALS['mock_query_args'][$key] = $args;
        $this->posts = $GLOBALS['mock_fixture'][$key] ?? array();
    }
}

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

require_once RYOKOURENT_PLUGIN_DIR . 'admin/dashboard.php';
require_once RYOKOURENT_PLUGIN_DIR . 'admin/booking-columns.php';

echo "=================================================================" . PHP_EOL;
echo "RUNNING ADMIN DASHBOARD TEST SUITE (TASK-022)" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

echo "\n1. Rentang hari & pemetaan lokasi:\n";
$r = ryokourent_dashboard_today_range('2026-10-07 15:30');
run_test('Rentang hari ini WIB: 00:00 s/d 00:00 besok', '2026-10-07 00:00' === $r['start'] && '2026-10-08 00:00' === $r['end']);
run_test('Label form "Pool Batu" dipetakan ke slug pool_batu', 'pool_batu' === ryokourent_dashboard_pickup_to_slug('Pool Batu'));
run_test('Label "Hotel/Homestay" dipetakan ke antar_hotel', 'antar_hotel' === ryokourent_dashboard_pickup_to_slug('Hotel/Homestay'));
run_test('Slug langsung tetap slug', 'pool_dinoyo' === ryokourent_dashboard_pickup_to_slug('pool_dinoyo'));

echo "\n2. Kalkulasi metrik:\n";
$GLOBALS['mock_fixture'] = array('pending' => array(1, 2), 'today' => array(3, 4, 5), 'running' => array(6, 7, 8));
$GLOBALS['mock_meta']    = array(
    6 => array('_ryokou_booking_pickup_loc' => 'Pool Batu'),
    7 => array('_ryokou_booking_pickup_loc' => 'pool_batu'),
    8 => array('_ryokou_booking_pickup_loc' => 'tidak-dikenal'),
);
$s = ryokourent_dashboard_get_stats(true);
run_test('Booking menunggu = 2', 2 === $s['pending']);
run_test('Unit disewa hari ini = 3', 3 === $s['rented_today']);
run_test('Unit aktif total = 3', 3 === $s['active_total']);
run_test('Pool Batu = 2 (label & slug digabung)', 2 === $s['pools']['pool_batu']['count']);
run_test('Lokasi tak dikenal masuk "Lainnya" = 1', 1 === $s['pools']['lainnya']['count']);
run_test('Pool Dinoyo = 0', 0 === $s['pools']['pool_dinoyo']['count']);

echo "\n3. Efisiensi query (reviewOP M12):\n";
foreach ($GLOBALS['mock_query_args'] as $key => $a) {
    run_test("Query '$key': fields=ids, no_found_rows, bukan -1", 'ids' === $a['fields'] && true === $a['no_found_rows'] && -1 !== $a['posts_per_page'] && $a['posts_per_page'] > 0);
}
$mq = $GLOBALS['mock_query_args']['today']['meta_query'];
run_test('Query hari ini memakai overlap: start < besok 00:00 dan end > hari ini 00:00', '<' === $mq[0]['compare'] && '>' === $mq[1]['compare'] && 'DATETIME' === $mq[0]['type']);

echo "\n4. Transient cache:\n";
$GLOBALS['mock_query_count'] = 0;
ryokourent_dashboard_get_stats();
run_test('Panggilan kedua dilayani transient (0 query)', 0 === $GLOBALS['mock_query_count']);
ryokourent_dashboard_flush_cache();
ryokourent_dashboard_get_stats();
run_test('Setelah flush, statistik dihitung ulang (3 query)', 3 === $GLOBALS['mock_query_count']);
$GLOBALS['mock_transients'][RYOKOURENT_DASHBOARD_CACHE_KEY]['date'] = '2000-01-01';
$GLOBALS['mock_query_count'] = 0;
ryokourent_dashboard_get_stats();
run_test('Cache bertanggal lama diabaikan', 3 === $GLOBALS['mock_query_count']);
ryokourent_dashboard_maybe_flush_on_transition('status_dikonfirmasi', 'status_menunggu', (object) array('post_type' => 'penyewaan'));
run_test('Perubahan status penyewaan membuang cache', false === get_transient(RYOKOURENT_DASHBOARD_CACHE_KEY));
ryokourent_dashboard_get_stats();
ryokourent_dashboard_maybe_flush_on_transition('publish', 'draft', (object) array('post_type' => 'post'));
run_test('Post type lain tidak membuang cache', false !== get_transient(RYOKOURENT_DASHBOARD_CACHE_KEY));

echo "\n5. Menu, otorisasi, dan escaping:\n";
ryokourent_dashboard_register_menu();
run_test('Submenu di bawah penyewaan dengan capability manage_ryokourent_bookings', 'edit.php?post_type=penyewaan' === $GLOBALS['mock_submenu']['parent'] && 'manage_ryokourent_bookings' === $GLOBALS['mock_submenu']['cap']);
$hooks = array_column($GLOBALS['mock_actions'], 0);
run_test('Hook admin_menu, transition_post_status, deleted_post terpasang', in_array('admin_menu', $hooks, true) && in_array('transition_post_status', $hooks, true) && in_array('deleted_post', $hooks, true));

$GLOBALS['mock_transients'][RYOKOURENT_DASHBOARD_CACHE_KEY] = array(
    'date' => ryokourent_dashboard_today_range()['date'], 'generated_at' => '10:00',
    'rented_today' => 1, 'pending' => 2, 'active_total' => 1, 'pools' => ryokourent_dashboard_tally_pools(array('pool_batu'), ryokourent_get_pool_locations()),
);
ob_start();
ryokourent_dashboard_render_page();
$html = ob_get_clean();
run_test('Label pool di-escape (tidak ada tag mentah)', false === strpos($html, '<i>Batu</i>') && false !== strpos($html, '&lt;i&gt;Batu'));
run_test('Quick link ke status_menunggu dan status_berjalan', false !== strpos($html, 'post_status=status_menunggu') && false !== strpos($html, 'post_status=status_berjalan'));

$GLOBALS['mock_can'] = false;
$blocked = false;
try { ryokourent_dashboard_render_page(); } catch (Exception $e) { $blocked = ('WP_DIE_INVOKED: 403' === $e->getMessage()); }
run_test('Tanpa capability: 403 Forbidden', $blocked);
$cols = ryokourent_booking_columns(array('cb' => 'x', 'title' => 'T', 'date' => 'D'));
run_test('Kolom booking tidak ditambah untuk user tanpa capability', !isset($cols['ryokourent_motor']));
$GLOBALS['mock_can'] = true;

echo "\n6. Kolom daftar booking:\n";
$cols = ryokourent_booking_columns(array('cb' => 'x', 'title' => 'T', 'date' => 'D'));
run_test('Kolom Motor, Jadwal, Total ditambahkan', isset($cols['ryokourent_motor'], $cols['ryokourent_jadwal'], $cols['ryokourent_total']));
$GLOBALS['mock_meta'][50] = array('_ryokou_booking_motor_id' => 9, '_ryokou_booking_start_datetime' => '2026-10-07 08:00', '_ryokou_booking_end_datetime' => '2026-10-08 08:00', '_ryokou_booking_total_price' => 255000);
ob_start(); ryokourent_booking_column_content('ryokourent_motor', 50); $m = ob_get_clean();
ob_start(); ryokourent_booking_column_content('ryokourent_total', 50); $t = ob_get_clean();
run_test('Nama motor di-escape', false === strpos($m, '<b>') && false !== strpos($m, '&lt;b&gt;'));
run_test('Total diformat Rupiah: Rp 255.000', 'Rp 255.000' === $t);

echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
echo "-----------------------------------------------------------------\n";
exit($pass_count === $test_count ? 0 : 1);
