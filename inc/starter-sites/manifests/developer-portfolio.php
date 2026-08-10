<?php
/**
 * Starter Site: Developer Portfolio ("Aad - Linux Coder" label).
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'developer-portfolio',
    'label'       => __('Aad - Linux Coder', 'ajnanda'),
    'site_kit'    => 'developer-portfolio',
    'description' => __('A 4-page site for a developer/coder portfolio: Home, Projects, About, and Contact. Pairs well with the "Developer Portfolio" Site Kit (AJNanda → Site Kits) — apply that before importing for a clean, spacious, tech-forward look.', 'ajnanda'),
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
