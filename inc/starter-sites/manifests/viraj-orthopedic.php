<?php
/**
 * Starter Site: Viraj - Orthopedic.
 *
 * A full orthopedic-practice site — Home, About, Conditions & Treatments, an
 * individual procedure page, Sports Medicine, For Patients, Insurance &
 * Billing, Care Team, Patient Stories, FAQ, Blog, and Contact &
 * Appointments — plus a primary menu. Pairs with the "Kinetic Clinic" Site
 * Kit (fresh green, bold display). Built from AJNanda section patterns:
 * placeholder copy and placeholder image cards throughout.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'slug'        => 'viraj-orthopedic',
    'label'       => __('Viraj - Orthopedic', 'ajnanda'),
    'description' => __('A complete orthopedic-practice site: Home, About, Conditions & Treatments, a procedure page, Sports Medicine, For Patients, Insurance & Billing, Care Team, Patient Stories, FAQ, Blog, and Contact & Appointments — with a primary menu. Pairs with the Kinetic Clinic Site Kit. Placeholder copy and images throughout; swap in the practice\'s own.', 'ajnanda'),
    'site_kit'    => 'kinetic-clinic',
    'pages'       => array(
        array('key' => 'home',           'title' => __('Home', 'ajnanda'),                  'slug' => 'home',                    'page_design' => 'ajnanda/page-home-orthopedic',        'menu_order' => 1),
        array('key' => 'about',          'title' => __('About Dr. Viraj', 'ajnanda'),        'slug' => 'about',                   'page_design' => 'ajnanda/page-about-professional',     'menu_order' => 2),
        array('key' => 'conditions',     'title' => __('Conditions & Treatments', 'ajnanda'), 'slug' => 'conditions-and-treatments', 'page_design' => 'ajnanda/page-conditions-treatments', 'menu_order' => 3),
        array('key' => 'procedure-knee', 'title' => __('Knee Replacement', 'ajnanda'),       'slug' => 'knee-replacement',        'page_design' => 'ajnanda/page-service-single',         'menu_order' => 4),
        array('key' => 'sports-medicine', 'title' => __('Sports Medicine', 'ajnanda'),       'slug' => 'sports-medicine',         'page_design' => 'ajnanda/page-sports-medicine',        'menu_order' => 5),
        array('key' => 'for-patients',   'title' => __('For Patients', 'ajnanda'),           'slug' => 'for-patients',            'page_design' => 'ajnanda/page-for-patients',           'menu_order' => 6),
        array('key' => 'insurance',      'title' => __('Insurance & Billing', 'ajnanda'),    'slug' => 'insurance-and-billing',   'page_design' => 'ajnanda/page-insurance-billing',      'menu_order' => 7),
        array('key' => 'team',           'title' => __('Our Care Team', 'ajnanda'),          'slug' => 'care-team',               'page_design' => 'ajnanda/page-team',                   'menu_order' => 8),
        array('key' => 'reviews',        'title' => __('Patient Stories', 'ajnanda'),        'slug' => 'patient-stories',         'page_design' => 'ajnanda/page-patient-reviews',        'menu_order' => 9),
        array('key' => 'faq',            'title' => __('FAQ', 'ajnanda'),                    'slug' => 'faq',                     'page_design' => 'ajnanda/page-faq',                    'menu_order' => 10),
        array('key' => 'blog',           'title' => __('Blog', 'ajnanda'),                   'slug' => 'blog',                    'page_design' => 'ajnanda/page-blog-landing',           'menu_order' => 11),
        array('key' => 'contact',        'title' => __('Contact & Appointments', 'ajnanda'), 'slug' => 'contact',                 'page_design' => 'ajnanda/page-contact',                'menu_order' => 12),
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        // Flat menu. The single procedure page (Knee Replacement) is reached
        // from Conditions & Treatments, so it is left off the primary nav.
        'pages' => array('home', 'about', 'conditions', 'sports-medicine', 'for-patients', 'insurance', 'team', 'reviews', 'faq', 'blog', 'contact'),
    ),
    'home_page_key'  => 'home',
    'posts_page_key' => 'blog',
);
