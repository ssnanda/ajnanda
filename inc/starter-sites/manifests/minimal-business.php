<?php
/**
 * Starter Site: Minimal Business.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'minimal-business',
    'label'       => __('Minimal Business', 'ajnanda'),
    'description' => __('The smallest useful site: Home, About, Services, and Contact. A good starting point for any business type.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',     'title' => __('Home', 'ajnanda'),     'slug' => 'home',     'page_design' => 'ajnanda/page-home-small-business', 'menu_order' => 1),
        array('key' => 'about',    'title' => __('About', 'ajnanda'),    'slug' => 'about',    'page_design' => 'ajnanda/page-about-story',         'menu_order' => 2),
        array('key' => 'services', 'title' => __('Services', 'ajnanda'), 'slug' => 'services', 'page_design' => 'ajnanda/page-services-overview',   'menu_order' => 3),
        array('key' => 'contact',  'title' => __('Contact', 'ajnanda'),  'slug' => 'contact',  'page_design' => 'ajnanda/page-contact',             'menu_order' => 4),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'about', 'services', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
