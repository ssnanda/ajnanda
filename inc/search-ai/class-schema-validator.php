<?php
/** Normalization helpers shared by semantic contributors. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Validator {
    public static function text($value) {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8')));
    }

    public static function block_text($block) {
        if (! is_array($block)) { return ''; }
        $html = '';
        if (! empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $inner) { $html .= render_block($inner); }
        } elseif (! empty($block['innerHTML'])) {
            $html = $block['innerHTML'];
        }
        return self::text($html);
    }

    public static function is_placeholder($value) {
        $value = strtolower(self::text($value));
        if ('' === $value) { return true; }
        $placeholders = array('question', 'question one goes here?', 'question two goes here?', 'add a clear, direct answer to the question.', 'a clear, direct answer to the question.', 'how to', 'step one', 'step two', 'step three', 'team member', 'role or short bio.');
        return in_array($value, $placeholders, true);
    }
}
