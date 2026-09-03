<?php
/** Provider-neutral semantic block contributors. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Contributors {
    public static function collect($entries, $context) {
        $result = array('nodes' => array(), 'relationships' => array(), 'explicit_faq' => false);
        $contributors = apply_filters('ajnanda_search_ai_schema_contributors', array(
            'ajnanda/faq' => array(__CLASS__, 'faq'),
            'ajnanda/how-to' => array(__CLASS__, 'how_to'),
            'ajnanda/team' => array(__CLASS__, 'person'),
        ), $context);
        foreach ($entries as $entry) {
            $name = $entry['block']['blockName'];
            if (empty($contributors[$name]) || ! is_callable($contributors[$name])) { continue; }
            $contribution = call_user_func($contributors[$name], $entry['block'], $context);
            if (! is_array($contribution)) { continue; }
            foreach (array('nodes', 'relationships') as $key) {
                if (! empty($contribution[$key]) && is_array($contribution[$key])) {
                    $result[$key] = array_merge($result[$key], $contribution[$key]);
                }
            }
            if (! empty($contribution['explicit_faq'])) { $result['explicit_faq'] = true; }
        }
        return self::deduplicate($result);
    }

    public static function faq($block, $context) {
        if (empty($block['attrs']['enableSchema'])) { return array(); }
        $questions = array();
        foreach (self::descendants($block, 'core/details') as $detail) {
            $question = AJNanda_Search_AI_Schema_Validator::text($detail['attrs']['summary'] ?? '');
            $answer = AJNanda_Search_AI_Schema_Validator::block_text($detail);
            if (AJNanda_Search_AI_Schema_Validator::is_placeholder($question) || AJNanda_Search_AI_Schema_Validator::is_placeholder($answer)) { continue; }
            $key = strtolower($question);
            if (isset($questions[$key])) { continue; }
            $questions[$key] = array('@type' => 'Question', 'name' => $question, 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $answer));
        }
        if (! $questions) { return array('explicit_faq' => true); }
        $id = $context->next_id('faq');
        return array(
            'explicit_faq' => true,
            'nodes' => array(array('@type' => 'FAQPage', '@id' => $id, 'url' => $context->url . '#' . wp_parse_url($id, PHP_URL_FRAGMENT), 'isPartOf' => array('@id' => $context->webpage_id), 'mainEntity' => array_values($questions))),
            'relationships' => array(array($context->webpage_id, 'hasPart', $id)),
        );
    }

    public static function how_to($block, $context) {
        if (empty($block['attrs']['showSchema'])) { return array(); }
        $heading = self::first_descendant($block, 'core/heading');
        $list = self::first_descendant($block, 'core/list');
        $name = $heading ? AJNanda_Search_AI_Schema_Validator::text($heading['attrs']['content'] ?? $heading['innerHTML'] ?? '') : '';
        $steps = array();
        $raw_steps = array();
        foreach (self::descendants($block, 'core/list-item') as $item) {
            $raw_steps[] = $item['attrs']['content'] ?? $item['innerHTML'] ?? '';
        }
        if (! $raw_steps) {
            $list_html = $list ? ($list['attrs']['values'] ?? $list['innerHTML'] ?? '') : '';
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', (string) $list_html, $matches)) { $raw_steps = $matches[1]; }
        }
        foreach ($raw_steps as $step) {
                $step = AJNanda_Search_AI_Schema_Validator::text($step);
                if (! AJNanda_Search_AI_Schema_Validator::is_placeholder($step)) { $steps[] = array('@type' => 'HowToStep', 'position' => count($steps) + 1, 'text' => $step); }
        }
        if (AJNanda_Search_AI_Schema_Validator::is_placeholder($name) || count($steps) < 2) { return array(); }
        $id = $context->next_id('howto');
        return array('nodes' => array(array('@type' => 'HowTo', '@id' => $id, 'name' => $name, 'url' => $context->url . '#' . wp_parse_url($id, PHP_URL_FRAGMENT), 'isPartOf' => array('@id' => $context->webpage_id), 'step' => $steps)), 'relationships' => array(array($context->webpage_id, 'hasPart', $id)));
    }

    public static function person($block, $context) {
        if (empty($block['attrs']['enableSchema'])) { return array(); }
        $heading = self::first_descendant($block, 'core/heading');
        $paragraph = self::first_descendant($block, 'core/paragraph');
        $image = self::first_descendant($block, 'core/image');
        $name = $heading ? AJNanda_Search_AI_Schema_Validator::text($heading['attrs']['content'] ?? $heading['innerHTML'] ?? '') : '';
        if (AJNanda_Search_AI_Schema_Validator::is_placeholder($name)) { return array(); }
        $id = $context->next_id('person');
        $node = array('@type' => 'Person', '@id' => $id, 'name' => $name, 'worksFor' => array('@id' => $context->identity_id));
        $description = $paragraph ? AJNanda_Search_AI_Schema_Validator::text($paragraph['attrs']['content'] ?? $paragraph['innerHTML'] ?? '') : '';
        if (! AJNanda_Search_AI_Schema_Validator::is_placeholder($description)) { $node['description'] = $description; }
        if ($image) {
            $url = ! empty($image['attrs']['url']) ? $image['attrs']['url'] : (! empty($image['attrs']['id']) ? wp_get_attachment_image_url(absint($image['attrs']['id']), 'large') : '');
            if ($url) { $node['image'] = esc_url_raw($url); }
        }
        return array('nodes' => array($node), 'relationships' => array(array($context->webpage_id, 'about', $id)));
    }

    public static function legacy_faq($entries, $context) {
        $questions = array();
        foreach ($entries as $entry) {
            if ('core/details' !== ($entry['block']['blockName'] ?? '') || in_array('ajnanda/faq', $entry['ancestors'] ?? array(), true)) { continue; }
            $detail = $entry['block'];
            $question = AJNanda_Search_AI_Schema_Validator::text($detail['attrs']['summary'] ?? '');
            $answer = AJNanda_Search_AI_Schema_Validator::block_text($detail);
            if (AJNanda_Search_AI_Schema_Validator::is_placeholder($question) || AJNanda_Search_AI_Schema_Validator::is_placeholder($answer) || '?' !== substr($question, -1)) { continue; }
            $questions[strtolower($question)] = array('@type' => 'Question', 'name' => $question, 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $answer));
        }
        if (! $questions) { return array(); }
        $id = $context->next_id('faq');
        return array('nodes' => array(array('@type' => 'FAQPage', '@id' => $id, 'url' => $context->url . '#' . wp_parse_url($id, PHP_URL_FRAGMENT), 'isPartOf' => array('@id' => $context->webpage_id), 'mainEntity' => array_values($questions))), 'relationships' => array(array($context->webpage_id, 'hasPart', $id)));
    }

    private static function descendants($block, $name) {
        $found = array();
        foreach (($block['innerBlocks'] ?? array()) as $inner) {
            if ($name === ($inner['blockName'] ?? '')) { $found[] = $inner; }
            $found = array_merge($found, self::descendants($inner, $name));
        }
        return $found;
    }

    private static function first_descendant($block, $name) {
        $found = self::descendants($block, $name);
        return $found ? reset($found) : null;
    }

    private static function deduplicate($result) {
        $seen = array();
        $removed = array();
        $nodes = array();
        foreach ($result['nodes'] as $node) {
            $comparable = $node;
            unset($comparable['@id'], $comparable['url']);
            $fingerprint = md5(wp_json_encode($comparable));
            if (isset($seen[$fingerprint])) {
                if (! empty($node['@id'])) { $removed[$node['@id']] = $seen[$fingerprint]; }
                continue;
            }
            $seen[$fingerprint] = $node['@id'] ?? $fingerprint;
            $nodes[] = $node;
        }
        foreach ($result['relationships'] as &$relationship) {
            if (isset($removed[$relationship[2]])) { $relationship[2] = $removed[$relationship[2]]; }
        }
        unset($relationship);
        $result['nodes'] = $nodes;
        $result['relationships'] = array_values(array_unique($result['relationships'], SORT_REGULAR));
        return $result;
    }
}
