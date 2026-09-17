<?php
/**
 * AJNanda Cover background rotation.
 *
 * Opt-in per block: js/editor-cover-rotator.js adds a "Background rotation"
 * panel to core/cover that stores four comment-delimiter attributes —
 * ajnRotateEnabled, ajnRotateImageIds, ajnRotateInterval, ajnRotateFade.
 * They never touch the block's save() markup, so a Cover that doesn't turn
 * rotation on serializes and renders byte-for-byte as before (attributes at
 * their defaults aren't written to the comment at all), and removing the
 * theme can't invalidate a Cover that did.
 *
 * At render time, only for a Cover with rotation on, an image background and
 * at least one extra image, this filter adds a data-ajn-cover-rotate JSON
 * attribute (responsive src/srcset per extra image, interval, fade) to the
 * Cover wrapper and enqueues js/cover-rotator.js. The Cover's own background
 * image is always the first slide, so no-JS / reduced-motion visitors see
 * exactly the Cover as configured.
 *
 * @package AJNanda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normalized rotation settings for a parsed core/cover block, or null when
 * rotation shouldn't run for it.
 *
 * @param array $block Parsed block.
 * @return array{interval:int,fade:int,images:array<int,array{src:string,srcset:string}>}|null
 */
function ajnanda_cover_rotator_config(array $block) {
    $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array();

    if (empty($attrs['ajnRotateEnabled'])) {
        return null;
    }

    // Video backgrounds have no image to crossfade.
    if (isset($attrs['backgroundType']) && 'image' !== $attrs['backgroundType']) {
        return null;
    }

    $cover_image_id = !empty($attrs['useFeaturedImage'])
        ? (int) get_post_thumbnail_id()
        : absint($attrs['id'] ?? 0);

    $ids = isset($attrs['ajnRotateImageIds']) && is_array($attrs['ajnRotateImageIds'])
        ? $attrs['ajnRotateImageIds']
        : array();

    $images = array();
    $seen   = array($cover_image_id => true);
    foreach ($ids as $id) {
        $id = absint($id);
        if (!$id || isset($seen[$id]) || !wp_attachment_is_image($id)) {
            continue;
        }
        $seen[$id] = true;

        $src = wp_get_attachment_image_url($id, 'full');
        if (!$src) {
            continue;
        }

        $srcset   = wp_get_attachment_image_srcset($id, 'full');
        $images[] = array(
            'src'    => esc_url_raw($src),
            'srcset' => is_string($srcset) ? $srcset : '',
        );
    }

    if (empty($images)) {
        return null;
    }

    $interval = is_numeric($attrs['ajnRotateInterval'] ?? null) ? (float) $attrs['ajnRotateInterval'] : 5.0;
    $fade     = is_numeric($attrs['ajnRotateFade'] ?? null) ? (float) $attrs['ajnRotateFade'] : 0.8;
    $interval = max(2.0, min(30.0, $interval));
    $fade     = max(0.0, min(3.0, $fade, $interval / 2));

    return array(
        'interval' => (int) round($interval * 1000),
        'fade'     => (int) round($fade * 1000),
        'images'   => $images,
    );
}

/**
 * Attach rotation data to an opted-in Cover and enqueue the front-end script.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string
 */
function ajnanda_render_cover_rotator($block_content, $block) {
    if ('' === $block_content || empty($block['attrs']['ajnRotateEnabled']) || !class_exists('WP_HTML_Tag_Processor')) {
        return $block_content;
    }

    $config = ajnanda_cover_rotator_config($block);
    if (null === $config) {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag() || !$processor->has_class('wp-block-cover')) {
        return $block_content;
    }

    $processor->set_attribute('data-ajn-cover-rotate', wp_json_encode($config, JSON_UNESCAPED_SLASHES));

    wp_enqueue_script(
        'ajnanda-cover-rotator',
        get_template_directory_uri() . '/js/cover-rotator.js',
        array(),
        ajnanda_asset_version('js/cover-rotator.js'),
        true
    );

    return $processor->get_updated_html();
}
add_filter('render_block_core/cover', 'ajnanda_render_cover_rotator', 10, 2);
