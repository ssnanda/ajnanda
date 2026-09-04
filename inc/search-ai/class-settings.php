<?php
/**
 * Search & AI settings and legacy migration.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJNanda_Search_AI_Settings {

    const MIGRATION_VERSION = 3;
    const VERSION_MOD = 'search_ai_migration_version';

    /**
     * New setting defaults. Values remain theme-native theme mods.
     */
    public static function defaults() {
        return array(
            'search_ai_enabled'                  => true,
            'search_ai_allow_traditional_search'=> true,
            'search_ai_allow_ai_search'          => true,
            'search_ai_allow_ai_training'        => true,
            'search_ai_allow_user_retrieval'     => true,
            'search_ai_crawler_logging_enabled'  => false,
            'search_ai_log_retention_days'       => 90,
            'search_ai_crawler_ip_mode'          => 'anonymized',
            'search_ai_suspicious_bot_detection_enabled' => true,
            'search_ai_suspicious_bot_period_days' => 7,
        );
    }

    public static function get($key, $default = null) {
        $defaults = self::defaults();
        if (null === $default && array_key_exists($key, $defaults)) {
            $default = $defaults[$key];
        }
        return get_theme_mod($key, $default);
    }

    public static function set($key, $value) {
        set_theme_mod($key, $value);
    }

    /**
     * Copy legacy intent into the expanded controls once. Legacy values are
     * retained and remain authoritative for the Phase 1 frontend.
     */
    public static function maybe_migrate() {
        $installed_version = (int) get_theme_mod(self::VERSION_MOD, 0);
        if ($installed_version >= self::MIGRATION_VERSION) {
            return;
        }

        $legacy_ai = (bool) get_theme_mod('seo_allow_ai_crawlers', true);
        $mappings = array(
            'search_ai_allow_ai_search'      => $legacy_ai,
            'search_ai_allow_ai_training'    => $legacy_ai,
            'search_ai_allow_user_retrieval' => $legacy_ai,
            'search_ai_profile_phone'        => get_theme_mod('seo_business_phone', ''),
            'search_ai_profile_address_street' => get_theme_mod('seo_business_address', ''),
        );

        foreach ($mappings as $key => $value) {
            if ('__ajnanda_unset__' === get_theme_mod($key, '__ajnanda_unset__')) {
                set_theme_mod($key, $value);
            }
        }

        if ($installed_version < 3) {
            self::migrate_discovery_content();
        }

        set_theme_mod(self::VERSION_MOD, self::MIGRATION_VERSION);
    }

    /**
     * Repair known legacy values without broad SQL replacement. WordPress APIs
     * preserve post data and each change is restricted to an exact old value.
     */
    private static function migrate_discovery_content() {
        foreach (array('seo_default_social_image') as $key) {
            $value = get_theme_mod($key, '');
            if ($value) { set_theme_mod($key, esc_url_raw(ajnanda_seo_normalize_site_url($value))); }
        }
        set_theme_mod('search_ai_profile_website', home_url('/'));
        if ('University Place Office Suites LLC' === get_theme_mod('search_ai_profile_alternate_name', '')) {
            set_theme_mod('search_ai_profile_alternate_name', '');
        }

        $utility_ids = get_posts(array(
            'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1,
            'post_name__in' => array('login', 'password-reset', 'edit-profile', 'member-home', 'account', 'my-account', 'client-portal', 'products-sandbox'),
            'fields' => 'ids', 'no_found_rows' => true,
        ));
        $excluded = array_values(array_unique(array_merge(
            array_map('absint', (array) get_theme_mod('search_ai_excluded_post_ids', array())),
            array_map('absint', $utility_ids)
        )));
        set_theme_mod('search_ai_excluded_post_ids', $excluded);

        $replacements = array(
            'Located in Charlotte &amp; Service All of North Carolina' => 'Located in Charlotte &amp; Serving All of North Carolina.',
            'Located in Charlotte & Service All of North Carolina' => 'Located in Charlotte & Serving All of North Carolina.',
            'Ensure you never miss important legal deadlines or documents' => 'Help reduce the risk of missing important legal documents and deadlines.',
            'ensure they never miss important legal documents' => 'help reduce the risk of missing important legal documents',
            'Public business registration address' => 'Registered-agent address on North Carolina Secretary of State filings only',
            '<td>Google Business Profile</td><td>No</td><td>Yes</td>' => '<td>Google Business Profile</td><td>No</td><td>Only when the customer independently satisfies Google’s eligibility requirements</td>',
        );
        $posts = get_posts(array('post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 'suppress_filters' => false));
        foreach ($posts as $post) {
            $content = str_replace(array_keys($replacements), array_values($replacements), $post->post_content);
            $content = preg_replace(
                '#(<td>\s*Google Business Profile\s*</td>\s*<td>\s*No\s*</td>\s*<td>)\s*Yes\s*(</td>)#i',
                '$1Only when the customer independently satisfies Google’s eligibility requirements$2',
                $content
            );
            $excerpt = str_replace(array_keys($replacements), array_values($replacements), $post->post_excerpt);
            $update = array('ID' => $post->ID);
            if ($content !== $post->post_content) { $update['post_content'] = $content; }
            if ($excerpt !== $post->post_excerpt) { $update['post_excerpt'] = $excerpt; }
            if ('7-mistakes-new-business-owners-make-in-north-carolina' === $post->post_name && '7 Mistakes' === $post->post_title) {
                $update['post_title'] = '7 Mistakes New Business Owners Make in North Carolina';
            }
            if (count($update) > 1) { wp_update_post(wp_slash($update)); }

            $social_image = get_post_meta($post->ID, '_ajnanda_seo_social_image', true);
            if ($social_image) { update_post_meta($post->ID, '_ajnanda_seo_social_image', esc_url_raw(ajnanda_seo_normalize_site_url($social_image))); }
        }

        // This option belongs to the catalog integration but contains display
        // copy only. Preserve every product/price relationship and change only
        // the exact obsolete note when present.
        $dependencies = get_option('ajcore_public_product_dependencies', array());
        if (is_array($dependencies)) {
            $old_note = "One time Charge\nDocumentation Required\n1. ID\n2. Address Proof ID\n3. CMRA Form - Notarized or Signed in Person";
            $new_note = 'One-time setup fee. Required documentation: government-issued ID, proof of home address, and notarized USPS Form 1583 or in-person verification.';
            $changed = false;
            foreach ($dependencies as &$dependency) {
                $stored_note = is_array($dependency) && isset($dependency['dependency_note']) ? str_replace("\r\n", "\n", trim((string) $dependency['dependency_note'])) : '';
                if ($old_note === $stored_note) {
                    $dependency['dependency_note'] = $new_note;
                    $changed = true;
                }
            }
            unset($dependency);
            if ($changed) { update_option('ajcore_public_product_dependencies', $dependencies, false); }
        }

        // Set crawl-ready descriptions only on the identified NC LLC Agents
        // installation. Other AJNanda sites continue to use their own content.
        if ('NC LLC Agents Inc' === trim((string) get_bloginfo('name'))) {
            $descriptions = array(
                'about' => 'Learn about NC LLC Agents Inc, a Charlotte-based company providing registered agent, LLC formation, and virtual office services statewide.',
                'service' => 'Compare North Carolina registered agent, LLC formation, and virtual office services with clear pricing and practical address-use guidance.',
                'email-us' => 'Email NC LLC Agents Inc with questions about registered agent, LLC formation, virtual office, or mail-handling services in North Carolina.',
                'guide-me' => 'Answer a few questions to identify the North Carolina registered agent, LLC formation, or virtual office service that fits your needs.',
            );
            $front_id = (int) get_option('page_on_front');
            if ($front_id) { update_post_meta($front_id, '_ajnanda_seo_description', 'NC LLC Agents Inc provides North Carolina registered agent, LLC formation, and virtual office services with clear pricing and responsive support.'); }
            $posts_id = (int) get_option('page_for_posts');
            if ($posts_id) { update_post_meta($posts_id, '_ajnanda_seo_description', 'Explore practical North Carolina business guides covering LLC formation, registered agents, compliance, legal mail, taxes, and reporting.'); }
            foreach ($descriptions as $slug => $description) {
                $page = get_page_by_path($slug, OBJECT, 'page');
                if ($page) { update_post_meta($page->ID, '_ajnanda_seo_description', $description); }
            }
        }
    }
}
