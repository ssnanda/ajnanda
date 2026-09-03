<?php
/** Provider-aware crawler identity verification. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Crawler_Verifier {
    public static function cached($crawler_key, $ip) {
        $cached = get_transient(self::cache_key($crawler_key, $ip));
        return is_array($cached) ? $cached : null;
    }

    public static function verify($crawler_key, $ip) {
        $cached = self::cached($crawler_key, $ip);
        if ($cached) { return $cached; }
        $registry = AJNanda_Search_AI_Crawler_Registry::all();
        $spec = $registry[$crawler_key]['verification'] ?? array();
        if (empty($spec) || 'forward_confirmed_reverse_dns' !== ($spec['method'] ?? '')) {
            return self::result('not_verifiable', 'none', __('The provider has no supported documented DNS verification method in AJNanda.', 'ajnanda'));
        }
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return self::result('failed', 'forward_confirmed_reverse_dns', __('The source IP is invalid, private, or reserved and cannot represent a verified public provider crawler.', 'ajnanda'));
        }
        $hostname = strtolower(rtrim((string) gethostbyaddr($ip), '.'));
        if (! $hostname || $hostname === $ip || ! self::allowed_hostname($hostname, (array) $spec['domains'])) {
            $result = self::result('failed', 'forward_confirmed_reverse_dns', __('Reverse DNS did not resolve to an allowed provider domain.', 'ajnanda'));
            self::cache($crawler_key, $ip, $result, 6 * HOUR_IN_SECONDS);
            return $result;
        }
        $addresses = self::forward_addresses($hostname);
        if (! in_array($ip, $addresses, true)) {
            $result = self::result('failed', 'forward_confirmed_reverse_dns', __('Forward DNS did not resolve the provider hostname back to the original IP.', 'ajnanda'));
            self::cache($crawler_key, $ip, $result, 6 * HOUR_IN_SECONDS);
            return $result;
        }
        $result = self::result('verified', 'forward_confirmed_reverse_dns', sprintf(__('Reverse and forward DNS confirmed %s.', 'ajnanda'), $hostname));
        self::cache($crawler_key, $ip, $result, DAY_IN_SECONDS);
        return $result;
    }

    public static function process_pending() {
        if (! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) { return; }
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id, crawler_key, ip_value FROM " . AJNanda_Search_AI_Crawler_Log_Store::table() . " WHERE verification_state = 'pending' AND ip_mode = 'full' ORDER BY id ASC LIMIT 5", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        foreach ($rows as $row) {
            AJNanda_Search_AI_Crawler_Log_Store::update_verification($row['id'], self::verify($row['crawler_key'], $row['ip_value']));
        }
    }

    private static function allowed_hostname($hostname, $domains) {
        foreach ($domains as $domain) {
            $domain = strtolower(ltrim($domain, '.'));
            if ($hostname === $domain || substr($hostname, -strlen('.' . $domain)) === '.' . $domain) { return true; }
        }
        return false;
    }

    private static function forward_addresses($hostname) {
        $addresses = gethostbynamel($hostname) ?: array();
        if (function_exists('dns_get_record')) {
            foreach ((array) dns_get_record($hostname, DNS_AAAA) as $record) {
                if (! empty($record['ipv6'])) { $addresses[] = $record['ipv6']; }
            }
        }
        return array_values(array_unique($addresses));
    }

    private static function result($state, $method, $reason) { return compact('state', 'method', 'reason'); }
    private static function cache_key($crawler_key, $ip) { return 'ajnanda_crawler_verify_' . md5($crawler_key . '|' . $ip); }
    private static function cache($crawler_key, $ip, $result, $ttl) { set_transient(self::cache_key($crawler_key, $ip), $result, $ttl); }
}
