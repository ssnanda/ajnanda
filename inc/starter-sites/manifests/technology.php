<?php
/**
 * Starter Site: Technology / SaaS.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'technology',
    'label'       => __('Technology / SaaS', 'ajnanda'),
    'description' => __('A product-led site for a technology or software company: Home, Solutions, an individual Solution, About, Resources, Contact, and Blog.', 'ajnanda'),
    'pages'       => array(
        array('key' => 'home',      'title' => __('Home', 'ajnanda'),              'slug' => 'home',      'page_design' => 'ajnanda/page-home-technology',    'menu_order' => 1),
        array('key' => 'solutions', 'title' => __('Solutions', 'ajnanda'),         'slug' => 'solutions', 'page_design' => 'ajnanda/page-services-technology', 'menu_order' => 2),
        array('key' => 'solution',  'title' => __('Solution', 'ajnanda'),          'slug' => 'solution',  'page_design' => 'ajnanda/page-solution-detail',     'menu_order' => 3),
        array('key' => 'about',     'title' => __('About', 'ajnanda'),             'slug' => 'about',     'page_design' => 'ajnanda/page-about-story',         'menu_order' => 4),
        array('key' => 'resources', 'title' => __('Resources', 'ajnanda'),         'slug' => 'resources', 'page_design' => 'ajnanda/page-resource-center',     'menu_order' => 5),
        array('key' => 'blog',      'title' => __('Blog', 'ajnanda'),              'slug' => 'blog',      'page_design' => 'ajnanda/page-blog-landing',        'menu_order' => 6),
        array('key' => 'contact',   'title' => __('Contact', 'ajnanda'),           'slug' => 'contact',   'page_design' => 'ajnanda/page-contact',             'menu_order' => 7),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'solutions', 'about', 'resources', 'blog', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
