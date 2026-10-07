<?php
/**
 * Kolom daftar admin untuk CPT 'penyewaan' (TASK-022).
 *
 * TASK-022: kolom ringkas. TASK-023: kolom "Status & Aksi" berisi quick action perubahan status
 * (Konfirmasi, Serah Terima/Berjalan + plat nomor, Selesai, Batalkan) yang dilindungi nonce,
 * capability, whitelist, dan aturan transisi di includes/booking.php.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/admin
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Definisikan kolom daftar penyewaan.
 *
 * @param array $columns Kolom bawaan.
 * @return array
 */
function ryokourent_booking_columns($columns) {
    if (!current_user_can('manage_ryokourent_bookings')) {
        return $columns;
    }

    $new = array();
    if (isset($columns['cb'])) {
        $new['cb'] = $columns['cb'];
    }
    $new['title']              = __('Kode & Penyewa', 'ryokourent');
    $new['ryokourent_motor']   = __('Motor', 'ryokourent');
    $new['ryokourent_jadwal']  = __('Jadwal Sewa', 'ryokourent');
    $new['ryokourent_total']   = __('Total', 'ryokourent');
    $new['ryokourent_status']  = __('Status & Aksi', 'ryokourent');
    if (isset($columns['date'])) {
        $new['date'] = $columns['date'];
    }

    return $new;
}
add_filter('manage_penyewaan_posts_columns', 'ryokourent_booking_columns');

/**
 * Render isi kolom (semua output di-escape).
 *
 * @param string $column  Kunci kolom.
 * @param int    $post_id ID booking.
 */
function ryokourent_booking_column_content($column, $post_id) {
    if (!current_user_can('manage_ryokourent_bookings')) {
        return;
    }

    switch ($column) {
        case 'ryokourent_motor':
            $motor_id = absint(get_post_meta($post_id, '_ryokou_booking_motor_id', true));
            echo esc_html($motor_id ? get_the_title($motor_id) : '—');
            break;
        case 'ryokourent_jadwal':
            $start = (string) get_post_meta($post_id, '_ryokou_booking_start_datetime', true);
            $end   = (string) get_post_meta($post_id, '_ryokou_booking_end_datetime', true);
            echo esc_html($start . ' → ' . $end);
            break;
        case 'ryokourent_total':
            $total = (int) get_post_meta($post_id, '_ryokou_booking_total_price', true);
            echo esc_html($total > 0 ? 'Rp ' . number_format($total, 0, ',', '.') : '—');
            break;
        case 'ryokourent_status':
            ryokourent_render_status_actions($post_id);
            break;
    }
}
add_action('manage_penyewaan_posts_custom_column', 'ryokourent_booking_column_content', 10, 2);

/**
 * Label tombol quick action per status tujuan.
 *
 * @return array status tujuan => label.
 */
function ryokourent_status_action_labels() {
    return array(
        'status_dikonfirmasi' => __('Konfirmasi', 'ryokourent'),
        'status_berjalan'     => __('Serah Terima (Berjalan)', 'ryokourent'),
        'status_selesai'      => __('Selesai', 'ryokourent'),
        'status_dibatalkan'   => __('Batalkan', 'ryokourent'),
    );
}

/**
 * Label status untuk tampilan kolom.
 *
 * @return array status => label.
 */
function ryokourent_status_display_labels() {
    return array(
        'status_menunggu'     => __('Menunggu Konfirmasi', 'ryokourent'),
        'status_dikonfirmasi' => __('Dikonfirmasi', 'ryokourent'),
        'status_berjalan'     => __('Sewa Berjalan', 'ryokourent'),
        'status_selesai'      => __('Selesai', 'ryokourent'),
        'status_dibatalkan'   => __('Dibatalkan', 'ryokourent'),
    );
}

/**
 * Render status saat ini + tombol quick action yang valid untuk status tersebut.
 *
 * Tombol memakai name="ryokourent_status_action" value="<id>:<status>" sehingga hanya
 * tombol yang diklik yang terkirim (formulir daftar WP membungkus semua baris).
 * Plat nomor dikirim sebagai ryokourent_plate[<id>].
 *
 * @param int $post_id ID booking.
 */
function ryokourent_render_status_actions($post_id) {
    $post_id = absint($post_id);
    $current = (string) get_post_status($post_id);
    $labels  = ryokourent_status_display_labels();

    echo '<strong>' . esc_html(isset($labels[$current]) ? $labels[$current] : '—') . '</strong>';

    $transitions = ryokourent_get_status_transitions();
    $targets     = isset($transitions[$current]) ? $transitions[$current] : array();
    if (empty($targets)) {
        return;
    }

    $action_labels = ryokourent_status_action_labels();
    echo '<div class="ryokourent-status-actions" style="margin-top:6px;">';
    printf(
        '<input type="hidden" name="%s" value="%s" />',
        esc_attr('ryokourent_status_nonce_' . $post_id),
        esc_attr(wp_create_nonce('ryokourent_status_' . $post_id))
    );

    if (in_array('status_berjalan', $targets, true)) {
        printf(
            '<input type="text" name="%s" placeholder="%s" style="width:120px; display:block; margin-bottom:4px;" autocomplete="off" />',
            esc_attr('ryokourent_plate[' . $post_id . ']'),
            esc_attr__('Plat nomor unit', 'ryokourent')
        );
    }

    foreach ($targets as $target) {
        if (!isset($action_labels[$target])) {
            continue;
        }
        printf(
            '<button type="submit" name="ryokourent_status_action" value="%s" class="button button-small" style="margin:0 4px 4px 0;">%s</button>',
            esc_attr($post_id . ':' . $target),
            esc_html($action_labels[$target])
        );
    }
    echo '</div>';
}

/**
 * Otorisasi quick action: capability (403) lalu nonce per booking.
 *
 * @param int $booking_id ID booking.
 */
function ryokourent_authorize_status_action($booking_id) {
    if (!current_user_can('manage_ryokourent_bookings')) {
        wp_die(esc_html__('Akses ditolak.', 'ryokourent'), '', array('response' => 403));
    }
    check_admin_referer('ryokourent_status_' . absint($booking_id), 'ryokourent_status_nonce_' . absint($booking_id));
}

/**
 * Proses satu quick action (tanpa redirect) dan simpan flash notice.
 *
 * @param int    $booking_id ID booking.
 * @param string $to         Status tujuan.
 * @param string $plate      Plat nomor mentah.
 * @return array Hasil standar dari ryokourent_transition_booking_status().
 */
function ryokourent_process_status_action($booking_id, $to, $plate = '') {
    $result  = ryokourent_transition_booking_status($booking_id, $to, $plate);
    $user_id = function_exists('get_current_user_id') ? get_current_user_id() : 0;

    if ($user_id > 0) {
        if ($result['success']) {
            set_transient('ryokourent_admin_success_' . $user_id, $result['message'], 45);
        } else {
            set_transient('ryokourent_admin_error_' . $user_id, $result['message'], 45);
        }
    }

    return $result;
}

/**
 * Hook load-edit.php: tangkap POST quick action dari daftar Penyewaan.
 */
function ryokourent_handle_booking_status_action() {
    if (!isset($_POST['ryokourent_status_action'], $_REQUEST['post_type']) || 'penyewaan' !== $_REQUEST['post_type']) {
        return;
    }

    $raw = sanitize_text_field(wp_unslash($_POST['ryokourent_status_action']));
    if (!preg_match('/^([0-9]+):(status_[a-z]+)$/', $raw, $m)) {
        return;
    }

    $booking_id = absint($m[1]);
    $to         = $m[2];

    ryokourent_authorize_status_action($booking_id);

    $plate = '';
    if (isset($_POST['ryokourent_plate']) && is_array($_POST['ryokourent_plate']) && isset($_POST['ryokourent_plate'][$booking_id]) && is_scalar($_POST['ryokourent_plate'][$booking_id])) {
        $plate = wp_unslash((string) $_POST['ryokourent_plate'][$booking_id]);
    }

    ryokourent_process_status_action($booking_id, $to, $plate);

    $back = wp_get_referer();
    wp_safe_redirect($back ? $back : admin_url('edit.php?post_type=penyewaan'));
    exit;
}
add_action('load-edit.php', 'ryokourent_handle_booking_status_action');

/**
 * Tampilkan notice sukses quick action (notice error memakai handler di availability.php).
 */
function ryokourent_display_status_success_notice() {
    $user_id = function_exists('get_current_user_id') ? get_current_user_id() : 0;
    if ($user_id <= 0) {
        return;
    }
    $notice = get_transient('ryokourent_admin_success_' . $user_id);
    if ($notice) {
        delete_transient('ryokourent_admin_success_' . $user_id);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
    }
}
add_action('admin_notices', 'ryokourent_display_status_success_notice');
