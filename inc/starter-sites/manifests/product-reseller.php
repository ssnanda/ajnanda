<?php
/**
 * Starter Site: Product / Reseller.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'product-reseller',
    'label'       => __('Product / Reseller', 'ajnanda'),
    'description' => __('A catalog-driven site for a product reseller or solutions provider: Home, Products, Solutions, Partners, Resources, About, and Contact.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',      'title' => __('Home', 'ajnanda'),      'slug' => 'home',      'page_design' => 'ajnanda/page-home-product-solution', 'menu_order' => 1),
        array('key' => 'products',  'title' => __('Products', 'ajnanda'),  'slug' => 'products',  'page_design' => 'ajnanda/page-products-overview',     'menu_order' => 2),
        array('key' => 'solutions', 'title' => __('Solutions', 'ajnanda'), 'slug' => 'solutions', 'page_design' => 'ajnanda/page-services-overview',     'menu_order' => 3),
        array('key' => 'partners',  'title' => __('Partners', 'ajnanda'),  'slug' => 'partners',  'page_design' => 'ajnanda/page-partners',              'menu_order' => 4),
        array('key' => 'resources', 'title' => __('Resources', 'ajnanda'), 'slug' => 'resources', 'page_design' => 'ajnanda/page-resource-center',       'menu_order' => 5),
        array('key' => 'about',     'title' => __('About', 'ajnanda'),     'slug' => 'about',     'page_design' => 'ajnanda/page-about-company',         'menu_order' => 6),
        array('key' => 'contact',   'title' => __('Contact', 'ajnanda'),   'slug' => 'contact',   'page_design' => 'ajnanda/page-contact',               'menu_order' => 7),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'products', 'solutions', 'partners', 'resources', 'about', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
