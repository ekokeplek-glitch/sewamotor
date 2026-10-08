<?php
/**
 * Dashboard Operasional Ryokourent (TASK-022).
 *
 * Metrik: Unit Disewa Hari Ini, Booking Menunggu, Unit Aktif per lokasi.
 * Query ringan (fields => ids, no_found_rows) + transient 5 menit.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/admin
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('RYOKOURENT_DASHBOARD_CACHE_KEY')) {
    define('RYOKOURENT_DASHBOARD_CACHE_KEY', 'ryokourent_dashboard_stats');
}
if (!defined('RYOKOURENT_DASHBOARD_QUERY_LIMIT')) {
    define('RYOKOURENT_DASHBOARD_QUERY_LIMIT', 500);
}

/**
 * Rentang hari ini (WIB) dalam format meta booking (Y-m-d H:i).
 *
 * @param string|null $now Waktu acuan (untuk pengujian).
 * @return array{date:string,start:string,end:string}
 */
function ryokourent_dashboard_today_range($now = null) {
    $ref   = new DateTimeImmutable($now ? $now : 'now', ryokourent_get_timezone());
    $start = $ref->setTime(0, 0, 0);

    return array(
        'date'  => $start->format('Y-m-d'),
        'start' => $start->format('Y-m-d H:i'),
        'end'   => $start->modify('+1 day')->format('Y-m-d H:i'),
    );
}

/**
 * Ubah nilai pickup tersimpan (label dari form booking, atau slug) menjadi slug lokasi resmi.
 *
 * @param string $raw Nilai meta `_ryokou_booking_pickup_loc`.
 * @return string Slug lokasi, atau nilai asli jika tidak dikenal.
 */
function ryokourent_dashboard_pickup_to_slug($raw) {
    $raw = trim((string) $raw);
    $map = array(
        'Pool Dinoyo'    => 'pool_dinoyo',
        'Pool Batu'      => 'pool_batu',
        'Stasiun Malang' => 'stasiun_malang',
        'Hotel/Homestay' => 'antar_hotel',
    );

    return isset($map[$raw]) ? $map[$raw] : $raw;
}

/**
 * Ambil ID booking secara efisien (tanpa posts_per_page => -1).
 *
 * @param string|string[] $statuses   Status post.
 * @param array           $meta_query Meta query opsional.
 * @param bool            $need_meta  True jika meta akan dibaca per ID (prime cache).
 * @return int[]
 */
function ryokourent_dashboard_query_ids($statuses, $meta_query = array(), $need_meta = false) {
    $args = array(
        'post_type'              => 'penyewaan',
        'post_status'            => $statuses,
        'fields'                 => 'ids',
        'posts_per_page'         => RYOKOURENT_DASHBOARD_QUERY_LIMIT,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => (bool) $need_meta,
    );
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }

    $query = new WP_Query($args);

    return array_map('intval', (array) $query->posts);
}

/**
 * Hitung unit aktif per lokasi; slug tak dikenal masuk "Lainnya".
 *
 * @param string[]             $slugs  Slug lokasi tiap booking berjalan.
 * @param array<string,string> $labels slug => label resmi.
 * @return array<string,array{label:string,count:int}>
 */
function ryokourent_dashboard_tally_pools(array $slugs, array $labels) {
    $out = array();
    foreach ($labels as $slug => $label) {
        $out[$slug] = array('label' => (string) $label, 'count' => 0);
    }
    foreach ($slugs as $slug) {
        if (!isset($out[$slug])) {
            if (!isset($out['lainnya'])) {
                $out['lainnya'] = array('label' => __('Lainnya', 'ryokourent'), 'count' => 0);
            }
            $slug = 'lainnya';
        }
        $out[$slug]['count']++;
    }

    return $out;
}

/**
 * Statistik dashboard (cache transient 5 menit; diabaikan jika tanggal WIB sudah berganti).
 *
 * @param bool $force Lewati cache.
 * @return array
 */
function ryokourent_dashboard_get_stats($force = false) {
    $range = ryokourent_dashboard_today_range();

    if (!$force) {
        $cached = get_transient(RYOKOURENT_DASHBOARD_CACHE_KEY);
        if (is_array($cached) && isset($cached['date']) && $cached['date'] === $range['date']) {
            return $cached;
        }
    }

    $pending = ryokourent_dashboard_query_ids('status_menunggu');
    $today   = ryokourent_dashboard_query_ids(
        array('status_dikonfirmasi', 'status_berjalan'),
        array(
            'relation' => 'AND',
            array(
                'key'     => '_ryokou_booking_start_datetime',
                'value'   => $range['end'],
                'compare' => '<',
                'type'    => 'DATETIME',
            ),
            array(
                'key'     => '_ryokou_booking_end_datetime',
                'value'   => $range['start'],
                'compare' => '>',
                'type'    => 'DATETIME',
            ),
        )
    );
    $running = ryokourent_dashboard_query_ids('status_berjalan', array(), true);

    $slugs = array();
    foreach ($running as $booking_id) {
        $slugs[] = ryokourent_dashboard_pickup_to_slug(get_post_meta($booking_id, '_ryokou_booking_pickup_loc', true));
    }

    $limit = RYOKOURENT_DASHBOARD_QUERY_LIMIT;
    $stats = array(
        'date'         => $range['date'],
        'generated_at' => ryokourent_get_now_wib('H:i'),
        'rented_today' => count($today),
        'pending'      => count($pending),
        'active_total' => count($running),
        'pools'        => ryokourent_dashboard_tally_pools($slugs, ryokourent_get_pool_locations()),
        'capped'       => (count($pending) >= $limit || count($today) >= $limit || count($running) >= $limit),
    );

    set_transient(RYOKOURENT_DASHBOARD_CACHE_KEY, $stats, 5 * MINUTE_IN_SECONDS);

    return $stats;
}

/**
 * Buang cache statistik.
 */
function ryokourent_dashboard_flush_cache() {
    delete_transient(RYOKOURENT_DASHBOARD_CACHE_KEY);
}

/**
 * Buang cache saat status/booking penyewaan berubah (termasuk booking baru).
 *
 * @param string  $new_status Status baru.
 * @param string  $old_status Status lama.
 * @param WP_Post $post       Objek post.
 */
function ryokourent_dashboard_maybe_flush_on_transition($new_status, $old_status, $post) {
    if ($post && isset($post->post_type) && 'penyewaan' === $post->post_type) {
        ryokourent_dashboard_flush_cache();
    }
}
add_action('transition_post_status', 'ryokourent_dashboard_maybe_flush_on_transition', 10, 3);

/**
 * Buang cache saat booking dihapus.
 *
 * @param int $post_id ID post.
 */
function ryokourent_dashboard_maybe_flush_on_delete($post_id) {
    if ('penyewaan' === get_post_type($post_id)) {
        ryokourent_dashboard_flush_cache();
    }
}
add_action('deleted_post', 'ryokourent_dashboard_maybe_flush_on_delete');

/**
 * Daftarkan submenu Dashboard di bawah menu Penyewaan.
 */
function ryokourent_dashboard_register_menu() {
    add_submenu_page(
        'edit.php?post_type=penyewaan',
        __('Dashboard Operasional', 'ryokourent'),
        __('Dashboard', 'ryokourent'),
        'manage_ryokourent_bookings',
        'ryokourent-dashboard',
        'ryokourent_dashboard_render_page'
    );
}
add_action('admin_menu', 'ryokourent_dashboard_register_menu');

/**
 * Render halaman dashboard.
 */
function ryokourent_dashboard_render_page() {
    if (!current_user_can('manage_ryokourent_bookings')) {
        wp_die(
            esc_html__('Anda tidak memiliki wewenang untuk mengakses halaman ini.', 'ryokourent'),
            '',
            array('response' => 403)
        );
    }

    $s           = ryokourent_dashboard_get_stats();
    $pending_url = add_query_arg(array('post_type' => 'penyewaan', 'post_status' => 'status_menunggu'), admin_url('edit.php'));
    $running_url = add_query_arg(array('post_type' => 'penyewaan', 'post_status' => 'status_berjalan'), admin_url('edit.php'));
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Dashboard Operasional Ryokourent', 'ryokourent'); ?></h1>
        <p>
            <?php
            /* translators: %s: jam WIB */
            echo esc_html(sprintf(__('Diperbarui pukul %s WIB (cache 5 menit).', 'ryokourent'), $s['generated_at']));
            ?>
        </p>
        <?php if (!empty($s['capped'])) : ?>
            <div class="notice notice-warning"><p><?php echo esc_html__('Jumlah data melebihi batas hitung; angka bisa lebih rendah dari kenyataan.', 'ryokourent'); ?></p></div>
        <?php endif; ?>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <div class="card">
                <h2><?php echo esc_html__('Unit Disewa Hari Ini', 'ryokourent'); ?></h2>
                <p style="font-size:32px;"><?php echo esc_html(number_format_i18n($s['rented_today'])); ?></p>
                <a href="<?php echo esc_url($running_url); ?>"><?php echo esc_html__('Lihat sewa berjalan', 'ryokourent'); ?></a>
            </div>
            <div class="card">
                <h2><?php echo esc_html__('Menunggu Konfirmasi', 'ryokourent'); ?></h2>
                <p style="font-size:32px;"><?php echo esc_html(number_format_i18n($s['pending'])); ?></p>
                <a href="<?php echo esc_url($pending_url); ?>"><?php echo esc_html__('Proses sekarang', 'ryokourent'); ?></a>
            </div>
        </div>
        <h2><?php echo esc_html__('Unit Aktif (Sewa Berjalan) per Lokasi', 'ryokourent'); ?></h2>
        <table class="widefat striped" style="max-width:560px;">
            <tbody>
            <?php foreach ($s['pools'] as $pool) : ?>
                <tr>
                    <td><?php echo esc_html($pool['label']); ?></td>
                    <td><?php echo esc_html(number_format_i18n($pool['count'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
