<?php
/** Objective Search & AI readiness checks. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Readiness {
    const STATES = array('pass', 'warning', 'fail', 'not_applicable', 'externally_unverifiable');

    public static function report() {
        $checks = array();
        $profile = AJNanda_Search_AI_Site_Profile::get();
        $policy = AJNanda_Search_AI_Content_Policy::settings();
        $discovery = AJNanda_Search_AI_Discovery_Files::status(true);
        $types = AJNanda_Search_AI_Site_Profile::organization_types();

        self::add($checks, 'foundation', 'search_visibility', (int) get_option('blog_public') ? 'pass' : 'fail', __('Search-engine visibility', 'ajnanda'), (int) get_option('blog_public') ? __('WordPress permits public indexing.', 'ajnanda') : __('WordPress currently asks search engines not to index this site.', 'ajnanda'), 'settings', 3);
        self::add($checks, 'foundation', 'https', 'https' === wp_parse_url(home_url('/'), PHP_URL_SCHEME) ? 'pass' : 'warning', __('HTTPS site URL', 'ajnanda'), 'https' === wp_parse_url(home_url('/'), PHP_URL_SCHEME) ? __('The canonical site URL uses HTTPS.', 'ajnanda') : __('The canonical site URL does not use HTTPS.', 'ajnanda'), 'settings', 2);
        self::add($checks, 'foundation', 'canonical_url', wp_http_validate_url(home_url('/')) ? 'pass' : 'fail', __('Canonical site URL', 'ajnanda'), wp_http_validate_url(home_url('/')) ? home_url('/') : __('WordPress does not provide a valid public site URL.', 'ajnanda'), 'settings', 3);
        $permalink = (string) get_option('permalink_structure');
        self::add($checks, 'foundation', 'permalinks', $permalink ? 'pass' : 'warning', __('Permalink structure', 'ajnanda'), $permalink ? __('Readable permalinks are configured.', 'ajnanda') : __('Plain query-string permalinks make public URLs harder to understand.', 'ajnanda'), 'settings', 1);

        self::add($checks, 'entity', 'profile_name', trim($profile['name']) ? 'pass' : 'fail', __('Site or organization name', 'ajnanda'), trim($profile['name']) ? __('A canonical identity name is available.', 'ajnanda') : __('Add the primary name used to identify this site.', 'ajnanda'), 'site-profile', 3);
        self::add($checks, 'entity', 'profile_description', strlen(trim($profile['description'])) >= 30 ? 'pass' : 'warning', __('Site description', 'ajnanda'), strlen(trim($profile['description'])) >= 30 ? __('A useful machine-readable description is available.', 'ajnanda') : __('Add a concise description of the organization or site.', 'ajnanda'), 'site-profile', 2);
        self::add($checks, 'entity', 'organization_type', isset($types[$profile['organization_type']]) ? 'pass' : 'warning', __('Organization type', 'ajnanda'), isset($types[$profile['organization_type']]) ? __('The selected Schema.org organization type is supported.', 'ajnanda') : __('Review the organization type.', 'ajnanda'), 'site-profile', 1);
        self::add($checks, 'entity', 'logo', $profile['logo_url'] ? 'pass' : 'warning', __('Identity logo', 'ajnanda'), $profile['logo_url'] ? __('A public identity logo is configured.', 'ajnanda') : __('Add a logo if this organization publicly uses one.', 'ajnanda'), 'site-profile', 0);
        self::location_checks($checks, $profile);
        self::add($checks, 'entity', 'identity_links', $profile['identity_urls'] ? 'pass' : 'not_applicable', __('Identity links', 'ajnanda'), $profile['identity_urls'] ? __('Public identity links are available.', 'ajnanda') : __('Optional: add public profiles that help disambiguate the entity.', 'ajnanda'), 'site-profile', 0);

        foreach (array('meta_description' => __('Metadata', 'ajnanda'), 'canonical' => __('Canonical output', 'ajnanda')) as $capability => $label) {
            $owner = AJNanda_Search_AI_Capability_Ownership::get($capability);
            self::add($checks, 'search', $capability, 'pass', $label, self::ownership_message($owner), 'seo', 2);
        }
        self::add($checks, 'search', 'sitemap', self::endpoint_state($discovery['sitemap']['url'], 'sitemap'), __('XML sitemap', 'ajnanda'), self::endpoint_message($discovery['sitemap']['url'], 'sitemap', $discovery['sitemap']['ownership']), 'discovery-files', 2);

        self::add($checks, 'ai', 'ai_search', AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search') ? 'pass' : 'warning', __('AI Search discovery', 'ajnanda'), AJNanda_Search_AI_Settings::get('search_ai_allow_ai_search') ? __('Supported AI Search crawlers are permitted by site policy.', 'ajnanda') : __('AI Search retrieval is restricted by site policy.', 'ajnanda'), 'ai-discovery', 1);
        self::add($checks, 'ai', 'ai_training', 'not_applicable', __('AI model training policy', 'ajnanda'), AJNanda_Search_AI_Settings::get('search_ai_allow_ai_training') ? __('Allowed by site-owner choice; this does not affect readiness.', 'ajnanda') : __('Restricted by site-owner choice; this does not reduce readiness.', 'ajnanda'), 'ai-discovery', 0);
        self::add($checks, 'ai', 'user_retrieval', 'externally_unverifiable', __('User-initiated retrieval', 'ajnanda'), AJNanda_Search_AI_Settings::get('search_ai_allow_user_retrieval') ? __('Allowed where providers honor robots policy; provider behavior cannot be fully verified here.', 'ajnanda') : __('Restricted where technically controllable; some providers may not honor robots policy.', 'ajnanda'), 'ai-discovery', 0);
        self::add($checks, 'ai', 'registry', count(AJNanda_Search_AI_Crawler_Registry::all()) ? 'pass' : 'fail', __('Crawler policy registry', 'ajnanda'), sprintf(__('The maintainable registry contains %d provider tokens.', 'ajnanda'), count(AJNanda_Search_AI_Crawler_Registry::all())), 'ai-discovery', 2);

        self::content_checks($checks, $policy);
        self::discovery_checks($checks, $discovery);
        self::ownership_checks($checks);
        self::crawler_log_checks($checks);

        $report = array('checks' => apply_filters('ajnanda_search_ai_readiness_checks', $checks));
        $report['categories'] = self::categories($report['checks']);
        $report['score'] = self::score($report['checks']);
        $report['issues'] = self::issues($report['checks']);
        return apply_filters('ajnanda_search_ai_readiness_report', $report);
    }

    private static function add(&$checks, $category, $id, $state, $label, $message, $tab, $weight = 1) {
        if (! in_array($state, self::STATES, true)) { $state = 'warning'; }
        $checks[] = array('id' => $id, 'category' => $category, 'state' => $state, 'label' => $label, 'message' => $message, 'tab' => $tab, 'weight' => max(0, (int) $weight));
    }

    private static function location_checks(&$checks, $profile) {
        $mode = $profile['location_mode'];
        if ('physical' === $mode) {
            $address = array_filter($profile['address']);
            $complete = ! empty($profile['address']['street']) && ! empty($profile['address']['city']) && ! empty($profile['address']['state']) && ! empty($profile['address']['postal']) && ! empty($profile['address']['country']);
            self::add($checks, 'entity', 'location', $complete ? 'pass' : 'warning', __('Physical location', 'ajnanda'), $complete ? __('The public PostalAddress is complete.', 'ajnanda') : sprintf(__('Physical location is selected, but only %d of 5 structured address fields are complete.', 'ajnanda'), count($address)), 'site-profile', 2);
        } elseif (in_array($mode, array('service_area', 'regional_national'), true)) {
            self::add($checks, 'entity', 'location', $profile['service_areas'] ? 'pass' : 'warning', __('Service areas', 'ajnanda'), $profile['service_areas'] ? __('Public service areas match the selected location model.', 'ajnanda') : __('Add at least one service area for the selected location model.', 'ajnanda'), 'site-profile', 1);
        } else {
            self::add($checks, 'entity', 'location', 'not_applicable', __('Public location', 'ajnanda'), __('No public location is intentionally advertised.', 'ajnanda'), 'site-profile', 0);
        }
    }

    private static function content_checks(&$checks, $policy) {
        self::add($checks, 'content', 'policy_service', 'pass', __('Central Content Access policy', 'ajnanda'), __('The shared policy service is operational.', 'ajnanda'), 'content-access', 3);
        $count = count($policy['excluded_post_ids']) + count($policy['excluded_post_types']) + count($policy['excluded_paths']);
        self::add($checks, 'content', 'exclusions', 'pass', __('Explicit exclusions', 'ajnanda'), sprintf(_n('%d exclusion rule is configured and centrally applied.', '%d exclusion rules are configured and centrally applied.', $count, 'ajnanda'), $count), 'content-access', 0);
        $broad = array_intersect(array('post', 'page'), $policy['excluded_post_types']);
        self::add($checks, 'content', 'broad_exclusions', $broad ? 'warning' : 'pass', __('Broad content exclusions', 'ajnanda'), $broad ? sprintf(__('Entire public content types are excluded: %s. Confirm this is intentional.', 'ajnanda'), implode(', ', $broad)) : __('Posts and Pages are not broadly excluded.', 'ajnanda'), 'content-access', 2);
        $conflicts = array();
        foreach (AJNanda_Search_AI_Discovery_Files::important_page_ids() as $id) {
            if (empty(AJNanda_Search_AI_Content_Policy::evaluate($id)['advertise']['llms_txt'])) { $conflicts[] = get_the_title($id) ?: '#' . $id; }
        }
        self::add($checks, 'content', 'important_conflicts', $conflicts ? 'warning' : 'pass', __('Important Page conflicts', 'ajnanda'), $conflicts ? sprintf(__('Selected Important Pages are excluded from llms.txt: %s.', 'ajnanda'), implode(', ', $conflicts)) : __('Selected Important Pages do not conflict with llms.txt exclusions.', 'ajnanda'), $conflicts ? 'content-access' : 'discovery-files', 1);
    }

    private static function discovery_checks(&$checks, $discovery) {
        self::add($checks, 'outputs', 'robots_policy', 'pass', __('WordPress robots policy', 'ajnanda'), __('WordPress can generate the AJNanda robots policy.', 'ajnanda'), 'discovery-files', 2);
        $endpoint = $discovery['robots']['endpoint'];
        self::add($checks, 'outputs', 'robots_endpoint', self::diagnostic_state($endpoint), __('Public robots.txt endpoint', 'ajnanda'), __('WordPress policy is available. ', 'ajnanda') . self::diagnostic_message($endpoint), 'discovery-files', 0);
        $llms = $discovery['llms_txt'];
        if (! $llms['ownership']['ajnanda']) {
            self::add($checks, 'outputs', 'llms', 'pass', __('llms.txt ownership', 'ajnanda'), self::ownership_message($llms['ownership']), 'discovery-files', 1);
        } elseif (! $llms['enabled']) {
            self::add($checks, 'outputs', 'llms', 'warning', __('llms.txt', 'ajnanda'), __('AJNanda owns llms.txt, but the output is disabled.', 'ajnanda'), 'seo', 1);
        } else {
            self::add($checks, 'outputs', 'llms', self::diagnostic_state($llms['endpoint']), __('llms.txt', 'ajnanda'), self::diagnostic_message($llms['endpoint']), 'discovery-files', 1);
        }
        $schema = $discovery['schema'];
        self::add($checks, 'outputs', 'schema', ($schema['active'] || ! $schema['ownership']['ajnanda']) ? 'pass' : 'warning', __('Structured data', 'ajnanda'), ! $schema['ownership']['ajnanda'] ? self::ownership_message($schema['ownership']) : ($schema['active'] ? __('AJNanda structured data is active.', 'ajnanda') : __('AJNanda owns structured data, but schema output is disabled.', 'ajnanda')), 'seo', 2);
    }

    private static function ownership_checks(&$checks) {
        $plugins = AJNanda_Search_AI_Capability_Ownership::detected_plugins();
        self::add($checks, 'ownership', 'provider_conflicts', count($plugins) > 1 ? 'warning' : 'pass', __('SEO provider configuration', 'ajnanda'), count($plugins) > 1 ? sprintf(__('Multiple recognized SEO providers are active: %s. Review their output settings for duplication.', 'ajnanda'), implode(', ', wp_list_pluck($plugins, 'label'))) : ($plugins ? sprintf(__('Capabilities are cleanly delegated to %s where appropriate.', 'ajnanda'), implode(', ', wp_list_pluck($plugins, 'label'))) : __('No external SEO provider conflicts were detected.', 'ajnanda')), 'seo', 2);
        foreach (array('meta_description', 'canonical', 'social', 'robots_meta', 'schema', 'sitemap', 'llms_txt', 'ai_crawler_policy') as $capability) {
            $owner = AJNanda_Search_AI_Capability_Ownership::get($capability);
            self::add($checks, 'ownership', 'owner_' . $capability, 'pass', sprintf(__('%s ownership', 'ajnanda'), ucwords(str_replace('_', ' ', $capability))), self::ownership_message($owner), 'seo', 0);
        }
    }

    private static function crawler_log_checks(&$checks) {
        $enabled = (bool) AJNanda_Search_AI_Settings::get('search_ai_crawler_logging_enabled');
        self::add($checks, 'ownership', 'crawler_log_enabled', $enabled ? 'pass' : 'not_applicable', __('Crawler logging', 'ajnanda'), $enabled ? __('WordPress-local crawler observation is enabled.', 'ajnanda') : __('Crawler logging is intentionally disabled; this does not reduce readiness.', 'ajnanda'), 'settings', 0);
        $healthy = AJNanda_Search_AI_Crawler_Log_Store::table_exists();
        self::add($checks, 'ownership', 'crawler_log_table', $healthy ? 'pass' : ($enabled ? 'warning' : 'not_applicable'), __('Crawler log storage', 'ajnanda'), $healthy ? __('The crawler event table is available.', 'ajnanda') : __('The crawler event table is unavailable.', 'ajnanda'), 'crawler-log', $enabled ? 1 : 0);
        $retention = (int) AJNanda_Search_AI_Settings::get('search_ai_log_retention_days', 90);
        self::add($checks, 'ownership', 'crawler_log_retention', in_array($retention, array(7, 30, 90, 180, 365), true) ? 'pass' : 'warning', __('Crawler log retention', 'ajnanda'), sprintf(__('Crawler observations are retained for %d days.', 'ajnanda'), $retention), 'settings', $enabled ? 1 : 0);
    }

    private static function ownership_message($owner) {
        return ! empty($owner['ajnanda']) ? __('Owned by AJNanda.', 'ajnanda') : sprintf(__('Cleanly delegated to %s.', 'ajnanda'), implode(', ', $owner['external']));
    }

    private static function endpoint_state($url, $suffix) {
        return self::diagnostic_state(AJNanda_Search_AI_Discovery_Files::endpoint_status($url, $suffix));
    }

    private static function endpoint_message($url, $suffix, $ownership) {
        $endpoint = AJNanda_Search_AI_Discovery_Files::endpoint_status($url, $suffix);
        $owner = self::ownership_message($ownership);
        return $owner . ' ' . self::diagnostic_message($endpoint);
    }

    private static function diagnostic_state($endpoint) {
        if ('success' === ($endpoint['result'] ?? '')) { return 'pass'; }
        if (in_array($endpoint['result'] ?? '', array('tls_error', 'transport_error'), true)) { return 'externally_unverifiable'; }
        return 'warning';
    }

    private static function diagnostic_message($endpoint) {
        if ('success' === ($endpoint['result'] ?? '')) { return __('The public endpoint responded successfully.', 'ajnanda'); }
        if ('http_error' === ($endpoint['result'] ?? '')) { return sprintf(__('The public endpoint returned HTTP %1$d %2$s.', 'ajnanda'), (int) ($endpoint['code'] ?? 0), (string) ($endpoint['message'] ?? '')); }
        return sprintf(__('WordPress could not verify the public endpoint (%1$s): %2$s', 'ajnanda'), (string) ($endpoint['error_code'] ?? __('transport error', 'ajnanda')), (string) ($endpoint['message'] ?? __('No diagnostic details were returned.', 'ajnanda')));
    }

    private static function score($checks) {
        $earned = 0; $possible = 0;
        foreach ($checks as $check) {
            if (! $check['weight'] || in_array($check['state'], array('not_applicable', 'externally_unverifiable'), true)) { continue; }
            $possible += $check['weight'];
            if ('pass' === $check['state']) { $earned += $check['weight']; }
            elseif ('warning' === $check['state']) { $earned += $check['weight'] * 0.5; }
        }
        return array('value' => $possible ? (int) round(100 * $earned / $possible) : 100, 'earned' => $earned, 'possible' => $possible);
    }

    private static function categories($checks) {
        $labels = array('foundation' => __('Foundation', 'ajnanda'), 'search' => __('Traditional Search', 'ajnanda'), 'ai' => __('AI Discovery', 'ajnanda'), 'entity' => __('Site Profile', 'ajnanda'), 'content' => __('Content Access', 'ajnanda'), 'outputs' => __('Discovery Files', 'ajnanda'), 'ownership' => __('Integrations / Ownership', 'ajnanda'));
        $result = array();
        foreach ($labels as $key => $label) {
            $subset = array_values(array_filter($checks, static function ($check) use ($key) { return $key === $check['category']; }));
            $issues = array_filter($subset, static function ($check) { return in_array($check['state'], array('fail', 'warning'), true) && $check['weight']; });
            $state = $issues ? (array_filter($issues, static function ($check) { return 'fail' === $check['state']; }) ? 'fail' : 'warning') : 'pass';
            $result[$key] = array('label' => $label, 'state' => $state, 'checks' => $subset);
        }
        return $result;
    }

    private static function issues($checks) {
        $issues = array_values(array_filter($checks, static function ($check) { return $check['weight'] && in_array($check['state'], array('fail', 'warning'), true); }));
        usort($issues, static function ($a, $b) {
            $severity = array('fail' => 2, 'warning' => 1);
            return (($severity[$b['state']] * 10) + $b['weight']) <=> (($severity[$a['state']] * 10) + $a['weight']);
        });
        return $issues;
    }
}
