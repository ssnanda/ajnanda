<?php
/**
 * Starter Site: Corporate / Business.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'corporate',
    'label'       => __('Corporate / Business', 'ajnanda'),
    'description' => __('A measured, credibility-first site for an established business: Home, About, Services, Contact, and Blog.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',     'title' => __('Home', 'ajnanda'),     'slug' => 'home',     'page_design' => 'ajnanda/page-home-corporate',    'menu_order' => 1),
        array('key' => 'about',    'title' => __('About', 'ajnanda'),    'slug' => 'about',    'page_design' => 'ajnanda/page-about-company',     'menu_order' => 2),
        array('key' => 'services', 'title' => __('Services', 'ajnanda'), 'slug' => 'services', 'page_design' => 'ajnanda/page-services-overview', 'menu_order' => 3),
        array('key' => 'blog',     'title' => __('Blog', 'ajnanda'),     'slug' => 'blog',     'page_design' => 'ajnanda/page-blog-landing',      'menu_order' => 4),
        array('key' => 'contact',  'title' => __('Contact', 'ajnanda'),  'slug' => 'contact',  'page_design' => 'ajnanda/page-contact',           'menu_order' => 5),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'about', 'services', 'blog', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
