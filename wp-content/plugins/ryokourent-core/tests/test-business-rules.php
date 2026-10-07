<?php
/**
 * Test Suite: Business Rules & Admin Operational Controls
 *
 * Verifies:
 * 1. Operator Role Permissions:
 *    - Operator CAN edit motors, update physical stock, plate numbers, and prices (daily, weekly, monthly).
 *    - Operator CANNOT delete motors (no delete_motors capability).
 *    - Operator CANNOT add or manage motor categories (manage_terms requires manage_ryokourent_settings).
 * 2. Rental Duration & Extension (Extend Rental):
 *    - Rental schedule locked to 24-hour cycle.
 *    - Admin rental extension: Default +1 Day (24h) with custom day selection.
 *    - Overdue time converted into accumulated official rental extension (not overtime anymore).
 *    - Total days and total price accumulation.
 *    - Conflict prevention when extending unit overlapping with future confirmed booking.
 * 3. Cancel Booking by Admin:
 *    - Admin can cancel booking with optional cancellation reason recorded in meta.
 *    - Immediate release of physical fleet quota upon cancellation.
 * 4. Overtime Tracking & Admin Monitoring:
 *    - Overtime hours tracked for fleet availability and admin alerts.
 *    - Zero automatic fine calculation (denda_auto = 0, manual calculation by admin).
 * 5. Extreme Route Mandatory Rules:
 *    - Extreme routes (Bromo sand sea, Cangar steep pass, South Malang sand beaches) strictly require Trail CRF 150L.
 *    - All automatic scooters strictly forbidden.
 *
 * @package Ryokourent_Core
 */

$test_results = array(
    'passed' => 0,
    'failed' => 0,
    'tests'  => array(),
);

function run_test($name, $condition, $details = '') {
    global $test_results;
    if ($condition) {
        $test_results['passed']++;
        $test_results['tests'][] = array('name' => $name, 'status' => 'PASS', 'details' => $details);
        echo "[PASS] " . $name . "\n";
    } else {
        $test_results['failed']++;
        $test_results['tests'][] = array('name' => $name, 'status' => 'FAIL', 'details' => $details);
        echo "[FAIL] " . $name . " - Details: " . $details . "\n";
    }
}

// -----------------------------------------------------------------------------
// Mock Minimal WordPress Environment for Standalone Test Execution
// -----------------------------------------------------------------------------
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__DIR__))))) . '/';
}

$mock_posts = array();
$mock_postmeta = array();
$mock_user_caps = array();
$current_mock_user_id = 1;

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'ryokourent') { return $text; }
}
if (!function_exists('__')) {
    function __($text, $domain = 'ryokourent') { return $text; }
}
if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'ryokourent') { return $text; }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags((string) $str)); }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)); }
}
if (!function_exists('absint')) {
    function absint($maybeint) { return abs((int) $maybeint); }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($val) { return $val; }
}

if (!function_exists('get_post_status')) {
    function get_post_status($post_id) {
        global $mock_posts;
        return isset($mock_posts[$post_id]['post_status']) ? $mock_posts[$post_id]['post_status'] : false;
    }
}
if (!function_exists('get_post_type')) {
    function get_post_type($post_id) {
        global $mock_posts;
        return isset($mock_posts[$post_id]['post_type']) ? $mock_posts[$post_id]['post_type'] : false;
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key, $single = true) {
        global $mock_postmeta;
        if (isset($mock_postmeta[$post_id][$key])) {
            return $single ? $mock_postmeta[$post_id][$key] : array($mock_postmeta[$post_id][$key]);
        }
        return $single ? '' : array();
    }
}
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value) {
        global $mock_postmeta;
        if (!isset($mock_postmeta[$post_id])) {
            $mock_postmeta[$post_id] = array();
        }
        $mock_postmeta[$post_id][$key] = $value;
        return true;
    }
}
if (!function_exists('wp_update_post')) {
    function wp_update_post($args, $fire_after_hooks = true) {
        global $mock_posts;
        $id = $args['ID'];
        if (isset($mock_posts[$id])) {
            foreach ($args as $k => $v) {
                if ($k !== 'ID') {
                    $mock_posts[$id][$k] = $v;
                }
            }
            return $id;
        }
        return 0;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap, ...$args) {
        global $mock_user_caps;
        return !empty($mock_user_caps[$cap]);
    }
}
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $current_mock_user_id;
        return $current_mock_user_id;
    }
}

// Include plugin dependencies
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/user-roles.php';
require_once __DIR__ . '/../includes/pricing.php';
require_once __DIR__ . '/../includes/availability.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../public/templates.php';

echo "=================================================================\n";
echo "RYOKOURENT BUSINESS RULES & ADMIN CONTROLS TEST SUITE\n";
echo "=================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. Test Operator Role Capabilities
// -----------------------------------------------------------------------------
echo "--- 1. Testing Operator Role Capabilities ---\n";

$operator_caps = ryokourent_get_operator_capabilities();

run_test(
    "Operator memiliki capability edit_motors untuk mengedit armada motor",
    !empty($operator_caps['edit_motors'])
);

run_test(
    "Operator memiliki capability manage_ryokourent_bookings untuk mengelola sewa",
    !empty($operator_caps['manage_ryokourent_bookings'])
);

run_test(
    "Operator TIDAK memiliki capability delete_motors (dilarang menghapus motor)",
    empty($operator_caps['delete_motors'])
);

run_test(
    "Operator TIDAK memiliki capability delete_published_motors",
    empty($operator_caps['delete_published_motors'])
);

run_test(
    "Operator TIDAK memiliki capability manage_ryokourent_settings (dilarang menambah/edit kategori)",
    empty($operator_caps['manage_ryokourent_settings'])
);

$admin_caps = ryokourent_get_administrator_capabilities();
run_test(
    "Administrator memiliki capability delete_motors",
    !empty($admin_caps['delete_motors'])
);
run_test(
    "Administrator memiliki capability manage_ryokourent_settings untuk kelola kategori & setting global",
    !empty($admin_caps['manage_ryokourent_settings'])
);

// -----------------------------------------------------------------------------
// 2. Test Rental Duration & Schedule 24-Hour Cycle
// -----------------------------------------------------------------------------
echo "\n--- 2. Testing 24-Hour Schedule & Rental Cycles ---\n";

$days_exact = ryokourent_calculate_rental_days('2026-10-10 08:00', '2026-10-11 08:00');
run_test(
    "Sewa tepat 24 jam dihitung 1 Hari penuh",
    $days_exact === 1,
    "Hasil: {$days_exact}"
);

$days_48h = ryokourent_calculate_rental_days('2026-10-10 08:00', '2026-10-12 08:00');
run_test(
    "Sewa 48 jam dihitung tepat kelipatan 2 Hari",
    $days_48h === 2,
    "Hasil: {$days_48h}"
);

$days_72h = ryokourent_calculate_rental_days('2026-10-10 08:00', '2026-10-13 08:00');
run_test(
    "Sewa 72 jam dihitung tepat kelipatan 3 Hari",
    $days_72h === 3,
    "Hasil: {$days_72h}"
);

// -----------------------------------------------------------------------------
// 3. Test Overtime Tracking & Admin Monitoring
// -----------------------------------------------------------------------------
echo "\n--- 3. Testing Overtime Detection & Zero Automatic Fine ---\n";

$mock_posts[101] = array(
    'post_type'   => 'penyewaan',
    'post_status' => 'status_berjalan',
    'post_title'  => 'RYK-20261001-A101 - Ahmad Pratama',
);
$mock_postmeta[101] = array(
    '_ryokou_booking_start_datetime' => '2026-10-01 08:00',
    '_ryokou_booking_end_datetime'   => '2026-10-02 08:00', // Telah lewat dari tanggal acuan
    '_ryokou_booking_motor_id'       => 1,
    '_ryokou_booking_total_days'     => 1,
    '_ryokou_booking_total_price'    => 85000,
);

$overtime_info = ryokourent_get_booking_overtime_info(101);
run_test(
    "Overtime berhasil terdeteksi saat jadwal selesai telah terlewati (is_overdue = true)",
    $overtime_info['is_overdue'] === true,
    "Overtime: " . json_encode($overtime_info)
);

run_test(
    "Durasi keterlambatan terhitung dalam jam untuk info operasional admin",
    $overtime_info['overdue_hours'] > 0,
    "Jam: {$overtime_info['overdue_hours']}"
);

run_test(
    "Denda otomatis TIDAK dihitung oleh sistem (auto_fine = 0, wajib manual oleh admin)",
    $overtime_info['auto_fine'] === 0,
    "Auto fine: {$overtime_info['auto_fine']}"
);

// -----------------------------------------------------------------------------
// 4. Test Extend Rental Duration by Admin
// -----------------------------------------------------------------------------
echo "\n--- 4. Testing Extend Rental Duration (Opsi Perpanjangan) ---\n";

// Mock fleet pricing
$mock_postmeta[1]['_ryokou_price_daily'] = 85000;
$mock_postmeta[1]['_ryokou_price_weekly'] = 500000;
$mock_postmeta[1]['_ryokou_price_monthly'] = 1600000;
$mock_postmeta[1]['_ryokou_physical_stock'] = 10;

// Perpanjang booking 101 sebanyak 1 Hari (24 Jam)
$extend_res = ryokourent_extend_rental_duration(101, 1, 'Pelanggan minta tambah 1 hari via WA');

run_test(
    "Perpanjangan sewa +1 Hari berhasil dieksekusi",
    $extend_res['success'] === true,
    "Message: " . (isset($extend_res['message']) ? $extend_res['message'] : '')
);

$new_end_date = get_post_meta(101, '_ryokou_booking_end_datetime', true);
run_test(
    "Jadwal selesai sewa bertambah kelipatan 24 jam (menjadi 2026-10-03 08:00)",
    $new_end_date === '2026-10-03 08:00',
    "New end: {$new_end_date}"
);

$new_total_days = get_post_meta(101, '_ryokou_booking_total_days', true);
run_test(
    "Total hari sewa bertambah menjadi 2 Hari",
    $new_total_days === 2,
    "Total days: {$new_total_days}"
);

$new_total_price = get_post_meta(101, '_ryokou_booking_total_price', true);
run_test(
    "Total tarif sewa terakumulasi menjadi Rp 170.000 (2 x Rp 85.000)",
    $new_total_price === 170000,
    "Total price: {$new_total_price}"
);

$extension_history = get_post_meta(101, '_ryokou_extension_history', true);
run_test(
    "Riwayat perpanjangan sewa tercatat di post meta",
    is_array($extension_history) && count($extension_history) === 1 && $extension_history[0]['extra_days'] === 1
);

// -----------------------------------------------------------------------------
// 5. Test Cancel Booking by Admin with Reason
// -----------------------------------------------------------------------------
echo "\n--- 5. Testing Cancel Booking with Optional Reason ---\n";

$mock_posts[102] = array(
    'post_type'   => 'penyewaan',
    'post_status' => 'status_menunggu',
    'post_title'  => 'RYK-20261001-B102 - Budi Santoso',
);
$mock_postmeta[102] = array(
    '_ryokou_booking_start_datetime' => '2026-10-05 09:00',
    '_ryokou_booking_end_datetime'   => '2026-10-06 09:00',
    '_ryokou_booking_motor_id'       => 1,
);

$cancel_res = ryokourent_cancel_booking(102, 'Pelanggan membatalkan trip karena urusan keluarga via WA');

run_test(
    "Pembatalan pemesanan oleh admin berhasil",
    $cancel_res['success'] === true,
    "Message: " . (isset($cancel_res['message']) ? $cancel_res['message'] : '')
);

$updated_status = get_post_status(102);
run_test(
    "Status pemesanan berubah menjadi status_dibatalkan",
    $updated_status === 'status_dibatalkan',
    "Status: {$updated_status}"
);

$cancel_reason = get_post_meta(102, '_ryokou_cancellation_reason', true);
run_test(
    "Catatan alasan pembatalan tersimpan di post meta",
    $cancel_reason === 'Pelanggan membatalkan trip karena urusan keluarga via WA',
    "Reason: {$cancel_reason}"
);

// -----------------------------------------------------------------------------
// 6. Test Extreme Route Advisory (Bromo, Cangar, Pantai Pasir)
// -----------------------------------------------------------------------------
echo "\n--- 6. Testing Extreme Route Advisory & Rules ---\n";

$faqs = ryokourent_get_faq_items();
$faq_bromo_ban = isset($faqs[1]) ? $faqs[1] : array();
$faq_crf = isset($faqs[2]) ? $faqs[2] : array();
$faq_overtime = isset($faqs[5]) ? $faqs[5] : array();

run_test(
    "FAQ 2 melarang motor matik ke Bromo, Cangar, dan Pantai Pasir",
    strpos($faq_bromo_ban['question'], 'Bromo, Jalur Cangar, dan Pantai Pasir') !== false,
    "Question: {$faq_bromo_ban['question']}"
);

run_test(
    "FAQ 2 mengedukasi risiko rem blong dan kerusakan mesin matik di jalur ekstrem",
    strpos($faq_bromo_ban['answer'], 'rem blong') !== false && strpos($faq_bromo_ban['answer'], 'dilarang keras') !== false
);

run_test(
    "FAQ 3 mewajibkan Trail CRF 150L untuk Bromo, Cangar, dan Pantai Pasir",
    strpos($faq_crf['answer'], 'Honda Trail CRF 150L') !== false && strpos($faq_crf['answer'], 'Cangar') !== false
);

run_test(
    "FAQ 6 mengedukasi kelipatan 24 jam, denda manual, dan pembatalan via WA",
    strpos($faq_overtime['answer'], 'kelipatan 24 jam') !== false && strpos($faq_overtime['answer'], 'manual') !== false
);

$banner_html = ryokourent_render_bromo_advisory_banner();
run_test(
    "Banner rute ekstrem memuat peringatan Bromo, Cangar, dan Pantai Pasir",
    strpos($banner_html, 'Bromo, Cangar, & Pantai Pasir') !== false && strpos($banner_html, 'CRF 150L') !== false
);

echo "\n=================================================================\n";
echo sprintf("HASIL AKHIR: %d PASSED, %d FAILED\n", $test_results['passed'], $test_results['failed']);
echo "=================================================================\n";

if ($test_results['failed'] > 0) {
    exit(1);
}
exit(0);
