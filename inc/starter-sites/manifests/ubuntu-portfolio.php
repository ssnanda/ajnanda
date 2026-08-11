<?php
/**
 * Starter Site: Ubuntu Portfolio ("Aad - Ubuntu" label).
 *
 * Same 4-page shape as the "developer-portfolio" starter (Home, Projects,
 * About, Contact — same Page Designs), pointed at the new "Ubuntu
 * Terminal" Site Kit instead of "Developer Portfolio": Aubergine color
 * scheme + Developer Mono font pairing + Dark Surface Mode on (see
 * inc/site-kits.php, inc/dark-surface-mode.php). A separate starter
 * rather than just changing developer-portfolio's site_kit, so both
 * looks (light "Sky" tech-blue vs. dark Ubuntu-orange-on-charcoal) stay
 * independently pickable from the Starter Sites screen.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'ubuntu-portfolio',
    'label'       => __('Aad - Ubuntu', 'ajnanda'),
    'site_kit'    => 'ubuntu-terminal',
    'description' => __('The same 4-page developer/coder portfolio as "Aad - Linux Coder" (Home, Projects, About, Contact), paired with the "Ubuntu Terminal" Site Kit instead: a dark, Ubuntu/GNOME-desktop-inspired look — deep charcoal surfaces with a warm terminal-orange accent. Apply that kit (AJNanda → Site Kits) before importing for the full dark look.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',     'title' => __('Home', 'ajnanda'),     'slug' => 'home',     'page_design' => 'ajnanda/page-home-developer', 'menu_order' => 1),
        array('key' => 'projects', 'title' => __('Projects', 'ajnanda'), 'slug' => 'projects', 'page_design' => 'ajnanda/page-projects',       'menu_order' => 2),
        array('key' => 'about',    'title' => __('About', 'ajnanda'),    'slug' => 'about',    'page_design' => 'ajnanda/page-about-story',    'menu_order' => 3),
        array('key' => 'contact',  'title' => __('Contact', 'ajnanda'),  'slug' => 'contact',  'page_design' => 'ajnanda/page-contact',        'menu_order' => 4),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'projects', 'about', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
