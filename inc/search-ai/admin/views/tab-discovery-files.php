<?php
if (! defined('ABSPATH')) { exit; }
$owner_label = static function ($item) {
    if (! empty($item['ownership']['ajnanda'])) { return __('AJNanda', 'ajnanda'); }
    return implode(', ', $item['ownership']['external']);
};
$robots_endpoint = $discovery_status['robots']['endpoint'];
$llms_endpoint = $discovery_status['llms_txt']['endpoint'];
$llms_full_endpoint = $discovery_status['llms_full_txt']['endpoint'];
$ai_endpoint = $discovery_status['ai_txt']['endpoint'];
$security_endpoint = $discovery_status['security_txt']['endpoint'];
$picker_nonce = wp_create_nonce('ajnanda_search_ai_find_content');
?>
<details class="ajnanda-admin-section ajnanda-search-ai-advanced">
    <summary><strong><?php esc_html_e('Discovery output status', 'ajnanda'); ?></strong> <span><?php esc_html_e('Review endpoint reachability and ownership.', 'ajnanda'); ?></span></summary>
    <div class="ajnanda-search-ai-details-body">
    <p><?php esc_html_e('Status and ownership of the standard discovery outputs used by this site. Configure them in their respective tabs.', 'ajnanda'); ?></p>
    <?php if ($discovery_status['policy_count']) : ?><div class="notice notice-info inline"><p><?php printf(esc_html(_n('Content Access currently contains %d exclusion rule.', 'Content Access currently contains %d exclusion rules.', $discovery_status['policy_count'], 'ajnanda')), (int) $discovery_status['policy_count']); ?></p></div><?php endif; ?>
    <div class="ajnanda-admin-grid ajnanda-discovery-grid">
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('XML Sitemap', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['sitemap']['ownership']['ajnanda'] ? 'is-success' : 'is-warning'; ?>"><?php echo esc_html($owner_label($discovery_status['sitemap'])); ?></span><p><?php echo $discovery_status['sitemap']['ownership']['ajnanda'] ? esc_html__('WordPress core sitemap with AJNanda Content Access filtering.', 'ajnanda') : esc_html__('A recognized SEO plugin owns sitemap generation; AJNanda does not apply competing core-sitemap filters.', 'ajnanda'); ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['sitemap']['url']); ?>"><?php esc_html_e('View Sitemap', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('robots.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill is-success"><?php esc_html_e('WordPress policy available', 'ajnanda'); ?></span><?php if ($robots_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $robots_endpoint['result'] ? 'is-success' : ('http_error' === $robots_endpoint['result'] ? 'is-warning' : 'is-externally_unverifiable'); ?>"><?php echo 'success' === $robots_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : ('http_error' === $robots_endpoint['result'] ? esc_html(sprintf(__('Public endpoint returned HTTP %d', 'ajnanda'), $robots_endpoint['code'])) : esc_html__('Endpoint externally unverifiable', 'ajnanda')); ?></span><?php endif; ?><p><?php if ($robots_endpoint && in_array($robots_endpoint['result'], array('tls_error', 'transport_error'), true)) { echo esc_html($robots_endpoint['message']); } else { esc_html_e('Preserves WordPress rules and appends AJNanda’s registry-backed AI crawler policy. A public endpoint warning usually indicates web-server or reverse-proxy routing, not a broken WordPress policy.', 'ajnanda'); } ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['robots']['url']); ?>"><?php esc_html_e('View robots.txt', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('llms.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['llms_txt']['enabled'] ? 'is-success' : 'is-warning'; ?>"><?php echo $discovery_status['llms_txt']['enabled'] ? esc_html__('Enabled', 'ajnanda') : esc_html__('Disabled or delegated', 'ajnanda'); ?></span><?php if ($llms_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $llms_endpoint['result'] ? 'is-success' : ('http_error' === $llms_endpoint['result'] ? 'is-warning' : 'is-externally_unverifiable'); ?>"><?php echo 'success' === $llms_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : ('http_error' === $llms_endpoint['result'] ? esc_html(sprintf(__('Endpoint returned HTTP %d', 'ajnanda'), $llms_endpoint['code'])) : esc_html__('Endpoint externally unverifiable', 'ajnanda')); ?></span><?php endif; ?><p><?php if ($llms_endpoint && in_array($llms_endpoint['result'], array('tls_error', 'transport_error'), true)) { echo esc_html($llms_endpoint['message']); } else { printf(esc_html__('Owner: %s. Uses the Site Profile and excludes content blocked from llms.txt advertising.', 'ajnanda'), esc_html($owner_label($discovery_status['llms_txt']))); } ?></p><?php if ($discovery_status['llms_txt']['enabled']) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['llms_txt']['url']); ?>"><?php esc_html_e('View llms.txt', 'ajnanda'); ?></a><?php endif; ?></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('llms-full.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['llms_full_txt']['enabled'] ? 'is-success' : 'is-warning'; ?>"><?php echo $discovery_status['llms_full_txt']['enabled'] ? esc_html__('Enabled', 'ajnanda') : esc_html__('Disabled or delegated', 'ajnanda'); ?></span><?php if ($llms_full_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $llms_full_endpoint['result'] ? 'is-success' : ('http_error' === $llms_full_endpoint['result'] ? 'is-warning' : 'is-externally_unverifiable'); ?>"><?php echo 'success' === $llms_full_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : ('http_error' === $llms_full_endpoint['result'] ? esc_html(sprintf(__('Endpoint returned HTTP %d', 'ajnanda'), $llms_full_endpoint['code'])) : esc_html__('Endpoint externally unverifiable', 'ajnanda')); ?></span><?php endif; ?><p><?php if ($llms_full_endpoint && in_array($llms_full_endpoint['result'], array('tls_error', 'transport_error'), true)) { echo esc_html($llms_full_endpoint['message']); } else { esc_html_e('Full text of eligible public pages and posts. It follows the same ownership, enablement, and Content Access exclusions as llms.txt.', 'ajnanda'); } ?></p><?php if ($discovery_status['llms_full_txt']['enabled']) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['llms_full_txt']['url']); ?>"><?php esc_html_e('View llms-full.txt', 'ajnanda'); ?></a><?php endif; ?></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('ai.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill is-success"><?php esc_html_e('Enabled', 'ajnanda'); ?></span><?php if ($ai_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $ai_endpoint['result'] ? 'is-success' : 'is-warning'; ?>"><?php echo 'success' === $ai_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : esc_html(sprintf(__('Endpoint returned HTTP %d', 'ajnanda'), $ai_endpoint['code'])); ?></span><?php endif; ?><p><?php esc_html_e('Site-owner policy permitting AI search, citation, and retrieval while requiring attribution and respecting privacy.', 'ajnanda'); ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['ai_txt']['url']); ?>"><?php esc_html_e('View ai.txt', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('security.txt', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill is-success"><?php esc_html_e('RFC 9116', 'ajnanda'); ?></span><?php if ($security_endpoint) : ?><span class="ajnanda-admin-pill <?php echo 'success' === $security_endpoint['result'] ? 'is-success' : 'is-warning'; ?>"><?php echo 'success' === $security_endpoint['result'] ? esc_html__('Public endpoint reachable', 'ajnanda') : esc_html(sprintf(__('Endpoint returned HTTP %d', 'ajnanda'), $security_endpoint['code'])); ?></span><?php endif; ?><p><?php esc_html_e('Canonical vulnerability-reporting contact with English preference and a rolling one-year expiration.', 'ajnanda'); ?></p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url($discovery_status['security_txt']['url']); ?>"><?php esc_html_e('View security.txt', 'ajnanda'); ?></a></div>
        <div class="ajnanda-admin-card"><h2><?php esc_html_e('Schema', 'ajnanda'); ?></h2><span class="ajnanda-admin-pill <?php echo $discovery_status['schema']['active'] ? 'is-success' : 'is-warning'; ?>"><?php echo $discovery_status['schema']['active'] ? esc_html__('AJNanda active', 'ajnanda') : ($discovery_status['schema']['enabled'] ? esc_html__('Delegated', 'ajnanda') : esc_html__('Disabled', 'ajnanda')); ?></span><p><?php printf(esc_html__('Owner: %s. AJNanda schema uses the canonical Site Profile when AJNanda owns this capability.', 'ajnanda'), esc_html($owner_label($discovery_status['schema']))); ?></p></div>
    </div>
    </div>
</details>

<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('Edit discovery files', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('AJNanda normally generates these files from current site data and policies. Enable a custom override only when you want the saved text to replace dynamic generation. Custom overrides do not update automatically when site content changes.', 'ajnanda'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ajnanda-discovery-editors" class="ajnanda-file-workspace">
        <input type="hidden" name="action" value="ajnanda_save_discovery_file_editors">
        <?php wp_nonce_field('ajnanda_save_discovery_file_editors'); ?>
        <?php
        $text_editors = array(
            'robots_txt' => array('label' => 'robots.txt', 'url' => $discovery_status['robots']['url'], 'format' => __('Robots directives', 'ajnanda')),
            'llms_txt' => array('label' => 'llms.txt', 'url' => $discovery_status['llms_txt']['url'], 'format' => __('Markdown', 'ajnanda')),
            'llms_full_txt' => array('label' => 'llms-full.txt', 'url' => $discovery_status['llms_full_txt']['url'], 'format' => __('Markdown', 'ajnanda')),
            'ai_txt' => array('label' => 'ai.txt', 'url' => $discovery_status['ai_txt']['url'], 'format' => __('Plain-text policy fields', 'ajnanda')),
        );
        ?>
        <nav class="ajnanda-file-tree" aria-label="<?php esc_attr_e('Discovery files', 'ajnanda'); ?>">
            <p class="ajnanda-file-tree-heading"><?php esc_html_e('Discovery files', 'ajnanda'); ?></p>
            <?php foreach ($text_editors as $file => $editor) : ?>
                <button type="button" class="ajnanda-file-tree-item<?php echo 'robots_txt' === $file ? ' is-active' : ''; ?>" data-file-target="<?php echo esc_attr($file); ?>" aria-pressed="<?php echo 'robots_txt' === $file ? 'true' : 'false'; ?>"><span aria-hidden="true">├─</span> <?php echo esc_html($editor['label']); ?><?php if (AJNanda_Search_AI_Discovery_Files::custom_enabled($file)) : ?><small><?php esc_html_e('Custom', 'ajnanda'); ?></small><?php endif; ?></button>
            <?php endforeach; ?>
            <button type="button" class="ajnanda-file-tree-item" data-file-target="security_txt" aria-pressed="false"><span aria-hidden="true">└─</span> security.txt</button>
        </nav>
        <div class="ajnanda-file-panels">
        <?php
        foreach ($text_editors as $file => $editor) :
            $custom_content = AJNanda_Search_AI_Discovery_Files::custom_content($file);
        ?>
            <section class="ajnanda-discovery-editor<?php echo 'robots_txt' === $file ? ' is-active' : ''; ?>" data-file-panel="<?php echo esc_attr($file); ?>" <?php echo 'robots_txt' === $file ? '' : 'hidden'; ?>>
                <header class="ajnanda-file-editor-header"><div><h3><?php echo esc_html($editor['label']); ?></h3><span class="ajnanda-admin-pill"><?php echo esc_html($editor['format']); ?></span></div><a href="<?php echo esc_url($editor['url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('View public file', 'ajnanda'); ?> &rarr;</a></header>
                <p><label><input type="checkbox" name="search_ai_custom_<?php echo esc_attr($file); ?>_enabled" value="1" <?php checked(AJNanda_Search_AI_Discovery_Files::custom_enabled($file)); ?>> <?php esc_html_e('Use custom content instead of automatic generation', 'ajnanda'); ?></label></p>
                <textarea class="large-text code" rows="24" name="search_ai_custom_<?php echo esc_attr($file); ?>_content" spellcheck="false"><?php echo esc_textarea($custom_content); ?></textarea>
                <div class="ajnanda-file-editor-actions"><button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'ajnanda'); ?></button><button type="button" class="button" data-undo-discovery><?php esc_html_e('Undo edits', 'ajnanda'); ?></button><button type="button" class="button" data-load-discovery="<?php echo esc_url($editor['url']); ?>"><?php esc_html_e('Load current public file', 'ajnanda'); ?></button><span class="spinner" aria-hidden="true"></span><span class="ajnanda-file-dirty" hidden><?php esc_html_e('Unsaved changes', 'ajnanda'); ?></span></div>
            </section>
        <?php endforeach; ?>

        <section class="ajnanda-discovery-editor" data-file-panel="security_txt" hidden>
            <header class="ajnanda-file-editor-header"><div><h3><?php esc_html_e('security.txt', 'ajnanda'); ?></h3><span class="ajnanda-admin-pill"><?php esc_html_e('RFC 9116 fields', 'ajnanda'); ?></span></div><a href="<?php echo esc_url($discovery_status['security_txt']['url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('View public file', 'ajnanda'); ?> &rarr;</a></header>
            <p><?php esc_html_e('Structured fields preserve RFC 9116 formatting. Expiration must be a future UTC timestamp in RFC 3339 format.', 'ajnanda'); ?></p>
            <table class="form-table" role="presentation">
                <tr><th><label for="search_ai_security_contact"><?php esc_html_e('Contact URI', 'ajnanda'); ?></label></th><td><input class="large-text code" type="text" id="search_ai_security_contact" name="search_ai_security_contact" value="<?php echo esc_attr(get_theme_mod('search_ai_security_contact', home_url('/email-us/'))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_contact', home_url('/email-us/'))); ?>" placeholder="https://example.com/security-contact/ or mailto:security@example.com"></td></tr>
                <tr><th><label for="search_ai_security_expires"><?php esc_html_e('Expires', 'ajnanda'); ?></label></th><td><input class="regular-text code" type="text" id="search_ai_security_expires" name="search_ai_security_expires" value="<?php echo esc_attr(get_theme_mod('search_ai_security_expires', gmdate('Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_expires', gmdate('Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS))); ?>" pattern="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z"></td></tr>
                <tr><th><label for="search_ai_security_languages"><?php esc_html_e('Preferred languages', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_security_languages" name="search_ai_security_languages" value="<?php echo esc_attr(get_theme_mod('search_ai_security_languages', 'en')); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_languages', 'en')); ?>"></td></tr>
                <tr><th><label for="search_ai_security_canonical"><?php esc_html_e('Canonical URL', 'ajnanda'); ?></label></th><td><input class="large-text" type="url" id="search_ai_security_canonical" name="search_ai_security_canonical" value="<?php echo esc_attr(get_theme_mod('search_ai_security_canonical', home_url('/.well-known/security.txt'))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_canonical', home_url('/.well-known/security.txt'))); ?>"></td></tr>
            </table>
            <div class="ajnanda-file-editor-actions"><button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'ajnanda'); ?></button><button type="button" class="button" data-undo-discovery><?php esc_html_e('Undo edits', 'ajnanda'); ?></button><span class="ajnanda-file-dirty" hidden><?php esc_html_e('Unsaved changes', 'ajnanda'); ?></span></div>
        </section>
        </div>
    </form>
</div>

<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('llms.txt Important Pages', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Select the authoritative public pages that help machines understand this site. AJNanda separately includes the 16 most recently updated eligible articles.', 'ajnanda'); ?></p>
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
                <?php foreach ($invalid_important_pages as $invalid_id => $invalid) : ?>
                    <div class="ajnanda-content-picker-item is-invalid" data-content-id="<?php echo esc_attr($invalid_id); ?>"><input type="hidden" name="search_ai_llms_important_page_ids[]" value="<?php echo esc_attr($invalid_id); ?>"><span><strong><?php echo esc_html($invalid['title']); ?></strong> <span class="ajnanda-admin-pill is-warning"><?php esc_html_e('Not discoverable', 'ajnanda'); ?></span><small><?php echo esc_html(AJNanda_Search_AI_Stale_References::reason_label($invalid['reasons'][0] ?? 'missing')); ?> <?php esc_html_e('Your selection is kept but withheld from public AI discovery until this is resolved.', 'ajnanda'); ?></small></span><button type="button" class="button-link-delete" data-remove-content><?php esc_html_e('Remove', 'ajnanda'); ?></button></div>
                <?php endforeach; ?>
                <p class="ajnanda-content-picker-empty" <?php echo ($selected_important_pages || $invalid_important_pages) ? 'hidden' : ''; ?>><?php esc_html_e('No additional Important Pages selected. Only reliable WordPress foundational pages will be included automatically.', 'ajnanda'); ?></p>
            </div>
        </div>
        <?php submit_button(__('Save Important Pages', 'ajnanda')); ?>
    </form>
</div>

<script>
(function () {
    var workspace = document.getElementById('ajnanda-discovery-editors');
    if (workspace) {
        var treeItems = workspace.querySelectorAll('[data-file-target]');
        var panels = workspace.querySelectorAll('[data-file-panel]');
        treeItems.forEach(function (item) {
            item.addEventListener('click', function () {
                treeItems.forEach(function (candidate) { candidate.classList.remove('is-active'); candidate.setAttribute('aria-pressed', 'false'); });
                panels.forEach(function (panel) { panel.classList.remove('is-active'); panel.hidden = true; });
                item.classList.add('is-active'); item.setAttribute('aria-pressed', 'true');
                var panel = workspace.querySelector('[data-file-panel="' + item.dataset.fileTarget + '"]');
                if (panel) { panel.hidden = false; panel.classList.add('is-active'); }
            });
        });
        workspace.querySelectorAll('[data-file-panel]').forEach(function (panel) {
            var fields = panel.querySelectorAll('textarea, input:not([type="hidden"]):not([type="submit"])');
            var dirty = panel.querySelector('.ajnanda-file-dirty');
            function updateDirty() {
                var changed = Array.prototype.some.call(fields, function (field) {
                    if ('checkbox' === field.type) { return field.checked !== field.defaultChecked; }
                    return field.value !== (field.dataset.savedValue !== undefined ? field.dataset.savedValue : field.defaultValue);
                });
                if (dirty) { dirty.hidden = ! changed; }
            }
            fields.forEach(function (field) { field.addEventListener('input', updateDirty); field.addEventListener('change', updateDirty); });
            var undo = panel.querySelector('[data-undo-discovery]');
            if (undo) { undo.addEventListener('click', function () {
                fields.forEach(function (field) {
                    if ('checkbox' === field.type) { field.checked = field.defaultChecked; }
                    else { field.value = field.dataset.savedValue !== undefined ? field.dataset.savedValue : field.defaultValue; }
                });
                updateDirty();
            }); }
        });
    }
    document.querySelectorAll('[data-load-discovery]').forEach(function (button) {
        button.addEventListener('click', function () {
            var editor = button.closest('.ajnanda-discovery-editor');
            var textarea = editor.querySelector('textarea');
            var spinner = editor.querySelector('.spinner');
            spinner.classList.add('is-active'); button.disabled = true;
            fetch(button.dataset.loadDiscovery, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (response) { if (!response.ok) { throw new Error('HTTP ' + response.status); } return response.text(); })
                .then(function (content) { textarea.value = content; textarea.dispatchEvent(new Event('input', { bubbles: true })); })
                .catch(function (error) { window.alert(<?php echo wp_json_encode(__('The public file could not be loaded: ', 'ajnanda')); ?> + error.message); })
                .finally(function () { spinner.classList.remove('is-active'); button.disabled = false; });
        });
    });
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
