<?php
/**
 * Starter Site: Family Blog.
 *
 * Deliberately reuses the exact same page designs "Personal / Creative"
 * uses (Home — Personal, Blog Landing, Gallery, About — Story) — the
 * difference between the two starter sites is curation/audience and
 * default Site Kit pairing, not new markup. Composition over duplication
 * applies at the starter-site level too, not just within a page design.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'family-blog',
    'label'       => __('Family Blog', 'ajnanda'),
    'description' => __('A 4-page site for sharing family updates: Home, Blog, Gallery, and About. Pairs well with the "Family Warmth" Site Kit (AJNanda → Site Kits) — apply that before importing for a warm, journal-like look.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',    'title' => __('Home', 'ajnanda'),    'slug' => 'home',    'page_design' => 'ajnanda/page-home-personal', 'menu_order' => 1),
        array('key' => 'blog',    'title' => __('Blog', 'ajnanda'),    'slug' => 'blog',    'page_design' => 'ajnanda/page-blog-landing',  'menu_order' => 2),
        array('key' => 'gallery', 'title' => __('Gallery', 'ajnanda'), 'slug' => 'gallery', 'page_design' => 'ajnanda/page-gallery',       'menu_order' => 3),
        array('key' => 'about',   'title' => __('About', 'ajnanda'),   'slug' => 'about',   'page_design' => 'ajnanda/page-about-story',   'menu_order' => 4),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'blog', 'gallery', 'about'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
