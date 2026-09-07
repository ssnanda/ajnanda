<?php
/**
 * Starter Site: Nanda - Dentist.
 *
 * A full dental-practice site — every page a small practice needs, a primary
 * menu, a front page and a blog. Built entirely from AJNanda section patterns,
 * so it ships with placeholder copy and placeholder image cards (no real
 * photos); replace them after import.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'nanda-dentist',
    'label'       => __('Nanda - Dentist', 'ajnanda'),
    'description' => __('A complete dental-practice site: Home, About, Services, an individual treatment page, New Patients, Insurance & Financing, Meet the Team, Smile Gallery, Patient Reviews, FAQ, Blog, and Contact & Appointments — plus a primary menu. Placeholder copy and images throughout; swap in the practice\'s own.', 'ajnanda'),
    'site_kit'    => 'corporate-blue',
    'pages'       => array(
        array('key' => 'home',            'title' => __('Home', 'ajnanda'),                   'slug' => 'home',                  'page_design' => 'ajnanda/page-home-dental',          'menu_order' => 1),
        array('key' => 'about',           'title' => __('About Us', 'ajnanda'),               'slug' => 'about',                 'page_design' => 'ajnanda/page-about-dental',         'menu_order' => 2),
        array('key' => 'services',        'title' => __('Services', 'ajnanda'),               'slug' => 'services',              'page_design' => 'ajnanda/page-services-dental',      'menu_order' => 3),
        array('key' => 'service-implants', 'title' => __('Dental Implants', 'ajnanda'),        'slug' => 'dental-implants',       'page_design' => 'ajnanda/page-service-single',       'menu_order' => 4),
        array('key' => 'new-patients',    'title' => __('New Patients', 'ajnanda'),            'slug' => 'new-patients',          'page_design' => 'ajnanda/page-new-patients-dental',  'menu_order' => 5),
        array('key' => 'insurance',       'title' => __('Insurance & Financing', 'ajnanda'),   'slug' => 'insurance-and-financing', 'page_design' => 'ajnanda/page-dental-financing',   'menu_order' => 6),
        array('key' => 'team',            'title' => __('Meet the Team', 'ajnanda'),           'slug' => 'team',                  'page_design' => 'ajnanda/page-team',                 'menu_order' => 7),
        array('key' => 'gallery',         'title' => __('Smile Gallery', 'ajnanda'),           'slug' => 'smile-gallery',         'page_design' => 'ajnanda/page-gallery',              'menu_order' => 8),
        array('key' => 'reviews',         'title' => __('Patient Reviews', 'ajnanda'),         'slug' => 'reviews',               'page_design' => 'ajnanda/page-patient-reviews',      'menu_order' => 9),
        array('key' => 'faq',             'title' => __('FAQ', 'ajnanda'),                     'slug' => 'faq',                   'page_design' => 'ajnanda/page-faq',                  'menu_order' => 10),
        array('key' => 'blog',            'title' => __('Blog', 'ajnanda'),                    'slug' => 'blog',                  'page_design' => 'ajnanda/page-blog-landing',         'menu_order' => 11),
        array('key' => 'contact',         'title' => __('Contact & Appointments', 'ajnanda'),  'slug' => 'contact',              'page_design' => 'ajnanda/page-contact',              'menu_order' => 12),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        // Flat menu. The single treatment page (Dental Implants) is reached from
        // Services, so it is intentionally left off the primary nav.
        'pages' => array('home', 'about', 'services', 'new-patients', 'insurance', 'team', 'gallery', 'reviews', 'faq', 'blog', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
