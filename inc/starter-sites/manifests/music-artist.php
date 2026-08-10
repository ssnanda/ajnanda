<?php
/**
 * Starter Site: Music Artist / DJ.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'music-artist',
    'label'       => __('Music Artist / DJ', 'ajnanda'),
    'description' => __('A 5-page site for a musician, DJ, or band: Home, Music/Releases, Shows, About, and Contact/Booking. Pairs well with the "Neon Night" Site Kit (AJNanda → Site Kits) — apply that before importing for the full dark, high-energy look.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',    'title' => __('Home', 'ajnanda'),    'slug' => 'home',    'page_design' => 'ajnanda/page-home-music-artist', 'menu_order' => 1),
        array('key' => 'music',   'title' => __('Music', 'ajnanda'),   'slug' => 'music',   'page_design' => 'ajnanda/page-music-releases',    'menu_order' => 2),
        array('key' => 'shows',   'title' => __('Shows', 'ajnanda'),   'slug' => 'shows',   'page_design' => 'ajnanda/page-tour-dates',        'menu_order' => 3),
        array('key' => 'about',   'title' => __('About', 'ajnanda'),   'slug' => 'about',   'page_design' => 'ajnanda/page-about-artist',      'menu_order' => 4),
        array('key' => 'contact', 'title' => __('Contact', 'ajnanda'), 'slug' => 'contact', 'page_design' => 'ajnanda/page-contact',           'menu_order' => 5),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'music', 'shows', 'about', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
