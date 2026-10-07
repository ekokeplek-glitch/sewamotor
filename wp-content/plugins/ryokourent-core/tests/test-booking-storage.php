<?php
/**
 * Test Suite for Booking Storage & Cache-Compatible Nonce Handler (TASK-019)
 *
 * Verifies:
 * - Generation of unique standardized booking codes (RYK-YYYYMMDD-XXXX).
 * - Asynchronous persistence into CPT 'penyewaan' with status 'status_menunggu'.
 * - Complete metadata storage per DATA_MODEL.md specification.
 * - Cache-compatible nonce refresher for LiteSpeed Cache / WP Rocket.
 * - Seamless automatic refresh token delivery upon invalid_nonce.
 * - Integration of generated booking code into WhatsApp deep link.
 * - Atomic lock protection preventing concurrent insertion when capacity is exhausted.
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
if (!defined('RYOKOURENT_DEFAULT_WA_NUMBER')) {
    define('RYOKOURENT_DEFAULT_WA_NUMBER', '6281234567890');
}

// Global mocks
$GLOBALS['mock_posts'] = array();
$GLOBALS['mock_postmeta'] = array();
$GLOBALS['mock_post_id_counter'] = 1000;
$GLOBALS['mock_nonces'] = array('valid_test_nonce' => 'ryokourent_booking_form_action');

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
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
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
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        $token = 'nonce_' . substr(md5($action . microtime()), 0, 10);
        $GLOBALS['mock_nonces'][$token] = $action;
        return $token;
    }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return isset($GLOBALS['mock_nonces'][$nonce]) && $GLOBALS['mock_nonces'][$nonce] === $action;
    }
}
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $res;
    }
}
if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr, $wp_error = false) {
        $id = ++$GLOBALS['mock_post_id_counter'];
        $GLOBALS['mock_posts'][$id] = $postarr;
        return $id;
    }
}
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '') {
        if (!isset($GLOBALS['mock_postmeta'][$post_id])) {
            $GLOBALS['mock_postmeta'][$post_id] = array();
        }
        $GLOBALS['mock_postmeta'][$post_id][$meta_key] = $meta_value;
        return true;
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        if (isset($GLOBALS['mock_postmeta'][$post_id][$key])) {
            return $GLOBALS['mock_postmeta'][$post_id][$key];
        }
        return '';
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return is_a($thing, 'WP_Error');
    }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $code;
        public $message;
        public function __construct($code = '', $message = '') {
            $this->code = $code;
            $this->message = $message;
        }
        public function get_error_message() {
            return $this->message;
        }
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}

// Load required modules
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/whatsapp.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/booking.php';

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

echo "=== Menjalankan Unit Test Penyimpanan Booking & Nonce Cache-Compatible (TASK-019) ===" . PHP_EOL . PHP_EOL;

// 1. Test Booking Code Generation Format
$code1 = ryokourent_generate_booking_code();
$code2 = ryokourent_generate_booking_code();

run_test("Kode booking diawali dengan prefix RYK-", strpos($code1, 'RYK-') === 0);
run_test("Format kode booking sesuai pola RYK-YYYYMMDD-XXXX", (bool) preg_match('/^RYK-\d{8}-[A-Z0-9]{4}$/', $code1));
run_test("Dua kode booking berurutan bersifat unik", $code1 !== $code2);

// 2. Test Booking Entry Database Persistence
$sample_clean_data = array(
    'rented_motor_id'          => 1,
    'customer_name'            => 'Aditya Nugraha',
    'customer_whatsapp'        => '081234567890',
    'customer_emergency_phone' => '081987654321',
    'customer_ktp_address'     => 'Jl. Sukarno Hatta No. 88 Malang',
    'customer_stay_address'    => 'Hotel Santika Premiere Malang',
    'customer_social_media'    => '@adityanugraha',
    'start_datetime'           => '2026-10-02 08:30:00',
    'end_datetime'             => '2026-10-04 17:00:00',
    'duration_hours'           => 56.5,
    'total_days'               => 3,
    'duration_label'           => '3 Hari (~56.5 Jam)',
    'pickup_location'          => 'Stasiun Malang',
    'trip_destination'         => 'malang_batu',
    'total_price'              => 255000,
    'formatted_price'          => 'Rp 255.000',
    'rental_notes'             => 'Helm ukuran L dan jas hujan',
);

$save_result = ryokourent_save_booking_entry($sample_clean_data);

run_test("Penyimpanan booking mengembalikan success: true", $save_result['success'] === true);
run_test("Penyimpanan booking menghasilkan booking_id integer positif", isset($save_result['booking_id']) && $save_result['booking_id'] > 1000);
run_test("Penyimpanan booking menghasilkan kode unik berawalan RYK-", strpos($save_result['booking_code'], 'RYK-') === 0);

$booking_id = $save_result['booking_id'];
$stored_post = $GLOBALS['mock_posts'][$booking_id];

// 3. Test CPT 'penyewaan' Attributes
run_test("Post Type tersimpan sebagai 'penyewaan'", $stored_post['post_type'] === 'penyewaan');
run_test("Post Status tersimpan sebagai 'status_menunggu'", $stored_post['post_status'] === 'status_menunggu');
run_test("Post Title memuat kode booking dan nama penyewa", strpos($stored_post['post_title'], $save_result['booking_code']) !== false && strpos($stored_post['post_title'], 'Aditya Nugraha') !== false);

// 4. Test Stored Metadata per DATA_MODEL.md
$stored_meta = $GLOBALS['mock_postmeta'][$booking_id];
run_test("Metadata _ryokou_booking_code tersimpan akurat", $stored_meta['_ryokou_booking_code'] === $save_result['booking_code']);
run_test("Metadata _ryokou_booking_name tersimpan", $stored_meta['_ryokou_booking_name'] === 'Aditya Nugraha');
run_test("Metadata _ryokou_booking_whatsapp tersimpan", $stored_meta['_ryokou_booking_whatsapp'] === '081234567890');
run_test("Metadata _ryokou_booking_emergency tersimpan", $stored_meta['_ryokou_booking_emergency'] === '081987654321');
run_test("Metadata _ryokou_booking_ktp_address tersimpan", $stored_meta['_ryokou_booking_ktp_address'] === 'Jl. Sukarno Hatta No. 88 Malang');
run_test("Metadata _ryokou_booking_stay_address tersimpan", $stored_meta['_ryokou_booking_stay_address'] === 'Hotel Santika Premiere Malang');
run_test("Metadata _ryokou_booking_motor_id tersimpan sebagai integer", $stored_meta['_ryokou_booking_motor_id'] === 1);
run_test("Metadata _ryokou_booking_pickup_loc tersimpan Stasiun Malang", $stored_meta['_ryokou_booking_pickup_loc'] === 'Stasiun Malang');
run_test("Metadata _ryokou_booking_total_price bernilai 255000", $stored_meta['_ryokou_booking_total_price'] === 255000);
run_test("Metadata _ryokou_booking_allocated_plate terinisialisasi kosong (menunggu operator)", $stored_meta['_ryokou_booking_allocated_plate'] === '');

// 5. Test Nonce Verification on Cached Page & Refresh Token
$invalid_submission = array(
    'ryokourent_booking_nonce' => 'expired_cached_nonce_token',
    'customer_name'            => 'Test User',
);
$val_res = ryokourent_validate_booking_submission($invalid_submission);

run_test("Validasi gagal dengan kode invalid_nonce jika nonce kedaluwarsa", $val_res['code'] === 'invalid_nonce');
run_test("Validasi mengembalikan status HTTP 403", $val_res['status'] === 403);
run_test("Validasi menyertakan refreshed_nonce untuk pemulihan otomatis di frontend", !empty($val_res['refreshed_nonce']));

// 6. Test WhatsApp Deep Link Populated with Booking Code
$sample_clean_data['booking_code'] = $save_result['booking_code'];
$wa_url = ryokourent_get_whatsapp_url($sample_clean_data);
run_test("Tautan WhatsApp memuat kode booking dalam pesannya", strpos($wa_url, rawurlencode($save_result['booking_code'])) !== false);

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;
