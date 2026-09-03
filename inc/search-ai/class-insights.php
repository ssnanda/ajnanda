<?php
/** Evidence-based Search & AI insight providers. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Insights {
    public static function report() {
        $providers = apply_filters('ajnanda_search_ai_insight_providers', array('site_kit' => array(__CLASS__, 'site_kit')));
        $report = array('providers' => array(), 'opportunities' => array());
        foreach ($providers as $slug => $callback) {
            if (! is_callable($callback)) { continue; }
            $provider = call_user_func($callback);
            if (! is_array($provider)) { continue; }
            $report['providers'][$slug] = $provider;
            if (! empty($provider['opportunities'])) { $report['opportunities'] = array_merge($report['opportunities'], $provider['opportunities']); }
        }
        $readiness = AJNanda_Search_AI_Readiness::report();
        $report['configuration_issues'] = array_slice($readiness['issues'], 0, 3);
        return apply_filters('ajnanda_search_ai_insights_report', $report);
    }

    public static function site_kit() {
        if (! class_exists('\Google\Site_Kit\Plugin')) {
            return array('label' => 'Google Site Kit', 'state' => 'unavailable', 'message' => __('Google Site Kit is not active. Search and performance evidence is unavailable, but this does not reduce technical readiness.', 'ajnanda'), 'opportunities' => array());
        }
        $dates = array('startDate' => gmdate('Y-m-d', strtotime('-28 days')), 'endDate' => gmdate('Y-m-d', strtotime('-1 day')));
        $search = self::request('search-console', 'searchanalytics', array_merge($dates, array('dimensions' => array('page', 'query'), 'limit' => 100)));
        $page_visibility = self::request('search-console', 'searchanalytics', array_merge($dates, array('dimensions' => array('page'), 'limit' => 1000)));
        $pagespeed = self::request('pagespeed-insights', 'pagespeed', array('strategy' => 'mobile'));
        if (null === $search && null === $pagespeed) {
            return array('label' => 'Google Site Kit', 'state' => 'unavailable', 'message' => __('Site Kit is active, but connected Search Console/PageSpeed data was not available.', 'ajnanda'), 'opportunities' => array());
        }
        $opportunities = array();
        if (is_array($search)) {
            foreach ($search as $row) {
                $keys = is_array($row['keys'] ?? null) ? $row['keys'] : array();
                $page = $keys[0] ?? '';
                $query = $keys[1] ?? '';
                $clicks = (float) ($row['clicks'] ?? 0);
                $impressions = (float) ($row['impressions'] ?? 0);
                $position = (float) ($row['position'] ?? 0);
                $ctr = $impressions ? $clicks / $impressions : 0;
                if ($page && $query && $impressions >= 20 && $ctr < 0.02) {
                    $opportunities[] = self::opportunity('low_ctr', __('Low click-through opportunity', 'ajnanda'), sprintf(__('“%1$s” received %2$d impressions with %3$s CTR for this page.', 'ajnanda'), $query, (int) $impressions, number_format_i18n($ctr * 100, 1) . '%'), $page, 'warning');
                } elseif ($page && $query && $position >= 6 && $position <= 20 && $impressions >= 10) {
                    $opportunities[] = self::opportunity('ranking', __('Ranking opportunity', 'ajnanda'), sprintf(__('“%1$s” averaged position %2$s for this page.', 'ajnanda'), $query, number_format_i18n($position, 1)), $page, 'info');
                }
                if (count($opportunities) >= 8) { break; }
            }
        }
        if (is_array($page_visibility)) {
            $observed = array();
            foreach ($page_visibility as $row) {
                if (! empty($row['keys'][0])) { $observed[] = untrailingslashit($row['keys'][0]); }
            }
            foreach (AJNanda_Search_AI_Discovery_Files::important_page_ids() as $id) {
                $url = get_permalink($id);
                if ($url && ! in_array(untrailingslashit($url), $observed, true) && ! empty(AJNanda_Search_AI_Content_Policy::evaluate($id)['advertise']['traditional_search'])) {
                    $opportunities[] = self::opportunity('important_visibility', __('Important Page visibility', 'ajnanda'), __('No Search Console impressions were observed for this selected Important Page in the returned 28-day dataset.', 'ajnanda'), $url, 'info');
                }
            }
        }
        if (is_array($pagespeed) && isset($pagespeed['lighthouseResult']['categories']['performance']['score'])) {
            $score = (int) round((float) $pagespeed['lighthouseResult']['categories']['performance']['score'] * 100);
            if ($score < 90) {
                $opportunities[] = self::opportunity('performance', __('Mobile performance', 'ajnanda'), sprintf(__('PageSpeed reports a mobile performance score of %d/100.', 'ajnanda'), $score), home_url('/'), $score < 50 ? 'warning' : 'info');
            }
        }
        return array('label' => 'Google Site Kit', 'state' => 'available', 'message' => __('Evidence from the last 28 complete days of connected Search Console data and the available mobile PageSpeed result.', 'ajnanda'), 'opportunities' => $opportunities);
    }

    private static function opportunity($type, $label, $message, $url, $state) {
        return compact('type', 'label', 'message', 'url', 'state');
    }

    private static function request($module, $datapoint, $params) {
        $key = 'ajnanda_search_ai_insight_' . md5($module . '|' . $datapoint . '|' . wp_json_encode($params));
        $cached = get_transient($key);
        if (is_array($cached) && array_key_exists('data', $cached)) { return $cached['data']; }
        $data = function_exists('ajnanda_seo_site_kit_request') ? ajnanda_seo_site_kit_request($module, $datapoint, $params) : null;
        set_transient($key, array('data' => $data), 15 * MINUTE_IN_SECONDS);
        return $data;
    }
}
