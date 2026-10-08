<?php
/**
 * Catalog & Motor Card Template Rendering for Ryokourent
 *
 * Provides template helper functions to output mobile-first motor cards,
 * tabbed category filters, specs badges, pricing breakdown, and CTA buttons.
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
 * Format motor price into standard Indonesian Rupiah string or placeholder.
 *
 * @since 1.0.0
 * @param mixed  $price  Numeric price value or empty.
 * @param string $suffix Optional suffix (e.g. '/ 24 Jam').
 * @param string $fallback Fallback text when price is 0 or empty.
 * @return string Formatted price HTML.
 */
function ryokourent_format_catalog_price($price, $suffix = '', $fallback = 'Tanya Admin') {
    $price_num = is_numeric($price) ? floatval($price) : 0;
    if ($price_num <= 0) {
        return '<span class="ryokou-price-placeholder">' . esc_html($fallback) . '</span>';
    }

    $formatted = 'Rp ' . number_format($price_num, 0, ',', '.');
    if (!empty($suffix)) {
        $formatted .= ' <span class="ryokou-price-period">' . esc_html($suffix) . '</span>';
    }

    return $formatted;
}

/**
 * Render single motor card HTML.
 *
 * @since 1.0.0
 * @param WP_Post|int $post_or_id Post object or Post ID.
 * @param array       $custom_data Optional array to override/mock data.
 * @return string HTML card output.
 */
function ryokourent_render_motor_card($post_or_id, $custom_data = array()) {
    $post = is_object($post_or_id) ? $post_or_id : get_post($post_or_id);
    $post_id = $post ? $post->ID : (is_numeric($post_or_id) ? intval($post_or_id) : 0);

    // Default metadata extraction
    $title           = $post ? get_the_title($post) : (isset($custom_data['title']) ? $custom_data['title'] : 'Armada Motor');
    $permalink       = $post ? get_permalink($post) : (isset($custom_data['permalink']) ? $custom_data['permalink'] : '#');
    $engine_cc       = $post ? get_post_meta($post_id, '_ryokou_engine_cc', true) : (isset($custom_data['engine_cc']) ? $custom_data['engine_cc'] : '');
    $transmission    = $post ? get_post_meta($post_id, '_ryokou_transmission', true) : (isset($custom_data['transmission']) ? $custom_data['transmission'] : '');
    $route_char      = $post ? get_post_meta($post_id, '_ryokou_route_character', true) : (isset($custom_data['route_character']) ? $custom_data['route_character'] : '');
    $is_bromo_ready  = $post ? (get_post_meta($post_id, '_ryokou_is_bromo_ready', true) === 'yes') : (!empty($custom_data['is_bromo_ready']));
    $status_label    = $post ? get_post_meta($post_id, '_ryokou_status_label', true) : (isset($custom_data['status_label']) ? $custom_data['status_label'] : 'Tersedia');
    $price_daily     = $post ? get_post_meta($post_id, '_ryokou_price_daily', true) : (isset($custom_data['price_daily']) ? $custom_data['price_daily'] : 0);
    $price_weekly    = $post ? get_post_meta($post_id, '_ryokou_price_weekly', true) : (isset($custom_data['price_weekly']) ? $custom_data['price_weekly'] : 0);
    $price_monthly   = $post ? get_post_meta($post_id, '_ryokou_price_monthly', true) : (isset($custom_data['price_monthly']) ? $custom_data['price_monthly'] : 0);

    // Fallback status if empty
    if (empty($status_label)) {
        $status_label = 'Tersedia';
    }

    // Determine category slugs for data-category filter attribute
    $category_slugs = array();
    $category_names = array();
    if ($post) {
        $terms = get_the_terms($post_id, 'kategori_motor');
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                $category_slugs[] = $t->slug;
                $category_names[] = $t->name;
            }
        }
    } elseif (isset($custom_data['category_slugs'])) {
        $category_slugs = (array) $custom_data['category_slugs'];
        $category_names = isset($custom_data['category_names']) ? (array) $custom_data['category_names'] : $category_slugs;
    }

    $category_attr = esc_attr(implode(' ', $category_slugs));
    $category_display = !empty($category_names) ? esc_html($category_names[0]) : 'Armada Ryokou';

    // Status badge class
    $status_slug = sanitize_title($status_label);
    $status_class = 'ryokou-badge-status-' . $status_slug;
    if ($status_label === 'Tersedia') {
        $status_class = 'ryokou-status-available';
    } elseif ($status_label === 'Booking Menipis') {
        $status_class = 'ryokou-status-warning';
    } elseif ($status_label === 'Penuh') {
        $status_class = 'ryokou-status-full';
    }

    // WhatsApp link preparation
    $wa_number = get_option('ryokourent_wa_number', defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $wa_number_clean = preg_replace('/[^0-9]/', '', (string) $wa_number);
    $wa_message = "Halo Admin Ryokourent, saya ingin sewa motor " . $title . " di Malang/Batu. Apakah masih tersedia?";
    $wa_url = 'https://api.whatsapp.com/send?phone=' . esc_attr($wa_number_clean) . '&text=' . rawurlencode($wa_message);

    // Thumbnail image
    $thumbnail_html = '';
    if ($post && has_post_thumbnail($post_id)) {
        $thumbnail_html = get_the_post_thumbnail($post_id, 'medium_large', array(
            'class'   => 'ryokou-card-img',
            'alt'     => esc_attr($title),
            'loading' => 'lazy',
        ));
    } elseif (!empty($custom_data['image_url'])) {
        $thumbnail_html = '<img src="' . esc_url($custom_data['image_url']) . '" alt="' . esc_attr($title) . '" class="ryokou-card-img" loading="lazy" />';
    } else {
        // High quality stylized placeholder
        $thumbnail_html = '<div class="ryokou-card-img-placeholder">
            <span class="ryokou-card-placeholder-icon">🛵</span>
            <span class="ryokou-card-placeholder-text">' . esc_html($title) . '</span>
        </div>';
    }

    ob_start();
    ?>
    <article class="ryokou-motor-card" data-motor-id="<?php echo esc_attr($post_id); ?>" data-motor-name="<?php echo esc_attr($title); ?>" data-category="<?php echo $category_attr; ?>">
        <div class="ryokou-card-media">
            <a href="<?php echo esc_url($permalink); ?>" class="ryokou-card-media-link" aria-label="<?php echo esc_attr($title); ?>">
                <?php echo $thumbnail_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>

            <!-- Floating Badges Top -->
            <div class="ryokou-card-top-badges">
                <span class="ryokou-badge <?php echo esc_attr($status_class); ?>">
                    <span class="ryokou-badge-dot"></span>
                    <?php echo esc_html($status_label); ?>
                </span>

                <?php if ($is_bromo_ready) : ?>
                    <span class="ryokou-badge ryokou-badge-bromo" title="Armada Resmi & Wajib Rute Kaldera Pasir Bromo">
                        <span class="ryokou-badge-icon">🌋</span> Bromo Ready
                    </span>
                <?php else : ?>
                    <span class="ryokou-badge ryokou-badge-city" title="Hanya untuk rute Malang & Wisata Batu. Dilarang ke Lautan Pasir Bromo.">
                        Malang & Batu
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ryokou-card-content">
            <div class="ryokou-card-header">
                <span class="ryokou-category-label"><?php echo $category_display; ?></span>
                <h3 class="ryokou-motor-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>
            </div>

            <!-- Specs Grid -->
            <div class="ryokou-specs-grid">
                <?php if (!empty($engine_cc)) : ?>
                    <div class="ryokou-spec-item" title="Kapasitas Mesin">
                        <span class="ryokou-spec-label">Mesin</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($engine_cc); ?> cc</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($transmission)) : ?>
                    <div class="ryokou-spec-item" title="Tipe Transmisi">
                        <span class="ryokou-spec-label">Transmisi</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($transmission); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($route_char)) : ?>
                    <div class="ryokou-spec-item ryokou-spec-route" title="Karakter Rute">
                        <span class="ryokou-spec-label">Karakter</span>
                        <span class="ryokou-spec-val"><?php echo esc_html($route_char); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Included Facilities Included in Every Rental -->
            <div class="ryokou-card-facilities">
                <span class="ryokou-facility-item" title="2 Helm SNI Higienis">
                    <span class="ryokou-facility-icon">🪖</span> 2 Helm SNI
                </span>
                <span class="ryokou-facility-item" title="2 Jas Hujan Setelan">
                    <span class="ryokou-facility-icon">🌧️</span> 2 Jas Hujan
                </span>
                <span class="ryokou-facility-item" title="Phone Holder Stang">
                    <span class="ryokou-facility-icon">📱</span> Holder HP
                </span>
            </div>

            <!-- Pricing Section -->
            <div class="ryokou-card-pricing">
                <div class="ryokou-price-primary">
                    <span class="ryokou-price-label">Tarif Harian (24 Jam)</span>
                    <span class="ryokou-price-amount">
                        <?php echo ryokourent_format_catalog_price($price_daily, '/ 24 Jam', 'Tanya Admin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                </div>

                <div class="ryokou-price-secondary">
                    <?php if (!empty($price_weekly) && floatval($price_weekly) > 0) : ?>
                        <span class="ryokou-secondary-rate" title="Tarif Paket Mingguan 7 Hari">
                            Mingguan: Rp <?php echo esc_html(number_format(floatval($price_weekly), 0, ',', '.')); ?>
                        </span>
                    <?php else : ?>
                        <span class="ryokou-secondary-rate">Paket Mingguan: Hemat</span>
                    <?php endif; ?>

                    <?php if (!empty($price_monthly) && floatval($price_monthly) > 0) : ?>
                        <span class="ryokou-secondary-rate" title="Tarif Paket Bulanan 30 Hari">
                            Bulanan: Rp <?php echo esc_html(number_format(floatval($price_monthly), 0, ',', '.')); ?>
                        </span>
                    <?php else : ?>
                        <span class="ryokou-secondary-rate">Paket Bulanan: Hubungi Kami</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons (CTA) -->
            <div class="ryokou-card-actions">
                <a href="#booking-form" class="ryokou-btn ryokou-btn-primary ryokou-btn-select-motor" data-motor-id="<?php echo esc_attr($post_id); ?>" data-motor-name="<?php echo esc_attr($title); ?>">
                    <span class="ryokou-btn-icon">⚡</span>
                    <span class="ryokou-btn-text">Sewa Sekarang</span>
                </a>
                <a href="<?php echo esc_url($wa_url); ?>" class="ryokou-btn ryokou-btn-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp untuk unit <?php echo esc_attr($title); ?>">
                    <span class="ryokou-btn-icon">💬</span>
                    <span class="ryokou-btn-text">Chat WA</span>
                </a>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render the entire catalog grid including header filters and fallback.
 *
 * @since 1.0.0
 * @param array $args Shortcode attributes and query overrides.
 * @return string HTML catalog markup.
 */
function ryokourent_render_catalog_grid($args = array()) {
    $defaults = array(
        'kategori'    => '',
        'limit'       => -1,
        'show_filter' => 'yes',
        'columns'     => 3,
    );
    $parsed_args = wp_parse_args($args, $defaults);

    $limit = intval($parsed_args['limit']);
    if ($limit <= 0 || $limit > 100) {
        $limit = 100;
    }

    // Query published motor units
    $query_args = array(
        'post_type'      => 'motor',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows'  => true,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    );

    if (!empty($parsed_args['kategori'])) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'kategori_motor',
                'field'    => 'slug',
                'terms'    => sanitize_title($parsed_args['kategori']),
            ),
        );
    }

    $motor_query = new WP_Query($query_args);

    // Fetch categories for tab filters
    $categories = function_exists('ryokourent_get_motor_categories') ? ryokourent_get_motor_categories() : array();

    ob_start();
    ?>
    <section class="ryokou-catalog-section" id="katalog-motor">
        <div class="ryokou-catalog-container">
            <!-- Catalog Section Header -->
            <div class="ryokou-catalog-header">
                <span class="ryokou-section-tag">PILIHAN ARMADA TERBAIK</span>
                <h2 class="ryokou-section-title">Katalog Armada Sepeda Motor Malang & Batu</h2>
                <p class="ryokou-section-desc">
                    Semua unit dalam kondisi prima, rutin servis di bengkel resmi Honda, ban tebal, serta dilengkapi 2 helm SNI steril dan 2 jas hujan setelan.
                </p>
            </div>

            <!-- Tab Filters (Mobile-First Scrollable Pills) -->
            <?php if ($parsed_args['show_filter'] === 'yes') : ?>
                <div class="ryokou-filter-nav-wrapper">
                    <div class="ryokou-filter-tabs" role="tablist" aria-label="Filter Kategori Motor">
                        <button type="button" class="ryokou-filter-btn active" data-filter="all" role="tab" aria-selected="true">
                            Semua Unit
                        </button>
                        <?php if (!empty($categories)) : ?>
                            <?php foreach ($categories as $cat) : ?>
                                <button type="button" class="ryokou-filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>" role="tab" aria-selected="false">
                                    <?php echo esc_html($cat->name); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <!-- Fallback standard categories if terms not yet loaded -->
                            <button type="button" class="ryokou-filter-btn" data-filter="beat-series" role="tab">Honda BeAT Series</button>
                            <button type="button" class="ryokou-filter-btn" data-filter="scoopy-vario" role="tab">Honda Scoopy & Vario</button>
                            <button type="button" class="ryokou-filter-btn" data-filter="trail-adventure" role="tab">Trail Adventure (Bromo)</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Catalog Grid -->
            <div class="ryokou-catalog-grid ryokou-columns-<?php echo esc_attr($parsed_args['columns']); ?>" id="ryokou-catalog-grid">
                <?php
                if ($motor_query->have_posts()) {
                    while ($motor_query->have_posts()) {
                        $motor_query->the_post();
                        echo ryokourent_render_motor_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    wp_reset_postdata();
                } else {
                    // Blueprint Default Fleet Fallback (7 Models) when no posts published yet
                    $blueprint_fleet = ryokourent_get_blueprint_default_fleet();
                    foreach ($blueprint_fleet as $custom_motor) {
                        echo ryokourent_render_motor_card(0, $custom_motor); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
                ?>
            </div>

            <!-- Empty Filter State Notice (Hidden by default, shown by JS if filter has 0 results) -->
            <div class="ryokou-catalog-empty" id="ryokou-catalog-empty" style="display: none;">
                <div class="ryokou-empty-icon">🔍</div>
                <h4 class="ryokou-empty-title">Tidak ada unit motor dalam kategori ini</h4>
                <p class="ryokou-empty-desc">Silakan pilih kategori lain atau hubungi admin via WhatsApp untuk rekomendasi armada.</p>
                <button type="button" class="ryokou-btn ryokou-btn-secondary" id="ryokou-reset-filter-btn">Lihat Semua Unit</button>
            </div>

            <!-- Trust & Inclusions Banner -->
            <div class="ryokou-catalog-trust-banner">
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">🛡️</span>
                    <div>
                        <strong>Unit Terawat & Servis Rutin</strong>
                        <span>Selalu diservis sebelum serah terima unit ke penyewa</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">🪖</span>
                    <div>
                        <strong>Fasilitas Lengkap Gratis</strong>
                        <span>2 Helm SNI bersih + 2 Jas Hujan setelan berkualitas</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">📍</span>
                    <div>
                        <strong>2 Lokasi Pool Resmi</strong>
                        <span>Pool Dinoyo (Malang) & Pool Diponegoro (Kota Batu)</span>
                    </div>
                </div>
                <div class="ryokou-trust-item">
                    <span class="ryokou-trust-icon">⏱️</span>
                    <div>
                        <strong>Jam Layanan 07.00 – 23.00</strong>
                        <span>Antar-jemput stasiun/hotel fleksibel menyesuaikan sikon</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Return default 7 fleet units from blueprint specification for immediate presentation.
 *
 * @since 1.0.0
 * @return array Array of motor unit data dictionaries.
 */
function ryokourent_get_blueprint_default_fleet() {
    return array(
        array(
            'title'           => 'Honda BeAT Deluxe',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Sangat Irit',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 85000,
            'price_weekly'    => 500000,
            'price_monthly'   => 1600000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda BeAT CBS',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Irit Dalam Kota',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 80000,
            'price_weekly'    => 480000,
            'price_monthly'   => 1500000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda BeAT Street',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Lincah & Naked Handlebar',
            'is_bromo_ready'  => false,
            'status_label'    => 'Booking Menipis',
            'price_daily'     => 85000,
            'price_weekly'    => 500000,
            'price_monthly'   => 1600000,
            'category_slugs'  => array('beat-series'),
            'category_names'  => array('Honda BeAT Series'),
        ),
        array(
            'title'           => 'Honda Scoopy',
            'permalink'       => '#',
            'engine_cc'       => 110,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman & Stylish Retro',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 95000,
            'price_weekly'    => 570000,
            'price_monthly'   => 1800000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Honda Vario 125',
            'permalink'       => '#',
            'engine_cc'       => 125,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman & Bagasi Lega',
            'is_bromo_ready'  => false,
            'status_label'    => 'Tersedia',
            'price_daily'     => 100000,
            'price_weekly'    => 600000,
            'price_monthly'   => 1900000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Honda Vario 160',
            'permalink'       => '#',
            'engine_cc'       => 160,
            'transmission'    => 'Otomatis (CVT)',
            'route_character' => 'Nyaman, Bertenaga, Stabil',
            'is_bromo_ready'  => false,
            'status_label'    => 'Booking Menipis',
            'price_daily'     => 130000,
            'price_weekly'    => 780000,
            'price_monthly'   => 2400000,
            'category_slugs'  => array('scoopy-vario'),
            'category_names'  => array('Honda Scoopy & Vario'),
        ),
        array(
            'title'           => 'Trail CRF 150L',
            'permalink'       => '#',
            'engine_cc'       => 150,
            'transmission'    => 'Manual 5-Speed',
            'route_character' => 'Adventure (Wajib Bromo)',
            'is_bromo_ready'  => true,
            'status_label'    => 'Tersedia',
            'price_daily'     => 250000,
            'price_weekly'    => 1500000,
            'price_monthly'   => 4500000,
            'category_slugs'  => array('trail-adventure'),
            'category_names'  => array('Trail Adventure (Bromo)'),
        ),
    );
}

/**
 * Retrieve the 7 official FAQ items based on the Ryokourent blueprint.
 *
 * @since 1.0.0
 * @return array<int, array<string, string>> Array of FAQ items with question and answer.
 */
function ryokourent_get_faq_items() {
    $settings   = function_exists('ryokourent_get_settings') ? ryokourent_get_settings() : array();
    $open_hour  = !empty($settings['operational_open']) ? $settings['operational_open'] : '07:00';
    $close_hour = !empty($settings['operational_close']) ? $settings['operational_close'] : '23:00';

    return array(
        array(
            'id'       => 'faq-dokumen',
            'question' => __('Apa saja dokumen persyaratan untuk menyewa motor di Ryokourent?', 'ryokourent'),
            'answer'   => __('Penyewa wajib menunjukkan e-KTP Asli serta 2 (dua) dokumen identitas pendukung yang sah dan masih berlaku, seperti SIM A, Paspor, BPJS/KIS, NPWP, KTM Mahasiswa aktif (khusus mahasiswa Malang), atau ID Pegawai resmi. Foto dokumen dikirimkan langsung ke admin WhatsApp untuk verifikasi cepat tanpa perlu registrasi akun berbelit.', 'ryokourent'),
        ),
        array(
            'id'       => 'faq-bromo-larangan',
            'question' => __('Mengapa seluruh unit matik DILARANG KERAS ke Lautan Pasir Bromo, Jalur Cangar, dan Pantai Pasir?', 'ryokourent'),
            'answer'   => __('Jalur ekstrem seperti lautan pasir Bromo, tanjakan/turunan curam Cangar (rawan rem blong pada matik), dan medan pantai pasir berisiko tinggi bagi keselamatan. Transmisi otomatis matik (CVT) rawan kemasukan debu vulkanik/pasir, overheat, belt selip, serta tidak memiliki engine brake memadai di turunan curam. Demi keselamatan jiwa dan keutuhan armada, seluruh motor matik dilarang keras melintasi rute tersebut.', 'ryokourent'),
        ),
        array(
            'id'       => 'faq-bromo-crf',
            'question' => __('Motor apa yang WAJIB digunakan jika ingin ke Bromo, Jalur Cangar, atau Pantai Pasir?', 'ryokourent'),
            'answer'   => __('Untuk perjalanan ke jalur ekstrem (Gunung Bromo, tanjakan/turunan Cangar, dan kawasan pantai pasir Malang Selatan), penyewa WAJIB menyewa unit Honda Trail CRF 150L. Unit ini telah dilengkapi suspensi Showa inverted front fork (upside-down), ban pacul dual-purpose, mesin bertenaga dengan ground clearance tinggi, serta engine brake manual yang aman untuk tanjakan dan turunan ekstrem.', 'ryokourent'),
        ),
        array(
            'id'       => 'faq-antar-jemput',
            'question' => __('Apakah motor bisa diantar ke stasiun atau tempat menginap (hotel/villa)?', 'ryokourent'),
            'answer'   => __('Bisa! Kami melayani serah terima langsung di 2 Pool resmi (Dinoyo Malang & Diponegoro Batu), serta layanan antar-jemput ke Stasiun Malang Kota Baru, Stasiun Malang Kota Lama, maupun hotel/homestay/villa di Malang Raya dan Kota Wisata Batu. Layanan pengantaran disesuaikan dengan situasi dan kondisi (sikon) tim lapangan pada jam operasional kami.', 'ryokourent'),
        ),
        array(
            'id'       => 'faq-jam-operasional',
            'question' => sprintf(
                /* translators: 1: Jam buka, 2: Jam tutup */
                __('Kapan jam operasional pelayanan dan serah terima armada?', 'ryokourent'),
                $open_hour,
                $close_hour
            ),
            'answer'   => sprintf(
                /* translators: 1: Jam buka, 2: Jam tutup */
                __('Jam pelayanan operasional Pool dan reservasi WhatsApp kami adalah pukul %s – %s WIB setiap hari. Serah terima unit, konfirmasi booking, dan pengembalian armada dilayani selama rentang jam tersebut.', 'ryokourent'),
                esc_html($open_hour),
                esc_html($close_hour)
            ),
        ),
        array(
            'id'       => 'faq-overtime',
            'question' => __('Bagaimana aturan durasi sewa 24 jam, keterlambatan (overtime), dan pembatalan booking?', 'ryokourent'),
            'answer'   => __('Durasi sewa dikunci dalam kelipatan 24 jam per hari. Apabila terjadi kendala keterlambatan pengembalian unit atau kebutuhan perpanjangan sewa, pelanggan wajib menghubungi admin via WhatsApp secepatnya. Denda keterlambatan tidak dihitung otomatis oleh web melainkan ditangani secara manual oleh admin lapangan, atau dapat langsung dialihkan menjadi akumulasi perpanjangan sewa resmi (+24 jam). Pembatalan booking juga dilakukan secara manual melalui komunikasi WhatsApp dengan admin.', 'ryokourent'),
        ),
        array(
            'id'       => 'faq-batas-wilayah',
            'question' => __('Apakah motor boleh dibawa keluar wilayah Malang Raya dan Kota Batu?', 'ryokourent'),
            'answer'   => __('Area operasional standar sewa motor adalah wilayah Malang Raya (Kota Malang, Kabupaten Malang pantai selatan/utara) dan Kota Wisata Batu. Penggunaan armada keluar batas wilayah Malang Raya (misal: Surabaya, Kediri, Blitar, Probolinggo di luar rute resmi Bromo CRF) WAJIB memperoleh izin tertulis terlebih dahulu dari Admin Ryokourent sebelum keberangkatan.', 'ryokourent'),
        ),
    );
}

/**
 * Retrieve official pool locations merged with settings.
 *
 * @since 1.0.0
 * @return array<string, array<string, mixed>> Array of pool details.
 */
function ryokourent_get_pool_details() {
    $settings = function_exists('ryokourent_get_settings') ? ryokourent_get_settings() : array();

    $open_hour  = !empty($settings['operational_open']) ? $settings['operational_open'] : '07:00';
    $close_hour = !empty($settings['operational_close']) ? $settings['operational_close'] : '23:00';

    return array(
        'pool_dinoyo' => array(
            'id'          => 'pool-dinoyo',
            'code'        => 'Pool 1 (Kota Malang)',
            'name'        => !empty($settings['pool_dinoyo_name']) ? $settings['pool_dinoyo_name'] : __('Pool Malang Dinoyo (Pusat Kota / Kampus)', 'ryokourent'),
            'badge'       => __('Pusat Kota & Kampus', 'ryokourent'),
            'address'     => !empty($settings['pool_dinoyo_address']) ? $settings['pool_dinoyo_address'] : __('Jl. MT Haryono Gg. 21 No. 23, Dinoyo, Lowokwaru, Kota Malang', 'ryokourent'),
            'maps_url'    => !empty($settings['pool_dinoyo_maps']) ? $settings['pool_dinoyo_maps'] : 'https://maps.google.com/?q=Ryokourent+Dinoyo+Malang',
            'hours'       => sprintf('%s – %s WIB', $open_hour, $close_hour),
            'highlights'  => array(
                __('Dekat kampus ternama (UB, UIN Maulana Malik Ibrahim, Polinema, Unisma, UM).', 'ryokourent'),
                __('Akses kilat ke koridor kuliner Soekarno-Hatta (Suhat) & pusat oleh-oleh khas Malang.', 'ryokourent'),
                __('Titik temu pengantaran cepat ke Stasiun Malang Kota Baru & Stasiun Malang Kota Lama.', 'ryokourent'),
            ),
        ),
        'pool_batu' => array(
            'id'          => 'pool-batu',
            'code'        => 'Pool 2 (Kota Wisata Batu)',
            'name'        => !empty($settings['pool_batu_name']) ? $settings['pool_batu_name'] : __('Pool Batu Diponegoro (Kota Wisata Batu)', 'ryokourent'),
            'badge'       => __('Kota Wisata Batu', 'ryokourent'),
            'address'     => !empty($settings['pool_batu_address']) ? $settings['pool_batu_address'] : __('Jl. Belakang Pompa Bensin, Jl. Diponegoro No. 45, Sisir, Kec. Batu, Kota Wisata Batu', 'ryokourent'),
            'maps_url'    => !empty($settings['pool_batu_maps']) ? $settings['pool_batu_maps'] : 'https://maps.google.com/?q=Ryokourent+Batu',
            'hours'       => sprintf('%s – %s WIB', $open_hour, $close_hour),
            'highlights'  => array(
                __('Berada tepat di jantung Kota Wisata Batu, dekat Alun-Alun Batu & Pos Ketan Legenda.', 'ryokourent'),
                __('Akses langsung tanpa hambatan menuju Jatim Park 1, 2, 3, Museum Angkut, & BNS.', 'ryokourent'),
                __('Titik awal nyaman untuk menjelajahi Selecta, Coban Rondo, paralayang, hingga Cangar.', 'ryokourent'),
            ),
        ),
    );
}

/**
 * Render FAQ Accordion HTML Component (7 points based on blueprint).
 *
 * @since 1.0.0
 * @param array $args Optional styling or title overrides.
 * @return string HTML rendered output.
 */
function ryokourent_render_faq_section($args = array()) {
    $defaults = array(
        'section_id'  => 'syarat-faq',
        'title'       => __('Syarat Sewa & Pertanyaan Sering Diajukan (FAQ)', 'ryokourent'),
        'subtitle'    => __('Transparansi penuh demi keselamatan, keamanan armada, dan kenyamanan liburan Anda di Malang & Batu.', 'ryokourent'),
        'show_header' => true,
    );
    $parsed = wp_parse_args($args, $defaults);
    $faqs   = ryokourent_get_faq_items();

    ob_start();
    ?>
    <section class="ryokou-faq-section" id="<?php echo esc_attr($parsed['section_id']); ?>">
        <div class="ryokou-faq-container">
            <?php if ($parsed['show_header']) : ?>
                <div class="ryokou-faq-header">
                    <span class="ryokou-section-tag"><?php esc_html_e('PANDUAN & FAQ RESMI', 'ryokourent'); ?></span>
                    <h2 class="ryokou-section-title"><?php echo esc_html($parsed['title']); ?></h2>
                    <p class="ryokou-section-desc"><?php echo esc_html($parsed['subtitle']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Document Requirements Banner -->
            <div class="ryokou-req-banner">
                <div class="ryokou-req-banner-icon">📋</div>
                <div class="ryokou-req-banner-content">
                    <h3 class="ryokou-req-banner-title"><?php esc_html_e('Syarat Dokumen Jaminan Sewa (Wajib e-KTP + 2 Pendukung)', 'ryokourent'); ?></h3>
                    <p class="ryokou-req-banner-text">
                        <?php esc_html_e('Setiap penyewa wajib menunjukkan e-KTP Asli serta 2 dokumen identitas pendukung sah (SIM A, Paspor, BPJS/KIS, NPWP, KTM Mahasiswa, atau ID Karyawan). Verifikasi dilakukan privat via WhatsApp tanpa penyimpanan dokumen publik.', 'ryokourent'); ?>
                    </p>
                </div>
            </div>

            <!-- Accordion List -->
            <div class="ryokou-accordion" role="region" aria-label="<?php esc_attr_e('Daftar Pertanyaan Umum Ryokourent', 'ryokourent'); ?>">
                <?php foreach ($faqs as $index => $faq) : ?>
                    <div class="ryokou-accordion-item" id="<?php echo esc_attr($faq['id']); ?>">
                        <button
                            type="button"
                            class="ryokou-accordion-trigger"
                            aria-expanded="false"
                            aria-controls="<?php echo esc_attr($faq['id'] . '-content'); ?>"
                            id="<?php echo esc_attr($faq['id'] . '-trigger'); ?>"
                        >
                            <span class="ryokou-accordion-q-num">Q<?php echo esc_html($index + 1); ?></span>
                            <span class="ryokou-accordion-title"><?php echo esc_html($faq['question']); ?></span>
                            <span class="ryokou-accordion-icon" aria-hidden="true">+</span>
                        </button>
                        <div
                            class="ryokou-accordion-content"
                            id="<?php echo esc_attr($faq['id'] . '-content'); ?>"
                            role="region"
                            aria-labelledby="<?php echo esc_attr($faq['id'] . '-trigger'); ?>"
                            hidden
                        >
                            <div class="ryokou-accordion-body">
                                <p><?php echo esc_html($faq['answer']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Official Pool Locations Section with Google Maps deep links.
 *
 * @since 1.0.0
 * @param array $args Optional overrides.
 * @return string HTML rendered output.
 */
function ryokourent_render_pool_locations_section($args = array()) {
    $defaults = array(
        'section_id'  => 'lokasi-pool',
        'title'       => __('Area Layanan & Dua Lokasi Pool Resmi', 'ryokourent'),
        'subtitle'    => __('Titik strategis di Kota Malang dan Kota Wisata Batu untuk serah terima unit langsung atau pengantaran ke tempat menginap Anda.', 'ryokourent'),
        'show_header' => true,
    );
    $parsed = wp_parse_args($args, $defaults);
    $pools  = ryokourent_get_pool_details();

    ob_start();
    ?>
    <section class="ryokou-pools-section" id="<?php echo esc_attr($parsed['section_id']); ?>">
        <div class="ryokou-pools-container">
            <?php if ($parsed['show_header']) : ?>
                <div class="ryokou-pools-header">
                    <span class="ryokou-section-tag"><?php esc_html_e('POOL MALANG & BATU', 'ryokourent'); ?></span>
                    <h2 class="ryokou-section-title"><?php echo esc_html($parsed['title']); ?></h2>
                    <p class="ryokou-section-desc"><?php echo esc_html($parsed['subtitle']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Pool Cards Grid -->
            <div class="ryokou-pools-grid">
                <?php foreach ($pools as $pool) : ?>
                    <article class="ryokou-pool-card" id="<?php echo esc_attr($pool['id']); ?>">
                        <div class="ryokou-pool-card-header">
                            <div class="ryokou-pool-badge-wrap">
                                <span class="ryokou-pool-code"><?php echo esc_html($pool['code']); ?></span>
                                <span class="ryokou-badge ryokou-badge-city"><?php echo esc_html($pool['badge']); ?></span>
                            </div>
                            <span class="ryokou-pool-hours">
                                <span class="ryokou-hours-icon">🕒</span> <?php echo esc_html($pool['hours']); ?>
                            </span>
                        </div>

                        <div class="ryokou-pool-card-body">
                            <h3 class="ryokou-pool-name"><?php echo esc_html($pool['name']); ?></h3>
                            <p class="ryokou-pool-address">
                                <span class="ryokou-pin-icon">📍</span> <?php echo esc_html($pool['address']); ?>
                            </p>

                            <div class="ryokou-pool-highlights">
                                <h4 class="ryokou-highlights-title"><?php esc_html_e('Keunggulan Akses Lokasi:', 'ryokourent'); ?></h4>
                                <ul class="ryokou-highlights-list">
                                    <?php foreach ($pool['highlights'] as $highlight) : ?>
                                        <li><?php echo esc_html($highlight); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="ryokou-pool-card-footer">
                            <a
                                href="<?php echo esc_url($pool['maps_url']); ?>"
                                class="ryokou-btn ryokou-btn-maps"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="<?php echo esc_attr(sprintf(__('Buka peta Google Maps untuk %s', 'ryokourent'), $pool['name'])); ?>"
                            >
                                <span class="ryokou-btn-icon">🗺️</span>
                                <span><?php esc_html_e('Buka di Google Maps', 'ryokourent'); ?></span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Delivery Sikon Notice -->
            <div class="ryokou-delivery-notice">
                <div class="ryokou-delivery-icon">🛵</div>
                <div class="ryokou-delivery-text">
                    <strong><?php esc_html_e('Layanan Antar-Jemput Fleksibel Sesuai Situasi & Kondisi (Sikon):', 'ryokourent'); ?></strong>
                    <span><?php esc_html_e(' Selain ambil langsung di kedua Pool resmi di atas, unit dapat diantar ke Stasiun Malang Kota Baru atau penginapan (hotel/homestay/villa) Anda di Malang & Batu dengan konfirmasi admin terlebih dahulu.', 'ryokourent'); ?></span>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Bromo Mandatory Rules Banner (Trail CRF 150L).
 *
 * @since 1.0.0
 * @return string HTML rendered output.
 */
function ryokourent_render_bromo_advisory_banner() {
    ob_start();
    ?>
    <aside class="ryokou-bromo-advisory-banner" role="alert" aria-label="<?php esc_attr_e('Peringatan Keselamatan Jalur Ekstrem Bromo, Cangar, dan Pantai Pasir', 'ryokourent'); ?>">
        <div class="ryokou-bromo-advisory-inner">
            <div class="ryokou-bromo-advisory-icon" aria-hidden="true">⛰️</div>
            <div class="ryokou-bromo-advisory-content">
                <div class="ryokou-bromo-advisory-badge"><?php esc_html_e('ATURAN WAJIB JALUR EKSTREM & OFF-ROAD', 'ryokourent'); ?></div>
                <h3 class="ryokou-bromo-advisory-title">
                    <?php esc_html_e('Jalur Ekstrem Bromo, Cangar, & Pantai Pasir Wajib Honda Trail CRF 150L — Unit Matik Dilarang Keras!', 'ryokourent'); ?>
                </h3>
                <p class="ryokou-bromo-advisory-desc">
                    <?php esc_html_e('Demi keselamatan jiwa dan mencegah risiko rem blong serta kerusakan transmisi, seluruh unit motor matik (BeAT, Scoopy, Vario, PCX) DILARANG KERAS melintasi jalur ekstrem naik-turun curam atau off-road: Lautan Pasir Gunung Bromo, tanjakan/turunan terjal Cangar, dan pantai pasir Malang Selatan. Rute-rute tersebut WAJIB menggunakan Honda Trail CRF 150L dengan suspensi upside-down Showa dan ban pacul dual-purpose.', 'ryokourent'); ?>
                </p>
                <div class="ryokou-bromo-advisory-actions">
                    <a href="#katalog-motor" class="ryokou-btn ryokou-btn-amber-sm ryokou-filter-trigger-crf" data-filter="trail-adventure">
                        <span><?php esc_html_e('Lihat Unit Trail CRF 150L Siap Jalur Ekstrem', 'ryokourent'); ?></span> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </aside>
    <?php
    return ob_get_clean();
}

/**
 * Render Floating Mobile Action Bar (TASK-026: Mobile-First Optimization).
 *
 * Requirements:
 * - Height strictly <= 15% viewport (< ~85px on mobile).
 * - Ergonomic single-thumb interaction zone.
 * - Minimum 44x44px touch targets.
 * - Direct zero-friction access to WhatsApp and Booking Form.
 * - Displays quick status/hours indicator (07:00-23:00 WIB).
 *
 * @since 1.0.0
 * @param array $args Optional custom arguments.
 * @return string HTML rendered output.
 */
function ryokourent_render_floating_mobile_bar($args = array()) {
    $settings   = function_exists('ryokourent_get_settings') ? ryokourent_get_settings() : array();
    $wa_number  = !empty($settings['wa_primary']) ? $settings['wa_primary'] : (defined('RYOKOURENT_DEFAULT_WA_NUMBER') ? RYOKOURENT_DEFAULT_WA_NUMBER : '62895384017772');
    $clean_wa   = preg_replace('/[^0-9]/', '', (string) $wa_number);
    $hours_open = !empty($settings['pool_open']) ? $settings['pool_open'] : '07:00';
    $hours_close = !empty($settings['pool_close']) ? $settings['pool_close'] : '23:00';

    $wa_msg = isset($args['wa_message']) ? $args['wa_message'] : 'Halo Admin Ryokourent, saya ingin tanya ketersediaan sewa motor di Malang/Batu hari ini.';
    $wa_url = 'https://api.whatsapp.com/send?phone=' . esc_attr($clean_wa) . '&text=' . rawurlencode($wa_msg);
    $booking_anchor = isset($args['booking_anchor']) ? $args['booking_anchor'] : '#booking-form';

    ob_start();
    ?>
    <nav class="ryokou-floating-mobile-bar" aria-label="<?php esc_attr_e('Aksi Cepat Mobile Ryokourent', 'ryokourent'); ?>" role="navigation">
        <div class="ryokou-floating-inner">
            <!-- Left Info Block: Operating Hours & Status -->
            <div class="ryokou-floating-info">
                <span class="ryokou-floating-status">
                    <span class="ryokou-pulse-dot" aria-hidden="true"></span>
                    <span class="ryokou-status-text"><?php esc_html_e('Buka', 'ryokourent'); ?></span>
                </span>
                <span class="ryokou-floating-hours"><?php echo esc_html($hours_open . ' - ' . $hours_close); ?> WIB</span>
            </div>

            <!-- Right Action Group: Thumb Friendly Targets -->
            <div class="ryokou-floating-actions">
                <a
                    href="<?php echo esc_url($booking_anchor); ?>"
                    class="ryokou-floating-btn ryokou-floating-btn-book"
                    aria-label="<?php esc_attr_e('Pesan motor sekarang', 'ryokourent'); ?>"
                >
                    <span class="ryokou-btn-icon" aria-hidden="true">⚡</span>
                    <span class="ryokou-btn-label"><?php esc_html_e('Form Sewa', 'ryokourent'); ?></span>
                </a>
                <a
                    href="<?php echo esc_url($wa_url); ?>"
                    class="ryokou-floating-btn ryokou-floating-btn-wa"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php esc_attr_e('Chat WhatsApp Admin Ryokourent', 'ryokourent'); ?>"
                >
                    <span class="ryokou-btn-icon" aria-hidden="true">💬</span>
                    <span class="ryokou-btn-label"><?php esc_html_e('Chat WA', 'ryokourent'); ?></span>
                </a>
            </div>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}

