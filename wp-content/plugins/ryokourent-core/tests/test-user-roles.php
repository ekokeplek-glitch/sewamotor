<?php
/**
 * Test Suite for Operator Role Registration (TASK-020)
 *
 * Verifies:
 * - Role `ryokourent_operator` registered with label "Operator Ryokourent".
 * - Capability whitelist exactly: read + manage_ryokourent_bookings.
 * - No theme/plugin/user/settings capabilities granted.
 * - Idempotent registration, stray capability removal, missing capability restore, label sync.
 * - Version-gated auto sync on init.
 * - Safe deactivation (role kept while in use) and full uninstall cleanup.
 * - Main plugin file wiring (activation/deactivation hooks).
 *
 * Run: php wp-content/plugins/ryokourent-core/tests/test-user-roles.php
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
$GLOBALS['mock_roles']        = array();
$GLOBALS['mock_users']        = array();
$GLOBALS['mock_options']      = array();
$GLOBALS['mock_actions']      = array();
$GLOBALS['mock_transients']   = array('ryokourent_dashboard_stats' => 'cached');
$GLOBALS['mock_wp_roles']     = (object) array(
    'roles'      => array(),
    'role_names' => array(),
    'role_key'   => 'wp_user_roles',
);

// Mock WordPress classes.
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
if (!class_exists('WP_User')) {
    class WP_User {
        public $ID;
        public $roles;
        public function __construct($id, $roles) {
            $this->ID    = $id;
            $this->roles = $roles;
        }
        public function remove_role($role) {
            $this->roles = array_values(array_diff($this->roles, array($role)));
        }
        public function add_role($role) {
            if (!in_array($role, $this->roles, true)) {
                $this->roles[] = $role;
            }
        }
    }
}

// Mock WordPress functions.
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['mock_actions'][] = array($tag, $callback, $priority);
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
if (!function_exists('delete_transient')) {
    function delete_transient($name) {
        unset($GLOBALS['mock_transients'][$name]);
        return true;
    }
}
if (!function_exists('wp_roles')) {
    function wp_roles() {
        return $GLOBALS['mock_wp_roles'];
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
            return null; // Perilaku WordPress: role sudah ada -> null.
        }
        $obj                           = new WP_Role($role, $capabilities);
        $GLOBALS['mock_roles'][$role]  = $obj;
        $store                         = $GLOBALS['mock_wp_roles'];
        $store->roles[$role]           = array('name' => $display_name, 'capabilities' => $capabilities);
        $store->role_names[$role]      = $display_name;
        return $obj;
    }
}
if (!function_exists('remove_role')) {
    function remove_role($role) {
        unset($GLOBALS['mock_roles'][$role]);
        unset($GLOBALS['mock_wp_roles']->roles[$role]);
        unset($GLOBALS['mock_wp_roles']->role_names[$role]);
    }
}
if (!function_exists('get_users')) {
    function get_users($args = array()) {
        $out = array();
        foreach ($GLOBALS['mock_users'] as $user) {
            if (isset($args['role']) && !in_array($args['role'], $user->roles, true)) {
                continue;
            }
            $out[] = (isset($args['fields']) && 'ID' === $args['fields']) ? $user->ID : $user;
            if (isset($args['number']) && count($out) >= $args['number']) {
                break;
            }
        }
        return $out;
    }
}

// Load module under test.
require_once RYOKOURENT_PLUGIN_DIR . 'includes/user-roles.php';

// Test runner.
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

echo "=== Menjalankan Unit Test Role Operator (TASK-020) ===" . PHP_EOL . PHP_EOL;

$slug = ryokourent_get_operator_role_slug();

// 1. Konstanta & whitelist capability.
run_test("Slug role operator adalah ryokourent_operator", 'ryokourent_operator' === $slug);

$allowed = ryokourent_get_operator_capabilities();
$expected_caps = array('read', 'manage_ryokourent_bookings', 'edit_motors', 'edit_others_motors', 'edit_published_motors');
run_test("Whitelist capability memuat read, bookings, dan edit motor eksisting", array_keys($allowed) === $expected_caps);

$forbidden = array(
    'manage_ryokourent_settings', 'create_motors', 'publish_motors', 'delete_motors',
    'delete_others_motors', 'delete_published_motors', 'delete_private_motors',
    'upload_files', 'manage_options', 'edit_theme_options', 'edit_themes', 'install_themes',
    'switch_themes', 'update_themes', 'delete_themes', 'edit_plugins', 'install_plugins', 'activate_plugins',
    'update_plugins', 'delete_plugins', 'create_users', 'edit_users', 'delete_users', 'list_users',
    'promote_users', 'remove_users', 'update_core', 'unfiltered_html',
);
$leak = array_intersect($forbidden, array_keys($allowed));
run_test("Whitelist tidak memuat capability terlarang (tambah/hapus motor, tarif, upload)", empty($leak));

// 2. Registrasi awal.
run_test("Role belum ada sebelum registrasi", null === get_role($slug));
run_test("ryokourent_register_operator_role() mengembalikan true", true === ryokourent_register_operator_role());

$role = get_role($slug);
run_test("Role ryokourent_operator terdaftar", is_object($role));
run_test("Label role adalah 'Operator Ryokourent'", 'Operator Ryokourent' === $GLOBALS['mock_wp_roles']->roles[$slug]['name']);
run_test("Role memiliki capability read", $role->has_cap('read'));
run_test("Role memiliki capability manage_ryokourent_bookings", $role->has_cap('manage_ryokourent_bookings'));
run_test("Role memiliki capability edit_motors", $role->has_cap('edit_motors'));
run_test("Role memiliki capability edit_others_motors", $role->has_cap('edit_others_motors'));
run_test("Role memiliki capability edit_published_motors", $role->has_cap('edit_published_motors'));
run_test("Role hanya memiliki 5 capability", 5 === count($role->capabilities));

$has_forbidden = false;
foreach ($forbidden as $cap) {
    if ($role->has_cap($cap)) {
        $has_forbidden = true;
    }
}
run_test("Role tidak memiliki satupun capability terlarang", !$has_forbidden);

// 3. Idempoten.
$before = $role->capabilities;
ryokourent_register_operator_role();
ryokourent_register_operator_role();
run_test("Registrasi berulang tidak mengubah capability (idempoten)", get_role($slug)->capabilities === $before);
run_test("Registrasi berulang tidak menggandakan role", 1 === count($GLOBALS['mock_roles']));

// 4. Sinkronisasi role yang sudah 'kotor'.
$role->add_cap('manage_options');
$role->add_cap('edit_themes');
$role->remove_cap('manage_ryokourent_bookings');
ryokourent_register_operator_role();
$role = get_role($slug);
run_test("Capability berlebih (manage_options, edit_themes) dicabut saat sinkronisasi", !$role->has_cap('manage_options') && !$role->has_cap('edit_themes'));
run_test("Capability whitelist yang hilang dipulihkan", $role->has_cap('manage_ryokourent_bookings') && $role->has_cap('read'));

// 5. Sinkronisasi label role lama.
$GLOBALS['mock_wp_roles']->roles[$slug]['name'] = 'Ryokourent Operator';
$GLOBALS['mock_wp_roles']->role_names[$slug]    = 'Ryokourent Operator';
ryokourent_register_operator_role();
run_test("Label role lama diselaraskan menjadi 'Operator Ryokourent'", 'Operator Ryokourent' === $GLOBALS['mock_wp_roles']->roles[$slug]['name']);
run_test("Perubahan label dipersist lewat update_option(role_key)", isset($GLOBALS['mock_options']['wp_user_roles'][$slug]) && 'Operator Ryokourent' === $GLOBALS['mock_options']['wp_user_roles'][$slug]['name']);

// 6. Install + sinkronisasi berbasis versi.
remove_role($slug);
$GLOBALS['mock_options'] = array();
run_test("ryokourent_install_operator_role() berhasil", true === ryokourent_install_operator_role());
run_test("Install menyimpan versi skema role", ryokourent_get_roles_version() === get_option('ryokourent_roles_version'));

$registered = false;
foreach ($GLOBALS['mock_actions'] as $action) {
    if ('init' === $action[0] && 'ryokourent_maybe_sync_operator_role' === $action[1]) {
        $registered = true;
    }
}
run_test("Sinkronisasi otomatis terpasang pada hook init", $registered);

remove_role($slug);
ryokourent_maybe_sync_operator_role();
run_test("Sinkronisasi dilewati jika versi skema sama (tidak menimpa perubahan manual)", null === get_role($slug));

delete_option('ryokourent_roles_version');
ryokourent_maybe_sync_operator_role();
run_test("Sinkronisasi berjalan jika versi skema belum tercatat", is_object(get_role($slug)) && ryokourent_get_roles_version() === get_option('ryokourent_roles_version'));

// 7. Deaktivasi aman.
$GLOBALS['mock_users'] = array(new WP_User(5, array($slug)));
run_test("Deaktivasi mengembalikan 'kept' saat role masih dipakai user", 'kept' === ryokourent_deactivate_operator_role());
run_test("Role tetap ada saat masih dipakai user", is_object(get_role($slug)));
run_test("Deaktivasi menghapus penanda versi skema", false === get_option('ryokourent_roles_version'));

$GLOBALS['mock_users'] = array();
run_test("Deaktivasi mengembalikan 'removed' saat tidak ada user", 'removed' === ryokourent_deactivate_operator_role());
run_test("Role terhapus bersih saat tidak ada user", null === get_role($slug));
run_test("Deaktivasi saat role tidak ada mengembalikan 'absent' tanpa error", 'absent' === ryokourent_deactivate_operator_role());

// 8. Uninstall (menjalankan uninstall.php apa adanya).
ryokourent_install_operator_role();
$GLOBALS['mock_roles']['administrator'] = new WP_Role('administrator', array(
    'manage_options'             => true,
    'manage_ryokourent_bookings' => true,
    'manage_ryokourent_settings' => true,
));
$user_only  = new WP_User(10, array($slug));
$user_multi = new WP_User(11, array($slug, 'editor'));
$user_other = new WP_User(12, array('subscriber'));
$GLOBALS['mock_users'] = array($user_only, $user_multi, $user_other);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', 'ryokourent-core/ryokourent-core.php');
}
include RYOKOURENT_PLUGIN_DIR . 'uninstall.php';

run_test("Uninstall menghapus role ryokourent_operator", null === get_role($slug));
run_test("Uninstall memindahkan user operator-saja ke subscriber", array('subscriber') === $user_only->roles);
run_test("Uninstall mempertahankan role lain milik user (editor)", array('editor') === $user_multi->roles);
run_test("Uninstall tidak menyentuh user non-operator", array('subscriber') === $user_other->roles);
run_test("Uninstall mencabut capability kustom dari administrator", !$GLOBALS['mock_roles']['administrator']->has_cap('manage_ryokourent_bookings') && !$GLOBALS['mock_roles']['administrator']->has_cap('manage_ryokourent_settings'));
run_test("Uninstall tidak mencabut capability bawaan administrator", $GLOBALS['mock_roles']['administrator']->has_cap('manage_options'));
run_test("Uninstall menghapus penanda versi skema role", false === get_option('ryokourent_roles_version'));
run_test("Uninstall tetap membersihkan transient dashboard", !isset($GLOBALS['mock_transients']['ryokourent_dashboard_stats']));

// 9. Wiring berkas utama plugin.
$main = file_get_contents(RYOKOURENT_PLUGIN_DIR . 'ryokourent-core.php');
run_test("Activation hook memanggil ryokourent_install_operator_role()", false !== strpos($main, 'ryokourent_install_operator_role()'));
run_test("Deactivation hook memanggil ryokourent_deactivate_operator_role()", false !== strpos($main, 'ryokourent_deactivate_operator_role()'));
run_test("Tidak ada lagi add_role operator inline di berkas utama", false === strpos($main, "add_role('ryokourent_operator'"));
run_test("Modul user-roles.php terdaftar di loader plugins_loaded", false !== strpos($main, "'includes/user-roles.php'"));

echo PHP_EOL . "Hasil: {$pass_count}/{$test_count} pengujian berhasil." . PHP_EOL;

exit($pass_count === $test_count ? 0 : 1);
