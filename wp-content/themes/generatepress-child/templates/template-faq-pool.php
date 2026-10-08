<?php
/**
 * Template Name: Halaman FAQ & Lokasi Pool (Ryokourent)
 *
 * Template for displaying official Pool locations, Operating Hours (07:00-23:00 WIB),
 * Bromo Mandatory Advisory, and 7-item Interactive FAQ Accordion.
 *
 * @package GeneratePress_Child_Ryokourent
 * @since   1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue public assets if registered
if (function_exists('wp_enqueue_style')) {
    wp_enqueue_style('ryokourent-public');
}
if (function_exists('wp_enqueue_script')) {
    wp_enqueue_script('ryokourent-filter');
}
?>

<main id="primary" class="site-main ryokou-page-faq-pools">
    <div class="ryokou-page-hero">
        <div class="ryokou-container">
            <nav class="ryokou-single-nav" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ryokou-back-link">
                    <span class="ryokou-back-arrow">&larr;</span> <?php esc_html_e('Beranda Ryokourent', 'generatepress-child'); ?>
                </a>
                <span class="ryokou-nav-separator">/</span>
                <span class="ryokou-nav-current"><?php esc_html_e('FAQ & Lokasi Pool', 'generatepress-child'); ?></span>
            </nav>

            <div class="ryokou-page-hero-header">
                <span class="ryokou-section-tag"><?php esc_html_e('PUSAT INFORMASI RESMI', 'generatepress-child'); ?></span>
                <h1 class="ryokou-section-title"><?php esc_html_e('Lokasi 2 Pool Resmi, Jam Operasional & FAQ', 'generatepress-child'); ?></h1>
                <p class="ryokou-section-desc">
                    <?php esc_html_e('Panduan lengkap operasional sewa motor di Kota Malang & Kota Wisata Batu, titik serah terima unit, ketentuan wajib armada Bromo, dan pertanyaan sering diajukan.', 'generatepress-child'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Bromo Advisory Warning Banner -->
    <div class="ryokou-container" style="padding-top: 1.5rem;">
        <?php
        if (function_exists('ryokourent_render_bromo_advisory_banner')) {
            echo ryokourent_render_bromo_advisory_banner(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
    </div>

    <!-- Official Pool Locations Section -->
    <?php
    if (function_exists('ryokourent_render_pool_locations_section')) {
        echo ryokourent_render_pool_locations_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>

    <!-- 7-Item Interactive FAQ Accordion Section -->
    <?php
    if (function_exists('ryokourent_render_faq_section')) {
        echo ryokourent_render_faq_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>

    <!-- Action Callout Bar -->
    <section class="ryokou-cta-callout-section">
        <div class="ryokou-container text-center">
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #ffffff; margin-bottom: 0.75rem;">
                <?php esc_html_e('Siap Eksplorasi Malang & Wisata Batu Tanpa Macet?', 'generatepress-child'); ?>
            </h2>
            <p style="color: #94a3b8; font-size: 0.9rem; max-width: 600px; margin: 0 auto 1.5rem; line-height: 1.6;">
                <?php esc_html_e('Pesan unit motor Anda sekarang dengan alur zero-friction WhatsApp. Tanpa akun, draf pesan tersusun otomatis langsung ke admin.', 'generatepress-child'); ?>
            </p>
            <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo esc_url(home_url('/#booking-form')); ?>" class="ryokou-btn ryokou-btn-primary" style="padding: 0.85rem 1.5rem; font-weight: 700; text-decoration: none;">
                    <span>⚡ <?php esc_html_e('Pesan Motor Sekarang', 'generatepress-child'); ?></span>
                </a>
                <a href="<?php echo esc_url(home_url('/#katalog-motor')); ?>" class="ryokou-btn" style="padding: 0.85rem 1.5rem; background: #162032; border: 1px solid #223249; color: #ffffff; text-decoration: none;">
                    <span>🛵 <?php esc_html_e('Lihat Semua Unit', 'generatepress-child'); ?></span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php
// Output floating mobile bar if function exists (TASK-026)
if (function_exists('ryokourent_render_floating_mobile_bar')) {
    echo ryokourent_render_floating_mobile_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

get_footer();

