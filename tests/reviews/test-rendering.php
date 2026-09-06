<?php
class AJNanda_Reviews_Rendering_Test extends WP_UnitTestCase {
    public function tear_down() {
        $GLOBALS['ajnanda_reviews_editor_preview'] = false;
        if (class_exists('AJCore_Reviews')) { AJCore_Reviews::unschedule(); foreach (array('snapshot', 'credentials', 'config', 'selection', 'sync_meta', 'display') as $key) { AJCore_Reviews_Vault::delete('ajcore_reviews_' . $key); } }
        parent::tear_down();
    }
    private function seed_google() {
        if (!function_exists('ajcore_get_featured_google_reviews')) { $this->markTestSkipped('Run this integration case with AJ Core enabled.'); }
        if (!function_exists('sodium_crypto_secretbox')) { $this->markTestSkipped('PHP sodium required.'); }
        update_option('ajcore_reviews_config', array('account' => 'accounts/100', 'location' => 'locations/200'), false);
        $provider = new AJCore_Reviews_Fixture_Provider();
        $provider->rows[0]['googleMapsUri'] = 'https://example.test/individual-review';
        $provider->rows[0]['reportingUri'] = 'https://example.test/report-review';
        $data = $provider->fetch('accounts/100', 'locations/200');
        AJCore_Reviews_Vault::write('ajcore_reviews_snapshot', $data);
        AJCore_Reviews_Vault::write('ajcore_reviews_credentials', array('refresh_token' => 'fixture-not-valid'));
        update_option('ajcore_reviews_selection', array(array_key_first($data['reviews']) => 0), false);
        update_option('ajcore_reviews_sync_meta', array('last_success' => time()), false);
        return $data;
    }
    public function test_blocks_register_with_dynamic_rendering() {
        foreach (array('ajnanda/google-reviews', 'ajnanda/manual-testimonials') as $name) {
            $block = WP_Block_Type_Registry::get_instance()->get_registered($name);
            $this->assertNotNull($block);
            $this->assertTrue($block->is_dynamic());
            $this->assertFalse($block->attributes['autoplay']['default']);
            $this->assertSame(array('grid', 'list', 'carousel', 'featured'), $block->attributes['layout']['enum']);
        }
    }
    public function test_without_ajcore_frontend_is_empty_and_editor_explains_dependency() {
        if (function_exists('ajcore_get_review_collections')) { $this->markTestSkipped('Run with AJCORE_TESTS_DISABLED=1.'); }
        $this->assertSame('', ajnanda_render_google_reviews(array()));
        $this->assertSame('', ajnanda_render_manual_testimonials(array()));
        $GLOBALS['ajnanda_reviews_editor_preview'] = true;
        $this->assertStringContainsString('AJ Core is required', ajnanda_render_google_reviews(array()));
    }
    public function test_google_rendering_escapes_text_preserves_attribution_and_source_links() {
        $this->seed_google();
        $html = ajnanda_render_google_reviews(array('heading' => '<script>heading</script>', 'layout' => 'list'));
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Featured Google reviews selected by the business', $html);
        $this->assertStringContainsString('translate="no">Google Maps', $html);
        $this->assertStringContainsString('https://example.test/individual-review', $html);
        $this->assertStringContainsString('https://example.test/report-review', $html);
        $this->assertStringContainsString('aj-reviews--list', $html);
        $this->assertStringContainsString('data-google-expires=', $html);
        $this->assertStringNotContainsString('AggregateRating', $html);
    }
    public function test_expired_content_and_rating_summary_never_render() {
        $data = $this->seed_google();
        $data['retrieved_at'] = time() - AJCore_Reviews::TTL - 1;
        $data['expires_at'] = time() - 1;
        set_transient('ajcore_reviews_snapshot', AJCore_Reviews_Vault::seal($data), HOUR_IN_SECONDS);
        $this->assertSame('', ajnanda_render_google_reviews(array()));
        $GLOBALS['ajnanda_reviews_editor_preview'] = true;
        $this->assertStringContainsString('expired', ajnanda_render_google_reviews(array()));
    }
    public function test_empty_and_unfeatured_collections_have_editor_only_help() {
        $this->seed_google(); delete_option('ajcore_reviews_selection');
        $this->assertSame('', ajnanda_render_google_reviews(array()));
        $GLOBALS['ajnanda_reviews_editor_preview'] = true;
        $this->assertStringContainsString('No Google reviews are featured', ajnanda_render_google_reviews(array()));
    }
    public function test_layouts_and_read_more_preserve_full_original_text() {
        $this->seed_google();
        foreach (array('grid', 'list', 'featured', 'carousel') as $layout) {
            $html = ajnanda_render_google_reviews(array('layout' => $layout, 'textLines' => 3));
            $this->assertStringContainsString('aj-reviews--' . $layout, $html);
            $this->assertStringContainsString('<details class="aj-review__expand">', $html);
            $this->assertStringContainsString(esc_html(ajcore_reviews_test_review()['comment']), $html);
            if ($layout === 'carousel') { $this->assertStringContainsString('data-autoplay="false"', $html); $this->assertStringContainsString('data-prev', $html); $this->assertStringContainsString('data-next', $html); }
        }
    }
    public function test_manual_content_has_no_google_branding_or_private_notes() {
        $this->seed_google();
        $id = self::factory()->post->create(array('post_type' => AJCore_Testimonials::TYPE, 'post_status' => 'publish', 'post_title' => 'Manual fixture', 'post_content' => 'Independent testimonial'));
        update_post_meta($id, AJCore_Testimonials::META, array('featured' => true, 'notes' => 'Secret internal fixture', 'source_label' => 'Client letter'));
        $html = ajnanda_render_manual_testimonials(array());
        $this->assertStringContainsString('Independent testimonial', $html);
        $this->assertStringNotContainsString('Google Maps', $html);
        $this->assertStringNotContainsString('Secret internal', $html);
        $this->assertStringNotContainsString('data-google-expires', $html);
    }
    public function test_feeds_search_and_ordinary_post_rest_cannot_export_google_content() {
        $this->seed_google();
        global $wp_query;
        $wp_query->is_feed = true; $this->assertSame('', ajnanda_render_google_reviews(array())); $wp_query->is_feed = false;
        $wp_query->is_search = true; $this->assertSame('', ajnanda_render_google_reviews(array())); $wp_query->is_search = false;
        $id = self::factory()->post->create(array('post_status' => 'publish', 'post_content' => '<!-- wp:ajnanda/google-reviews /-->'));
        $response = rest_get_server()->dispatch(new WP_REST_Request('GET', '/wp/v2/posts/' . $id));
        $this->assertSame(200, $response->get_status());
        $this->assertStringNotContainsString('Fixture Reviewer', $response->get_data()['content']['rendered']);
    }
    public function test_reusable_pattern_detection_and_existing_slider_share_carousel() {
        $reference = self::factory()->post->create(array('post_type' => 'wp_block', 'post_content' => '<!-- wp:ajnanda/google-reviews /-->'));
        $this->assertTrue(ajnanda_reviews_contains_google('<!-- wp:core/block {"ref":' . $reference . '} /-->'));
        $html = ajnanda_blocks_render_slider(array('autoplay' => true), '<div class="aj-slide">Fixture</div><div class="aj-slide">Another fixture</div>');
        $this->assertStringContainsString('data-aj-carousel', $html);
        $this->assertStringContainsString('data-pause', $html);
        $this->assertStringNotContainsString('data-swiper', $html);
    }
}
