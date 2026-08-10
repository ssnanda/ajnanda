<?php
/**
 * Starter Site: Personal / Creative ("Jasna - Hello Kitty" label).
 *
 * The label references a pastel/cute aesthetic direction only — no
 * Sanrio/Hello Kitty character art, logos, or trademarked assets are
 * used anywhere in this manifest or its page designs, same as every
 * other AJNanda pattern (images are always generic placeholders, never
 * real artwork). Personalize with a real name/label at import time.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'personal-creative',
    'label'       => __('Jasna - Hello Kitty', 'ajnanda'),
    'site_kit'    => 'bubblegum-pop',
    'description' => __('A 4-page site for a personal, hobby, or fan page: Home, Blog, Gallery, and About. Pairs well with the "Bubblegum Pop" Site Kit (AJNanda → Site Kits) — apply that before importing for a soft, playful, pastel look.', 'ajnanda'),
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
