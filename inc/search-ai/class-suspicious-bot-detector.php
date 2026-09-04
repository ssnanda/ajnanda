<?php
/** Conservative, evidence-based analysis of stored crawler observations. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Suspicious_Bot_Detector {
    const MAX_SCAN = 5000;

    public static function patterns() {
        return apply_filters('ajnanda_search_ai_suspicious_path_patterns', array(
            'credentials' => array('/.env', '/.aws/', '/.ssh/', '/.config/', '/credentials', '/secrets', '/gcp-service', '/gc-service', '/application.env', '/proc/self/environ', '/etc/passwd', '/root/.', '/.openai/', '/.claude'),
            'configuration' => array('/wp-config', '/config.php', '/config.json', '/config.yml', '/config.yaml', '/config.toml', '/.git/', '/.svn/', '/.idea/', '/.vscode/', '/.gradle/'),
            'exploit_probe' => array('/phpmyadmin', '/vendor/phpunit', '/actuator/', '/graphql/console', '/eval-stdin.php', '/shell.php', '/adminer'),
        ));
    }

    public static function classify($event) {
        $path = strtolower(rawurldecode((string) ($event['request_path'] ?? '/')));
        $reasons = array();
        foreach (self::patterns() as $group => $needles) {
            foreach ($needles as $needle) {
                if (false !== strpos($path, strtolower($needle))) { $reasons[] = $group; break; }
            }
        }
        if ('failed' === ($event['verification_state'] ?? '')) { $reasons[] = 'identity_verification_failed'; }
        if (! $reasons) { return array('suspicious' => false, 'severity' => 'none', 'reasons' => array()); }
        $status = absint($event['http_status'] ?? 0);
        $sensitive = (bool) array_intersect($reasons, array('credentials', 'configuration', 'exploit_probe'));
        $severity = $sensitive && $status >= 200 && $status < 300 ? 'critical' : ($sensitive ? 'high' : 'medium');
        if ($sensitive && ! in_array($event['verification_state'] ?? '', array('verified'), true) && 'unknown' !== ($event['provider_key'] ?? 'unknown')) {
            $reasons[] = 'claimed_crawler_on_probe_path';
        }
        return array('suspicious' => true, 'severity' => $severity, 'reasons' => array_values(array_unique($reasons)));
    }

    public static function report($days = null) {
        $enabled = (bool) AJNanda_Search_AI_Settings::get('search_ai_suspicious_bot_detection_enabled', true);
        $days = $days ?: absint(AJNanda_Search_AI_Settings::get('search_ai_suspicious_bot_period_days', 7));
        if (! in_array($days, array(1, 7, 30, 90), true)) { $days = 7; }
        $empty = array('enabled' => $enabled, 'days' => $days, 'scanned' => 0, 'scan_limited' => false, 'total' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'successful_sensitive' => 0, 'claimed_crawlers' => 0, 'top_paths' => array(), 'recent' => array(), 'guidance' => array());
        if (! $enabled || ! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) { return $empty; }
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . AJNanda_Search_AI_Crawler_Log_Store::table() . ' WHERE observed_at >= %s ORDER BY observed_at DESC, id DESC LIMIT %d', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days), self::MAX_SCAN), ARRAY_A);
        $report = $empty; $report['scanned'] = count($rows); $report['scan_limited'] = self::MAX_SCAN === count($rows); $paths = array();
        foreach ($rows as $row) {
            $result = self::classify($row);
            if (! $result['suspicious']) { continue; }
            $row['suspicion'] = $result;
            $report['total']++; $report[$result['severity']]++;
            if ('critical' === $result['severity']) { $report['successful_sensitive']++; }
            if (in_array('claimed_crawler_on_probe_path', $result['reasons'], true)) { $report['claimed_crawlers']++; }
            $path = $row['request_path'];
            $paths[$path] = ($paths[$path] ?? 0) + 1;
            if (count($report['recent']) < 50) { $report['recent'][] = $row; }
        }
        arsort($paths); $report['top_paths'] = array_slice($paths, 0, 10, true);
        $report['guidance'] = self::guidance($report);
        return apply_filters('ajnanda_search_ai_suspicious_bot_report', $report);
    }

    private static function guidance($report) {
        $items = array();
        if (! $report['total']) {
            $items[] = array('state' => 'success', 'title' => __('No high-confidence suspicious bot activity detected', 'ajnanda'), 'text' => __('AJNanda found no stored crawler requests matching its conservative probe patterns for this period.', 'ajnanda'));
            return $items;
        }
        if ($report['successful_sensitive']) {
            $items[] = array('state' => 'fail', 'title' => __('Review successful responses immediately', 'ajnanda'), 'text' => sprintf(_n('%d sensitive-looking probe received a successful HTTP response. Confirm that no credential, configuration, or diagnostic content was exposed.', '%d sensitive-looking probes received successful HTTP responses. Confirm that no credential, configuration, or diagnostic content was exposed.', $report['successful_sensitive'], 'ajnanda'), $report['successful_sensitive']));
        } else {
            $items[] = array('state' => 'success', 'title' => __('Sensitive probes were rejected or redirected', 'ajnanda'), 'text' => __('No detected sensitive-path probe returned a successful 2xx response. Continue reviewing hosting or edge-security logs for broader visibility.', 'ajnanda'));
        }
        if ($report['claimed_crawlers']) {
            $items[] = array('state' => 'warning', 'title' => __('Crawler names may be spoofed', 'ajnanda'), 'text' => sprintf(_n('%d suspicious request claimed a recognized search or AI crawler identity without verified evidence. Do not interpret these as confirmed provider visits.', '%d suspicious requests claimed recognized search or AI crawler identities without verified evidence. Do not interpret these as confirmed provider visits.', $report['claimed_crawlers'], 'ajnanda'), $report['claimed_crawlers']));
        }
        $items[] = array('state' => 'warning', 'title' => __('Prefer blocking at the hosting or edge layer', 'ajnanda'), 'text' => __('If these probes persist, review Hostinger, Cloudflare, or another WAF/security log. WordPress-level blocking occurs after the request has already consumed server resources.', 'ajnanda'));
        return $items;
    }

    public static function reason_label($reason) {
        $labels = array('credentials' => __('Credential or secret-file probe', 'ajnanda'), 'configuration' => __('Configuration-file probe', 'ajnanda'), 'exploit_probe' => __('Known exploit or diagnostic-path probe', 'ajnanda'), 'identity_verification_failed' => __('Crawler identity verification failed', 'ajnanda'), 'claimed_crawler_on_probe_path' => __('Recognized crawler name used on a suspicious path', 'ajnanda'));
        return $labels[$reason] ?? ucfirst(str_replace('_', ' ', $reason));
    }
}
