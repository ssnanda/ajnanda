<?php
/**
 * Starter Site: Property / Management.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'property-management',
    'label'       => __('Property / Management', 'ajnanda'),
    'description' => __('A straightforward site for a property or facilities management business: Home, Services, Properties, About, FAQ, and Contact.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',       'title' => __('Home', 'ajnanda'),       'slug' => 'home',       'page_design' => 'ajnanda/page-home-small-business', 'menu_order' => 1),
        array('key' => 'services',   'title' => __('Services', 'ajnanda'),   'slug' => 'services',   'page_design' => 'ajnanda/page-services-overview',   'menu_order' => 2),
        array('key' => 'properties', 'title' => __('Properties', 'ajnanda'), 'slug' => 'properties', 'page_design' => 'ajnanda/page-products-overview',   'menu_order' => 3),
        array('key' => 'about',      'title' => __('About', 'ajnanda'),      'slug' => 'about',      'page_design' => 'ajnanda/page-about-company',       'menu_order' => 4),
        array('key' => 'faq',        'title' => __('FAQ', 'ajnanda'),        'slug' => 'faq',        'page_design' => 'ajnanda/page-faq',                 'menu_order' => 5),
        array('key' => 'contact',    'title' => __('Contact', 'ajnanda'),    'slug' => 'contact',    'page_design' => 'ajnanda/page-contact',             'menu_order' => 6),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'services', 'properties', 'about', 'faq', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => '',
);
