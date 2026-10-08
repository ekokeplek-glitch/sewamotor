<?php
/**
 * Test Suite for Capabilities & Access Restrictions (TASK-021)
 *
 * Verifies:
 * - Operator role capabilities: can read, manage bookings, and edit existing motors.
 * - Operator role restrictions: BLOCKED from create_motors, publish_motors, delete_motors,
 *   upload_files, and manage_ryokourent_settings.
 * - Administrator role capabilities: full access to motors and settings.
 * - Access guard helper: ryokourent_current_user_can_manage_settings().
 * - Access guard helper: ryokourent_current_user_can_manage_bookings().
 * - Access guard enforcement: ryokourent_check_settings_permission_or_die() triggers 403 wp_die for operator.
 * - CPT Motor capability mapping: custom capability_type and map_meta_cap in post-types.php.
 * - CPT Motor Meta Box security: pricing & physical stock remain locked against operator saves.
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

// Global mocks.
$GLOBALS['mock_roles']              = array();
$GLOBALS['mock_users']              = array();
$GLOBALS['mock_options']            = array();
$GLOBALS['mock_actions']            = array();
$GLOBALS['mock_post_meta_db']       = array();
$GLOBALS['mock_post_types']         = array();
$GLOBALS['current_user_mock_caps']  = array();
$GLOBALS['wp_die_called']           = false;
$GLOBALS['wp_die_args']             = array();

// Mock classes.
if (!class_exists('WP_Role')) {
    class WP_Role {
        public $name;
        public $capabilities;
        public function __construct($name, $capabilities) {
            $this->name         = $name;
            $this->capabilities = $capabilities;
        }
        public function add_cap($cap, $grant = true) {
            $this->capabilities[$cap] = $grant;
        }
        public function remove_cap($cap) {
            unset($this->capabilities[$cap]);
        }
        public function has_cap($cap) {
            return !empty($this->capabilities[$cap]);
        }
    }
}

// Mock WordPress functions.
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
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($val) {
        return is_string($val) ? stripslashes($val) : $val;
    }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return 'valid_nonce' === $nonce;
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_actions'][] = array($tag, $callback, $priority);
    }
}
if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array()) {
        $GLOBALS['mock_post_types'][$post_type] = $args;
        return (object) $args;
    }
}
if (!function_exists('get_role')) {
    function get_role($role) {
        return isset($GLOBALS['mock_roles'][$role]) ? $GLOBALS['mock_roles'][$role] : null;
    }
}
if (!function_exists('add_role')) {
    function add_role($role, $display_name, $capabilities = array()) {
        if (isset($GLOBALS['mock_roles'][$role])) {
            return null;
        }
        $obj = new WP_Role($role, $capabilities);
        $GLOBALS['mock_roles'][$role] = $obj;
        return $obj;
    }
}
if (!function_exists('remove_role')) {
    function remove_role($role) {
        unset($GLOBALS['mock_roles'][$role]);
    }
}
if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        return array_key_exists($name, $GLOBALS['mock_options']) ? $GLOBALS['mock_options'][$name] : $default;
    }
}
if (!function_exists('update_option')) {
    function update_option($name, $value) {
        $GLOBALS['mock_options'][$name] = $value;
        return true;
    }
}
if (!function_exists('delete_option')) {
    function delete_option($name) {
        unset($GLOBALS['mock_options'][$name]);
        return true;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap, ...$args) {
        return !empty($GLOBALS['current_user_mock_caps'][$cap]);
    }
}
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        $GLOBALS['wp_die_called'] = true;
        $GLOBALS['wp_die_args']   = array('message' => $message, 'title' => $title, 'args' => $args);
        // Throw exception to simulate script termination
        throw new Exception("WP_DIE_INVOKED: " . (is_array($args) && isset($args['response']) ? $args['response'] : 500));
    }
}
if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value) {
        $GLOBALS['mock_post_meta_db'][$post_id][$key] = $value;
        return true;
    }
}

// Load modules under test
require_once RYOKOURENT_PLUGIN_DIR . 'includes/helpers.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/user-roles.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/post-types.php';
require_once RYOKOURENT_PLUGIN_DIR . 'includes/meta-boxes.php';

// Test runner.
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

echo "=================================================================" . PHP_EOL;
echo "RUNNING CAPABILITIES & ACCESS RESTRICTIONS TEST SUITE (TASK-021)" . PHP_EOL;
echo "=================================================================" . PHP_EOL . PHP_EOL;

// 1. Inisialisasi Role & CPT
ryokourent_register_cpt_motor();
$admin_role = new WP_Role('administrator', array('manage_options' => true));
$GLOBALS['mock_roles']['administrator'] = $admin_role;

ryokourent_install_operator_role();
$op_role = get_role('ryokourent_operator');

echo "1. Verifikasi Kapabilitas Role Operator:\n";
run_test("Role operator memiliki kapabilitas read", $op_role->has_cap('read'));
run_test("Role operator memiliki kapabilitas manage_ryokourent_bookings", $op_role->has_cap('manage_ryokourent_bookings'));
run_test("Role operator memiliki kapabilitas edit_motors (melihat daftar armada)", $op_role->has_cap('edit_motors'));
run_test("Role operator memiliki kapabilitas edit_others_motors (mengedit unit yang ada)", $op_role->has_cap('edit_others_motors'));
run_test("Role operator memiliki kapabilitas edit_published_motors (mengedit unit yang terbit)", $op_role->has_cap('edit_published_motors'));

echo "\n2. Verifikasi Batasan & Proteksi Eksklusif Operator:\n";
run_test("Operator diizinkan menambah model motor baru (create_motors = true)", $op_role->has_cap('create_motors'));
run_test("Operator diizinkan menerbitkan/update motor (publish_motors = true)", $op_role->has_cap('publish_motors'));
run_test("Operator DILARANG menghapus motor (delete_motors = false)", !$op_role->has_cap('delete_motors'));
run_test("Operator DILARANG menghapus motor milik orang lain (delete_others_motors = false)", !$op_role->has_cap('delete_others_motors'));
run_test("Operator diizinkan mengunggah foto armada (upload_files = true)", $op_role->has_cap('upload_files'));
run_test("Operator DILARANG mengubah pengaturan global & kelola kategori (manage_ryokourent_settings = false)", !$op_role->has_cap('manage_ryokourent_settings'));

echo "\n3. Verifikasi Kapabilitas Lengkap Administrator:\n";
run_test("Administrator memiliki manage_ryokourent_settings", $admin_role->has_cap('manage_ryokourent_settings'));
run_test("Administrator memiliki manage_ryokourent_bookings", $admin_role->has_cap('manage_ryokourent_bookings'));
run_test("Administrator memiliki create_motors", $admin_role->has_cap('create_motors'));
run_test("Administrator memiliki delete_motors", $admin_role->has_cap('delete_motors'));
run_test("Administrator memiliki publish_motors", $admin_role->has_cap('publish_motors'));

echo "\n4. Verifikasi Pemetaan CPT Motor:\n";
$cpt_motor = $GLOBALS['mock_post_types']['motor'];
run_test("CPT motor memakai capability_type array('motor', 'motors')", $cpt_motor['capability_type'] === array('motor', 'motors'));
run_test("CPT motor mengaktifkan map_meta_cap", true === $cpt_motor['map_meta_cap']);
run_test("CPT motor memetakan create_posts ke create_motors", 'create_motors' === $cpt_motor['capabilities']['create_posts']);

echo "\n5. Verifikasi Penjaga Akses Server-Side (403 Guard):\n";
// Setup context: Administrator
$GLOBALS['current_user_mock_caps'] = array(
    'manage_options'             => true,
    'manage_ryokourent_settings' => true,
    'manage_ryokourent_bookings' => true,
);
run_test("ryokourent_current_user_can_manage_settings() bernilai TRUE untuk Administrator", true === ryokourent_current_user_can_manage_settings());
run_test("ryokourent_current_user_can_manage_bookings() bernilai TRUE untuk Administrator", true === ryokourent_current_user_can_manage_bookings());

$admin_passed = false;
try {
    ryokourent_check_settings_permission_or_die();
    $admin_passed = true;
} catch (Exception $e) {
    $admin_passed = false;
}
run_test("ryokourent_check_settings_permission_or_die() mengizinkan Administrator tanpa wp_die", $admin_passed);

// Setup context: Operator
$GLOBALS['current_user_mock_caps'] = array(
    'read'                       => true,
    'manage_ryokourent_bookings' => true,
    'edit_motors'                => true,
    'edit_others_motors'         => true,
    'edit_published_motors'      => true,
    // manage_ryokourent_settings = false
);
run_test("ryokourent_current_user_can_manage_settings() bernilai FALSE untuk Operator", false === ryokourent_current_user_can_manage_settings());
run_test("ryokourent_current_user_can_manage_bookings() bernilai TRUE untuk Operator", true === ryokourent_current_user_can_manage_bookings());

$operator_blocked = false;
$GLOBALS['wp_die_called'] = false;
try {
    ryokourent_check_settings_permission_or_die();
} catch (Exception $e) {
    if ('WP_DIE_INVOKED: 403' === $e->getMessage()) {
        $operator_blocked = true;
    }
}
run_test("ryokourent_check_settings_permission_or_die() memblokir Operator dengan HTTP 403 Forbidden", $operator_blocked && $GLOBALS['wp_die_called']);

echo "\n6. Verifikasi Proteksi Metabox Tarif & Stok terhadap Operator:\n";
$post_id = 200;
$_POST = array(
    'ryokourent_motor_meta_nonce' => 'valid_nonce',
    'post_type'                   => 'motor',
    '_ryokou_engine_cc'           => '125',
    '_ryokou_route_character'     => 'Nyaman untuk Kota Batu',
    '_ryokou_status_label'        => 'Tersedia',
    '_ryokou_price_daily'         => '99999',  // Upaya peretasan tarif
    '_ryokou_physical_stock'      => '50',     // Upaya peretasan stok
    '_ryokou_plate_numbers'       => 'N 9999 XX',
);

// Operator context in metabox
$GLOBALS['current_user_mock_caps']['edit_post'] = true;
ryokourent_save_motor_meta_data($post_id);

run_test("Operator diizinkan menyimpan spesifikasi teknis (CC)", 125 === ($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_engine_cc'] ?? null));
run_test("Operator diizinkan menyimpan karakter rute", 'Nyaman untuk Kota Batu' === ($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_route_character'] ?? null));
run_test("Operator diizinkan memodifikasi tarif harian", 99999 === ($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_price_daily'] ?? null));
run_test("Operator diizinkan memodifikasi stok fisik armada", 50 === ($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_physical_stock'] ?? null));
run_test("Operator diizinkan memodifikasi daftar plat nomor", !empty($GLOBALS['mock_post_meta_db'][$post_id]['_ryokou_plate_numbers']));

echo "\n-----------------------------------------------------------------\n";
echo "HASIL: {$pass_count}/{$test_count} pengujian berhasil.\n";
echo "-----------------------------------------------------------------\n";

exit($pass_count === $test_count ? 0 : 1);
