<?php
/**
 * Admin Settings & Bulk Price Adjustment Interface for Ryokourent (TASK-024)
 *
 * Provides backend administrative interfaces for:
 * - Official WhatsApp admin contact, secondary fallback, and default draft template.
 * - Daily pool operating hours (07:00 - 23:00 WIB).
 * - Official pool location addresses and Google Maps deep links.
 * - Multi-tier bulk price adjustment (nominal and percentage) with strict boundary validation (ADR-012).
 *
 * Security: Protected by capability 'manage_ryokourent_settings' and WordPress admin nonces.
 * Operator role is denied access with HTTP 403 Forbidden.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/admin
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Ryokourent settings submenus in WP-Admin.
 *
 * Registered under CPT 'penyewaan' and CPT 'motor' menus for operator/admin convenience.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_settings_menu() {
    // Under CPT 'penyewaan'
    add_submenu_page(
        'edit.php?post_type=penyewaan',
        __('Pengaturan Ryokourent', 'ryokourent'),
        __('Pengaturan', 'ryokourent'),
        'manage_ryokourent_settings',
        'ryokourent-settings',
        'ryokourent_render_settings_page'
    );

    // Also under CPT 'motor' for easy access
    add_submenu_page(
        'edit.php?post_type=motor',
        __('Pengaturan Tarif & Sistem', 'ryokourent'),
        __('Pengaturan Tarif', 'ryokourent'),
        'manage_ryokourent_settings',
        'ryokourent-settings-motor',
        'ryokourent_render_settings_page'
    );
}
add_action('admin_menu', 'ryokourent_register_settings_menu');

/**
 * Render Ryokourent Admin Settings & Bulk Price Management Page.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_render_settings_page() {
    // 1. Strict Server-side Access Guard (HTTP 403 Forbidden for Operator or unauthorized roles)
    if (function_exists('ryokourent_check_settings_permission_or_die')) {
        ryokourent_check_settings_permission_or_die();
    } elseif (!current_user_can('manage_ryokourent_settings') && !current_user_can('manage_options')) {
        wp_die(
            esc_html__('Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.', 'ryokourent'),
            esc_html__('403 Forbidden', 'ryokourent'),
            array('response' => 403)
        );
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    $message    = '';
    $msg_type   = 'success';
    $bulk_logs  = array();

    // 2. Handle POST Submissions
    if ('POST' === $_SERVER['REQUEST_METHOD']) {
        // Tab 1: General & WhatsApp Settings Save
        if (isset($_POST['ryokourent_action']) && 'save_general' === $_POST['ryokourent_action']) {
            check_admin_referer('ryokourent_save_settings_action', 'ryokourent_settings_nonce');

            if (function_exists('ryokourent_save_general_settings')) {
                $save_result = ryokourent_save_general_settings($_POST);
                if (!empty($save_result['success'])) {
                    $message  = $save_result['message'];
                    $msg_type = 'success';
                } else {
                    $message  = !empty($save_result['message']) ? $save_result['message'] : __('Gagal menyimpan pengaturan.', 'ryokourent');
                    if (!empty($save_result['errors'])) {
                        $message .= ' (' . implode(', ', array_map('esc_html', $save_result['errors'])) . ')';
                    }
                    $msg_type = 'error';
                }
            }
        }

        // Tab 2: Bulk Price Adjustment Execution
        if (isset($_POST['ryokourent_action']) && 'apply_bulk_price' === $_POST['ryokourent_action']) {
            check_admin_referer('ryokourent_bulk_price_action', 'ryokourent_bulk_price_nonce');

            if (function_exists('ryokourent_apply_bulk_price_adjustment')) {
                $bulk_result = ryokourent_apply_bulk_price_adjustment($_POST);
                if (!empty($bulk_result['success'])) {
                    $message   = $bulk_result['message'];
                    $msg_type  = 'success';
                    $bulk_logs = !empty($bulk_result['logs']) ? $bulk_result['logs'] : array();
                } else {
                    $message  = !empty($bulk_result['message']) ? $bulk_result['message'] : __('Penyesuaian harga massal gagal diproses.', 'ryokourent');
                    $msg_type = 'error';
                }
            }
        }
    }

    // 3. Fetch current settings and motorcycle categories
    $settings   = function_exists('ryokourent_get_settings') ? ryokourent_get_settings() : array();
    $categories = get_terms(array(
        'taxonomy'   => 'kategori_motor',
        'hide_empty' => false,
    ));
    if (is_wp_error($categories) || !is_array($categories)) {
        $categories = array();
    }

    // Current page URL for tab switching
    $base_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'ryokourent-settings';
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'penyewaan';
    $tab_url   = admin_url('edit.php?post_type=' . $post_type . '&page=' . $base_page);
    ?>
    <div class="wrap ryokourent-admin-wrap" style="max-width:1100px;">
        <h1 style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
            <span class="dashicons dashicons-admin-settings" style="font-size:32px; width:32px; height:32px; color:#f59e0b;"></span>
            <?php esc_html_e('Pengaturan Ryokourent & Penyesuaian Harga', 'ryokourent'); ?>
        </h1>

        <?php if (!empty($message)) : ?>
            <div class="notice notice-<?php echo esc_attr($msg_type); ?> is-dismissible" style="padding:12px 14px;">
                <p style="font-size:14px; margin:0; font-weight:600;">
                    <?php echo esc_html($message); ?>
                </p>
                <?php if (!empty($bulk_logs)) : ?>
                    <ul style="margin:8px 0 0 18px; font-size:13px; font-family:monospace; line-height:1.6;">
                        <?php foreach ($bulk_logs as $log) : ?>
                            <li><?php echo esc_html($log); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="<?php echo esc_url(add_query_arg('tab', 'general', $tab_url)); ?>" class="nav-tab <?php echo ('general' === $active_tab) ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-phone" style="vertical-align:text-top; margin-right:4px;"></span>
                <?php esc_html_e('Kontak WhatsApp & Jam Operasional', 'ryokourent'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'bulk_price', $tab_url)); ?>" class="nav-tab <?php echo ('bulk_price' === $active_tab) ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-tag" style="vertical-align:text-top; margin-right:4px;"></span>
                <?php esc_html_e('Penyesuaian Harga Massal (Peak Season)', 'ryokourent'); ?>
            </a>
        </nav>

        <?php if ('general' === $active_tab) : ?>
            <!-- TAB 1: GENERAL SETTINGS -->
            <form method="post" action="<?php echo esc_url(add_query_arg('tab', 'general', $tab_url)); ?>">
                <?php wp_nonce_field('ryokourent_save_settings_action', 'ryokourent_settings_nonce'); ?>
                <input type="hidden" name="ryokourent_action" value="save_general" />

                <div class="card" style="max-width:100%; padding:20px 24px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-radius:8px;">
                    <h2 style="margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px; font-size:17px; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-whatsapp" style="color:#25d366;"></span>
                        <?php esc_html_e('Nomor Kontak Resmi WhatsApp Admin', 'ryokourent'); ?>
                    </h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="wa_number"><?php esc_html_e('Nomor WhatsApp Utama (Admin)', 'ryokourent'); ?> <span style="color:#ef4444;">*</span></label>
                                </th>
                                <td>
                                    <input type="text" name="wa_number" id="wa_number" value="<?php echo esc_attr(isset($settings['wa_number']) ? $settings['wa_number'] : ''); ?>" class="regular-text" required placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx" />
                                    <p class="description">
                                        <?php esc_html_e('Nomor tujuan resmi konfirmasi pesanan dari pengunjung web. Gunakan format seluler Indonesia (08... atau 628...).', 'ryokourent'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wa_number_secondary"><?php esc_html_e('Nomor WhatsApp Cadangan (Opsional)', 'ryokourent'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="wa_number_secondary" id="wa_number_secondary" value="<?php echo esc_attr(isset($settings['wa_number_secondary']) ? $settings['wa_number_secondary'] : ''); ?>" class="regular-text" placeholder="628xxxxxxxxxx" />
                                    <p class="description">
                                        <?php esc_html_e('Nomor kontak cadangan/staf alternatif bila nomor utama sibuk.', 'ryokourent'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wa_default_template"><?php esc_html_e('Template Salam Pesan Default', 'ryokourent'); ?></label>
                                </th>
                                <td>
                                    <textarea name="wa_default_template" id="wa_default_template" rows="3" class="large-text"><?php echo esc_textarea(isset($settings['wa_default_template']) ? $settings['wa_default_template'] : ''); ?></textarea>
                                    <p class="description">
                                        <?php esc_html_e('Kalimat pembuka draf pesan WhatsApp sebelum detail identitas penyewa dan unit armada.', 'ryokourent'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="max-width:100%; padding:20px 24px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-radius:8px;">
                    <h2 style="margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px; font-size:17px; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-clock" style="color:#3b82f6;"></span>
                        <?php esc_html_e('Jam Operasional Layanan (WIB)', 'ryokourent'); ?>
                    </h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="operational_open"><?php esc_html_e('Jam Buka Pool', 'ryokourent'); ?> <span style="color:#ef4444;">*</span></label>
                                </th>
                                <td>
                                    <input type="time" name="operational_open" id="operational_open" value="<?php echo esc_attr(isset($settings['operational_open']) ? $settings['operational_open'] : '07:00'); ?>" class="small-text" style="width:120px;" required />
                                    <span class="description"><?php esc_html_e('Waktu paling awal pelanggan dapat mengambil atau mengembalikan armada motor (Default: 07:00 WIB).', 'ryokourent'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="operational_close"><?php esc_html_e('Jam Tutup Pool', 'ryokourent'); ?> <span style="color:#ef4444;">*</span></label>
                                </th>
                                <td>
                                    <input type="time" name="operational_close" id="operational_close" value="<?php echo esc_attr(isset($settings['operational_close']) ? $settings['operational_close'] : '23:00'); ?>" class="small-text" style="width:120px;" required />
                                    <span class="description"><?php esc_html_e('Waktu paling akhir operasional serah terima kendaraan (Default: 23:00 WIB).', 'ryokourent'); ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="max-width:100%; padding:20px 24px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-radius:8px;">
                    <h2 style="margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px; font-size:17px; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-location" style="color:#ef4444;"></span>
                        <?php esc_html_e('Lokasi 2 Pool Resmi (Malang Dinoyo & Batu Diponegoro)', 'ryokourent'); ?>
                    </h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Pool 1: Dinoyo -->
                            <tr>
                                <th scope="row"><label for="pool_dinoyo_name"><?php esc_html_e('Nama Pool 1 (Malang)', 'ryokourent'); ?></label></th>
                                <td><input type="text" name="pool_dinoyo_name" id="pool_dinoyo_name" value="<?php echo esc_attr(isset($settings['pool_dinoyo_name']) ? $settings['pool_dinoyo_name'] : ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_dinoyo_address"><?php esc_html_e('Alamat Pool 1', 'ryokourent'); ?></label></th>
                                <td><textarea name="pool_dinoyo_address" id="pool_dinoyo_address" rows="2" class="large-text"><?php echo esc_textarea(isset($settings['pool_dinoyo_address']) ? $settings['pool_dinoyo_address'] : ''); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_dinoyo_maps"><?php esc_html_e('Link Google Maps Pool 1', 'ryokourent'); ?></label></th>
                                <td><input type="url" name="pool_dinoyo_maps" id="pool_dinoyo_maps" value="<?php echo esc_url(isset($settings['pool_dinoyo_maps']) ? $settings['pool_dinoyo_maps'] : ''); ?>" class="large-text" placeholder="https://maps.google.com/..." /></td>
                            </tr>
                            <!-- Pool 2: Batu -->
                            <tr>
                                <th scope="row" style="border-top:1px dashed #cbd5e1;"><label for="pool_batu_name"><?php esc_html_e('Nama Pool 2 (Kota Batu)', 'ryokourent'); ?></label></th>
                                <td style="border-top:1px dashed #cbd5e1;"><input type="text" name="pool_batu_name" id="pool_batu_name" value="<?php echo esc_attr(isset($settings['pool_batu_name']) ? $settings['pool_batu_name'] : ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_batu_address"><?php esc_html_e('Alamat Pool 2', 'ryokourent'); ?></label></th>
                                <td><textarea name="pool_batu_address" id="pool_batu_address" rows="2" class="large-text"><?php echo esc_textarea(isset($settings['pool_batu_address']) ? $settings['pool_batu_address'] : ''); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pool_batu_maps"><?php esc_html_e('Link Google Maps Pool 2', 'ryokourent'); ?></label></th>
                                <td><input type="url" name="pool_batu_maps" id="pool_batu_maps" value="<?php echo esc_url(isset($settings['pool_batu_maps']) ? $settings['pool_batu_maps'] : ''); ?>" class="large-text" placeholder="https://maps.google.com/..." /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large" style="font-weight:600; padding:4px 20px;">
                        <?php esc_html_e('Simpan Pengaturan Umum', 'ryokourent'); ?>
                    </button>
                </p>
            </form>

        <?php elseif ('bulk_price' === $active_tab) : ?>
            <!-- TAB 2: BULK PRICE ADJUSTMENT (PEAK SEASON) -->
            <div class="card" style="max-width:100%; padding:20px 24px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-radius:8px;">
                <h2 style="margin-top:0; border-bottom:1px solid #e2e8f0; padding-bottom:10px; font-size:17px; display:flex; align-items:center; gap:8px;">
                    <span class="dashicons dashicons-chart-line" style="color:#f59e0b;"></span>
                    <?php esc_html_e('Penyesuaian Tarif Massal Armada (Peak Season / Diskon)', 'ryokourent'); ?>
                </h2>
                <p style="font-size:13px; color:#475569; line-height:1.5;">
                    <?php esc_html_e('Gunakan alat ini untuk menaikkan tarif sewa saat musim liburan (Idul Fitri, Nataru, Musim Libur Sekolah) atau memberikan diskon promo serempak per kategori motor. Sistem secara atomik memvalidasi batas harga agar tidak pernah bernilai Rp 0 atau minus.', 'ryokourent'); ?>
                </p>

                <div style="background:#fffbeb; border:1px solid #fde68a; border-left:4px solid #f59e0b; padding:12px 16px; border-radius:4px; margin-bottom:20px; font-size:13px;">
                    <strong><?php esc_html_e('Batasan & Aturan Keamanan Sistem (ADR-012):', 'ryokourent'); ?></strong>
                    <ul style="margin:6px 0 0 16px; line-height:1.5;">
                        <li><?php esc_html_e('Kenaikan/penurunan persentase dibatasi ketat antara -50% sampai +200%.', 'ryokourent'); ?></li>
                        <li><?php esc_html_e('Hasil akhir harga sewa wajib bernilai positif (lebih besar dari Rp 0). Jika ada 1 model motor yang menghasilkan Rp <= 0, pembaruan dibatalkan.', 'ryokourent'); ?></li>
                        <li><?php esc_html_e('Penyesuaian persentase otomatis dibulatkan ke kelipatan seribu Rupiah terdekat untuk menjaga kerapian harga sewa.', 'ryokourent'); ?></li>
                    </ul>
                </div>

                <form method="post" action="<?php echo esc_url(add_query_arg('tab', 'bulk_price', $tab_url)); ?>" onsubmit="return confirm('Apakah Anda yakin ingin menerapkan penyesuaian harga ini ke seluruh armada motor yang dipilih?');">
                    <?php wp_nonce_field('ryokourent_bulk_price_action', 'ryokourent_bulk_price_nonce'); ?>
                    <input type="hidden" name="ryokourent_action" value="apply_bulk_price" />

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="bulk_category"><?php esc_html_e('Kategori Motor Target', 'ryokourent'); ?></label></th>
                                <td>
                                    <select name="category" id="bulk_category" class="regular-text" style="font-weight:600;">
                                        <option value="all"><?php esc_html_e('— Semua Kategori Armada Motor —', 'ryokourent'); ?></option>
                                        <?php foreach ($categories as $cat) : ?>
                                            <option value="<?php echo esc_attr($cat->slug); ?>">
                                                <?php echo esc_html($cat->name . ' (' . $cat->count . ' unit)'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Pilih kategori motor tertentu atau ubah seluruh armada sekaligus.', 'ryokourent'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bulk_type"><?php esc_html_e('Jenis Penyesuaian', 'ryokourent'); ?></label></th>
                                <td>
                                    <select name="type" id="bulk_type" style="font-weight:600; width:220px;">
                                        <option value="nominal"><?php esc_html_e('Nominal Tetap (Rp)', 'ryokourent'); ?></option>
                                        <option value="percentage"><?php esc_html_e('Persentase (%)', 'ryokourent'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="bulk_amount"><?php esc_html_e('Nilai Perubahan (+ / -)', 'ryokourent'); ?> <span style="color:#ef4444;">*</span></label></th>
                                <td>
                                    <input type="number" step="any" name="amount" id="bulk_amount" class="regular-text" required placeholder="Contoh: 10000 (naik Rp 10.000) atau 15 (naik 15%)" />
                                    <p class="description">
                                        <?php esc_html_e('Masukkan angka positif untuk kenaikan harga (misal: 10000 atau 15) atau angka negatif untuk diskon potongan harga (misal: -5000 atau -10).', 'ryokourent'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Paket Tarif yang Disesuaikan', 'ryokourent'); ?></th>
                                <td>
                                    <fieldset>
                                        <label style="margin-right:16px;">
                                            <input type="checkbox" name="rate_types[]" value="daily" checked />
                                            <strong><?php esc_html_e('Tarif Harian', 'ryokourent'); ?></strong>
                                        </label>
                                        <label style="margin-right:16px;">
                                            <input type="checkbox" name="rate_types[]" value="weekly" checked />
                                            <strong><?php esc_html_e('Tarif Mingguan', 'ryokourent'); ?></strong>
                                        </label>
                                        <label>
                                            <input type="checkbox" name="rate_types[]" value="monthly" checked />
                                            <strong><?php esc_html_e('Tarif Bulanan', 'ryokourent'); ?></strong>
                                        </label>
                                    </fieldset>
                                    <p class="description"><?php esc_html_e('Centang paket tarif mana saja yang akan dikenakan perubahan.', 'ryokourent'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit" style="margin-top:20px;">
                        <button type="submit" class="button button-primary button-large" style="background:#d97706; border-color:#b45309; font-weight:600; padding:4px 20px;">
                            <span class="dashicons dashicons-update" style="vertical-align:text-top; margin-right:4px;"></span>
                            <?php esc_html_e('Terapkan Penyesuaian Harga Massal', 'ryokourent'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- TABEL DAFTAR TARIF MOTOR SAAT INI (REFERENCE PREVIEW) -->
            <div class="card" style="max-width:100%; padding:20px 24px; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-radius:8px;">
                <h3 style="margin-top:0; font-size:15px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                    <?php esc_html_e('Daftar Tarif Armada Motor Saat Ini', 'ryokourent'); ?>
                </h3>
                <?php
                $preview_query = new WP_Query(array(
                    'post_type'      => 'motor',
                    'post_status'    => 'publish',
                    'posts_per_page' => 50,
                    'no_found_rows'  => true,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ));
                if ($preview_query->have_posts()) :
                    ?>
                    <table class="widefat fixed striped" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th style="width:30%;"><?php esc_html_e('Model Motor', 'ryokourent'); ?></th>
                                <th style="width:25%;"><?php esc_html_e('Kategori', 'ryokourent'); ?></th>
                                <th style="width:15%;"><?php esc_html_e('Tarif Harian', 'ryokourent'); ?></th>
                                <th style="width:15%;"><?php esc_html_e('Tarif Mingguan', 'ryokourent'); ?></th>
                                <th style="width:15%;"><?php esc_html_e('Tarif Bulanan', 'ryokourent'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($preview_query->have_posts()) :
                                $preview_query->the_post();
                                $m_id     = get_the_ID();
                                $daily    = (int) get_post_meta($m_id, '_ryokou_price_daily', true);
                                $weekly   = (int) get_post_meta($m_id, '_ryokou_price_weekly', true);
                                $monthly  = (int) get_post_meta($m_id, '_ryokou_price_monthly', true);
                                $cats     = get_the_terms($m_id, 'kategori_motor');
                                $cat_name = (!empty($cats) && !is_wp_error($cats)) ? $cats[0]->name : '—';
                                ?>
                                <tr>
                                    <td><strong><a href="<?php echo esc_url(get_edit_post_link($m_id)); ?>"><?php the_title(); ?></a></strong></td>
                                    <td><span class="badge" style="background:#e2e8f0; padding:2px 8px; border-radius:12px; font-size:11px;"><?php echo esc_html($cat_name); ?></span></td>
                                    <td><?php echo esc_html($daily > 0 ? 'Rp ' . number_format($daily, 0, ',', '.') : '—'); ?></td>
                                    <td><?php echo esc_html($weekly > 0 ? 'Rp ' . number_format($weekly, 0, ',', '.') : '—'); ?></td>
                                    <td><?php echo esc_html($monthly > 0 ? 'Rp ' . number_format($monthly, 0, ',', '.') : '—'); ?></td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p style="color:#64748b; font-style:italic;"><?php esc_html_e('Belum ada unit motor terbit di katalog.', 'ryokourent'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
