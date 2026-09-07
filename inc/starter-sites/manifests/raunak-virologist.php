<?php
/**
 * Starter Site: Raunak - Virologist.
 *
 * A researcher / lab-lead site for a working virologist and science
 * communicator — Home, Research, Publications, The Lab, Outbreak Response,
 * Talks & Media, Public Engagement, About, a Field Notes blog, and Contact
 * — plus a primary menu. Pairs with the "Lab Fluoro" Site Kit (magenta on
 * near-black). Mostly curation over the shared researcher page designs
 * (same markup family as `aad-astrophysicist`), plus one lab-specific page.
 * Placeholder copy and placeholder image cards throughout.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'raunak-virologist',
    'label'       => __('Raunak - Virologist', 'ajnanda'),
    'description' => __('A virologist / lab-lead site: Home, Research, Publications, The Lab, Outbreak Response, Talks & Media, Public Engagement, About, a Field Notes blog, and Contact — with a primary menu, a front page and a blog. Pairs with the Lab Fluoro Site Kit. Placeholder copy and images throughout; swap in your own.', 'ajnanda'),
    'site_kit'    => 'lab-fluoro',
    'pages'       => array(
        array('key' => 'home',        'title' => __('Home', 'ajnanda'),              'slug' => 'home',              'page_design' => 'ajnanda/page-home-scientist',    'menu_order' => 1),
        array('key' => 'research',     'title' => __('Research', 'ajnanda'),          'slug' => 'research',          'page_design' => 'ajnanda/page-research',          'menu_order' => 2),
        array('key' => 'publications', 'title' => __('Publications', 'ajnanda'),      'slug' => 'publications',      'page_design' => 'ajnanda/page-publications',      'menu_order' => 3),
        array('key' => 'lab',          'title' => __('The Lab', 'ajnanda'),           'slug' => 'lab',               'page_design' => 'ajnanda/page-team',              'menu_order' => 4),
        array('key' => 'outbreak',     'title' => __('Outbreak Response', 'ajnanda'), 'slug' => 'outbreak-response', 'page_design' => 'ajnanda/page-outbreak-response', 'menu_order' => 5),
        array('key' => 'talks',        'title' => __('Talks & Media', 'ajnanda'),     'slug' => 'talks-and-media',   'page_design' => 'ajnanda/page-talks-media',       'menu_order' => 6),
        array('key' => 'engagement',   'title' => __('Public Engagement', 'ajnanda'), 'slug' => 'public-engagement', 'page_design' => 'ajnanda/page-outreach',          'menu_order' => 7),
        array('key' => 'about',        'title' => __('About', 'ajnanda'),             'slug' => 'about',             'page_design' => 'ajnanda/page-about-story',       'menu_order' => 8),
        array('key' => 'notes',        'title' => __('Field Notes', 'ajnanda'),       'slug' => 'field-notes',       'page_design' => 'ajnanda/page-blog-landing',      'menu_order' => 9),
        array('key' => 'contact',      'title' => __('Contact', 'ajnanda'),           'slug' => 'contact',           'page_design' => 'ajnanda/page-contact',           'menu_order' => 10),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'research', 'publications', 'lab', 'outbreak', 'talks', 'engagement', 'about', 'notes', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'notes',
);
