<?php
/**
 * Template Name: Halaman FAQ & Lokasi Pool (Ryokourent)
 *
 * Route entry template for FAQ and Pool locations page in GeneratePress Child Theme.
 *
 * @package GeneratePress_Child_Ryokourent
 * @since   1.0.0
 */

// Route to template partial
$template = locate_template(array('templates/template-faq-pool.php'));
if ($template) {
    require $template;
} else {
    require __DIR__ . '/templates/template-faq-pool.php';
}
