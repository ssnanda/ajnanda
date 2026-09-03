<?php
/** Recursive Gutenberg block traversal for semantic contributors. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Block_Walker {
    const MAX_DEPTH = 12;

    public static function walk_post($post_id) {
        $content = get_post_field('post_content', absint($post_id));
        return self::walk_blocks(parse_blocks((string) $content), array(), 0, array(), array());
    }

    private static function walk_blocks($blocks, $path, $depth, $seen_refs, $ancestors) {
        if (! is_array($blocks) || $depth > self::MAX_DEPTH) { return array(); }
        $result = array();
        foreach ($blocks as $index => $block) {
            if (! is_array($block) || empty($block['blockName'])) { continue; }
            $block_path = array_merge($path, array($index));
            if ('core/block' === $block['blockName'] && ! empty($block['attrs']['ref'])) {
                $ref = absint($block['attrs']['ref']);
                if ($ref && empty($seen_refs[$ref])) {
                    $ref_post = get_post($ref);
                    if ($ref_post && in_array($ref_post->post_status, array('publish', 'private'), true)) {
                        $next_seen = $seen_refs;
                        $next_seen[$ref] = true;
                        $result = array_merge($result, self::walk_blocks(parse_blocks($ref_post->post_content), array_merge($block_path, array('ref-' . $ref)), $depth + 1, $next_seen, $ancestors));
                    }
                }
                continue;
            }
            $result[] = array('block' => $block, 'path' => $block_path, 'ancestors' => $ancestors);
            if (! empty($block['innerBlocks'])) {
                $result = array_merge($result, self::walk_blocks($block['innerBlocks'], $block_path, $depth + 1, $seen_refs, array_merge($ancestors, array($block['blockName']))));
            }
        }
        return $result;
    }
}
