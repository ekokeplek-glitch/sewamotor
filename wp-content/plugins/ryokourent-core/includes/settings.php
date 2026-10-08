<?php
/**
 * Settings Management & Bulk Price Adjustment Engine for Ryokourent
 *
 * Implements server-side configuration management for:
 * - Official WhatsApp admin contact and fallback numbers.
 * - Daily pool operating hours (07:00 - 23:00 WIB).
 * - Official pool location addresses and Google Maps deep links.
 * - Multi-tier bulk price adjustment (nominal and percentage) with strict boundary validation (ADR-012).
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/includes
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return default application configuration values.
 *
 * @since 1.0.0
 * @return array<string, string>
 */
function ryokourent_get_default_settings() {
    return array(
        'wa_number'             => '62895384017772',
        'wa_number_secondary'   => '6281234567890',
        'wa_default_template'   => __('Halo Admin Ryokourent, saya ingin memesan rental motor di Malang & Batu.', 'ryokourent'),
        'operational_open'      => '07:00',
        'operational_close'     => '23:00',
        'pool_dinoyo_name'      => __('Pool Malang Dinoyo (Pusat Kota / Kampus)', 'ryokourent'),
        'pool_dinoyo_address'   => __('Jl. MT Haryono No. 128, Dinoyo, Lowokwaru, Kota Malang', 'ryokourent'),
        'pool_dinoyo_maps'      => 'https://maps.google.com/?q=Ryokourent+Dinoyo+Malang',
        'pool_batu_name'        => __('Pool Batu Diponegoro (Kota Wisata Batu)', 'ryokourent'),
        'pool_batu_address'     => __('Jl. Diponegoro No. 45, Sisir, Kec. Batu, Kota Wisata Batu', 'ryokourent'),
        'pool_batu_maps'        => 'https://maps.google.com/?q=Ryokourent+Batu',
    );
}

/**
 * Retrieve current configuration options merged with defaults.
 *
 * @since 1.0.0
 * @return array<string, string>
 */
function ryokourent_get_settings() {
    $defaults = ryokourent_get_default_settings();

    $settings = array(
        'wa_number'             => (string) get_option('ryokourent_wa_number', $defaults['wa_number']),
        'wa_number_secondary'   => (string) get_option('ryokourent_wa_number_secondary', $defaults['wa_number_secondary']),
        'wa_default_template'   => (string) get_option('ryokourent_wa_default_template', $defaults['wa_default_template']),
        'operational_open'      => (string) get_option('ryokourent_operational_open', $defaults['operational_open']),
        'operational_close'     => (string) get_option('ryokourent_operational_close', $defaults['operational_close']),
        'pool_dinoyo_name'      => (string) get_option('ryokourent_pool_dinoyo_name', $defaults['pool_dinoyo_name']),
        'pool_dinoyo_address'   => (string) get_option('ryokourent_pool_dinoyo_address', $defaults['pool_dinoyo_address']),
        'pool_dinoyo_maps'      => (string) get_option('ryokourent_pool_dinoyo_maps', $defaults['pool_dinoyo_maps']),
        'pool_batu_name'        => (string) get_option('ryokourent_pool_batu_name', $defaults['pool_batu_name']),
        'pool_batu_address'     => (string) get_option('ryokourent_pool_batu_address', $defaults['pool_batu_address']),
        'pool_batu_maps'        => (string) get_option('ryokourent_pool_batu_maps', $defaults['pool_batu_maps']),
    );

    return $settings;
}

/**
 * Get current pool operating hours.
 *
 * @since 1.0.0
 * @return array Array with keys 'open' and 'close' in 'H:i' format.
 */
function ryokourent_get_operating_hours() {
    $settings = ryokourent_get_settings();
    return array(
        'open'  => !empty($settings['operational_open']) ? $settings['operational_open'] : '07:00',
        'close' => !empty($settings['operational_close']) ? $settings['operational_close'] : '23:00',
    );
}

/**
 * Validate and save general settings.
 *
 * @since 1.0.0
 * @param array $input Raw form POST input.
 * @return array Result array with 'success' (bool), 'message' (string), and 'errors' (array).
 */
function ryokourent_save_general_settings($input = array()) {
    if (!ryokourent_current_user_can_manage_settings()) {
        return array(
            'success' => false,
            'message' => __('Akses ditolak: Anda tidak memiliki wewenang untuk menyimpan pengaturan.', 'ryokourent'),
            'errors'  => array('permission' => __('Wewenang ditolak.', 'ryokourent')),
        );
    }

    $errors = array();

    // 1. WhatsApp Utama
    $raw_wa   = isset($input['wa_number']) ? trim((string) $input['wa_number']) : '';
    $clean_wa = ryokourent_sanitize_phone($raw_wa);
    if (empty($clean_wa)) {
        $errors['wa_number'] = __('Nomor WhatsApp resmi admin wajib diisi.', 'ryokourent');
    } elseif (!ryokourent_is_valid_phone($clean_wa)) {
        $errors['wa_number'] = __('Format nomor WhatsApp utama tidak valid (Gunakan format 08xx atau 628xx, 10-15 digit).', 'ryokourent');
    }

    // 2. WhatsApp Sekunder (opsional)
    $clean_wa_sec = '';
    if (!empty($input['wa_number_secondary'])) {
        $clean_wa_sec = ryokourent_sanitize_phone(trim((string) $input['wa_number_secondary']));
        if (!ryokourent_is_valid_phone($clean_wa_sec)) {
            $errors['wa_number_secondary'] = __('Format nomor WhatsApp cadangan tidak valid (format 08xx / 628xx).', 'ryokourent');
        }
    }

    // 3. Jam Operasional
    $open_time  = isset($input['operational_open']) ? trim((string) $input['operational_open']) : '07:00';
    $close_time = isset($input['operational_close']) ? trim((string) $input['operational_close']) : '23:00';

    if (!preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $open_time)) {
        $errors['operational_open'] = __('Format jam buka tidak valid (gunakan format HH:MM, contoh: 07:00).', 'ryokourent');
    }
    if (!preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $close_time)) {
        $errors['operational_close'] = __('Format jam tutup tidak valid (gunakan format HH:MM, contoh: 23:00).', 'ryokourent');
    }

    if (empty($errors['operational_open']) && empty($errors['operational_close'])) {
        $open_mins  = (int) substr($open_time, 0, 2) * 60 + (int) substr($open_time, 3, 2);
        $close_mins = (int) substr($close_time, 0, 2) * 60 + (int) substr($close_time, 3, 2);
        if ($close_mins <= $open_mins) {
            $errors['operational_close'] = __('Jam tutup operasional harus lebih lambat daripada jam buka.', 'ryokourent');
        }
    }

    // Jika ada error validasi, batalkan penyimpanan
    if (!empty($errors)) {
        return array(
            'success' => false,
            'message' => __('Beberapa data pengaturan tidak valid. Silakan periksa kembali input Anda.', 'ryokourent'),
            'errors'  => $errors,
        );
    }

    // Update options di database
    update_option('ryokourent_wa_number', $clean_wa);
    update_option('ryokourent_wa_number_secondary', $clean_wa_sec);

    if (isset($input['wa_default_template'])) {
        update_option('ryokourent_wa_default_template', sanitize_textarea_field(wp_unslash($input['wa_default_template'])));
    }

    update_option('ryokourent_operational_open', $open_time);
    update_option('ryokourent_operational_close', $close_time);

    if (isset($input['pool_dinoyo_name'])) {
        update_option('ryokourent_pool_dinoyo_name', sanitize_text_field(wp_unslash($input['pool_dinoyo_name'])));
    }
    if (isset($input['pool_dinoyo_address'])) {
        update_option('ryokourent_pool_dinoyo_address', sanitize_textarea_field(wp_unslash($input['pool_dinoyo_address'])));
    }
    if (isset($input['pool_dinoyo_maps'])) {
        update_option('ryokourent_pool_dinoyo_maps', esc_url_raw(wp_unslash($input['pool_dinoyo_maps'])));
    }

    if (isset($input['pool_batu_name'])) {
        update_option('ryokourent_pool_batu_name', sanitize_text_field(wp_unslash($input['pool_batu_name'])));
    }
    if (isset($input['pool_batu_address'])) {
        update_option('ryokourent_pool_batu_address', sanitize_textarea_field(wp_unslash($input['pool_batu_address'])));
    }
    if (isset($input['pool_batu_maps'])) {
        update_option('ryokourent_pool_batu_maps', esc_url_raw(wp_unslash($input['pool_batu_maps'])));
    }

    return array(
        'success' => true,
        'message' => __('Pengaturan umum dan nomor WhatsApp berhasil disimpan.', 'ryokourent'),
        'errors'  => array(),
    );
}

/**
 * Calculate adjusted price with boundary validation (ADR-012).
 *
 * Rules:
 * - Percentage change is bounded strictly between -50% and +200%.
 * - Resulting price must be strictly > 0.
 * - Percentage adjustment rounds to nearest thousand IDR.
 *
 * @since 1.0.0
 * @param int    $current_price Current rate in IDR.
 * @param string $type          'nominal' or 'percentage'.
 * @param float  $amount        Adjustment value (+10000, -5000, +15, -10).
 * @return array Array with keys 'is_valid' (bool), 'new_price' (int), and 'error' (string).
 */
function ryokourent_calculate_adjusted_price($current_price, $type, $amount) {
    $current_price = (int) $current_price;
    $amount        = (float) $amount;

    if ($current_price <= 0) {
        return array(
            'is_valid'  => false,
            'new_price' => 0,
            'error'     => __('Harga awal tidak valid.', 'ryokourent'),
        );
    }

    if (0.0 === $amount) {
        return array(
            'is_valid'  => false,
            'new_price' => $current_price,
            'error'     => __('Nilai penyesuaian tidak boleh nol.', 'ryokourent'),
        );
    }

    if ('percentage' === $type) {
        // Batasan persentase: -50% s/d +200%
        if ($amount < -50.0 || $amount > 200.0) {
            return array(
                'is_valid'  => false,
                'new_price' => 0,
                'error'     => __('Persentase penyesuaian harus berada di antara -50% hingga +200%.', 'ryokourent'),
            );
        }

        $multiplier = 1.0 + ($amount / 100.0);
        $raw_new    = $current_price * $multiplier;
        // Pembulatan ke kelipatan seribu Rupiah terdekat
        $new_price  = (int) (round($raw_new / 1000.0) * 1000);
    } elseif ('nominal' === $type) {
        $new_price = $current_price + (int) round($amount);
    } else {
        return array(
            'is_valid'  => false,
            'new_price' => 0,
            'error'     => __('Jenis penyesuaian harga tidak dikenal (harus nominal atau persentase).', 'ryokourent'),
        );
    }

    // Validasi harga baru harus strictly > 0
    if ($new_price <= 0) {
        return array(
            'is_valid'  => false,
            'new_price' => 0,
            'error'     => sprintf(
                __('Penyesuaian menghasilkan harga tidak valid (Rp %s). Tarif rental motor harus lebih besar dari Rp 0.', 'ryokourent'),
                number_format($new_price, 0, ',', '.')
            ),
        );
    }

    return array(
        'is_valid'  => true,
        'new_price' => $new_price,
        'error'     => '',
    );
}

/**
 * Apply bulk price adjustment across motorcycle fleet (ADR-012).
 *
 * Implements two-pass atomic verification:
 * Pass 1: Simulates calculation for all matched motorcycles. If ANY motor rate
 * would result in <= 0 or invalid boundary, the entire operation is rejected.
 * Pass 2: Persists updated prices into post meta.
 *
 * @since 1.0.0
 * @param array $params Array with keys: 'category', 'type', 'amount', 'rate_types'.
 * @return array Result array with 'success' (bool), 'message' (string), 'updated_count' (int), and 'logs' (array).
 */
function ryokourent_apply_bulk_price_adjustment($params = array()) {
    if (!ryokourent_current_user_can_manage_settings()) {
        return array(
            'success'       => false,
            'message'       => __('Akses ditolak: Hanya Administrator yang diizinkan melakukan penyesuaian harga massal.', 'ryokourent'),
            'updated_count' => 0,
            'logs'          => array(),
        );
    }

    $category   = isset($params['category']) ? sanitize_text_field(wp_unslash($params['category'])) : 'all';
    $type       = isset($params['type']) ? sanitize_key(wp_unslash($params['type'])) : 'nominal';
    $amount_raw = isset($params['amount']) ? (float) wp_unslash($params['amount']) : 0.0;
    $rate_types = isset($params['rate_types']) ? (array) $params['rate_types'] : array('daily');

    if (in_array('all', $rate_types, true)) {
        $rate_types = array('daily', 'weekly', 'monthly');
    }

    if (empty($rate_types)) {
        $rate_types = array('daily');
    }

    // Validasi awal nilai amount
    if (0.0 === $amount_raw) {
        return array(
            'success'       => false,
            'message'       => __('Gagal: Nilai penyesuaian harga tidak boleh 0.', 'ryokourent'),
            'updated_count' => 0,
            'logs'          => array(),
        );
    }

    if ('percentage' === $type && ($amount_raw < -50.0 || $amount_raw > 200.0)) {
        return array(
            'success'       => false,
            'message'       => __('Gagal: Persentase penyesuaian harus berada dalam rentang wajar (-50% sampai +200%).', 'ryokourent'),
            'updated_count' => 0,
            'logs'          => array(),
        );
    }

    // Query daftar armada motor (menghindari posts_per_page => -1 sesuai reviewOP M12)
    $query_args = array(
        'post_type'      => 'motor',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'no_found_rows'  => true,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
    );

    if (!empty($category) && 'all' !== $category) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'kategori_motor',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $motor_query = new WP_Query($query_args);
    $motor_ids   = isset($motor_query->posts) && is_array($motor_query->posts) ? $motor_query->posts : array();

    if (empty($motor_ids)) {
        return array(
            'success'       => false,
            'message'       => __('Tidak ada armada motor ditemukan pada kategori yang dipilih.', 'ryokourent'),
            'updated_count' => 0,
            'logs'          => array(),
        );
    }

    // -------------------------------------------------------------------------
    // PASS 1: Simulasi & Validasi Batas Seluruh Armada
    // -------------------------------------------------------------------------
    $planned_updates = array();

    foreach ($motor_ids as $motor_id) {
        $motor_id   = absint($motor_id);
        $title      = function_exists('get_the_title') ? get_the_title($motor_id) : ('Motor #' . $motor_id);
        $daily_old  = (int) get_post_meta($motor_id, '_ryokou_price_daily', true);
        $week_old   = (int) get_post_meta($motor_id, '_ryokou_price_weekly', true);
        $month_old  = (int) get_post_meta($motor_id, '_ryokou_price_monthly', true);

        $entry = array(
            'id'    => $motor_id,
            'title' => $title,
            'meta'  => array(),
        );

        // Harian
        if (in_array('daily', $rate_types, true) && $daily_old > 0) {
            $calc = ryokourent_calculate_adjusted_price($daily_old, $type, $amount_raw);
            if (!$calc['is_valid']) {
                return array(
                    'success'       => false,
                    'message'       => sprintf(
                        __('Penyesuaian dibatalkan: Unit "%s" menghasilkan tarif harian tidak valid (%s).', 'ryokourent'),
                        esc_html($title),
                        $calc['error']
                    ),
                    'updated_count' => 0,
                    'logs'          => array(),
                );
            }
            $entry['meta']['_ryokou_price_daily'] = array(
                'old' => $daily_old,
                'new' => $calc['new_price'],
            );
        }

        // Mingguan
        if (in_array('weekly', $rate_types, true) && $week_old > 0) {
            $calc = ryokourent_calculate_adjusted_price($week_old, $type, $amount_raw);
            if (!$calc['is_valid']) {
                return array(
                    'success'       => false,
                    'message'       => sprintf(
                        __('Penyesuaian dibatalkan: Unit "%s" menghasilkan tarif mingguan tidak valid (%s).', 'ryokourent'),
                        esc_html($title),
                        $calc['error']
                    ),
                    'updated_count' => 0,
                    'logs'          => array(),
                );
            }
            $entry['meta']['_ryokou_price_weekly'] = array(
                'old' => $week_old,
                'new' => $calc['new_price'],
            );
        }

        // Bulanan
        if (in_array('monthly', $rate_types, true) && $month_old > 0) {
            $calc = ryokourent_calculate_adjusted_price($month_old, $type, $amount_raw);
            if (!$calc['is_valid']) {
                return array(
                    'success'       => false,
                    'message'       => sprintf(
                        __('Penyesuaian dibatalkan: Unit "%s" menghasilkan tarif bulanan tidak valid (%s).', 'ryokourent'),
                        esc_html($title),
                        $calc['error']
                    ),
                    'updated_count' => 0,
                    'logs'          => array(),
                );
            }
            $entry['meta']['_ryokou_price_monthly'] = array(
                'old' => $month_old,
                'new' => $calc['new_price'],
            );
        }

        if (!empty($entry['meta'])) {
            $planned_updates[] = $entry;
        }
    }

    if (empty($planned_updates)) {
        return array(
            'success'       => false,
            'message'       => __('Tidak ada data tarif motor yang perlu diperbarui.', 'ryokourent'),
            'updated_count' => 0,
            'logs'          => array(),
        );
    }

    // -------------------------------------------------------------------------
    // PASS 2: Eksekusi Penyimpanan ke Post Meta
    // -------------------------------------------------------------------------
    $logs = array();
    foreach ($planned_updates as $item) {
        $log_rates = array();
        foreach ($item['meta'] as $meta_key => $rate_data) {
            update_post_meta($item['id'], $meta_key, $rate_data['new']);
            $label = ('_ryokou_price_daily' === $meta_key) ? 'Harian' : (('_ryokou_price_weekly' === $meta_key) ? 'Mingguan' : 'Bulanan');
            $log_rates[] = sprintf(
                '%s: Rp %s → Rp %s',
                $label,
                number_format($rate_data['old'], 0, ',', '.'),
                number_format($rate_data['new'], 0, ',', '.')
            );
        }

        $logs[] = sprintf(
            '[%s] %s',
            $item['title'],
            implode(', ', $log_rates)
        );
    }

    $amount_label = ('percentage' === $type) ? ($amount_raw . '%') : ('Rp ' . number_format($amount_raw, 0, ',', '.'));
    $summary = sprintf(
        __('Berhasil memperbarui tarif untuk %d armada motor (Penyesuaian: %s).', 'ryokourent'),
        count($planned_updates),
        $amount_label
    );

    return array(
        'success'       => true,
        'message'       => $summary,
        'updated_count' => count($planned_updates),
        'logs'          => $logs,
    );
}
