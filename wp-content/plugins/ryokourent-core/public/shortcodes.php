<?php
/**
 * Shortcodes Registration & Public Asset Enqueuing for Ryokourent
 *
 * Registers the [ryokou_catalog] shortcode and enqueues modern mobile-first
 * styles and filter scripts only when needed.
 *
 * @package    Ryokourent_Core
 * @subpackage Ryokourent_Core/public
 * @author     Ryokourent Dev Team
 * @since      1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register public scripts and styles.
 *
 * @since 1.0.0
 * @return void
 */
function ryokourent_register_public_assets() {
    // Public stylesheet
    wp_register_style(
        'ryokourent-public',
        RYOKOURENT_PLUGIN_URL . 'assets/css/ryokourent-public.css',
        array(),
        RYOKOURENT_VERSION
    );

    // Filter and interaction script
    wp_register_script(
        'ryokourent-filter',
        RYOKOURENT_PLUGIN_URL . 'assets/js/ryokourent-filter.js',
        array(),
        RYOKOURENT_VERSION,
        true
    );

    // Booking validation and AJAX submission script
    wp_register_script(
        'ryokourent-booking',
        RYOKOURENT_PLUGIN_URL . 'assets/js/ryokourent-booking.js',
        array(),
        RYOKOURENT_VERSION,
        true
    );

    $wa_number = get_option('ryokourent_wa_number', defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $clean_wa = preg_replace('/[^0-9]/', '', (string) $wa_number);

    wp_localize_script('ryokourent-filter', 'ryokouFilterConfig', array(
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'waNumber'      => $clean_wa,
        'bookingAnchor' => '#booking-form',
    ));

    wp_localize_script('ryokourent-booking', 'ryokouBookingConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ryokourent_booking_form_action'),
        'adminWa' => $clean_wa,
        'strings' => array(
            'submitting'   => __('Memproses pesanan...', 'ryokourent'),
            'submitText'   => __('Lanjutkan Pemesanan via WhatsApp', 'ryokourent'),
            'errName'      => __('Nama lengkap minimal 3 karakter sesuai e-KTP.', 'ryokourent'),
            'errPhone'     => __('Nomor WhatsApp harus nomor seluler Indonesia yang valid (10-15 digit, misal 081234567890).', 'ryokourent'),
            'errEmergency' => __('Nomor kontak darurat keluarga harus valid dan tidak boleh sama dengan nomor WhatsApp Anda.', 'ryokourent'),
            'errKtpAddress'  => __('Alamat KTP minimal 5 karakter.', 'ryokourent'),
            'errStayAddress' => __('Tempat menginap di Malang/Batu minimal 3 karakter.', 'ryokourent'),
            'errMotor'     => __('Silakan pilih model armada motor terlebih dahulu.', 'ryokourent'),
            'errRateLimit' => __('Terlalu banyak permintaan pemesanan dalam waktu singkat. Mohon tunggu beberapa menit.', 'ryokourent'),
            'errGeneral'   => __('Mohon periksa kembali isian formulir Anda.', 'ryokourent'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'ryokourent_register_public_assets');

/**
 * Shortcode callback for [ryokou_catalog].
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_catalog_shortcode($atts = array()) {
    // Enqueue registered assets when shortcode is evaluated
    wp_enqueue_style('ryokourent-public');
    wp_enqueue_script('ryokourent-filter');

    $parsed_atts = shortcode_atts(
        array(
            'kategori'    => '',
            'limit'       => -1,
            'show_filter' => 'yes',
            'columns'     => 3,
        ),
        $atts,
        'ryokou_catalog'
    );

    if (function_exists('ryokourent_render_catalog_grid')) {
        return ryokourent_render_catalog_grid($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul katalog Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_catalog', 'ryokourent_catalog_shortcode');

/**
 * Shortcode callback for [ryokou_booking_form].
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_booking_form_shortcode($atts = array()) {
    // Enqueue registered public assets
    wp_enqueue_style('ryokourent-public');
    wp_enqueue_script('ryokourent-filter');
    wp_enqueue_script('ryokourent-booking');

    $parsed_atts = shortcode_atts(
        array(
            'form_id'        => 'ryokourent-booking-form',
            'selected_motor' => 0,
            'title'          => __('Formulir Pemesanan Sewa Motor', 'ryokourent'),
        ),
        $atts,
        'ryokou_booking_form'
    );

    if (function_exists('ryokourent_render_booking_form')) {
        return ryokourent_render_booking_form($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul formulir booking Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_booking_form', 'ryokourent_booking_form_shortcode');

/**
 * Shortcode callback for [ryokou_faq].
 *
 * Displays the 7-item interactive accordion FAQ based on the Ryokourent blueprint.
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_faq_shortcode($atts = array()) {
    wp_enqueue_style('ryokourent-public');
    wp_enqueue_script('ryokourent-filter');

    $parsed_atts = shortcode_atts(
        array(
            'section_id'  => 'syarat-faq',
            'title'       => __('Syarat Sewa & Pertanyaan Sering Diajukan (FAQ)', 'ryokourent'),
            'subtitle'    => __('Transparansi penuh demi keselamatan, keamanan armada, dan kenyamanan liburan Anda di Malang & Batu.', 'ryokourent'),
            'show_header' => 'yes',
        ),
        $atts,
        'ryokou_faq'
    );

    $parsed_atts['show_header'] = ('no' !== $parsed_atts['show_header']);

    if (function_exists('ryokourent_render_faq_section')) {
        return ryokourent_render_faq_section($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul FAQ Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_faq', 'ryokourent_faq_shortcode');

/**
 * Shortcode callback for [ryokou_pools].
 *
 * Displays the 2 official pool locations (Dinoyo Malang & Diponegoro Batu) with Google Maps deep links.
 *
 * @since 1.0.0
 * @param array $atts User-defined shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_pools_shortcode($atts = array()) {
    wp_enqueue_style('ryokourent-public');

    $parsed_atts = shortcode_atts(
        array(
            'section_id'  => 'lokasi-pool',
            'title'       => __('Area Layanan & Dua Lokasi Pool Resmi', 'ryokourent'),
            'subtitle'    => __('Titik strategis di Kota Malang dan Kota Wisata Batu untuk serah terima unit langsung atau pengantaran ke tempat menginap Anda.', 'ryokourent'),
            'show_header' => 'yes',
        ),
        $atts,
        'ryokou_pools'
    );

    $parsed_atts['show_header'] = ('no' !== $parsed_atts['show_header']);

    if (function_exists('ryokourent_render_pool_locations_section')) {
        return ryokourent_render_pool_locations_section($parsed_atts);
    }

    return '<div class="ryokou-notice">' . esc_html__('Modul lokasi pool Ryokourent belum dimuat.', 'ryokourent') . '</div>';
}
add_shortcode('ryokou_pools', 'ryokourent_pools_shortcode');

/**
 * Shortcode callback for [ryokou_bromo_advisory].
 *
 * Displays high-contrast advisory banner enforcing Trail CRF 150L for Mount Bromo caldera trips.
 *
 * @since 1.0.0
 * @return string HTML rendered output.
 */
function ryokourent_bromo_advisory_shortcode() {
    wp_enqueue_style('ryokourent-public');

    if (function_exists('ryokourent_render_bromo_advisory_banner')) {
        return ryokourent_render_bromo_advisory_banner();
    }

    return '';
}
add_shortcode('ryokou_bromo_advisory', 'ryokourent_bromo_advisory_shortcode');

/**
 * Shortcode callback for [ryokou_mobile_bar].
 *
 * Displays mobile bottom floating action bar with WhatsApp and Booking CTAs (TASK-026).
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML rendered output.
 */
function ryokourent_mobile_bar_shortcode($atts = array()) {
    wp_enqueue_style('ryokourent-public');

    $parsed_atts = shortcode_atts(
        array(
            'booking_anchor' => '#booking-form',
            'wa_message'     => '',
        ),
        $atts,
        'ryokou_mobile_bar'
    );

    if (function_exists('ryokourent_render_floating_mobile_bar')) {
        return ryokourent_render_floating_mobile_bar($parsed_atts);
    }

    return '';
}
add_shortcode('ryokou_mobile_bar', 'ryokourent_mobile_bar_shortcode');



