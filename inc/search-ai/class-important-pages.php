<?php
/** Important Page selection resolution and validation. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

/**
 * Turns the administrator's stored Important Pages selection into the list that
 * public AI discovery outputs may actually use.
 *
 * The stored selection is treated as intent and is preserved verbatim in the
 * theme mod. Entries that are no longer eligible for discovery (draft, private,
 * trashed, deleted, noindex, excluded through Content Access, non-canonical) are
 * reported as invalid for the admin UI but are never emitted publicly and are
 * not silently deleted from configuration.
 */
class AJNanda_Search_AI_Important_Pages {

    /** The raw administrator selection as post IDs, in stored order. */
    public static function stored_ids() {
        return AJNanda_Search_AI_Discovery_Files::important_page_ids();
    }

    /**
     * Resolve the stored selection into valid and invalid buckets.
     *
     * @return array{
     *   valid: array<int, WP_Post>,
     *   invalid: array<int, array{post: WP_Post|null, title: string, reasons: string[]}>
     * }
     */
    public static function resolve() {
        $valid   = array();
        $invalid = array();
        foreach (self::stored_ids() as $id) {
            $eligibility = AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($id, 'llms_txt');
            $post        = get_post($id);
            if ($eligibility['eligible'] && $post) {
                $valid[$id] = $post;
                continue;
            }
            $invalid[$id] = array(
                'post'    => $post ?: null,
                'title'   => $post ? (get_the_title($post) ?: '#' . $id) : sprintf(__('Removed page #%d', 'ajnanda'), $id),
                'reasons' => $eligibility['reasons'] ?: array('missing'),
            );
        }
        return array('valid' => $valid, 'invalid' => $invalid);
    }

    /** Eligible Important Page IDs in stored order, for public discovery outputs. */
    public static function valid_ids() {
        return array_keys(self::resolve()['valid']);
    }

    /** WordPress foundational pages, included only while they are discoverable. */
    public static function foundational_ids() {
        $ids = array();
        foreach (array((int) get_option('page_on_front'), (int) get_option('page_for_posts')) as $id) {
            if ($id && AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($id, 'llms_txt')['eligible']) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /** Ordered, de-duplicated ID list every public AI discovery output should use. */
    public static function discovery_ids() {
        $ids = self::valid_ids();
        foreach (self::foundational_ids() as $id) {
            if (! in_array($id, $ids, true)) { $ids[] = $id; }
        }
        return $ids;
    }
}
