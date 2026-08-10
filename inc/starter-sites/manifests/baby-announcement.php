<?php
/**
 * Starter Site: Baby Announcement.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'baby-announcement',
    'label'       => __('Anad - Baby Announcement', 'ajnanda'),
    'description' => __('A 4-page site for a birth announcement, nursery, or new-arrival page: Home, Gallery, About, and Contact. Pairs well with the "Little One" Site Kit (AJNanda → Site Kits) — apply that before importing for a soft, gender-neutral pastel look.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',    'title' => __('Home', 'ajnanda'),    'slug' => 'home',    'page_design' => 'ajnanda/page-home-baby-announcement', 'menu_order' => 1),
        array('key' => 'gallery', 'title' => __('Gallery', 'ajnanda'), 'slug' => 'gallery', 'page_design' => 'ajnanda/page-gallery',                'menu_order' => 2),
        array('key' => 'about',   'title' => __('About', 'ajnanda'),   'slug' => 'about',   'page_design' => 'ajnanda/page-about-story',            'menu_order' => 3),
        array('key' => 'contact', 'title' => __('Contact', 'ajnanda'), 'slug' => 'contact', 'page_design' => 'ajnanda/page-contact',                'menu_order' => 4),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'gallery', 'about', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
