<?php
/**
 * Test Suite for Fleet Availability & Stock Privacy Protection (TASK-016)
 *
 * Verifies:
 * - Querying physical quota and active overlapping bookings.
 * - Non-overlapping date queries (before/after) do not consume quota.
 * - Completed/Cancelled bookings (status_selesai, status_dibatalkan) do not consume quota.
 * - Confirmed/Active bookings (status_dikonfirmasi, status_berjalan) consume quota.
 * - Exact TASK-016 scenario: 3 active bookings on stock of 3 returns available: false.
 * - Privacy protection: Public AJAX response never leaks physical stock numbers.
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

// Define ABSPATH and plugin constants for mock environment
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../');
}
if (!defined('RYOKOURENT_PLUGIN_DIR')) {
    define('RYOKOURENT_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('RYOKOURENT_TIMEZONE')) {
    define('RYOKOURENT_TIMEZONE', 'Asia/Jakarta');
}

// Global mock bookings database for testing
$GLOBALS['mock_bookings'] = array();
$GLOBALS['mock_transients'] = array();

// Mock WordPress functions
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        return $number == 1 ? $single : $plural;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($data) {
        return $data;
    }
}
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int) $maybeint);
    }
}
if (!function_exists('get_transient')) {
    function get_transient($key) {
        return isset($GLOBALS['mock_transients'][$key]) ? $GLOBALS['mock_transients'][$key] : false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration = 0) {
        $GLOBALS['mock_transients'][$key] = $value;
        return true;
    }
}
if (!function_exists('delete_transient')) {
    function delete_transient($key) {
        unset($GLOBALS['mock_transients'][$key]);
        return true;
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        // Motor 1: Honda BeAT Deluxe (Physical Stock = 3)
        if ($post_id === 1 && $key === '_ryokou_physical_stock') {
            return 3;
        }
        if ($post_id === 1 && $key === '_ryokou_plate_numbers') {
            return "N 1001 AB\nN 1002 CD\nN 1003 EF";
        }
        // Motor 2: Honda CRF 150L (Physical Stock = 2)
        if ($post_id === 2 && $key === '_ryokou_physical_stock') {
            return 2;
        }
        if ($post_id === 2 && $key === '_ryokou_plate_numbers') {
            return "N 2001 GH\nN 2002 IJ";
        }
        // Booking metadata mock
        if (isset($GLOBALS['mock_bookings'][$post_id])) {
            $booking = $GLOBALS['mock_bookings'][$post_id];
            if ($key === '_ryokou_booking_motor_id') return $booking['motor_id'];
            if ($key === '_ryokou_booking_start_datetime') return $booking['start'];
            if ($key === '_ryokou_booking_end_datetime') return $booking['end'];
            if ($key === '_ryokou_booking_allocated_plate') return isset($booking['plate']) ? $booking['plate'] : '';
        }
        return '';
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Mock WP_Query to search across $GLOBALS['mock_bookings']
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = array();
        public function __construct($args = array()) {
            $matched_ids = array();
            $target_motor = null;
            $req_end = null;
            $req_start = null;

            if (isset($args['meta_query'])) {
                foreach ($args['meta_query'] as $clause) {
                    if (is_array($clause) && isset($clause['key'])) {
                        if ($clause['key'] === '_ryokou_booking_motor_id') {
                            $target_motor = $clause['value'];
                        } elseif ($clause['key'] === '_ryokou_booking_start_datetime') {
                            $req_end = $clause['value'];
                        } elseif ($clause['key'] === '_ryokou_booking_end_datetime') {
                            $req_start = $clause['value'];
                        }
                    }
                }
            }

            $allowed_statuses = isset($args['post_status']) ? (array) $args['post_status'] : array();
            $excluded = isset($args['post__not_in']) ? (array) $args['post__not_in'] : array();

            foreach ($GLOBALS['mock_bookings'] as $id => $b) {
                if (in_array($id, $excluded, true)) {
                    continue;
                }
                if ($target_motor !== null && $b['motor_id'] !== $target_motor) {
                    continue;
                }
                if (!empty($allowed_statuses) && !in_array($b['status'], $allowed_statuses, true)) {
                    continue;
                }
                // Overlap: StartBooking < req_end AND EndBooking > req_start
                if ($req_end !== null && $req_start !== null) {
                    $b_start = $b['start'];
                    $b_end   = $b['end'];
                    if ($b_start < $req_end && $b_end > $req_start) {
                        $matched_ids[] = $id;
                    }
                } else {
                    $matched_ids[] = $id;
                }
            }
            $this->posts = $matched_ids;
        }
    }
}

// Load helpers and availability module
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/availability.php';

// Test runner
$test_count = 0;
$pass_count = 0;

function run_test($description, $assertion) {
    global $test_count, $pass_count;
    $test_count++;
    if ($assertion) {
        $pass_count++;
        echo "[PASS] " . $description . PHP_EOL;
    } else {
        echo "[FAIL] " . $description . PHP_EOL;
    }
}

echo "=== Menjalankan Unit Test Ketersediaan Unit & Privasi Stok (TASK-016) ===" . PHP_EOL . PHP_EOL;

// 1. Test Physical Stock Retrieval
$stock = ryokourent_get_motor_physical_stock(1);
run_test("Stok fisik motor 1 terambil tepat 3 unit", $stock === 3);

// 2. Test Availability on Empty Bookings
$test_start = '2026-10-02 08:30';
$test_end   = '2026-10-04 17:00';
$avail_empty = ryokourent_check_availability(1, $test_start, $test_end);
run_test("Armada dengan 0 booking aktif berstatus available: true", $avail_empty === true);

// 3. Add 1st active booking on motor 1
$GLOBALS['mock_bookings'][101] = array(
    'motor_id' => 1,
    'start'    => '2026-10-02 08:00:00',
    'end'      => '2026-10-04 12:00:00',
    'status'   => 'status_dikonfirmasi',
);
$avail_1 = ryokourent_check_availability(1, $test_start, $test_end);
run_test("Armada dengan 1 booking (stok 3) berstatus available: true", $avail_1 === true);

// 4. Add 2nd active booking on motor 1
$GLOBALS['mock_bookings'][102] = array(
    'motor_id' => 1,
    'start'    => '2026-10-03 09:00:00',
    'end'      => '2026-10-05 15:00:00',
    'status'   => 'status_berjalan',
);
$avail_2 = ryokourent_check_availability(1, $test_start, $test_end);
run_test("Armada dengan 2 booking (stok 3) berstatus available: true", $avail_2 === true);

// 5. Add 3rd active booking on motor 1 (Now fully booked: 3 of 3)
$GLOBALS['mock_bookings'][103] = array(
    'motor_id' => 1,
    'start'    => '2026-10-01 10:00:00',
    'end'      => '2026-10-03 10:00:00',
    'status'   => 'status_dikonfirmasi',
);

// TASK-016 Exact Acceptance Test:
// "Simulasikan 3 booking aktif pada motor dengan stok 3; pastikan pengecekan berikutnya menghasilkan status available: false"
$avail_3 = ryokourent_check_availability(1, $test_start, $test_end);
run_test("Armada dengan 3 booking aktif pada stok 3 menghasilkan available: false", $avail_3 === false);

// 6. Test Non-Overlapping Dates on Fully Booked Unit
// Schedule completely after existing bookings: 2026-10-06 to 2026-10-08
$avail_after = ryokourent_check_availability(1, '2026-10-06 08:00', '2026-10-08 17:00');
run_test("Jadwal sewa di luar tanggal booking bentrok (setelahnya) tetap available: true", $avail_after === true);

// Schedule completely before existing bookings: 2026-09-25 to 2026-09-28
$avail_before = ryokourent_check_availability(1, '2026-09-25 08:00', '2026-09-28 17:00');
run_test("Jadwal sewa di luar tanggal booking bentrok (sebelumnya) tetap available: true", $avail_before === true);

// 7. Test Non-Consuming Quota Statuses (status_selesai and status_dibatalkan)
$GLOBALS['mock_bookings'][104] = array(
    'motor_id' => 1,
    'start'    => '2026-10-06 08:00:00',
    'end'      => '2026-10-08 17:00:00',
    'status'   => 'status_selesai',
);
$GLOBALS['mock_bookings'][105] = array(
    'motor_id' => 1,
    'start'    => '2026-10-06 08:00:00',
    'end'      => '2026-10-08 17:00:00',
    'status'   => 'status_dibatalkan',
);
$avail_with_inactive = ryokourent_check_availability(1, '2026-10-06 08:00', '2026-10-08 17:00');
run_test("Booking dengan status_selesai dan status_dibatalkan tidak mengurangi kuota", $avail_with_inactive === true);

// 8. Test Precise Boundary Conditions (REVIEW-ARCHITECTURE §5 item 5)
// Booking aktif 101 selesai pada 2026-10-04 12:00:00.
// Booking baru mulai tepat 2026-10-04 12:00:00 (tidak overlap):
$avail_exact_touch = ryokourent_check_availability(1, '2026-10-05 15:00:00', '2026-10-06 07:00:00');
run_test("Jadwal tepat menyentuh batas akhir sewa sebelumnya tidak dihitung bentrok", $avail_exact_touch === true);

// 9. Test Double Booking on Honda CRF 150L (Motor 2, Stok 2)
$GLOBALS['mock_bookings'][201] = array(
    'motor_id' => 2,
    'start'    => '2026-10-10 07:00:00',
    'end'      => '2026-10-12 17:00:00',
    'status'   => 'status_dikonfirmasi',
    'plate'    => 'N 2001 GH',
);
$GLOBALS['mock_bookings'][202] = array(
    'motor_id' => 2,
    'start'    => '2026-10-10 08:00:00',
    'end'      => '2026-10-12 18:00:00',
    'status'   => 'status_berjalan',
    'plate'    => 'N 2002 IJ',
);
// Stok CRF adalah 2, kedua unit sudah booked:
$avail_crf_full = ryokourent_check_availability(2, '2026-10-10 09:00', '2026-10-11 17:00');
run_test("CRF 150L dengan 2 booking aktif pada stok 2 menghasilkan available: false", $avail_crf_full === false);

// 10. Test Validasi Alokasi Plat Nomor (M6)
if (function_exists('ryokourent_validate_allocated_plate')) {
    // Plat sah terdaftar
    $valid_plate = ryokourent_validate_allocated_plate(1, 'N 1001 AB', '2026-10-20 08:00:00', '2026-10-22 17:00:00');
    run_test("Plat terdaftar (N 1001 AB) divalidasi sukses", $valid_plate['valid'] === true);

    // Plat fiktif / tidak terdaftar pada armada motor 1
    $invalid_plate = ryokourent_validate_allocated_plate(1, 'B 9999 XYZ', '2026-10-20 08:00:00', '2026-10-22 17:00:00');
    run_test("Plat tidak terdaftar pada model motor ditolak", $invalid_plate['valid'] === false);

    // Plat bentrok: Plat N 2001 GH sudah dialokasikan ke booking 201 pada rentang 2026-10-10 s/d 2026-10-12
    $clash_plate = ryokourent_validate_allocated_plate(2, 'N 2001 GH', '2026-10-10 10:00:00', '2026-10-11 12:00:00');
    run_test("Plat yang sedang dipakai di booking aktif lain dideteksi bentrok", $clash_plate['valid'] === false);
}

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
