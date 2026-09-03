<?php
/** GitHub-backed Search & AI roadmap viewer. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Roadmap {
    const RAW_URL = 'https://raw.githubusercontent.com/ssnanda/ajnanda/main/docs/search-ai-roadmap.md';
    const SOURCE_URL = 'https://github.com/ssnanda/ajnanda/blob/main/docs/search-ai-roadmap.md';
    const TRANSIENT = 'ajnanda_search_ai_roadmap';
    const LAST_SUCCESS_OPTION = 'ajnanda_search_ai_roadmap_last_success';
    const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

    public static function get($force = false) {
        if ($force) { delete_transient(self::TRANSIENT); }
        if (! $force) {
            $cached = get_transient(self::TRANSIENT);
            if (is_array($cached) && ! empty($cached['markdown'])) {
                $cached['cache'] = 'fresh';
                return $cached;
            }
        }

        $response = wp_safe_remote_get(self::RAW_URL, array('timeout' => 8, 'redirection' => 3));
        if (! is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
            $markdown = wp_remote_retrieve_body($response);
            if (is_string($markdown) && '' !== trim($markdown)) {
                $result = array('markdown' => $markdown, 'refreshed' => time(), 'cache' => 'live', 'warning' => '');
                set_transient(self::TRANSIENT, $result, self::CACHE_TTL);
                update_option(self::LAST_SUCCESS_OPTION, $result, false);
                return $result;
            }
        }

        $message = is_wp_error($response)
            ? $response->get_error_message()
            : sprintf(__('GitHub returned HTTP %d.', 'ajnanda'), (int) wp_remote_retrieve_response_code($response));
        $last = get_option(self::LAST_SUCCESS_OPTION, array());
        if (is_array($last) && ! empty($last['markdown'])) {
            $last['cache'] = 'stale';
            $last['warning'] = $message;
            return $last;
        }
        return array('markdown' => '', 'refreshed' => 0, 'cache' => 'unavailable', 'warning' => $message);
    }

    public static function render_markdown($markdown) {
        $lines = preg_split('/\r\n|\r|\n/', (string) $markdown);
        $html = '';
        $paragraph = array();
        $in_list = false;
        $flush_paragraph = static function () use (&$html, &$paragraph) {
            if ($paragraph) { $html .= '<p>' . self::inline(implode(' ', $paragraph)) . '</p>'; $paragraph = array(); }
        };
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^(#{1,4})\s+(.+)$/', $trimmed, $match)) {
                $flush_paragraph();
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $level = min(4, strlen($match[1]));
                $html .= '<h' . $level . '>' . self::inline($match[2]) . '</h' . $level . '>';
            } elseif (preg_match('/^-\s+(.+)$/', $trimmed, $match)) {
                $flush_paragraph();
                if (! $in_list) { $html .= '<ul>'; $in_list = true; }
                $html .= '<li>' . self::inline($match[1]) . '</li>';
            } elseif ('' === $trimmed) {
                $flush_paragraph();
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
            } else {
                if ($in_list) { $html .= '</ul>'; $in_list = false; }
                $paragraph[] = $trimmed;
            }
        }
        $flush_paragraph();
        if ($in_list) { $html .= '</ul>'; }
        return wp_kses($html, array(
            'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(),
            'p' => array(), 'ul' => array(), 'li' => array(), 'strong' => array(), 'em' => array(), 'code' => array(),
            'a' => array('href' => true, 'target' => true, 'rel' => true),
        ));
    }

    private static function inline($text) {
        $tokens = array();
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^\s)]+)\)/', static function ($match) use (&$tokens) {
            $url = esc_url($match[2], array('http', 'https'));
            $html = $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($match[1]) . '</a>' : esc_html($match[1]);
            $key = '%%AJNROADMAP' . count($tokens) . '%%';
            $tokens[$key] = $html;
            return $key;
        }, (string) $text);
        $text = esc_html($text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        return strtr($text, $tokens);
    }
}
