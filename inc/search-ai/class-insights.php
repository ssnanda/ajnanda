<?php
/** Evidence-based Search & AI insight providers. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Insights {
    const SNAPSHOT_OPTION = 'ajnanda_search_ai_insights_snapshot';
    const FRESH_SECONDS = 6 * HOUR_IN_SECONDS;

    public static function report($readiness = null) {
        $providers = apply_filters('ajnanda_search_ai_insight_providers', array('site_kit' => array(__CLASS__, 'site_kit')));
        $report = array('providers' => array(), 'opportunities' => array());
        foreach ($providers as $slug => $callback) {
            if (! is_callable($callback)) { continue; }
            $provider = call_user_func($callback);
            if (! is_array($provider)) { continue; }
            $report['providers'][$slug] = $provider;
            if (! empty($provider['opportunities'])) { $report['opportunities'] = array_merge($report['opportunities'], $provider['opportunities']); }
        }
        // Insights must not trigger the public endpoint probes used by Overview.
        $readiness = is_array($readiness) ? $readiness : AJNanda_Search_AI_Readiness::report(false);
        $report['configuration_issues'] = array_slice($readiness['issues'], 0, 3);
        return apply_filters('ajnanda_search_ai_insights_report', $report);
    }

    public static function site_kit() {
        if (! class_exists('\\Google\\Site_Kit\\Plugin')) {
            return array('label' => 'Google Site Kit', 'state' => 'unavailable', 'message' => __('Google Site Kit is not active. Search and performance evidence is unavailable, but this does not reduce technical readiness.', 'ajnanda'), 'opportunities' => array());
        }
        $snapshot = get_option(self::SNAPSHOT_OPTION, array());
        $age = ! empty($snapshot['refreshed_at']) ? time() - (int) $snapshot['refreshed_at'] : PHP_INT_MAX;
        if (! empty($snapshot['provider']) && $age < self::FRESH_SECONDS) { return self::cache_state($snapshot['provider'], 'fresh', false); }
        if (! empty($snapshot['provider'])) { return self::cache_state($snapshot['provider'], 'stale', false); }
        return array('label' => 'Google Site Kit', 'state' => 'pending', 'message' => __('No evidence snapshot exists yet. Use Refresh evidence to fetch it; normal page loads never wait for Google.', 'ajnanda'), 'cache_state' => 'empty', 'refresh_pending' => false, 'opportunities' => array());
    }

    public static function refresh() {
        if (! class_exists('\\Google\\Site_Kit\\Plugin')) { return false; }
        $provider = self::build_provider();
        if ('available' !== ($provider['state'] ?? '')) { return false; }
        update_option(self::SNAPSHOT_OPTION, array('refreshed_at' => time(), 'provider' => $provider), false);
        return true;
    }

    private static function cache_state($provider, $state, $pending) { $provider['cache_state'] = $state; $provider['refresh_pending'] = $pending; return $provider; }

    private static function build_provider() {
        $current = array('startDate' => gmdate('Y-m-d', strtotime('-28 days')), 'endDate' => gmdate('Y-m-d', strtotime('-1 day')));
        $previous = array('startDate' => gmdate('Y-m-d', strtotime('-56 days')), 'endDate' => gmdate('Y-m-d', strtotime('-29 days')));
        $dimensions = array('dimensions' => array('page', 'query'), 'limit' => 1000);
        $search = self::request('search-console', 'searchanalytics', array_merge($current, $dimensions));
        $prior = self::request('search-console', 'searchanalytics', array_merge($previous, $dimensions));
        $pagespeed = self::request('pagespeed-insights', 'pagespeed', array('strategy' => 'mobile'));
        if (null === $search && null === $pagespeed) { return array('label' => 'Google Site Kit', 'state' => 'unavailable', 'message' => __('Site Kit is active, but connected Search Console/PageSpeed data was not available.', 'ajnanda'), 'opportunities' => array()); }

        $prior_index = self::row_index($prior); $opportunities = array(); $observed = array();
        foreach (is_array($search) ? $search : array() as $row) {
            $keys = is_array($row['keys'] ?? null) ? $row['keys'] : array();
            $page = (string) ($keys[0] ?? ''); $query = trim((string) ($keys[1] ?? ''));
            if (! $page || ! $query) { continue; }
            $observed[] = untrailingslashit($page); $metrics = self::metrics($row);
            $previous_metrics = $prior_index[$page . '|' . strtolower($query)] ?? null;
            if (self::looks_like_address($query) && $metrics['impressions'] >= 20) {
                $opportunities[] = self::opportunity('intent_review', __('Possible intent mismatch', 'ajnanda'), $page, $query, $metrics, $previous_metrics, __('This address-specific query may concern another entity or a lookup intent the page does not serve.', 'ajnanda'), __('Confirm relevance before changing the page. If irrelevant, do not optimize for it.', 'ajnanda'), 'medium', __('Query wording alone cannot prove user intent.', 'ajnanda'));
                continue;
            }
            $expected = self::expected_ctr($metrics['position']);
            if ($metrics['impressions'] >= 20 && $metrics['position'] <= 10 && $metrics['ctr'] < $expected) {
                $opportunities[] = self::opportunity('low_ctr', __('Position-adjusted click opportunity', 'ajnanda'), $page, $query, $metrics, $previous_metrics, sprintf(__('CTR is below the %s directional benchmark for this average-position range.', 'ajnanda'), number_format_i18n($expected * 100, 1) . '%'), __('Review whether the title and description accurately answer this query’s intent.', 'ajnanda'), 'medium', __('Device, geography, and search-result features can change expected CTR.', 'ajnanda'));
            } elseif ($metrics['impressions'] >= 10 && $metrics['position'] >= 6 && $metrics['position'] <= 20) {
                $opportunities[] = self::opportunity('ranking', __('Ranking opportunity', 'ajnanda'), $page, $query, $metrics, $previous_metrics, __('The page appears within striking distance of stronger first-page visibility.', 'ajnanda'), __('Check content completeness, internal links, and whether the page directly satisfies the query.', 'ajnanda'), 'medium', __('Search Console reports an average position, not one fixed ranking.', 'ajnanda'));
            }
        }
        foreach (AJNanda_Search_AI_Important_Pages::valid_ids() as $id) {
            $url = get_permalink($id);
            if ($url && ! in_array(untrailingslashit($url), array_unique($observed), true) && ! empty(AJNanda_Search_AI_Content_Policy::evaluate($id)['advertise']['traditional_search'])) {
                $opportunities[] = self::basic('important_visibility', __('Important Page visibility', 'ajnanda'), $url, __('No impressions were observed for this curated Important Page in the returned 28-day dataset.', 'ajnanda'), __('Confirm the page is indexable, internally linked, and represented in the sitemap.', 'ajnanda'), 'low', __('The connected API may return a limited or privacy-filtered dataset.', 'ajnanda'));
            }
        }
        if (is_array($pagespeed) && isset($pagespeed['lighthouseResult']['categories']['performance']['score'])) {
            $score = (int) round((float) $pagespeed['lighthouseResult']['categories']['performance']['score'] * 100);
            if ($score < 90) { $opportunities[] = self::basic('performance', __('Mobile performance', 'ajnanda'), home_url('/'), sprintf(__('The available mobile PageSpeed performance score is %d/100.', 'ajnanda'), $score), __('Review the PageSpeed diagnostics before choosing a performance fix.', 'ajnanda'), 'medium', __('Lab performance is directional and can vary between runs.', 'ajnanda'), array('mobile_performance_score' => $score)); }
        }
        usort($opportunities, static function($a, $b) { return ($b['impact_score'] ?? 0) <=> ($a['impact_score'] ?? 0); });
        return array('label' => 'Google Site Kit', 'state' => 'available', 'message' => __('Cached evidence from the last 28 complete days of connected Search Console data and the available mobile PageSpeed result.', 'ajnanda'), 'refreshed_at_utc' => gmdate('c'), 'periods' => array('current' => $current, 'previous' => $previous), 'trend' => self::trend($search, $prior), 'opportunities' => self::diverse_limit($opportunities, 8));
    }

    private static function opportunity($type, $label, $url, $query, $metrics, $previous, $interpretation, $action, $confidence, $caveats) {
        $evidence = sprintf(__('“%1$s”: %2$d impressions, %3$d clicks, %4$s CTR, average position %5$s.', 'ajnanda'), $query, $metrics['impressions'], $metrics['clicks'], number_format_i18n($metrics['ctr'] * 100, 1) . '%', number_format_i18n($metrics['position'], 1));
        $item = self::basic($type, $label, $url, $evidence, $action, $confidence, $caveats, $metrics);
        $item['query'] = $query; $item['interpretation'] = $interpretation; $item['previous_period'] = $previous;
        $item['impact_score'] = round($metrics['impressions'] * max(.005, self::expected_ctr($metrics['position']) - $metrics['ctr']), 2);
        return $item;
    }
    private static function basic($type, $label, $url, $observation, $action, $confidence, $caveats, $metrics = array()) { return array('type'=>$type,'label'=>$label,'message'=>$observation,'observation'=>$observation,'suggested_action'=>$action,'confidence'=>$confidence,'caveats'=>$caveats,'metrics'=>$metrics,'url'=>$url,'state'=>'info','impact_score'=>(float)($metrics['impressions']??0)); }
    private static function metrics($row) { $i=(int)round((float)($row['impressions']??0)); $c=(int)round((float)($row['clicks']??0)); return array('clicks'=>$c,'impressions'=>$i,'ctr'=>$i?$c/$i:0,'position'=>round((float)($row['position']??0),2)); }
    private static function row_index($rows) { $r=array(); foreach(is_array($rows)?$rows:array() as $row){$k=$row['keys']??array();if(!empty($k[0])&&!empty($k[1])){$r[$k[0].'|'.strtolower(trim($k[1]))]=self::metrics($row);}}return $r; }
    private static function expected_ctr($position) { if($position<=3)return .08;if($position<=5)return .04;if($position<=10)return .02;if($position<=20)return .01;return .005; }
    private static function looks_like_address($query) { return (bool)preg_match('/\b\d{2,6}\s+[\p{L}]/u',$query); }
    private static function trend($current,$previous){$a=self::totals($current);$b=self::totals($previous);return array('current'=>$a,'previous'=>$b,'change'=>array('clicks'=>$a['clicks']-$b['clicks'],'impressions'=>$a['impressions']-$b['impressions'],'ctr_percentage_points'=>round(($a['ctr']-$b['ctr'])*100,2),'position'=>round($a['position']-$b['position'],2)));}
    private static function totals($rows){$c=0;$i=0;$w=0;foreach(is_array($rows)?$rows:array() as $row){$m=self::metrics($row);$c+=$m['clicks'];$i+=$m['impressions'];$w+=$m['position']*$m['impressions'];}return array('clicks'=>$c,'impressions'=>$i,'ctr'=>$i?$c/$i:0,'position'=>$i?round($w/$i,2):0);}
    private static function diverse_limit($items,$limit){$r=array();$seen=array();foreach($items as $item){$key=($item['url']??'').'|'.($item['type']??'');if(($seen[$key]??0)>=2)continue;$seen[$key]=($seen[$key]??0)+1;$r[]=$item;if(count($r)>=$limit)break;}return $r;}
    private static function request($module,$datapoint,$params){return function_exists('ajnanda_seo_site_kit_request')?ajnanda_seo_site_kit_request($module,$datapoint,$params):null;}
}
