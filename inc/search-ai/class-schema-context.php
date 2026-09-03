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
    private $counts = array();

    public function __construct($post_id, $is_article = false) {
        $this->post_id = absint($post_id);
        $this->url = $this->post_id ? get_permalink($this->post_id) : home_url('/');
        $this->webpage_id = trailingslashit($this->url) . '#webpage';
        $this->website_id = home_url('/#website');
        $this->identity_id = home_url('/#identity');
        $this->is_article = (bool) $is_article;
        $this->primary_id = $this->is_article ? trailingslashit($this->url) . '#article' : $this->webpage_id;
        $this->profile = AJNanda_Search_AI_Site_Profile::get();
    }

    public function next_id($type) {
        $type = sanitize_key($type);
        $this->counts[$type] = isset($this->counts[$type]) ? $this->counts[$type] + 1 : 1;
        return trailingslashit($this->url) . '#' . $type . (1 === $this->counts[$type] ? '' : '-' . $this->counts[$type]);
    }
}
