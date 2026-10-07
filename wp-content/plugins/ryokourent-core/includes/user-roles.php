<?php
/**
 * User Roles: Operator Ryokourent (TASK-020)
 *
 * Mendaftarkan role `ryokourent_operator` dengan hak akses operasional minimal.
 * Role ini sengaja TIDAK memiliki hak untuk mengedit tema, plugin, user,
 * maupun pengaturan/tarif (`manage_ryokourent_settings` hanya milik administrator).
 *
 * Siklus hidup:
 * - Aktivasi plugin   : ryokourent_install_operator_role()  (idempoten, menyelaraskan role lama).
 * - Muat ulang (init) : ryokourent_maybe_sync_operator_role() (sekali per versi skema role).
 * - Deaktivasi plugin : ryokourent_deactivate_operator_role() (role dihapus hanya jika tidak dipakai user).
 * - Uninstall plugin  : ditangani di uninstall.php (user dipindah ke subscriber, role & cap dibersihkan).
 *
 * Pemberian capability ke administrator dan pembatasan akses halaman admin
 * ditangani pada TASK-021 (di luar lingkup file ini).
 *
 * @package Ryokourent_Core
 * @since   1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Slug role operator.
 *
 * @return string
 */
function ryokourent_get_operator_role_slug() {
    return 'ryokourent_operator';
}

/**
 * Versi skema role. Naikkan nilainya jika daftar capability operator berubah
 * agar instalasi yang sudah berjalan menyelaraskan dirinya secara otomatis.
 *
 * @return string
 */
function ryokourent_get_roles_version() {
    return '2';
}

/**
 * Daftar capability yang BOLEH dimiliki operator (whitelist mutlak).
 * Sesuai tinjauan arsitektur (reviewOP):
 * - Boleh: melihat dan mengedit motor eksisting (spesifikasi, deskripsi, status publik).
 * - Dilarang: menambah model motor baru (create_motors), menghapus (delete_*), menerbitkan (publish_*),
 *   mengubah tarif & stok (manage_ryokourent_settings), serta upload media global (upload_files).
 * Capability lain di role ini dianggap tidak sah dan akan dicabut saat sinkronisasi.
 *
 * @return array<string,bool>
 */
function ryokourent_get_operator_capabilities() {
    return array(
        'read'                       => true,
        'manage_ryokourent_bookings' => true,
        'edit_motors'                => true,
        'edit_others_motors'         => true,
        'edit_published_motors'      => true,
    );
}

/**
 * Daftar capability lengkap yang dimiliki Administrator untuk modul Ryokourent.
 *
 * @return array<string,bool>
 */
function ryokourent_get_administrator_capabilities() {
    return array(
        'manage_ryokourent_bookings' => true,
        'manage_ryokourent_settings' => true,
        'edit_motors'                => true,
        'edit_others_motors'         => true,
        'publish_motors'             => true,
        'read_private_motors'        => true,
        'delete_motors'              => true,
        'delete_private_motors'      => true,
        'delete_published_motors'    => true,
        'delete_others_motors'       => true,
        'edit_private_motors'        => true,
        'edit_published_motors'      => true,
        'create_motors'              => true,
    );
}

/**
 * Menambahkan seluruh capability kustom Ryokourent ke role administrator.
 *
 * @return bool True jika berhasil diberikan.
 */
function ryokourent_grant_admin_capabilities() {
    if (!function_exists('get_role')) {
        return false;
    }

    $admin = get_role('administrator');
    if (!$admin) {
        return false;
    }

    foreach (ryokourent_get_administrator_capabilities() as $cap => $grant) {
        $admin->add_cap($cap, $grant);
    }

    return true;
}

/**
 * Menyelaraskan label tampilan role pada data role WordPress.
 * add_role() tidak memperbarui label role yang sudah ada, sehingga dilakukan manual.
 *
 * @param string $label Label baru.
 * @return bool True jika label diperbarui.
 */
function ryokourent_sync_operator_role_label($label) {
    if (!function_exists('wp_roles')) {
        return false;
    }

    $slug     = ryokourent_get_operator_role_slug();
    $wp_roles = wp_roles();

    if (!isset($wp_roles->roles[$slug]) || $wp_roles->roles[$slug]['name'] === $label) {
        return false;
    }

    $wp_roles->roles[$slug]['name'] = $label;
    $wp_roles->role_names[$slug]    = $label;
    update_option($wp_roles->role_key, $wp_roles->roles);

    return true;
}

/**
 * Mendaftarkan role operator. Aman dipanggil berulang (idempoten):
 * - Role belum ada     : dibuat dengan capability whitelist.
 * - Role sudah ada     : capability di luar whitelist dicabut, yang kurang dipulihkan.
 *
 * @return bool True jika role tersedia dan sesuai whitelist setelah pemanggilan.
 */
function ryokourent_register_operator_role() {
    if (!function_exists('get_role') || !function_exists('add_role')) {
        return false;
    }

    $slug    = ryokourent_get_operator_role_slug();
    $allowed = ryokourent_get_operator_capabilities();
    $label   = __('Operator Ryokourent', 'ryokourent');
    $role    = get_role($slug);

    if (!$role) {
        $role = add_role($slug, $label, $allowed);
        return is_object($role);
    }

    // Cabut capability yang tidak ada di whitelist (mencegah eskalasi privilege).
    foreach (array_keys($role->capabilities) as $cap) {
        if (!isset($allowed[$cap])) {
            $role->remove_cap($cap);
        }
    }

    // Pulihkan capability whitelist yang hilang.
    foreach ($allowed as $cap => $grant) {
        if (empty($role->capabilities[$cap])) {
            $role->add_cap($cap, $grant);
        }
    }

    ryokourent_sync_operator_role_label($label);

    return true;
}

/**
 * Mendaftarkan role lalu menandai versi skema role pada database.
 * Dipakai oleh activation hook dan sinkronisasi otomatis.
 *
 * @return bool
 */
function ryokourent_install_operator_role() {
    $ok = ryokourent_register_operator_role();

    if ($ok) {
        ryokourent_grant_admin_capabilities();
        update_option('ryokourent_roles_version', ryokourent_get_roles_version());
    }

    return $ok;
}

/**
 * Sinkronisasi otomatis role ketika versi skema berubah (mis. plugin diperbarui
 * lewat penyalinan berkas tanpa aktivasi ulang). Tidak menimpa perubahan manual
 * pada role selama versi skema sama.
 *
 * @return void
 */
function ryokourent_maybe_sync_operator_role() {
    if (get_option('ryokourent_roles_version') === ryokourent_get_roles_version()) {
        return;
    }

    ryokourent_install_operator_role();
}
add_action('init', 'ryokourent_maybe_sync_operator_role', 5);

/**
 * Pembersihan saat deaktivasi plugin.
 *
 * Role dihapus hanya jika tidak ada user yang memakainya, supaya akun operator
 * yang sudah ada tidak kehilangan role secara diam-diam. Penghapusan penuh
 * dilakukan pada uninstall.php.
 *
 * @return string 'absent' (role tidak ada) | 'kept' (masih dipakai user) | 'removed'.
 */
function ryokourent_deactivate_operator_role() {
    $slug   = ryokourent_get_operator_role_slug();
    $status = 'absent';

    if (get_role($slug)) {
        $users = get_users(array(
            'role'   => $slug,
            'number' => 1,
            'fields' => 'ID',
        ));

        if (empty($users)) {
            remove_role($slug);
            $status = 'removed';
        } else {
            $status = 'kept';
        }
    }

    delete_option('ryokourent_roles_version');

    return $status;
}

// -----------------------------------------------------------------------------
// Helper Fungsi Pemeriksaan Hak Akses & Penjaga Akses (TASK-021)
// -----------------------------------------------------------------------------

/**
 * Memeriksa apakah user yang sedang aktif memiliki hak mengelola pengaturan Ryokourent.
 * Eksklusif hanya untuk Administrator (manage_ryokourent_settings atau manage_options).
 *
 * @since 1.0.0
 * @return bool True jika diizinkan, false jika ditolak.
 */
function ryokourent_current_user_can_manage_settings() {
    if (!function_exists('current_user_can')) {
        return false;
    }

    return current_user_can('manage_ryokourent_settings') || current_user_can('manage_options');
}

/**
 * Memeriksa apakah user yang sedang aktif memiliki hak mengelola pemesanan/booking rental.
 * Diberikan untuk Administrator dan Operator Ryokourent (manage_ryokourent_bookings).
 *
 * @since 1.0.0
 * @return bool True jika diizinkan, false jika ditolak.
 */
function ryokourent_current_user_can_manage_bookings() {
    if (!function_exists('current_user_can')) {
        return false;
    }

    return current_user_can('manage_ryokourent_bookings');
}

/**
 * Penjaga Akses Server-Side: Menolak akses dengan HTTP 403 Forbidden bila user
 * tidak memiliki capability manage_ryokourent_settings.
 *
 * Fungsi ini disiapkan untuk menjaga halaman admin settings (TASK-024) dan endpoint AJAX
 * konfigurasi tarif/kuota agar tidak bisa diakses langsung via URL oleh operator.
 *
 * @since 1.0.0
 * @param string $message Pesan penolakan khusus (opsional).
 * @return void Menghentikan eksekusi via wp_die() dengan kode HTTP 403.
 */
function ryokourent_check_settings_permission_or_die($message = '') {
    if (!ryokourent_current_user_can_manage_settings()) {
        if (empty($message)) {
            $message = __('Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses atau memodifikasi pengaturan Ryokourent.', 'ryokourent');
        }

        if (function_exists('wp_die')) {
            wp_die(
                esc_html($message),
                esc_html__('Akses Ditolak (403 Forbidden)', 'ryokourent'),
                array('response' => 403)
            );
        } else {
            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }
            exit(esc_html($message));
        }
    }
}
