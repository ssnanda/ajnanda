<?php
/**
 * Starter Site: Aad - Astrophysicist.
 *
 * A personal / academic site for a working researcher and science
 * communicator — Home, Research, Publications, Talks & Media, Outreach, a
 * Bio, a Notes blog, and Contact — plus a primary menu. Pairs with the
 * "Deep Space" Site Kit (violet on dark, elegant serif). Built from AJNanda
 * section patterns: placeholder copy and placeholder image cards throughout.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'aad-astrophysicist',
    'label'       => __('Aad - Astrophysicist', 'ajnanda'),
    'description' => __('A researcher / science-communicator site: Home, Research, Publications, Talks & Media, Outreach & Education, Bio, a Notes blog, and Contact — with a primary menu, a front page and a blog. Pairs with the Deep Space Site Kit. Placeholder copy and images throughout; swap in your own.', 'ajnanda'),
    'site_kit'    => 'deep-space',
    'pages'       => array(
        array('key' => 'home',         'title' => __('Home', 'ajnanda'),            'slug' => 'home',              'page_design' => 'ajnanda/page-home-scientist', 'menu_order' => 1),
        array('key' => 'research',      'title' => __('Research', 'ajnanda'),        'slug' => 'research',          'page_design' => 'ajnanda/page-research',       'menu_order' => 2),
        array('key' => 'publications',  'title' => __('Publications', 'ajnanda'),    'slug' => 'publications',      'page_design' => 'ajnanda/page-publications',   'menu_order' => 3),
        array('key' => 'talks',         'title' => __('Talks & Media', 'ajnanda'),   'slug' => 'talks-and-media',   'page_design' => 'ajnanda/page-talks-media',    'menu_order' => 4),
        array('key' => 'outreach',      'title' => __('Outreach & Education', 'ajnanda'), 'slug' => 'outreach',      'page_design' => 'ajnanda/page-outreach',       'menu_order' => 5),
        array('key' => 'bio',           'title' => __('Bio', 'ajnanda'),             'slug' => 'bio',               'page_design' => 'ajnanda/page-about-story',    'menu_order' => 6),
        array('key' => 'notes',         'title' => __('Notes', 'ajnanda'),           'slug' => 'notes',             'page_design' => 'ajnanda/page-blog-landing',   'menu_order' => 7),
        array('key' => 'contact',       'title' => __('Contact', 'ajnanda'),         'slug' => 'contact',           'page_design' => 'ajnanda/page-contact',        'menu_order' => 8),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'research', 'publications', 'talks', 'outreach', 'bio', 'notes', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'notes',
);
