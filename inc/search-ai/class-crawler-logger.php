<?php
/** Lightweight WordPress-local crawler request observation. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Crawler_Logger {
    private static $pending = null;

    public static function init() {
        add_action('template_redirect', array(__CLASS__, 'capture'), 0);
        add_action('shutdown', array(__CLASS__, 'write'), 999);
    }

    public static function classify($user_agent) {
        $user_agent = trim((string) $user_agent);
        if (! $user_agent) { return null; }
        foreach (AJNanda_Search_AI_Crawler_Registry::all() as $key => $crawler) {
            if (! empty($crawler['control_only']) || empty($crawler['token'])) { continue; }
            if (false !== stripos($user_agent, $crawler['token'])) {
                return array('crawler_key' => $key, 'provider_key' => sanitize_key($crawler['provider']), 'category' => $crawler['category'], 'reported_identity' => $crawler['label'], 'known' => true);
            }
        }
        if (preg_match('/(?:bot|crawler|spider)(?:[\s\/+;]|$)/i', $user_agent, $match)) {
            $identity = preg_split('/[\s\/;]+/', $user_agent)[0] ?: $match[1];
            return array('crawler_key' => 'unknown-crawler', 'provider_key' => 'unknown', 'category' => 'unknown', 'reported_identity' => sanitize_text_field(substr($identity, 0, 191)), 'known' => false);
        }
        return null;
    }

    public static function capture() {
        if (! AJNanda_Search_AI_Settings::get('search_ai_crawler_logging_enabled')) { return; }
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_user_logged_in()) { return; }
        $method = strtoupper(sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'] ?? 'GET')));
        if (! in_array($method, array('GET', 'HEAD'), true)) { return; }
        $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = '/' . ltrim($path, '/');
        if (preg_match('/\.(?:css|js|map|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot)$/i', $path) || 0 === strpos($path, '/wp-admin/') || 0 === strpos($path, '/wp-json/')) { return; }
        $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $classification = self::classify($ua);
        if (! $classification) { return; }
        if (! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) { return; }
        self::$pending = array('classification' => $classification, 'path' => substr($path, 0, 1000), 'method' => $method, 'user_agent' => substr($ua, 0, 1000), 'ip' => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')));
    }

    public static function write() {
        if (! self::$pending) { return; }
        $request = self::$pending;
        self::$pending = null;
        try {
            self::record($request['classification'], $request['path'], $request['method'], http_response_code() ?: null, $request['user_agent'], $request['ip']);
        } catch (Throwable $e) {
            // Observational logging must never affect the public response.
        }
    }

    public static function record($classification, $path, $method, $status, $user_agent, $ip, $source = 'wordpress') {
        if (! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) { return 0; }
        $mode = AJNanda_Search_AI_Settings::get('search_ai_crawler_ip_mode', 'anonymized');
        if (! in_array($mode, array('anonymized', 'hashed', 'full'), true)) { $mode = 'anonymized'; }
        $registry = AJNanda_Search_AI_Crawler_Registry::all();
        $can_verify = ! empty($registry[$classification['crawler_key']]['verification']);
        $verification = array('state' => 'reported_only', 'method' => 'user_agent', 'reason' => __('Identity is reported by User-Agent and has not been independently verified.', 'ajnanda'));
        if (! $classification['known']) {
            $verification = array('state' => 'not_verifiable', 'method' => 'none', 'reason' => __('Unknown crawler-like User-Agent; no provider verification method is available.', 'ajnanda'));
        } elseif (! $can_verify) {
            $verification = array('state' => 'not_verifiable', 'method' => 'none', 'reason' => __('This provider has no supported documented verification method in AJNanda.', 'ajnanda'));
        } elseif ('full' !== $mode) {
            $verification = array('state' => 'not_verifiable', 'method' => 'privacy_setting', 'reason' => __('Full IP storage is disabled, so provider DNS verification cannot be completed.', 'ajnanda'));
        } else {
            $cached = AJNanda_Search_AI_Crawler_Verifier::cached($classification['crawler_key'], $ip);
            $verification = $cached ?: array('state' => 'pending', 'method' => 'forward_confirmed_reverse_dns', 'reason' => __('Queued for bounded provider DNS verification.', 'ajnanda'));
        }
        $ip_value = '';
        if ('full' === $mode) { $ip_value = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : ''; }
        elseif ('hashed' === $mode && $ip) { $ip_value = hash_hmac('sha256', $ip, wp_salt('auth')); }
        elseif ($ip) { $ip_value = function_exists('wp_privacy_anonymize_ip') ? wp_privacy_anonymize_ip($ip) : ''; }
        return AJNanda_Search_AI_Crawler_Log_Store::insert(array(
            'observed_at' => current_time('mysql', true), 'request_path' => '/' . ltrim((string) wp_parse_url($path, PHP_URL_PATH), '/'), 'http_method' => strtoupper(substr($method, 0, 10)), 'http_status' => $status ? absint($status) : null,
            'crawler_key' => sanitize_key($classification['crawler_key']), 'provider_key' => sanitize_key($classification['provider_key']), 'category' => sanitize_key($classification['category']), 'user_agent' => substr(sanitize_text_field($user_agent), 0, 1000), 'reported_identity' => substr(sanitize_text_field($classification['reported_identity']), 0, 191),
            'verification_state' => $verification['state'], 'verification_method' => $verification['method'], 'verification_reason' => $verification['reason'], 'ip_value' => $ip_value, 'ip_mode' => $mode, 'source' => sanitize_key($source),
        ));
    }
}
