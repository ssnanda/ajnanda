<?php
if (! defined('ABSPATH')) { exit; }
$owner_label = static function ($item) {
    if (! empty($item['ownership']['ajnanda'])) { return __('AJNanda', 'ajnanda'); }
    return implode(', ', $item['ownership']['external']);
};
$robots_endpoint = $discovery_status['robots']['endpoint'];
$llms_endpoint = $discovery_status['llms_txt']['endpoint'];
$picker_nonce = wp_create_nonce('ajnanda_search_ai_find_content');
?>
<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('Discovery outputs', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Status and ownership of the standard discovery outputs used by this site. Configure them in their respective tabs.', 'ajnanda'); ?></p>
    <?php if ($discovery_status['policy_count']) : ?><div class="notice notice-info inline"><p><?php printf(esc_html(_n('Content Access currently contains %d exclusion rule.', 'Content Access currently contains %d exclusion rules.', $discovery_status['policy_count'], 'ajnanda')), (int) $discovery_status['policy_count']); ?></p></div><?php endif; ?>
    <div class="ajnanda-admin-grid ajnanda-discovery-grid">
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('XML Sitemap', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['sitemap']['ownership']['ajnanda'] ? 'is-success' : 'is-warning'; ?>"><?php echo esc_html($owner_label($discovery_status['sitemap'])); ?></span><p><?php echo $discovery_status['sitemap']['ownership']['ajnanda'] ? esc_html__('WordPress core sitemap with AJNanda Content Access filtering.', 'ajnanda') : esc_html__('A recognized SEO plugin owns sitemap generation; AJNanda does not apply competing core-sitemap filters.', 'ajnanda'); ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['sitemap']['url']); ?>"><?php esc_html_e('View Sitemap', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('robots.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill is-success"><?php esc_html_e('WordPress policy available', 'ajnanda'); ?></span><?php if ($robots_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $robots_endpoint['result'] ? 'is-success' : ('http_error' === $robots_endpoint['result'] ? 'is-warning' : 'is-externally_unverifiable'); ?>"><?php echo 'success' === $robots_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : ('http_error' === $robots_endpoint['result'] ? esc_html(sprintf(__('Public endpoint returned HTTP %d', 'ajnanda'), $robots_endpoint['code'])) : esc_html__('Endpoint externally unverifiable', 'ajnanda')); ?></span><?php endif; ?><p><?php if ($robots_endpoint && in_array($robots_endpoint['result'], array('tls_error', 'transport_error'), true)) { echo esc_html($robots_endpoint['message']); } else { esc_html_e('Preserves WordPress rules and appends AJNanda’s registry-backed AI crawler policy. A public endpoint warning usually indicates web-server or reverse-proxy routing, not a broken WordPress policy.', 'ajnanda'); } ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['robots']['url']); ?>"><?php esc_html_e('View robots.txt', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('llms.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['llms_txt']['enabled'] ? 'is-success' : 'is-warning'; ?>"><?php echo $discovery_status['llms_txt']['enabled'] ? esc_html__('Enabled', 'ajnanda') : esc_html__('Disabled or delegated', 'ajnanda'); ?></span><?php if ($llms_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $llms_endpoint['result'] ? 'is-success' : ('http_error' === $llms_endpoint['result'] ? 'is-warning' : 'is-externally_unverifiable'); ?>"><?php echo 'success' === $llms_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : ('http_error' === $llms_endpoint['result'] ? esc_html(sprintf(__('Endpoint returned HTTP %d', 'ajnanda'), $llms_endpoint['code'])) : esc_html__('Endpoint externally unverifiable', 'ajnanda')); ?></span><?php endif; ?><p><?php if ($llms_endpoint && in_array($llms_endpoint['result'], array('tls_error', 'transport_error'), true)) { echo esc_html($llms_endpoint['message']); } else { printf(esc_html__('Owner: %s. Uses the Site Profile and excludes content blocked from llms.txt advertising.', 'ajnanda'), esc_html($owner_label($discovery_status['llms_txt']))); } ?></p><?php if ($discovery_status['llms_txt']['enabled']) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['llms_txt']['url']); ?>"><?php esc_html_e('View llms.txt', 'ajnanda'); ?></a><?php endif; ?></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('Schema', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['schema']['active'] ? 'is-success' : 'is-warning'; ?>"><?php echo $discovery_status['schema']['active'] ? esc_html__('AJNanda active', 'ajnanda') : ($discovery_status['schema']['enabled'] ? esc_html__('Delegated', 'ajnanda') : esc_html__('Disabled', 'ajnanda')); ?></span><p><?php printf(esc_html__('Owner: %s. AJNanda schema uses the canonical Site Profile when AJNanda owns this capability.', 'ajnanda'), esc_html($owner_label($discovery_status['schema']))); ?></p></div>
    </div>
</div>

<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('llms.txt Important Pages', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Select the authoritative public pages that help machines understand this site. AJNanda also includes the configured homepage and posts page when published, plus a limited list of recent public articles.', 'ajnanda'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="ajnanda_save_llms_important_pages">
        <?php wp_nonce_field('ajnanda_save_llms_important_pages'); ?>
        <div class="ajnanda-content-picker" id="ajnanda_llms_page_picker" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr($picker_nonce); ?>">
            <label for="ajnanda_llms_page_search"><strong><?php esc_html_e('Find a page', 'ajnanda'); ?></strong></label>
            <input type="search" id="ajnanda_llms_page_search" class="regular-text" placeholder="<?php esc_attr_e('Search published pages…', 'ajnanda'); ?>" autocomplete="off">
            <span class="spinner" aria-hidden="true"></span>
            <div class="ajnanda-content-picker-results" role="listbox" aria-label="<?php esc_attr_e('Page search results', 'ajnanda'); ?>"></div>
            <h3><?php esc_html_e('Selected Important Pages', 'ajnanda'); ?></h3>
            <div class="ajnanda-content-picker-selected">
                <?php foreach ($selected_important_pages as $page) : ?>
                    <div class="ajnanda-content-picker-item" data-content-id="<?php echo esc_attr($page->ID); ?>"><input type="hidden" name="search_ai_llms_important_page_ids[]" value="<?php echo esc_attr($page->ID); ?>"><span><strong><?php echo esc_html(get_the_title($page) ?: __('(no title)', 'ajnanda')); ?></strong><small><?php echo esc_html(get_permalink($page)); ?></small></span><button type="button" class="button-link-delete" data-remove-content><?php esc_html_e('Remove', 'ajnanda'); ?></button></div>
                <?php endforeach; ?>
                <p class="ajnanda-content-picker-empty" <?php echo $selected_important_pages ? 'hidden' : ''; ?>><?php esc_html_e('No additional Important Pages selected. Only reliable WordPress foundational pages will be included automatically.', 'ajnanda'); ?></p>
            </div>
        </div>
        <?php submit_button(__('Save Important Pages', 'ajnanda')); ?>
    </form>
</div>

<script>
(function () {
    var picker = document.getElementById('ajnanda_llms_page_picker');
    if (!picker) { return; }
    var search = document.getElementById('ajnanda_llms_page_search');
    var results = picker.querySelector('.ajnanda-content-picker-results');
    var selected = picker.querySelector('.ajnanda-content-picker-selected');
    var spinner = picker.querySelector('.spinner');
    var timer;
    function updateEmpty() { selected.querySelector('.ajnanda-content-picker-empty').hidden = !!selected.querySelector('[data-content-id]'); }
    function addPage(item) {
        if (selected.querySelector('[data-content-id="' + item.id + '"]')) { return; }
        var row = document.createElement('div'); row.className = 'ajnanda-content-picker-item'; row.dataset.contentId = item.id;
        var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'search_ai_llms_important_page_ids[]'; hidden.value = item.id;
        var text = document.createElement('span'); var title = document.createElement('strong'); title.textContent = item.title; var url = document.createElement('small'); url.textContent = item.url; text.appendChild(title); text.appendChild(url);
        var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'button-link-delete'; remove.dataset.removeContent = ''; remove.textContent = <?php echo wp_json_encode(__('Remove', 'ajnanda')); ?>;
        row.appendChild(hidden); row.appendChild(text); row.appendChild(remove); selected.appendChild(row); updateEmpty();
    }
    search.addEventListener('input', function () {
        clearTimeout(timer); results.innerHTML = '';
        if (search.value.trim().length < 2) { spinner.classList.remove('is-active'); return; }
        timer = setTimeout(function () {
            spinner.classList.add('is-active');
            var requestUrl = picker.dataset.ajaxUrl + '?action=ajnanda_search_ai_find_content&post_type=page&_wpnonce=' + encodeURIComponent(picker.dataset.nonce) + '&search=' + encodeURIComponent(search.value.trim());
            fetch(requestUrl, { credentials: 'same-origin' }).then(function (response) { return response.json(); }).then(function (payload) {
                results.innerHTML = '';
                (payload.success ? payload.data : []).forEach(function (item) {
                    if (selected.querySelector('[data-content-id="' + item.id + '"]')) { return; }
                    var button = document.createElement('button'); button.type = 'button'; button.className = 'ajnanda-content-picker-result'; button.textContent = item.title; button.addEventListener('click', function () { addPage(item); button.remove(); }); results.appendChild(button);
                });
                if (!results.children.length) { results.textContent = <?php echo wp_json_encode(__('No matching unselected pages.', 'ajnanda')); ?>; }
            }).catch(function () { results.textContent = <?php echo wp_json_encode(__('Search could not be completed. Try again.', 'ajnanda')); ?>; }).finally(function () { spinner.classList.remove('is-active'); });
        }, 300);
    });
    selected.addEventListener('click', function (event) { if (event.target.matches('[data-remove-content]')) { event.target.closest('[data-content-id]').remove(); updateEmpty(); } });
})();
</script>
