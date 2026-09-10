<?php
if (! defined('ABSPATH')) { exit; }
$hostinger = AJNanda_Search_AI_Hostinger::status();
if (! $hostinger['relevant']) { return; }
$flag_label = static function ($value) {
    return null === $value ? __('Unknown / feature unavailable', 'ajnanda') : ($value ? __('Enabled in Hostinger settings', 'ajnanda') : __('Disabled in Hostinger settings', 'ajnanda'));
};
?>
<section class="ajnanda-admin-section">
    <h2><?php esc_html_e('Hostinger AI integration', 'ajnanda'); ?></h2>
    <p><?php echo esc_html($hostinger['platform'] ? __('Hostinger platform detected.', 'ajnanda') : __('Hostinger Tools detected; Hostinger hosting is not confirmed in this request.', 'ajnanda')); ?>
        <?php echo esc_html($hostinger['active'] ? sprintf(__('Tools version: %s', 'ajnanda'), $hostinger['version']) : __('Hostinger Tools is not active.', 'ajnanda')); ?></p>
    <p><strong><?php esc_html_e('Create LLMs.txt file:', 'ajnanda'); ?></strong> <?php echo esc_html($flag_label($hostinger['llms_enabled'])); ?><br>
        <strong><?php esc_html_e('Web2Agent:', 'ajnanda'); ?></strong> <?php echo esc_html($flag_label($hostinger['web2agent_enabled'])); ?></p>
    <?php if ($hostinger['temporary_domain']) : ?><p><?php esc_html_e('Hostinger disables LLM Optimization on temporary domains. Connect a domain in Hostinger first.', 'ajnanda'); ?></p><?php endif; ?>
    <p><?php esc_html_e('AJNanda remains the source for curated llms.txt and Search & AI policies. Keep Hostinger’s Create LLMs.txt file toggle off: its physical file can override AJNanda, and safe content synchronization is not supported by the reviewed Hostinger implementation.', 'ajnanda'); ?></p>
    <?php if ($hostinger['llms_enabled']) : ?><div class="notice notice-warning inline"><p><?php esc_html_e('Potential llms.txt conflict: Hostinger’s generator is enabled. Disable its individual file toggle and review the llms.txt integrity check in Discovery Files. Back up and review any remaining physical file before removing it.', 'ajnanda'); ?></p></div><?php endif; ?>
    <?php if ($hostinger['settings_url'] && current_user_can('manage_options')) : ?>
        <p><a class="button" href="<?php echo esc_url($hostinger['settings_url']); ?>"><?php echo esc_html($hostinger['available'] && ! $hostinger['web2agent_enabled'] ? __('Enable Web2Agent in Hostinger…', 'ajnanda') : __('Open Hostinger LLM Optimization', 'ajnanda')); ?></a></p>
        <p class="description"><?php esc_html_e('Use the individual Web2Agent toggle and complete Hostinger’s opt-in or consent steps there. The section’s master toggle can also enable Hostinger’s file generator. AJNanda does not change either setting.', 'ajnanda'); ?></p>
    <?php endif; ?>
    <?php if ($hostinger['endpoint']) : ?>
        <p><strong><?php esc_html_e('Hostinger MCP endpoint (derived):', 'ajnanda'); ?></strong> <a href="<?php echo esc_url($hostinger['endpoint']); ?>" target="_blank" rel="noopener noreferrer"><code><?php echo esc_html($hostinger['endpoint']); ?></code></a></p>
    <?php endif; ?>
    <p class="description"><?php esc_html_e('Saved opt-in does not confirm remote provisioning or endpoint health. Verify with Hostinger and an MCP client on the live domain. AJNanda’s policies remain unchanged; enforcement by Hostinger’s remote service requires production verification.', 'ajnanda'); ?></p>
</section>
