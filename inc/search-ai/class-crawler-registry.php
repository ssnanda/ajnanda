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
        );
    }

    public static function all() {
        return apply_filters('ajnanda_search_ai_crawler_registry', array(
            'oai-searchbot' => array('provider' => 'OpenAI', 'label' => 'OAI-SearchBot', 'token' => 'OAI-SearchBot', 'category' => 'ai_search', 'robots_control' => true),
            'gptbot' => array('provider' => 'OpenAI', 'label' => 'GPTBot', 'token' => 'GPTBot', 'category' => 'ai_training', 'robots_control' => true),
            'chatgpt-user' => array('provider' => 'OpenAI', 'label' => 'ChatGPT-User', 'token' => 'ChatGPT-User', 'category' => 'user_retrieval', 'robots_control' => false),
            'claude-searchbot' => array('provider' => 'Anthropic', 'label' => 'Claude-SearchBot', 'token' => 'Claude-SearchBot', 'category' => 'ai_search', 'robots_control' => true),
            'claudebot' => array('provider' => 'Anthropic', 'label' => 'ClaudeBot', 'token' => 'ClaudeBot', 'category' => 'ai_training', 'robots_control' => true),
            'claude-user' => array('provider' => 'Anthropic', 'label' => 'Claude-User', 'token' => 'Claude-User', 'category' => 'user_retrieval', 'robots_control' => true),
            'perplexitybot' => array('provider' => 'Perplexity', 'label' => 'PerplexityBot', 'token' => 'PerplexityBot', 'category' => 'ai_search', 'robots_control' => true),
            'perplexity-user' => array('provider' => 'Perplexity', 'label' => 'Perplexity-User', 'token' => 'Perplexity-User', 'category' => 'user_retrieval', 'robots_control' => false),
            'google-extended' => array('provider' => 'Google', 'label' => 'Google-Extended', 'token' => 'Google-Extended', 'category' => 'ai_training', 'robots_control' => true, 'control_only' => true),
            'ccbot' => array('provider' => 'Common Crawl', 'label' => 'CCBot', 'token' => 'CCBot', 'category' => 'ai_training', 'robots_control' => true),
        ));
    }

    public static function category_allowed($category) {
        $mapping = array(
            'traditional_search' => 'search_ai_allow_traditional_search',
            'ai_search' => 'search_ai_allow_ai_search',
            'ai_training' => 'search_ai_allow_ai_training',
            'user_retrieval' => 'search_ai_allow_user_retrieval',
        );
        return isset($mapping[$category]) ? (bool) AJNanda_Search_AI_Settings::get($mapping[$category]) : false;
    }
}
