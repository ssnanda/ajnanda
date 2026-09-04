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
$important_nonce = wp_create_nonce('ajnanda_save_llms_important_pages');
$available_important_pages = array_values(array_filter(get_pages(array('post_status' => 'publish', 'sort_column' => 'menu_order,post_title')), static function ($page) {
    return AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($page->ID, 'llms_txt')['eligible'];
}));
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
                <div class="ajnanda-file-editor-actions"><button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'ajnanda'); ?></button><button type="button" class="button" data-undo-discovery><?php esc_html_e('Undo edits', 'ajnanda'); ?></button><span class="spinner" aria-hidden="true"></span><span class="ajnanda-file-dirty" hidden><?php esc_html_e('Unsaved changes', 'ajnanda'); ?></span></div>
                <textarea class="large-text code" rows="20" name="search_ai_custom_<?php echo esc_attr($file); ?>_content" spellcheck="false" data-public-url="<?php echo esc_url($editor['url']); ?>"><?php echo esc_textarea($custom_content); ?></textarea>
            </section>
        <?php endforeach; ?>

        <section class="ajnanda-discovery-editor" data-file-panel="security_txt" hidden>
            <header class="ajnanda-file-editor-header"><div><h3><?php esc_html_e('security.txt', 'ajnanda'); ?></h3><span class="ajnanda-admin-pill"><?php esc_html_e('RFC 9116 fields', 'ajnanda'); ?></span></div><a href="<?php echo esc_url($discovery_status['security_txt']['url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('View public file', 'ajnanda'); ?> &rarr;</a></header>
            <p><?php esc_html_e('Structured fields preserve RFC 9116 formatting. Expiration must be a future UTC timestamp in RFC 3339 format.', 'ajnanda'); ?></p>
            <div class="ajnanda-file-editor-actions"><button type="submit" class="button button-primary"><?php esc_html_e('Save changes', 'ajnanda'); ?></button><button type="button" class="button" data-undo-discovery><?php esc_html_e('Undo edits', 'ajnanda'); ?></button><span class="ajnanda-file-dirty" hidden><?php esc_html_e('Unsaved changes', 'ajnanda'); ?></span></div>
            <table class="form-table" role="presentation">
                <tr><th><label for="search_ai_security_contact"><?php esc_html_e('Contact URI', 'ajnanda'); ?></label></th><td><input class="large-text code" type="text" id="search_ai_security_contact" name="search_ai_security_contact" value="<?php echo esc_attr(get_theme_mod('search_ai_security_contact', home_url('/email-us/'))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_contact', home_url('/email-us/'))); ?>" placeholder="https://example.com/security-contact/ or mailto:security@example.com"></td></tr>
                <tr><th><label for="search_ai_security_expires"><?php esc_html_e('Expires', 'ajnanda'); ?></label></th><td><input class="regular-text code" type="text" id="search_ai_security_expires" name="search_ai_security_expires" value="<?php echo esc_attr(get_theme_mod('search_ai_security_expires', gmdate('Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_expires', gmdate('Y-m-d\TH:i:s\Z', time() + YEAR_IN_SECONDS))); ?>" pattern="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z"></td></tr>
                <tr><th><label for="search_ai_security_languages"><?php esc_html_e('Preferred languages', 'ajnanda'); ?></label></th><td><input class="regular-text" type="text" id="search_ai_security_languages" name="search_ai_security_languages" value="<?php echo esc_attr(get_theme_mod('search_ai_security_languages', 'en')); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_languages', 'en')); ?>"></td></tr>
                <tr><th><label for="search_ai_security_canonical"><?php esc_html_e('Canonical URL', 'ajnanda'); ?></label></th><td><input class="large-text" type="url" id="search_ai_security_canonical" name="search_ai_security_canonical" value="<?php echo esc_attr(get_theme_mod('search_ai_security_canonical', home_url('/.well-known/security.txt'))); ?>" data-saved-value="<?php echo esc_attr(get_theme_mod('search_ai_security_canonical', home_url('/.well-known/security.txt'))); ?>"></td></tr>
            </table>
        </section>
        </div>
    </form>
</div>

<div class="ajnanda-admin-section">
    <h2><?php esc_html_e('llms.txt Important Pages', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Check the authoritative pages to include. Changes save automatically. AJNanda separately includes the 16 most recently updated eligible articles.', 'ajnanda'); ?> <span id="ajnanda-important-save-status" aria-live="polite"></span></p>
    <div class="ajnanda-important-page-checklist" id="ajnanda-important-pages" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr($important_nonce); ?>">
        <?php foreach ($available_important_pages as $page) : ?>
            <label><input type="checkbox" value="<?php echo esc_attr($page->ID); ?>" <?php checked(in_array($page->ID, AJNanda_Search_AI_Important_Pages::stored_ids(), true)); ?>><span><strong><?php echo esc_html(get_the_title($page) ?: __('(no title)', 'ajnanda')); ?></strong><small><?php echo esc_html(wp_make_link_relative(get_permalink($page))); ?></small></span></label>
        <?php endforeach; ?>
        <?php if (! $available_important_pages) : ?><p><?php esc_html_e('No eligible published pages are currently available.', 'ajnanda'); ?></p><?php endif; ?>
    </div>
</div>

<script>
(function () {
    var workspace = document.getElementById('ajnanda-discovery-editors');
    if (workspace) {
        var treeItems = workspace.querySelectorAll('[data-file-target]');
        var panels = workspace.querySelectorAll('[data-file-panel]');
        function loadPublicFile(panel) {
            var textarea = panel && panel.querySelector('textarea[data-public-url]');
            if (! textarea || textarea.dataset.loaded || textarea.value.trim()) { return; }
            textarea.dataset.loaded = 'loading';
            var spinner = panel.querySelector('.spinner');
            if (spinner) { spinner.classList.add('is-active'); }
            fetch(textarea.dataset.publicUrl, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (response) { if (! response.ok) { throw new Error('HTTP ' + response.status); } return response.text(); })
                .then(function (content) { textarea.value = content; textarea.dataset.loaded = 'yes'; textarea.dispatchEvent(new Event('input', { bubbles: true })); })
                .catch(function (error) { textarea.dataset.loaded = ''; window.alert(<?php echo wp_json_encode(__('The public file could not be loaded: ', 'ajnanda')); ?> + error.message); })
                .finally(function () { if (spinner) { spinner.classList.remove('is-active'); } });
        }
        treeItems.forEach(function (item) {
            item.addEventListener('click', function () {
                treeItems.forEach(function (candidate) { candidate.classList.remove('is-active'); candidate.setAttribute('aria-pressed', 'false'); });
                panels.forEach(function (panel) { panel.classList.remove('is-active'); panel.hidden = true; });
                item.classList.add('is-active'); item.setAttribute('aria-pressed', 'true');
                var panel = workspace.querySelector('[data-file-panel="' + item.dataset.fileTarget + '"]');
                if (panel) { panel.hidden = false; panel.classList.add('is-active'); loadPublicFile(panel); }
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
        loadPublicFile(workspace.querySelector('[data-file-panel].is-active'));
    }
    var checklist = document.getElementById('ajnanda-important-pages');
    if (checklist) {
        var saveTimer; var status = document.getElementById('ajnanda-important-save-status');
        checklist.addEventListener('change', function () {
            clearTimeout(saveTimer); status.textContent = <?php echo wp_json_encode(__('Saving…', 'ajnanda')); ?>;
            saveTimer = setTimeout(function () {
                var data = new FormData(); data.append('action', 'ajnanda_save_llms_important_pages'); data.append('_ajax_nonce', checklist.dataset.nonce);
                checklist.querySelectorAll('input:checked').forEach(function (checkbox) { data.append('ids[]', checkbox.value); });
                fetch(checklist.dataset.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) { if (! payload.success) { throw new Error(payload.data && payload.data.message ? payload.data.message : 'Save failed'); } status.textContent = <?php echo wp_json_encode(__('Saved.', 'ajnanda')); ?>; })
                    .catch(function () { status.textContent = <?php echo wp_json_encode(__('Could not save. Try again.', 'ajnanda')); ?>; });
            }, 250);
        });
    }
})();
</script>
