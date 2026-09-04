<?php
/**
 * AJNanda admin: SEO Insights screen.
 *
 * Moved here from the Customizer (see ajnanda_seo_register_admin_pages() in inc/seo.php) — the
 * insights themselves still come from ajnanda_seo_render_site_kit_insights() (Google Site Kit's
 * Search Console + PageSpeed data via its own REST routes), unchanged; this is only a different
 * page to view them on.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ajnanda-admin-wrap">
    <div class="ajnanda-admin-card">
        <?php echo ajnanda_seo_render_site_kit_insights(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</div>
