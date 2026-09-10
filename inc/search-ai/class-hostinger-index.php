<?php
/**
 * Optional, read-only coverage probe for Hostinger's Web2Agent MCP endpoint.
 *
 * Hostinger crawls rendered pages independently of AJNanda's curated discovery
 * output, so its index can silently omit published content. This asks the
 * public endpoint what it actually holds and compares that with what AJNanda
 * advertises. It runs only when an administrator requests it, never on page
 * load, and it never writes to Hostinger.
 *
 * @package AJNanda
 */

if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Hostinger_Index {

    const TRANSIENT = 'ajnanda_search_ai_hostinger_index';
    const PROTOCOL = '2024-11-05';
    const MAX_QUERIES = 12;
    const BUDGET_SECONDS = 25;
    const CACHE_SECONDS = 12 * HOUR_IN_SECONDS;

    /** Last stored report, or null. */
    public static function report() {
        $report = get_transient(self::TRANSIENT);
        return is_array($report) ? $report : null;
    }

    public static function clear() {
        delete_transient(self::TRANSIENT);
    }

    /**
     * Query the endpoint and store a coverage report.
     *
     * @return array The report, including an 'error' string when incomplete.
     */
    public static function probe() {
        $status = AJNanda_Search_AI_Hostinger::status();
        $expected = AJNanda_Search_AI_Discovery_Files::published_llms_links();
        $report = array(
            'checked_at' => time(),
            'endpoint' => $status['endpoint'],
            'queries' => 0,
            'indexed' => array(),
            'covered' => array(),
            'missing' => array(),
            'extra' => array(),
            'expected_count' => count($expected),
            'sampled' => count($expected) > self::MAX_QUERIES,
            'truncated' => false,
            'error' => '',
        );
        if ('' === $status['endpoint']) {
            $report['error'] = __('No Hostinger Web2Agent endpoint is available for this site.', 'ajnanda');
            return self::store($report);
        }

        $session = self::open_session($status['endpoint']);
        if (is_wp_error($session)) {
            $report['error'] = $session->get_error_message();
            return self::store($report);
        }

        $started = microtime(true);
        $indexed = array();
        foreach (self::queries($expected) as $query) {
            if ((microtime(true) - $started) > self::BUDGET_SECONDS) {
                $report['truncated'] = true;
                break;
            }
            $results = self::ask($status['endpoint'], $session, $query);
            if (is_wp_error($results)) {
                $report['error'] = $results->get_error_message();
                break;
            }
            $report['queries']++;
            foreach ($results as $url) {
                $normalised = AJNanda_Search_AI_Discovery_Files::normalise_url($url);
                if ('' !== $normalised && ! isset($indexed[$normalised])) { $indexed[$normalised] = $url; }
            }
        }

        $report['indexed'] = $indexed;
        foreach ($expected as $normalised => $title) {
            if (isset($indexed[$normalised])) { $report['covered'][$normalised] = $title; }
            else { $report['missing'][$normalised] = $title; }
        }
        foreach ($indexed as $normalised => $url) {
            if (! isset($expected[$normalised])) { $report['extra'][$normalised] = $url; }
        }
        return self::store($report);
    }

    private static function store($report) {
        set_transient(self::TRANSIENT, $report, self::CACHE_SECONDS);
        return $report;
    }

    /**
     * Retrieval queries.
     *
     * Published link titles are used because each one should surface its own
     * page; a page that never surfaces under its own title is good evidence it
     * is absent rather than merely ranked low.
     */
    private static function queries($expected) {
        $queries = array();
        foreach ($expected as $title) {
            $title = trim(wp_strip_all_tags((string) $title));
            if ('' !== $title) { $queries[] = $title; }
            if (count($queries) >= self::MAX_QUERIES) { break; }
        }
        if (empty($queries)) {
            $profile = AJNanda_Search_AI_Site_Profile::get();
            $queries[] = $profile['name'] ?: wp_parse_url(home_url(), PHP_URL_HOST);
        }
        return $queries;
    }

    /** Perform the MCP initialize handshake and return the session id. */
    private static function open_session($endpoint) {
        $response = self::rpc($endpoint, array(
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => array(
                'protocolVersion' => self::PROTOCOL,
                'capabilities' => (object) array(),
                'clientInfo' => array('name' => 'AJNanda Search & AI', 'version' => '1.0'),
            ),
        ));
        if (is_wp_error($response)) { return $response; }
        $code = (int) wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            return new WP_Error('ajnanda_mcp_http', sprintf(__('The Web2Agent endpoint returned HTTP %d.', 'ajnanda'), $code));
        }
        $session = trim((string) wp_remote_retrieve_header($response, 'mcp-session-id'));
        if ('' === $session) {
            return new WP_Error('ajnanda_mcp_session', __('The Web2Agent endpoint did not return a session id.', 'ajnanda'));
        }
        // Completing the handshake is required before tools may be called.
        self::rpc($endpoint, array('jsonrpc' => '2.0', 'method' => 'notifications/initialized'), $session);
        return $session;
    }

    /** Call the endpoint's search tool and return the page URLs it reports. */
    private static function ask($endpoint, $session, $query) {
        $response = self::rpc($endpoint, array(
            'jsonrpc' => '2.0',
            'id' => 9,
            'method' => 'tools/call',
            'params' => array('name' => 'ask', 'arguments' => array('query' => $query)),
        ), $session, 20);
        if (is_wp_error($response)) { return $response; }
        if (200 !== (int) wp_remote_retrieve_response_code($response)) {
            return new WP_Error('ajnanda_mcp_http', sprintf(__('The Web2Agent endpoint returned HTTP %d.', 'ajnanda'), (int) wp_remote_retrieve_response_code($response)));
        }
        $decoded = self::decode(wp_remote_retrieve_body($response));
        if (isset($decoded['error']['message'])) {
            return new WP_Error('ajnanda_mcp_error', (string) $decoded['error']['message']);
        }
        $text = isset($decoded['result']['content'][0]['text']) ? $decoded['result']['content'][0]['text'] : '';
        $items = json_decode((string) $text, true);
        if (! is_array($items)) { return array(); }
        $urls = array();
        foreach ($items as $item) {
            if (isset($item['page_url']) && is_string($item['page_url'])) { $urls[] = $item['page_url']; }
        }
        return $urls;
    }

    private static function rpc($endpoint, $payload, $session = '', $timeout = 12) {
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
        );
        if ('' !== $session) { $headers['Mcp-Session-Id'] = $session; }
        return wp_safe_remote_post($endpoint, array(
            'timeout' => $timeout,
            'redirection' => 1,
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'user-agent' => 'AJNanda-Discovery-Diagnostic/1.0',
        ));
    }

    /** Accept both a plain JSON body and a server-sent-event stream. */
    private static function decode($body) {
        $body = (string) $body;
        if ('' === trim($body)) { return null; }
        if ('{' === substr(ltrim($body), 0, 1)) { return json_decode($body, true); }
        $data = '';
        foreach (preg_split('/\r\n|\n|\r/', $body) as $line) {
            if (0 === strpos($line, 'data:')) { $data = trim(substr($line, 5)); }
        }
        return '' === $data ? null : json_decode($data, true);
    }
}
