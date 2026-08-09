<?php
/**
 * Starter Site: Insurance / Financial Services.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'insurance-financial',
    'label'       => __('Insurance / Financial Services', 'ajnanda'),
    'description' => __('A trust- and compliance-forward site for an insurance or financial-services business: Home, Services, an individual Service, About, FAQ, Contact, and Blog.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',     'title' => __('Home', 'ajnanda'),     'slug' => 'home',     'page_design' => 'ajnanda/page-home-corporate',      'menu_order' => 1),
        array('key' => 'services', 'title' => __('Services', 'ajnanda'), 'slug' => 'services', 'page_design' => 'ajnanda/page-services-overview',   'menu_order' => 2),
        array('key' => 'service',  'title' => __('Service', 'ajnanda'),  'slug' => 'service',  'page_design' => 'ajnanda/page-service-single',      'menu_order' => 3),
        array('key' => 'about',    'title' => __('About', 'ajnanda'),    'slug' => 'about',    'page_design' => 'ajnanda/page-about-professional',  'menu_order' => 4),
        array('key' => 'faq',      'title' => __('FAQ', 'ajnanda'),      'slug' => 'faq',      'page_design' => 'ajnanda/page-faq',                 'menu_order' => 5),
        array('key' => 'blog',     'title' => __('Blog', 'ajnanda'),     'slug' => 'blog',     'page_design' => 'ajnanda/page-blog-landing',        'menu_order' => 6),
        array('key' => 'contact',  'title' => __('Contact', 'ajnanda'),  'slug' => 'contact',  'page_design' => 'ajnanda/page-contact',             'menu_order' => 7),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'services', 'about', 'faq', 'blog', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
