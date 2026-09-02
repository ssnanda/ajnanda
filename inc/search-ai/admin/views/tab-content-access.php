<?php
if (! defined('ABSPATH')) { exit; }
$effect_labels = array(
    'noindex' => __('Request search engines not to index excluded content', 'ajnanda'),
    'traditional_search' => __('Do not advertise to traditional search', 'ajnanda'),
    'ai_search' => __('Do not advertise to AI Search/retrieval systems', 'ajnanda'),
    'ai_training' => __('Do not allow supported AI training crawlers', 'ajnanda'),
    'user_retrieval' => __('Request exclusion from user-initiated AI retrieval where controllable', 'ajnanda'),
    'sitemap' => __('Exclude from XML sitemaps', 'ajnanda'),
    'llms_txt' => __('Exclude from llms.txt', 'ajnanda'),
    'schema_relationships' => __('Exclude from schema relationships', 'ajnanda'),
);
$default_effects = AJNanda_Search_AI_Content_Policy::default_effects();
$uses_default = $policy['effects'] === $default_effects;
$ajax_nonce = wp_create_nonce('ajnanda_search_ai_find_content');
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ajnanda_save_search_ai_policy">
    <?php wp_nonce_field('ajnanda_save_search_ai_policy'); ?>
    <div class="ajnanda-admin-section">
        <h2><?php esc_html_e('Exclude content from discovery', 'ajnanda'); ?></h2>
        <p><strong><?php esc_html_e('Normal public content is discoverable.', 'ajnanda'); ?></strong> <?php esc_html_e('Search for the pages or posts that should be exceptions.', 'ajnanda'); ?></p>

        <label class="ajnanda-search-ai-primary-policy"><input type="checkbox" id="search_ai_default_exclusion" name="search_ai_default_exclusion" value="1" <?php checked($uses_default); ?>> <span><strong><?php esc_html_e('Exclude from Search & AI discovery', 'ajnanda'); ?></strong><small><?php esc_html_e('Applies AJNanda’s sensible default exclusion behavior to the content selected below.', 'ajnanda'); ?></small></span></label>

        <div class="ajnanda-content-picker" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr($ajax_nonce); ?>">
            <label for="ajnanda_content_search"><strong><?php esc_html_e('Find content to exclude', 'ajnanda'); ?></strong></label>
            <input type="search" id="ajnanda_content_search" class="regular-text" placeholder="<?php esc_attr_e('Search pages and posts…', 'ajnanda'); ?>" autocomplete="off">
            <span class="spinner" aria-hidden="true"></span>
            <div class="ajnanda-content-picker-results" role="listbox" aria-label="<?php esc_attr_e('Content search results', 'ajnanda'); ?>"></div>

            <h3><?php esc_html_e('Selected exclusions', 'ajnanda'); ?></h3>
            <div class="ajnanda-content-picker-selected">
                <?php foreach ($selected_content as $item) : $type = get_post_type_object($item->post_type); ?>
                    <div class="ajnanda-content-picker-item" data-content-id="<?php echo esc_attr($item->ID); ?>"><input type="hidden" name="search_ai_excluded_post_ids[]" value="<?php echo esc_attr($item->ID); ?>"><span><strong><?php echo esc_html(get_the_title($item) ?: __('(no title)', 'ajnanda')); ?></strong><small><?php echo esc_html($type ? $type->labels->singular_name : $item->post_type); ?></small></span><button type="button" class="button-link-delete" data-remove-content><?php esc_html_e('Remove', 'ajnanda'); ?></button></div>
                <?php endforeach; ?>
                <p class="ajnanda-content-picker-empty" <?php echo $selected_content ? 'hidden' : ''; ?>><?php esc_html_e('No specific pages or posts are excluded.', 'ajnanda'); ?></p>
            </div>
        </div>
        <p class="description"><?php esc_html_e('Discovery controls are not security. Protect private content with authentication and application permissions.', 'ajnanda'); ?></p>
    </div>

    <details class="ajnanda-admin-section ajnanda-search-ai-advanced">
        <summary><strong><?php esc_html_e('Advanced exclusions', 'ajnanda'); ?></strong> <span><?php esc_html_e('Post types and URL/path patterns', 'ajnanda'); ?></span></summary>
        <div class="ajnanda-search-ai-details-body">
            <h3><?php esc_html_e('Exclude entire post types', 'ajnanda'); ?></h3>
            <div class="ajnanda-search-ai-check-grid"><?php foreach ($public_post_types as $post_type => $object) : ?><label><input type="checkbox" name="search_ai_excluded_post_types[]" value="<?php echo esc_attr($post_type); ?>" <?php checked(in_array($post_type, $policy['excluded_post_types'], true)); ?>> <?php echo esc_html($object->labels->name); ?></label><?php endforeach; ?></div>
            <h3><label for="search_ai_excluded_paths"><?php esc_html_e('URL/path patterns', 'ajnanda'); ?></label></h3>
            <textarea class="large-text code" rows="6" id="search_ai_excluded_paths" name="search_ai_excluded_paths" placeholder="/client-portal/&#10;/account/&#10;/billing/"><?php echo esc_textarea(implode("\n", $policy['excluded_paths'])); ?></textarea>
            <p class="description"><?php esc_html_e('Examples only—AJNanda does not add these automatically. Enter one site path per line. A path matches itself and its children; * is supported as a wildcard.', 'ajnanda'); ?></p>
        </div>
    </details>

    <details class="ajnanda-admin-section ajnanda-search-ai-advanced" <?php echo $uses_default ? '' : 'open'; ?>>
        <summary><strong><?php esc_html_e('Advanced exclusion behavior', 'ajnanda'); ?></strong> <span><?php esc_html_e('Fine-tune independent policy dimensions', 'ajnanda'); ?></span></summary>
        <div class="ajnanda-search-ai-details-body">
            <p><?php esc_html_e('Changing these options creates a custom exclusion policy and turns off the sensible-default control above.', 'ajnanda'); ?></p>
            <div class="ajnanda-search-ai-toggle-list" id="ajnanda_advanced_effects"><?php foreach ($effect_labels as $key => $label) : ?><label><input type="checkbox" name="search_ai_exclusion_effects[<?php echo esc_attr($key); ?>]" value="1" <?php checked(! empty($policy['effects'][$key])); ?>> <span><strong><?php echo esc_html($label); ?></strong></span></label><?php endforeach; ?></div>
            <label class="ajnanda-search-ai-robots-control"><input type="checkbox" name="search_ai_exclusion_effects[automated_crawlers]" value="1" <?php checked(! empty($policy['effects']['automated_crawlers'])); ?>> <span><strong><?php esc_html_e('Also block supported automated crawlers with robots.txt Disallow', 'ajnanda'); ?></strong><small><?php esc_html_e('Usually leave this off when noindex is enabled. A crawler blocked from fetching a page cannot see its noindex directive. This is not a security control.', 'ajnanda'); ?></small></span></label>
        </div>
    </details>
    <?php submit_button(__('Save Content Access Policy', 'ajnanda')); ?>
</form>

<script>
(function () {
    var picker = document.querySelector('.ajnanda-content-picker');
    if (!picker) { return; }
    var search = document.getElementById('ajnanda_content_search');
    var results = picker.querySelector('.ajnanda-content-picker-results');
    var selected = picker.querySelector('.ajnanda-content-picker-selected');
    var spinner = picker.querySelector('.spinner');
    var timer;

    function updateEmpty() {
        selected.querySelector('.ajnanda-content-picker-empty').hidden = !!selected.querySelector('[data-content-id]');
    }
    function addItem(item) {
        if (selected.querySelector('[data-content-id="' + item.id + '"]')) { return; }
        var row = document.createElement('div');
        row.className = 'ajnanda-content-picker-item';
        row.dataset.contentId = item.id;
        var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'search_ai_excluded_post_ids[]'; hidden.value = item.id;
        var text = document.createElement('span');
        var title = document.createElement('strong'); title.textContent = item.title;
        var type = document.createElement('small'); type.textContent = item.type;
        var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'button-link-delete'; remove.dataset.removeContent = ''; remove.textContent = <?php echo wp_json_encode(__('Remove', 'ajnanda')); ?>;
        text.appendChild(title); text.appendChild(type); row.appendChild(hidden); row.appendChild(text); row.appendChild(remove); selected.appendChild(row); updateEmpty();
    }
    search.addEventListener('input', function () {
        clearTimeout(timer); results.innerHTML = '';
        if (search.value.trim().length < 2) { spinner.classList.remove('is-active'); return; }
        timer = setTimeout(function () {
            spinner.classList.add('is-active');
            var url = picker.dataset.ajaxUrl + '?action=ajnanda_search_ai_find_content&_wpnonce=' + encodeURIComponent(picker.dataset.nonce) + '&search=' + encodeURIComponent(search.value.trim());
            fetch(url, { credentials: 'same-origin' }).then(function (response) { return response.json(); }).then(function (payload) {
                results.innerHTML = '';
                (payload.success ? payload.data : []).forEach(function (item) {
                    if (selected.querySelector('[data-content-id="' + item.id + '"]')) { return; }
                    var button = document.createElement('button'); button.type = 'button'; button.className = 'ajnanda-content-picker-result'; button.textContent = item.title + ' — ' + item.type; button.addEventListener('click', function () { addItem(item); button.remove(); }); results.appendChild(button);
                });
                if (!results.children.length) { results.textContent = <?php echo wp_json_encode(__('No matching unselected content.', 'ajnanda')); ?>; }
            }).catch(function () { results.textContent = <?php echo wp_json_encode(__('Search could not be completed. Try again.', 'ajnanda')); ?>; }).finally(function () { spinner.classList.remove('is-active'); });
        }, 300);
    });
    selected.addEventListener('click', function (event) { if (event.target.matches('[data-remove-content]')) { event.target.closest('[data-content-id]').remove(); updateEmpty(); } });

    var primary = document.getElementById('search_ai_default_exclusion');
    document.querySelectorAll('.ajnanda-search-ai-advanced input[type="checkbox"]').forEach(function (input) {
        input.addEventListener('change', function () { primary.checked = false; });
    });
})();
</script>
