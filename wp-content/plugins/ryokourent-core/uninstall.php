<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Ryokourent_Core
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up temporary options or transients if necessary.
// We preserve CPT motor and penyewaan posts by default to avoid accidental business data loss.
delete_transient('ryokourent_dashboard_stats');

// -----------------------------------------------------------------------------
// Operator role cleanup (TASK-020).
// Plugin tidak dimuat saat uninstall, sehingga logika ditulis mandiri di sini.
// -----------------------------------------------------------------------------
$ryokourent_role_slug = 'ryokourent_operator';

// Pindahkan user yang masih memegang role operator agar tidak menjadi user tanpa role.
$ryokourent_operator_users = get_users(array('role' => $ryokourent_role_slug));
foreach ($ryokourent_operator_users as $ryokourent_user) {
    $ryokourent_user->remove_role($ryokourent_role_slug);
    if (empty($ryokourent_user->roles)) {
        $ryokourent_user->add_role('subscriber');
    }
}

remove_role($ryokourent_role_slug);

// Cabut capability kustom plugin dari administrator.
$ryokourent_admin_role = get_role('administrator');
if ($ryokourent_admin_role) {
    $ryokourent_custom_caps = array(
        'manage_ryokourent_bookings',
        'manage_ryokourent_settings',
        'edit_motors',
        'edit_others_motors',
        'publish_motors',
        'read_private_motors',
        'delete_motors',
        'delete_private_motors',
        'delete_published_motors',
        'delete_others_motors',
        'edit_private_motors',
        'edit_published_motors',
        'create_motors',
    );
    foreach ($ryokourent_custom_caps as $ryokourent_cap) {
        $ryokourent_admin_role->remove_cap($ryokourent_cap);
    }
}

delete_option('ryokourent_roles_version');
