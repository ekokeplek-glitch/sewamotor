<?php
/**
 * Test Suite Perubahan Status Booking: Quick Actions, Kuota & Validasi Plat (TASK-023).
 *
 * Jalankan: php tests/test-booking-status-actions.php
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

// ---------------------------------------------------------------------------
// Mock state
// ---------------------------------------------------------------------------
$GLOBALS['mock_hooks']       = array();
$GLOBALS['mock_meta']        = array();
$GLOBALS['mock_status']      = array();
$GLOBALS['mock_transients']  = array();
$GLOBALS['mock_overlap_ids'] = array();
$GLOBALS['mock_query_log']   = array();
$GLOBALS['mock_update_calls'] = array();
$GLOBALS['mock_update_fail'] = false;
$GLOBALS['mock_can']         = true;
$GLOBALS['mock_nonce_ok']    = true;
$GLOBALS['mock_user_id']     = 7;

// ---------------------------------------------------------------------------
// Mock fungsi WordPress
// ---------------------------------------------------------------------------
class WP_Error {
    public $code;
    public $message;
    public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
class Mock_WPDB {
    public $posts = 'wp_posts';
    public $log   = array();
    public function prepare($q) { $args = func_get_args(); array_shift($args); return $q . '|' . implode(',', $args); }
    public function get_var($q) { if (false !== strpos($q, 'GET_LOCK')) { $this->log[] = 'GET_LOCK'; return 1; } return null; }
    public function query($q) { if (false !== strpos($q, 'RELEASE_LOCK')) { $this->log[] = 'RELEASE_LOCK'; } return 1; }
    public function update($table, $data, $where) { $this->log[] = 'REVERT'; $GLOBALS['mock_status'][$where['ID']] = $data['post_status']; return 1; }
    public function count($what) { return count(array_keys($this->log, $what, true)); }
}
class WP_Query {
    public $posts = array();
    public function __construct($args) {
        $GLOBALS['mock_query_log'][] = $args;
        $ids = $GLOBALS['mock_overlap_ids'];
        if (!empty($args['post__not_in'])) {
            $ids = array_values(array_diff($ids, $args['post__not_in']));
        }
        $this->posts = $ids;
    }
}

function add_action($tag, $cb, $priority = 10, $args = 1) { $GLOBALS['mock_hooks'][$tag][] = $cb; }
function add_filter($tag, $cb, $priority = 10, $args = 1) { $GLOBALS['mock_hooks'][$tag][] = $cb; }
function __($t, $d = 'default') { return $t; }
function esc_html__($t, $d = 'default') { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
function esc_attr__($t, $d = 'default') { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); }
function esc_html($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function esc_attr($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function admin_url($p = '') { return 'http://example.test/wp-admin/' . $p; }
function absint($n) { return abs((int) $n); }
function sanitize_key($k) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $k)); }
function sanitize_text_field($s) { return trim(strip_tags((string) $s)); }
function wp_unslash($v) { return $v; }
function is_wp_error($x) { return $x instanceof WP_Error; }
function get_post_type($id) { return isset($GLOBALS['mock_status'][$id]) ? 'penyewaan' : false; }
function get_post_status($id) { return $GLOBALS['mock_status'][$id] ?? false; }
function get_post_meta($id, $k, $single = false) { return $GLOBALS['mock_meta'][$id][$k] ?? ''; }
function update_post_meta($id, $k, $v) { $GLOBALS['mock_meta'][$id][$k] = $v; return true; }
function get_transient($k) { return $GLOBALS['mock_transients'][$k] ?? false; }
function set_transient($k, $v, $e = 0) { $GLOBALS['mock_transients'][$k] = $v; return true; }
function delete_transient($k) { unset($GLOBALS['mock_transients'][$k]); return true; }
function get_current_user_id() { return $GLOBALS['mock_user_id']; }
function clean_post_cache($id) {}
function current_user_can($cap) { return $GLOBALS['mock_can']; }
function wp_verify_nonce($n, $a) { return $GLOBALS['mock_nonce_ok'] ? 1 : false; }
function wp_create_nonce($a) { return 'nonce_' . $a; }
function check_admin_referer($action, $name) {
    $GLOBALS['mock_referer_args'] = array($action, $name);
    if (!$GLOBALS['mock_nonce_ok']) { throw new Exception('NONCE_FAIL'); }
}
function wp_die($m, $t = '', $a = array()) { throw new Exception('WP_DIE_INVOKED: ' . ($a['response'] ?? 0)); }
function wp_get_referer() { return 'http://example.test/wp-admin/edit.php?post_type=penyewaan'; }
function wp_safe_redirect($u) { throw new Exception('REDIRECT:' . $u); }
function ryokourent_get_timezone() { return new DateTimeZone('Asia/Jakarta'); }
function wp_update_post($args, $wp_error = false) {
    $GLOBALS['mock_update_calls'][] = $args;
    if ($GLOBALS['mock_update_fail']) {
        return $wp_error ? new WP_Error('update_failed', 'gagal') : 0;
    }
    $id  = $args['ID'];
    $old = $GLOBALS['mock_status'][$id] ?? '';
    $new = $args['post_status'];
    $GLOBALS['mock_status'][$id] = $new;
    $post = (object) array('ID' => $id, 'post_type' => 'penyewaan');
    // Simulasi WordPress: jalankan semua callback transition_post_status.
    foreach (($GLOBALS['mock_hooks']['transition_post_status'] ?? array()) as $cb) {
        call_user_func($cb, $new, $old, $post);
    }
    return $id;
}

$test_count = 0;
$pass_count = 0;
function run_test($description, $assertion) {
    global $test_count, $pass_count;
    $test_count++;
    if ($assertion) { $pass_count++; echo "  [PASS] " . $description . PHP_EOL; }
    else { echo "  [FAIL] " . $description . PHP_EOL; }
}

require_once RYOKOURENT_PLUGIN_DIR . 'includes/availability.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/booking.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once RYOKOURENT_PLUGIN_DIR . 'admin/dashboard.php';
require_once RYOKOURENT_PLUGIN_DIR . 'admin/booking-columns.php';

/** Reset state dan siapkan fixture standar. */
function seed($booking_id, $status, $stock = 2) {
    $GLOBALS['wpdb']             = new Mock_WPDB();
    $GLOBALS['mock_overlap_ids'] = array();
    $GLOBALS['mock_query_log']   = array();
    $GLOBALS['mock_update_calls'] = array();
    $GLOBALS['mock_update_fail'] = false;
    $GLOBALS['mock_can']         = true;
    $GLOBALS['mock_nonce_ok']    = true;
    $GLOBALS['mock_transients']  = array();
    $GLOBALS['mock_status'][$booking_id] = $status;
    $GLOBALS['mock_meta'][9] = array(
        '_ryokou_physical_stock' => $stock,
        '_ryokou_plate_numbers'  => "N 1234 AB\nN 5678 CD",
    );
    $GLOBALS['mock_meta'][$booking_id] = array(
        '_ryokou_booking_motor_id'        => 9,
        '_ryokou_booking_start_datetime'  => '2026-10-10 08:00',
        '_ryokou_booking_end_datetime'    => '2026-10-12 08:00',
        '_ryokou_booking_allocated_plate' => '',
    );
}

echo "=================================================================" . PHP_EOL;
echo "RUNNING BOOKING STATUS ACTIONS TEST SUITE (TASK-023)" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

// ---------------------------------------------------------------------------
echo "\n1. Matriks transisi (alur lapangan):\n";
$allowed = array(
    array('status_menunggu', 'status_dikonfirmasi'),
    array('status_menunggu', 'status_dibatalkan'),
    array('status_dikonfirmasi', 'status_berjalan'),
    array('status_dikonfirmasi', 'status_dibatalkan'),
    array('status_berjalan', 'status_selesai'),
);
foreach ($allowed as $pair) {
    run_test("Diizinkan: {$pair[0]} -> {$pair[1]}", ryokourent_is_allowed_status_transition($pair[0], $pair[1]));
}
$denied = array(
    array('status_menunggu', 'status_berjalan'),
    array('status_menunggu', 'status_selesai'),
    array('status_dikonfirmasi', 'status_menunggu'),
    array('status_dikonfirmasi', 'status_selesai'),
    array('status_berjalan', 'status_dibatalkan'),
    array('status_berjalan', 'status_dikonfirmasi'),
    array('status_selesai', 'status_berjalan'),
    array('status_selesai', 'status_dibatalkan'),
    array('status_dibatalkan', 'status_menunggu'),
    array('status_dibatalkan', 'status_dikonfirmasi'),
);
foreach ($denied as $pair) {
    run_test("Ditolak: {$pair[0]} -> {$pair[1]}", !ryokourent_is_allowed_status_transition($pair[0], $pair[1]));
}
run_test('Batal tidak mungkin setelah unit dikirim (berjalan)', !ryokourent_is_allowed_status_transition('status_berjalan', 'status_dibatalkan'));

// ---------------------------------------------------------------------------
echo "\n2. Validasi transisi & plat nomor:\n";
seed(100, 'status_dikonfirmasi');
$r = ryokourent_validate_status_transition(100, 'status_menunggu', 'publish');
run_test('Status tujuan di luar whitelist ditolak', !$r['success'] && 'invalid_status' === $r['code']);
$r = ryokourent_validate_status_transition(100, 'status_menunggu', 'status_menunggu');
run_test('Status sama = no_change (sukses, tanpa perubahan)', $r['success'] && 'no_change' === $r['code']);
$r = ryokourent_validate_status_transition(0, 'auto-draft', 'status_menunggu');
run_test('Booking baru boleh dimulai dari Menunggu', $r['success']);
$r = ryokourent_validate_status_transition(0, 'auto-draft', 'status_dikonfirmasi');
run_test('Booking baru tidak boleh langsung Dikonfirmasi', !$r['success'] && 'invalid_transition' === $r['code']);
$r = ryokourent_validate_status_transition(100, 'status_dikonfirmasi', 'status_berjalan', '');
run_test('Berjalan tanpa plat: plate_required', !$r['success'] && 'plate_required' === $r['code']);
$r = ryokourent_validate_status_transition(100, 'status_dikonfirmasi', 'status_berjalan', 'N 9999 ZZ');
run_test('Plat tidak terdaftar pada motor: plate_invalid', !$r['success'] && 'plate_invalid' === $r['code']);
$r = ryokourent_validate_status_transition(100, 'status_dikonfirmasi', 'status_berjalan', ' n 1234  ab ');
run_test('Plat valid dinormalisasi menjadi "N 1234 AB"', $r['success'] && 'N 1234 AB' === $r['plate']);
$GLOBALS['mock_overlap_ids'] = array(903);
$GLOBALS['mock_meta'][903]['_ryokou_booking_allocated_plate'] = 'N 1234 AB';
$r = ryokourent_validate_status_transition(100, 'status_dikonfirmasi', 'status_berjalan', 'n-1234-ab');
run_test('Plat bentrok dengan sewa aktif lain: plate_invalid', !$r['success'] && 'plate_invalid' === $r['code']);
run_test('Normalisasi plat membuang tag dan simbol', 'N1234AB' === ryokourent_normalize_plate_for_storage('<b>n-1234-ab</b>'));

// ---------------------------------------------------------------------------
echo "\n3. Konfirmasi + kuota (Titik 2):\n";
seed(100, 'status_menunggu', 2);
$GLOBALS['mock_overlap_ids'] = array(901, 902);
$res = ryokourent_transition_booking_status(100, 'status_dikonfirmasi');
run_test('Kuota penuh: konfirmasi ditolak (quota_full)', !$res['success'] && 'quota_full' === $res['code']);
run_test('Kuota penuh: status tetap Menunggu', 'status_menunggu' === $GLOBALS['mock_status'][100]);
run_test('Kuota penuh: wp_update_post tidak dipanggil', 0 === count($GLOBALS['mock_update_calls']));
run_test('Pengecekan berjalan di dalam lock (GET_LOCK + RELEASE_LOCK)', 1 === $GLOBALS['wpdb']->count('GET_LOCK') && 1 === $GLOBALS['wpdb']->count('RELEASE_LOCK'));

seed(100, 'status_menunggu', 2);
$GLOBALS['mock_overlap_ids'] = array(100, 901);
$GLOBALS['mock_transients'][RYOKOURENT_DASHBOARD_CACHE_KEY] = array('stale' => true);
$res = ryokourent_transition_booking_status(100, 'status_dikonfirmasi');
$last = end($GLOBALS['mock_query_log']);
run_test('Kuota tersisa (booking sendiri dikecualikan): konfirmasi berhasil', $res['success'] && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);
run_test('Query kuota mengecualikan booking itu sendiri (post__not_in)', isset($last['post__not_in']) && array(100) === $last['post__not_in']);
run_test('Intercept tidak mengulang cek (tidak ada revert, tidak ada error flash)', 0 === $GLOBALS['wpdb']->count('REVERT') && false === get_transient('ryokourent_admin_error_7'));
run_test('Cache dashboard dibuang setelah status berubah', false === get_transient(RYOKOURENT_DASHBOARD_CACHE_KEY));
run_test('Flag bypass dibersihkan setelah selesai', empty($GLOBALS['ryokourent_status_validated'][100]));

seed(200, 'status_menunggu', 2);
$GLOBALS['mock_overlap_ids'] = array(901, 902);
wp_update_post(array('ID' => 200, 'post_status' => 'status_dikonfirmasi'));
run_test('Jalur langsung tanpa bypass tetap dijaga intercept (revert ke Menunggu)', 'status_menunggu' === $GLOBALS['mock_status'][200] && 1 === $GLOBALS['wpdb']->count('REVERT'));
run_test('Intercept menyimpan pesan error flash untuk admin', false !== get_transient('ryokourent_admin_error_7'));

// ---------------------------------------------------------------------------
echo "\n4. Serah terima (Berjalan) + plat nomor:\n";
seed(100, 'status_dikonfirmasi');
$res = ryokourent_transition_booking_status(100, 'status_berjalan', '');
run_test('Berjalan tanpa plat ditolak', !$res['success'] && 'plate_required' === $res['code']);
run_test('Status tetap Dikonfirmasi & plat tidak tersimpan', 'status_dikonfirmasi' === $GLOBALS['mock_status'][100] && '' === $GLOBALS['mock_meta'][100]['_ryokou_booking_allocated_plate']);
$res = ryokourent_transition_booking_status(100, 'status_berjalan', 'N 9999 ZZ');
run_test('Plat tidak terdaftar ditolak, status tidak berubah', !$res['success'] && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);
$res = ryokourent_transition_booking_status(100, 'status_berjalan', 'n 1234 ab');
run_test('Berjalan dengan plat valid berhasil', $res['success'] && 'status_berjalan' === $GLOBALS['mock_status'][100]);
run_test('Plat tersimpan ternormalisasi di meta booking', 'N 1234 AB' === $GLOBALS['mock_meta'][100]['_ryokou_booking_allocated_plate']);
run_test('Validasi plat berjalan di dalam lock', $GLOBALS['wpdb']->count('GET_LOCK') >= 1 && $GLOBALS['wpdb']->count('GET_LOCK') === $GLOBALS['wpdb']->count('RELEASE_LOCK'));

seed(100, 'status_dikonfirmasi');
$GLOBALS['mock_meta'][100]['_ryokou_booking_allocated_plate'] = 'LAMA';
$GLOBALS['mock_update_fail'] = true;
$res = ryokourent_transition_booking_status(100, 'status_berjalan', 'N 1234 AB');
run_test('Update gagal: hasil update_failed', !$res['success'] && 'update_failed' === $res['code']);
run_test('Update gagal: plat dikembalikan ke nilai sebelumnya', 'LAMA' === $GLOBALS['mock_meta'][100]['_ryokou_booking_allocated_plate']);

seed(100, 'status_dikonfirmasi');
$GLOBALS['mock_overlap_ids'] = array(903);
$GLOBALS['mock_meta'][903]['_ryokou_booking_allocated_plate'] = 'N 1234 AB';
$res = ryokourent_transition_booking_status(100, 'status_berjalan', 'N 1234 AB');
run_test('Plat ganda pada jadwal bertabrakan ditolak', !$res['success'] && 'plate_invalid' === $res['code'] && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);

// ---------------------------------------------------------------------------
echo "\n5. Selesai & Batalkan:\n";
seed(100, 'status_berjalan');
$res = ryokourent_transition_booking_status(100, 'status_selesai');
run_test('Berjalan -> Selesai berhasil tanpa lock', $res['success'] && 'status_selesai' === $GLOBALS['mock_status'][100] && 0 === $GLOBALS['wpdb']->count('GET_LOCK'));
$res = ryokourent_transition_booking_status(100, 'status_dibatalkan');
run_test('Selesai bersifat final (tidak bisa dibatalkan)', !$res['success'] && 'status_selesai' === $GLOBALS['mock_status'][100]);
seed(100, 'status_berjalan');
$res = ryokourent_transition_booking_status(100, 'status_dibatalkan');
run_test('Batal setelah unit dikirim (Berjalan) ditolak', !$res['success'] && 'invalid_transition' === $res['code'] && 'status_berjalan' === $GLOBALS['mock_status'][100]);
seed(100, 'status_menunggu');
$res = ryokourent_transition_booking_status(100, 'status_dibatalkan');
run_test('Menunggu -> Dibatalkan berhasil', $res['success'] && 'status_dibatalkan' === $GLOBALS['mock_status'][100]);
seed(100, 'status_dikonfirmasi');
$res = ryokourent_transition_booking_status(100, 'status_dibatalkan');
run_test('Dikonfirmasi -> Dibatalkan berhasil (kuota otomatis pulih)', $res['success'] && 'status_dibatalkan' === $GLOBALS['mock_status'][100]);
seed(100, 'status_menunggu');
$res = ryokourent_transition_booking_status(100, 'status_berjalan', 'N 1234 AB');
run_test('Lompat Menunggu -> Berjalan ditolak', !$res['success'] && 'status_menunggu' === $GLOBALS['mock_status'][100]);
$res = ryokourent_transition_booking_status(100, 'status_menunggu');
run_test('Status sama dilaporkan no_change', !$res['success'] && 'no_change' === $res['code']);
$res = ryokourent_transition_booking_status(100, 'publish');
run_test('Nilai status di luar whitelist ditolak', !$res['success'] && 'status_menunggu' === $GLOBALS['mock_status'][100]);
$res = ryokourent_transition_booking_status(99999, 'status_dikonfirmasi');
run_test('ID booking tidak dikenal ditolak', !$res['success'] && 'invalid_booking' === $res['code']);

// ---------------------------------------------------------------------------
echo "\n6. Tampilan kolom Status & Aksi:\n";
seed(100, 'status_menunggu');
ob_start(); ryokourent_booking_column_content('ryokourent_status', 100); $html = ob_get_clean();
run_test('Menunggu: tombol Konfirmasi dan Batalkan, tanpa input plat', false !== strpos($html, 'value="100:status_dikonfirmasi"') && false !== strpos($html, 'value="100:status_dibatalkan"') && false === strpos($html, 'ryokourent_plate'));
run_test('Nonce per booking tersedia', false !== strpos($html, 'name="ryokourent_status_nonce_100"') && false !== strpos($html, 'nonce_ryokourent_status_100'));
$GLOBALS['mock_status'][100] = 'status_dikonfirmasi';
ob_start(); ryokourent_booking_column_content('ryokourent_status', 100); $html = ob_get_clean();
run_test('Dikonfirmasi: input plat + tombol Serah Terima dan Batalkan', false !== strpos($html, 'name="ryokourent_plate[100]"') && false !== strpos($html, 'value="100:status_berjalan"') && false !== strpos($html, 'value="100:status_dibatalkan"'));
run_test('Dikonfirmasi: tidak ada tombol Selesai', false === strpos($html, 'status_selesai'));
$GLOBALS['mock_status'][100] = 'status_berjalan';
ob_start(); ryokourent_booking_column_content('ryokourent_status', 100); $html = ob_get_clean();
run_test('Berjalan: hanya tombol Selesai (tanpa Batalkan, tanpa plat)', false !== strpos($html, 'value="100:status_selesai"') && false === strpos($html, 'status_dibatalkan') && false === strpos($html, 'ryokourent_plate'));
foreach (array('status_selesai', 'status_dibatalkan') as $final) {
    $GLOBALS['mock_status'][100] = $final;
    ob_start(); ryokourent_booking_column_content('ryokourent_status', 100); $html = ob_get_clean();
    run_test("Status final {$final}: tidak ada tombol", false === strpos($html, '<button'));
}
$GLOBALS['mock_can'] = false;
ob_start(); ryokourent_booking_column_content('ryokourent_status', 100); $html = ob_get_clean();
run_test('Tanpa capability: kolom tidak dirender', '' === $html);
$cols = ryokourent_booking_columns(array('cb' => 'x', 'title' => 'T', 'date' => 'D'));
run_test('Tanpa capability: kolom tidak ditambahkan', !isset($cols['ryokourent_status']));
$GLOBALS['mock_can'] = true;
$cols = ryokourent_booking_columns(array('cb' => 'x', 'title' => 'T', 'date' => 'D'));
run_test('Dengan capability: kolom Status & Aksi ditambahkan', isset($cols['ryokourent_status']));

// ---------------------------------------------------------------------------
echo "\n7. Handler quick action (nonce, capability, whitelist):\n";
seed(100, 'status_dikonfirmasi');
$_REQUEST = array('post_type' => 'penyewaan');
$_POST    = array();
$threw = false;
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $threw = true; }
run_test('Tanpa POST quick action: handler tidak melakukan apa pun', !$threw);

$_POST = array('ryokourent_status_action' => 'bukan-format');
$threw = false;
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $threw = true; }
run_test('Format nilai tidak valid diabaikan', !$threw && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);

$_POST = array('ryokourent_status_action' => '100:status_berjalan', 'ryokourent_plate' => array(100 => 'N 1234 AB'));
$GLOBALS['mock_can'] = false;
$msg = '';
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Tanpa capability: 403 Forbidden dan status tidak berubah', 'WP_DIE_INVOKED: 403' === $msg && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);

$GLOBALS['mock_can'] = true;
$GLOBALS['mock_nonce_ok'] = false;
$msg = '';
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Nonce salah: ditolak dan status tidak berubah', 'NONCE_FAIL' === $msg && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100]);
run_test('Nonce diverifikasi per booking (action & nama field)', array('ryokourent_status_100', 'ryokourent_status_nonce_100') === $GLOBALS['mock_referer_args']);

$GLOBALS['mock_nonce_ok'] = true;
$_POST = array('ryokourent_status_action' => '100:status_berjalan', 'ryokourent_plate' => array(101 => 'N 1234 AB'));
$msg = '';
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Plat milik baris lain tidak dipakai: ditolak, flash error tersimpan', 0 === strpos($msg, 'REDIRECT:') && 'status_dikonfirmasi' === $GLOBALS['mock_status'][100] && false !== get_transient('ryokourent_admin_error_7'));

$GLOBALS['mock_transients'] = array();
$_POST = array('ryokourent_status_action' => '100:status_berjalan', 'ryokourent_plate' => array(100 => 'n 1234 ab'));
$msg = '';
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Aksi valid: status Berjalan, plat tersimpan, redirect ke daftar', 0 === strpos($msg, 'REDIRECT:') && 'status_berjalan' === $GLOBALS['mock_status'][100] && 'N 1234 AB' === $GLOBALS['mock_meta'][100]['_ryokou_booking_allocated_plate']);
run_test('Flash sukses tersimpan', false !== get_transient('ryokourent_admin_success_7'));
ob_start(); ryokourent_display_status_success_notice(); $notice = ob_get_clean();
run_test('Notice sukses ditampilkan lalu dihapus', false !== strpos($notice, 'notice-success') && false === get_transient('ryokourent_admin_success_7'));

$_POST = array('ryokourent_status_action' => '100:status_dibatalkan');
$msg = '';
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Nilai status di luar whitelist/transisi ditolak lewat handler', 'status_berjalan' === $GLOBALS['mock_status'][100]);
$_POST = array('ryokourent_status_action' => '100:status_hacked');
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $msg = $e->getMessage(); }
run_test('Status palsu "status_hacked" ditolak', 'status_berjalan' === $GLOBALS['mock_status'][100]);
$_REQUEST = array('post_type' => 'post');
$_POST    = array('ryokourent_status_action' => '100:status_selesai');
$threw = false;
try { ryokourent_handle_booking_status_action(); } catch (Exception $e) { $threw = true; }
run_test('Post type lain diabaikan', !$threw && 'status_berjalan' === $GLOBALS['mock_status'][100]);
$_REQUEST = array();
$_POST    = array();

// ---------------------------------------------------------------------------
echo "\n8. Jalur dropdown metabox (wp_insert_post_data) memakai aturan yang sama:\n";
function metabox_filter($booking_id, $post_status_param, $posted = array()) {
    $_POST = array_merge(array('ryokourent_booking_status' => $post_status_param, 'ryokourent_booking_meta_nonce' => 'abc'), $posted);
    return ryokourent_filter_booking_post_status(array('post_type' => 'penyewaan', 'post_status' => 'draft'), array('ID' => $booking_id));
}
seed(300, 'status_dikonfirmasi');
$d = metabox_filter(300, 'status_berjalan');
run_test('Dropdown Berjalan tanpa plat: status lama dipertahankan', 'status_dikonfirmasi' === $d['post_status'] && false !== get_transient('ryokourent_admin_error_7'));
$d = metabox_filter(300, 'status_berjalan', array('_ryokou_booking_allocated_plate' => 'N 1234 AB'));
run_test('Dropdown Berjalan dengan plat valid: diizinkan', 'status_berjalan' === $d['post_status']);
$d = metabox_filter(300, 'status_berjalan', array('_ryokou_booking_allocated_plate' => 'N 0000 XX'));
run_test('Dropdown Berjalan dengan plat tidak terdaftar: ditolak', 'status_dikonfirmasi' === $d['post_status']);
$d = metabox_filter(300, 'status_dikonfirmasi');
run_test('Dropdown tanpa perubahan status: aman', 'status_dikonfirmasi' === $d['post_status']);
seed(301, 'status_menunggu');
$d = metabox_filter(301, 'status_selesai');
run_test('Dropdown lompat Menunggu -> Selesai ditolak', 'status_menunggu' === $d['post_status']);
$d = metabox_filter(301, 'status_dikonfirmasi');
run_test('Dropdown Menunggu -> Dikonfirmasi diizinkan (kuota dijaga intercept)', 'status_dikonfirmasi' === $d['post_status']);
$d = metabox_filter(0, 'status_dikonfirmasi');
run_test('Booking baru dengan status Dikonfirmasi jatuh ke Menunggu', 'status_menunggu' === $d['post_status']);
$GLOBALS['mock_nonce_ok'] = false;
$d = metabox_filter(301, 'status_dikonfirmasi');
run_test('Nonce metabox salah: status tidak diubah oleh filter (ADR-009)', 'draft' === $d['post_status']);
$GLOBALS['mock_nonce_ok'] = true;
$GLOBALS['mock_can'] = false;
$d = metabox_filter(301, 'status_dikonfirmasi');
run_test('Tanpa capability: status tidak diubah oleh filter (ADR-009)', 'draft' === $d['post_status']);
$GLOBALS['mock_can'] = true;
$_POST = array('ryokourent_booking_status' => 'status_dikonfirmasi');
$d = ryokourent_filter_booking_post_status(array('post_type' => 'post', 'post_status' => 'draft'), array('ID' => 1));
run_test('Post type lain tidak disentuh', 'draft' === $d['post_status']);
$_POST = array();

echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
echo "-----------------------------------------------------------------\n";
exit($pass_count === $test_count ? 0 : 1);
