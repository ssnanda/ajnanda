<?php
/** Shared context for AJNanda schema contributors. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Context {
    public $post_id;
    public $url;
    public $webpage_id;
    public $website_id;
    public $identity_id;
    public $primary_id;
    public $is_article;
    public $profile;
    public $semantic_intent;
    public $title;
    public $description;
    public $image;
    private $counts = array();

    public function __construct($post_id, $is_article = false) {
        $this->post_id = absint($post_id);
        $this->url = $this->post_id ? get_permalink($this->post_id) : home_url('/');
        $this->webpage_id = trailingslashit($this->url) . '#webpage';
        $this->website_id = home_url('/#website');
        $this->identity_id = home_url('/#identity');
        $this->is_article = (bool) $is_article;
        $this->profile = AJNanda_Search_AI_Site_Profile::get();
        $this->semantic_intent = AJNanda_Search_AI_Page_Semantic_Intent::evaluate($this->post_id);
        $this->title = $this->post_id ? html_entity_decode(get_the_title($this->post_id), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8') : get_bloginfo('name');
        $description = $this->post_id ? get_post_meta($this->post_id, '_ajnanda_seo_description', true) : $this->profile['description'];
        if (! $description && $this->post_id && function_exists('ajnanda_seo_excerpt_fallback')) { $description = ajnanda_seo_excerpt_fallback($this->post_id); }
        $this->description = AJNanda_Search_AI_Schema_Validator::text($description);
        $this->image = $this->post_id && has_post_thumbnail($this->post_id) ? get_the_post_thumbnail_url($this->post_id, 'large') : '';
        if ($this->is_article) { $this->primary_id = trailingslashit($this->url) . '#article'; }
        elseif ('service' === $this->semantic_intent['effective']) { $this->primary_id = trailingslashit($this->url) . '#service'; }
        elseif ('product' === $this->semantic_intent['effective']) { $this->primary_id = trailingslashit($this->url) . '#product'; }
        elseif ('primary_location' === $this->semantic_intent['effective']) { $this->primary_id = $this->identity_id; }
        else { $this->primary_id = $this->webpage_id; }
    }

    public function next_id($type) {
        $type = sanitize_key($type);
        $this->counts[$type] = isset($this->counts[$type]) ? $this->counts[$type] + 1 : 1;
        return trailingslashit($this->url) . '#' . $type . (1 === $this->counts[$type] ? '' : '-' . $this->counts[$type]);
    }
}
