<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('Skip to content', 'ajnanda'); ?></a>

<?php
$header_layout      = ajnanda_get_header_layout();
$mobile_menu_style  = get_theme_mod('ajn_mobile_menu_style', 'slide');
$header_classes     = array('site-header', 'header-layout-' . $header_layout);
if ('overlay' === $mobile_menu_style) {
    $header_classes[] = 'mobile-nav-style-overlay';
}
?>
<header class="<?php echo esc_attr(implode(' ', $header_classes)); ?>" id="masthead">
    <?php ajnanda_render_header_accent_strip(); ?>
    <?php if ('builder' === $header_layout) : ?>
        <div class="header-builder-container container">
            <?php ajnanda_render_builder_layout('header'); ?>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" type="button" aria-label="<?php esc_attr_e('Toggle menu', 'ajnanda'); ?>" aria-controls="primary-menu" aria-expanded="false">
                <span class="mobile-menu-icon" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="mobile-menu-label"><?php esc_html_e('Menu', 'ajnanda'); ?></span>
            </button>
        </div>
    <?php else : ?>
        <div class="header-container container">
            <div class="site-branding">
                <?php ajnanda_render_builder_site_identity(); ?>
            </div>

            <nav class="main-navigation" id="site-navigation">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'menu_class'     => ajnanda_get_primary_menu_class('nav-menu'),
                    'fallback_cb'    => false,
                ));
                ?>
            </nav>

            <button class="mobile-menu-toggle" id="mobile-menu-toggle" type="button" aria-label="<?php esc_attr_e('Toggle menu', 'ajnanda'); ?>" aria-controls="primary-menu" aria-expanded="false">
                <span class="mobile-menu-icon" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="mobile-menu-label"><?php esc_html_e('Menu', 'ajnanda'); ?></span>
            </button>
        </div>
    <?php endif; ?>
</header>
<?php ajnanda_render_header_builder_preview(); ?>
