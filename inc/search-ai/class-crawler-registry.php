<?php
/** Maintainable crawler/provider policy registry. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Crawler_Registry {
    public static function categories() {
        return array(
            'traditional_search' => __('Traditional Search', 'ajnanda'),
            'ai_search' => __('AI Search / Retrieval', 'ajnanda'),
            'ai_training' => __('AI Training / Model Development', 'ajnanda'),
            'user_retrieval' => __('User-Initiated Retrieval', 'ajnanda'),
            'dataset' => __('Common Crawl / Dataset', 'ajnanda'),
            'seo_tool' => __('SEO / Marketing Tool', 'ajnanda'),
            'link_preview' => __('Link Preview', 'ajnanda'),
        );
    }

    public static function all() {
        return apply_filters('ajnanda_search_ai_crawler_registry', array(
            'oai-searchbot' => array('provider' => 'OpenAI', 'label' => 'OAI-SearchBot', 'token' => 'OAI-SearchBot', 'category' => 'ai_search', 'robots_control' => true, 'about' => 'Indexes pages for ChatGPT search results.'),
            'gptbot' => array('provider' => 'OpenAI', 'label' => 'GPTBot', 'token' => 'GPTBot', 'category' => 'ai_training', 'robots_control' => true, 'about' => 'Collects pages for OpenAI model training.'),
            'chatgpt-user' => array('provider' => 'OpenAI', 'label' => 'ChatGPT-User', 'token' => 'ChatGPT-User', 'category' => 'user_retrieval', 'robots_control' => false, 'about' => 'Fetches pages live while answering a ChatGPT user.'),
            'claude-searchbot' => array('provider' => 'Anthropic', 'label' => 'Claude-SearchBot', 'token' => 'Claude-SearchBot', 'category' => 'ai_search', 'robots_control' => true, 'about' => 'Indexes pages for Claude search results.'),
            'claudebot' => array('provider' => 'Anthropic', 'label' => 'ClaudeBot', 'token' => 'ClaudeBot', 'category' => 'ai_training', 'robots_control' => true, 'about' => 'Collects pages for Anthropic model training.'),
            'claude-user' => array('provider' => 'Anthropic', 'label' => 'Claude-User', 'token' => 'Claude-User', 'category' => 'user_retrieval', 'robots_control' => true, 'about' => 'Fetches pages live while answering a Claude user.'),
            'perplexitybot' => array('provider' => 'Perplexity', 'label' => 'PerplexityBot', 'token' => 'PerplexityBot', 'category' => 'ai_search', 'robots_control' => true, 'about' => 'Indexes pages for Perplexity answers.'),
            'perplexity-user' => array('provider' => 'Perplexity', 'label' => 'Perplexity-User', 'token' => 'Perplexity-User', 'category' => 'user_retrieval', 'robots_control' => false, 'about' => 'Fetches pages live while answering a Perplexity user.'),
            'google-extended' => array('provider' => 'Google', 'label' => 'Google-Extended', 'token' => 'Google-Extended', 'category' => 'ai_training', 'robots_control' => true, 'control_only' => true),
            'googlebot' => array('provider' => 'Google', 'label' => 'Googlebot', 'token' => 'Googlebot', 'category' => 'traditional_search', 'robots_control' => true, 'about' => 'Google Search indexing (also feeds AI Overviews).', 'verification' => array('method' => 'forward_confirmed_reverse_dns', 'domains' => array('.googlebot.com', '.google.com'))),
            'bingbot' => array('provider' => 'Microsoft', 'label' => 'Bingbot', 'token' => 'bingbot', 'category' => 'traditional_search', 'robots_control' => true, 'about' => 'Bing indexing (also feeds Copilot and DuckDuckGo).', 'verification' => array('method' => 'forward_confirmed_reverse_dns', 'domains' => array('.search.msn.com'))),
            'ccbot' => array('provider' => 'Common Crawl', 'label' => 'CCBot', 'token' => 'CCBot', 'category' => 'dataset', 'robots_control' => true, 'about' => 'Open web dataset widely used for AI training.'),
            // Recognized for logging only ('log_only'): AJNanda writes no robots.txt rule for these,
            // so adding them does not change the published robots.txt.
            'duckassistbot' => array('provider' => 'DuckDuckGo', 'label' => 'DuckAssistBot', 'token' => 'DuckAssistBot', 'category' => 'ai_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Fetches pages for DuckDuckGo AI answers.'),
            'duckduckbot' => array('provider' => 'DuckDuckGo', 'label' => 'DuckDuckBot', 'token' => 'DuckDuckBot', 'category' => 'traditional_search', 'robots_control' => true, 'log_only' => true, 'about' => 'DuckDuckGo search crawler.'),
            'applebot' => array('provider' => 'Apple', 'label' => 'Applebot', 'token' => 'Applebot', 'category' => 'traditional_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Siri and Spotlight search crawler.', 'verification' => array('method' => 'forward_confirmed_reverse_dns', 'domains' => array('.applebot.apple.com'))),
            'amazonbot' => array('provider' => 'Amazon', 'label' => 'Amazonbot', 'token' => 'Amazonbot', 'category' => 'ai_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Alexa answers and Amazon services.'),
            'meta-externalagent' => array('provider' => 'Meta', 'label' => 'meta-externalagent', 'token' => 'meta-externalagent', 'category' => 'ai_training', 'robots_control' => true, 'log_only' => true, 'about' => 'Collects pages for Meta AI training.'),
            'facebookexternalhit' => array('provider' => 'Meta', 'label' => 'facebookexternalhit', 'token' => 'facebookexternalhit', 'category' => 'link_preview', 'robots_control' => false, 'log_only' => true, 'about' => 'Builds link previews when a URL is shared on Facebook.'),
            'bytespider' => array('provider' => 'ByteDance', 'label' => 'Bytespider', 'token' => 'Bytespider', 'category' => 'ai_training', 'robots_control' => false, 'log_only' => true, 'about' => 'ByteDance/TikTok AI crawler; widely reported to ignore robots.txt.'),
            'mistralai-user' => array('provider' => 'Mistral', 'label' => 'MistralAI-User', 'token' => 'MistralAI-User', 'category' => 'user_retrieval', 'robots_control' => false, 'log_only' => true, 'about' => 'Fetches pages live for Le Chat users.'),
            'yandexbot' => array('provider' => 'Yandex', 'label' => 'YandexBot', 'token' => 'YandexBot', 'category' => 'traditional_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Yandex search crawler.', 'verification' => array('method' => 'forward_confirmed_reverse_dns', 'domains' => array('.yandex.ru', '.yandex.net', '.yandex.com'))),
            'baiduspider' => array('provider' => 'Baidu', 'label' => 'Baiduspider', 'token' => 'Baiduspider', 'category' => 'traditional_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Baidu search crawler.', 'verification' => array('method' => 'forward_confirmed_reverse_dns', 'domains' => array('.baidu.com', '.baidu.jp'))),
            'petalbot' => array('provider' => 'Huawei', 'label' => 'PetalBot', 'token' => 'PetalBot', 'category' => 'traditional_search', 'robots_control' => true, 'log_only' => true, 'about' => 'Petal Search (Huawei) crawler.'),
            'ahrefsbot' => array('provider' => 'Ahrefs', 'label' => 'AhrefsBot', 'token' => 'AhrefsBot', 'category' => 'seo_tool', 'robots_control' => true, 'log_only' => true, 'about' => 'SEO backlink/keyword tool; no search traffic to you.'),
            'semrushbot' => array('provider' => 'Semrush', 'label' => 'SemrushBot', 'token' => 'SemrushBot', 'category' => 'seo_tool', 'robots_control' => true, 'log_only' => true, 'about' => 'SEO backlink/keyword tool; no search traffic to you.'),
            'mj12bot' => array('provider' => 'Majestic', 'label' => 'MJ12bot', 'token' => 'MJ12bot', 'category' => 'seo_tool', 'robots_control' => true, 'log_only' => true, 'about' => 'SEO backlink index.'),
            'dotbot' => array('provider' => 'Moz', 'label' => 'DotBot', 'token' => 'DotBot', 'category' => 'seo_tool', 'robots_control' => true, 'log_only' => true, 'about' => 'Moz SEO link index.'),
            'dataforseobot' => array('provider' => 'DataForSEO', 'label' => 'DataForSeoBot', 'token' => 'DataForSeoBot', 'category' => 'seo_tool', 'robots_control' => true, 'log_only' => true, 'about' => 'SEO data reseller crawler.'),
        ));
    }

    /**
     * robots.txt status of one registry entry as shown in the crawler log.
     * Returns: allowed | blocked | ignores_robots | not_managed.
     */
    public static function robots_status($crawler_key) {
        $all = self::all();
        if (empty($all[$crawler_key])) { return 'not_managed'; }
        $crawler = $all[$crawler_key];
        if (empty($crawler['robots_control'])) { return 'ignores_robots'; }
        if (! empty($crawler['log_only'])) { return 'not_managed'; }
        return self::category_allowed($crawler['category']) ? 'allowed' : 'blocked';
    }

    public static function category_allowed($category) {
        $mapping = array(
            'traditional_search' => 'search_ai_allow_traditional_search',
            'ai_search' => 'search_ai_allow_ai_search',
            'ai_training' => 'search_ai_allow_ai_training',
            'user_retrieval' => 'search_ai_allow_user_retrieval',
            'dataset' => 'search_ai_allow_ai_training',
        );
        return isset($mapping[$category]) ? (bool) AJNanda_Search_AI_Settings::get($mapping[$category]) : false;
    }
}
