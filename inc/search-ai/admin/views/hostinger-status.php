<?php
if (! defined('ABSPATH')) { exit; }
$hostinger = AJNanda_Search_AI_Hostinger::status();
if (! $hostinger['relevant']) { return; }
$current_tab = isset($tab) && 'ai-discovery' === $tab ? 'ai-discovery' : 'discovery-files';
$flag_label = static function ($value) {
    return null === $value ? __('Unknown / feature unavailable', 'ajnanda') : ($value ? __('Enabled in Hostinger settings', 'ajnanda') : __('Disabled in Hostinger settings', 'ajnanda'));
};
$notice = sanitize_key(wp_unslash($_GET['ajnanda_hostinger'] ?? ''));
$index_report = AJNanda_Search_AI_Hostinger_Index::report();
?>
<section class="ajnanda-admin-section">
    <h2><?php esc_html_e('Hostinger AI integration', 'ajnanda'); ?></h2>
    <?php if ('web2agent-ok' === $notice) : ?>
        <div class="notice notice-success inline"><p><?php esc_html_e('Web2Agent opt-in saved through Hostinger Tools. Hostinger provisions and indexes the site separately; that can take a while.', 'ajnanda'); ?></p></div>
    <?php elseif ('web2agent-error' === $notice) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html(get_transient('ajnanda_hostinger_notice') ?: __('Web2Agent could not be enabled from AJNanda. Use the Hostinger Tools screen.', 'ajnanda')); ?></p></div>
    <?php elseif ('index-error' === $notice && $index_report) : ?>
        <div class="notice notice-error inline"><p><?php echo esc_html($index_report['error']); ?></p></div>
    <?php endif; ?>
    <p><?php echo esc_html($hostinger['platform'] ? __('Hostinger platform detected.', 'ajnanda') : __('Hostinger Tools detected; Hostinger hosting is not confirmed in this request.', 'ajnanda')); ?>
        <?php echo esc_html($hostinger['active'] ? sprintf(__('Tools version: %s', 'ajnanda'), $hostinger['version']) : __('Hostinger Tools is not active.', 'ajnanda')); ?></p>
    <p><strong><?php esc_html_e('Create LLMs.txt file:', 'ajnanda'); ?></strong> <?php echo esc_html($flag_label($hostinger['llms_enabled'])); ?><br>
        <strong><?php esc_html_e('Web2Agent:', 'ajnanda'); ?></strong> <?php echo esc_html($flag_label($hostinger['web2agent_enabled'])); ?></p>
    <?php if ($hostinger['temporary_domain']) : ?><p><?php esc_html_e('Hostinger disables LLM Optimization on temporary domains. Connect a domain in Hostinger first.', 'ajnanda'); ?></p><?php endif; ?>
    <p><?php esc_html_e('AJNanda remains the source for curated llms.txt and Search & AI policies. Keep Hostinger’s Create LLMs.txt file toggle off: its physical file can override AJNanda, and safe content synchronization is not supported by the reviewed Hostinger implementation.', 'ajnanda'); ?></p>
    <?php if ($hostinger['llms_enabled']) : ?><div class="notice notice-warning inline"><p><?php esc_html_e('Potential llms.txt conflict: Hostinger’s generator is enabled. Disable its individual file toggle and review the llms.txt integrity check in Discovery Files. Back up and review any remaining physical file before removing it.', 'ajnanda'); ?></p></div><?php endif; ?>

    <?php if (current_user_can('manage_options')) : ?>
        <p>
            <?php if ($hostinger['available'] && false === $hostinger['web2agent_enabled']) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                    <input type="hidden" name="action" value="ajnanda_enable_hostinger_web2agent">
                    <input type="hidden" name="ajnanda_tab" value="<?php echo esc_attr($current_tab); ?>">
                    <?php wp_nonce_field('ajnanda_enable_hostinger_web2agent'); ?>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Enable Web2Agent', 'ajnanda'); ?></button>
                </form>
            <?php endif; ?>
            <?php if ($hostinger['settings_url']) : ?>
                <a class="button" href="<?php echo esc_url($hostinger['settings_url']); ?>"><?php esc_html_e('Open Hostinger LLM Optimization', 'ajnanda'); ?></a>
            <?php endif; ?>
        </p>
        <p class="description"><?php esc_html_e('Enabling from here writes only Hostinger’s Web2Agent opt-in, through Hostinger’s own settings API, which fires the same opt-in notification its screen does. The file generator is never changed. Turn Web2Agent off, or change file generation, on the Hostinger Tools screen — its master toggle sets both at once.', 'ajnanda'); ?></p>
    <?php endif; ?>

    <?php if ($hostinger['endpoint']) : ?>
        <p><strong><?php esc_html_e('Hostinger MCP endpoint (derived):', 'ajnanda'); ?></strong> <code><?php echo esc_html($hostinger['endpoint']); ?></code></p>
        <p class="description"><?php echo esc_html(AJNanda_Search_AI_Settings::get('search_ai_llms_advertise_agent', true) ? __('AJNanda advertises this endpoint in its own llms.txt, so Hostinger’s file generator is not needed for discovery.', 'ajnanda') : __('Advertising this endpoint in AJNanda’s llms.txt is currently disabled in Settings.', 'ajnanda')); ?></p>
    <?php endif; ?>
    <p class="description"><?php esc_html_e('Saved opt-in does not confirm remote provisioning or endpoint health. AJNanda’s policies remain unchanged; Hostinger crawls rendered pages independently, so enforcement by its remote service requires production verification.', 'ajnanda'); ?></p>

    <?php if ($hostinger['endpoint'] && current_user_can('manage_options')) : ?>
        <h3><?php esc_html_e('Web2Agent index coverage', 'ajnanda'); ?></h3>
        <p class="description"><?php esc_html_e('Asks the public Web2Agent endpoint which pages it holds and compares that with the pages AJNanda advertises. This contacts Hostinger only when you run it, reads only public content, and changes nothing.', 'ajnanda'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ajnanda_probe_hostinger_index">
            <input type="hidden" name="ajnanda_tab" value="<?php echo esc_attr($current_tab); ?>">
            <?php wp_nonce_field('ajnanda_probe_hostinger_index'); ?>
            <button type="submit" class="button"><?php echo esc_html($index_report ? __('Re-check index coverage', 'ajnanda') : __('Check index coverage', 'ajnanda')); ?></button>
        </form>
        <?php if ($index_report && ! $index_report['error']) : ?>
            <?php $covered = count($index_report['covered']); $expected_count = (int) $index_report['expected_count']; ?>
            <p><strong><?php echo esc_html(sprintf(__('Indexed %1$d of %2$d advertised pages.', 'ajnanda'), $covered, $expected_count)); ?></strong>
                <?php echo esc_html(sprintf(__('Ran %d queries.', 'ajnanda'), (int) $index_report['queries'])); ?>
                <?php if ($index_report['truncated']) { esc_html_e('The check stopped at its time limit, so coverage may be understated.', 'ajnanda'); } ?>
                <?php if (! empty($index_report['sampled'])) { esc_html_e('More pages are advertised than queries were run, so a page listed as missing may instead have ranked below the results returned.', 'ajnanda'); } ?>
                <br><?php echo esc_html(sprintf(__('Checked %s ago.', 'ajnanda'), human_time_diff((int) $index_report['checked_at']))); ?></p>
            <?php if ($index_report['missing']) : ?>
                <div class="notice notice-warning inline"><p><strong><?php esc_html_e('Advertised but not found in Hostinger’s index:', 'ajnanda'); ?></strong><br>
                    <?php $shown = 0; foreach ($index_report['missing'] as $missing_url => $missing_title) : if ($shown++ >= 20) { break; } ?>
                        <?php echo esc_html($missing_title ?: $missing_url); ?> &mdash; <code><?php echo esc_html($missing_url); ?></code><br>
                    <?php endforeach; ?>
                    <?php if (count($index_report['missing']) > 20) : ?><?php echo esc_html(sprintf(__('…and %d more.', 'ajnanda'), count($index_report['missing']) - 20)); ?><br><?php endif; ?>
                    <?php esc_html_e('Hostinger crawls rendered pages, not llms.txt, so a missing page is usually a crawl or rendering gap rather than a policy exclusion.', 'ajnanda'); ?></p></div>
            <?php else : ?>
                <p><?php esc_html_e('Every advertised page was found in Hostinger’s index.', 'ajnanda'); ?></p>
            <?php endif; ?>
            <?php if ($index_report['extra']) : ?>
                <p><strong><?php esc_html_e('Indexed but not advertised by AJNanda:', 'ajnanda'); ?></strong><br>
                    <?php foreach ($index_report['extra'] as $extra_url) : ?><code><?php echo esc_html($extra_url); ?></code><br><?php endforeach; ?>
                    <?php esc_html_e('Review these against the Content Access policy. Hostinger indexes them independently of AJNanda’s exclusions.', 'ajnanda'); ?></p>
            <?php endif; ?>
        <?php elseif ($index_report && $index_report['error']) : ?>
            <p><?php echo esc_html(sprintf(__('Last check failed: %s', 'ajnanda'), $index_report['error'])); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</section>
