<?php if (! defined('ABSPATH')) { exit; } ?>
<div class="ajnanda-admin-section">
    <?php if ('stale' === $roadmap['cache']) : ?>
        <div class="notice notice-warning inline"><p><?php esc_html_e('The live roadmap could not be refreshed. Showing the last successfully loaded copy.', 'ajnanda'); ?> <?php echo esc_html($roadmap['warning']); ?></p></div>
    <?php elseif ('unavailable' === $roadmap['cache']) : ?>
        <div class="notice notice-error inline"><p><?php esc_html_e('Roadmap could not be loaded from GitHub.', 'ajnanda'); ?> <?php echo esc_html($roadmap['warning']); ?></p></div>
    <?php endif; ?>

    <?php if (! empty($roadmap['markdown'])) : ?>
        <article class="ajnanda-roadmap-content"><?php echo AJNanda_Search_AI_Roadmap::render_markdown($roadmap['markdown']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
    <?php else : ?>
        <p><a class="button button-primary" href="<?php echo esc_url(AJNanda_Search_AI_Roadmap::SOURCE_URL); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View the roadmap on GitHub', 'ajnanda'); ?></a></p>
    <?php endif; ?>

    <hr>
    <p class="description">
        <?php esc_html_e('Roadmap source: GitHub', 'ajnanda'); ?> ·
        <a href="<?php echo esc_url(AJNanda_Search_AI_Roadmap::SOURCE_URL); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View source on GitHub', 'ajnanda'); ?></a>
        <?php if (! empty($roadmap['refreshed'])) : ?> · <?php printf(esc_html__('Last refreshed: %s', 'ajnanda'), esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $roadmap['refreshed']))); ?><?php endif; ?>
    </p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="ajnanda_refresh_search_ai_roadmap">
        <?php wp_nonce_field('ajnanda_refresh_search_ai_roadmap'); ?>
        <?php submit_button(__('Refresh Roadmap', 'ajnanda'), 'secondary', 'submit', false); ?>
    </form>
</div>
