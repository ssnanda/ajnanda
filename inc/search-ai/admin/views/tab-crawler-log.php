<?php
if (! defined('ABSPATH')) { exit; }
$categories = AJNanda_Search_AI_Crawler_Registry::categories();
$states = array('verified' => __('Verified', 'ajnanda'), 'reported_only' => __('Reported only', 'ajnanda'), 'failed' => __('Verification failed', 'ajnanda'), 'not_verifiable' => __('Not verifiable', 'ajnanda'), 'pending' => __('Pending', 'ajnanda'));
$query = $crawler_log['query']; $aggregates = $crawler_log['aggregates']; $filters = $query['filters'];
$base = add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'crawler-log'), admin_url('admin.php'));
$logging_enabled = (bool) AJNanda_Search_AI_Settings::get('search_ai_crawler_logging_enabled');
?>
<div class="notice notice-info inline"><p><?php esc_html_e('This log only observes crawler requests that reach WordPress/PHP. Requests served entirely by a CDN or edge cache are not visible here. No observed activity does not mean a crawler never visited.', 'ajnanda'); ?></p></div>
<?php if (! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) : ?>
<div class="notice notice-error inline"><p><?php esc_html_e('The crawler event table is unavailable. Public requests will continue normally without logging.', 'ajnanda'); ?></p></div>
<?php elseif (! $logging_enabled) : ?>
<div class="ajnanda-admin-section">
    <span class="ajnanda-admin-pill is-warning"><?php esc_html_e('Logging disabled', 'ajnanda'); ?></span>
    <h2><?php esc_html_e('Crawler logging is disabled', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('AJNanda is not currently recording crawler requests, so zero requests must not be interpreted as observed traffic.', 'ajnanda'); ?></p>
    <a class="button button-primary" href="<?php echo esc_url(add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'settings'), admin_url('admin.php'))); ?>"><?php esc_html_e('Configure Crawler Logging', 'ajnanda'); ?></a>
</div>
<?php else : ?>
<form method="get" class="ajnanda-crawler-filters">
    <input type="hidden" name="page" value="<?php echo esc_attr(AJNanda_Search_AI_Admin::PAGE_SLUG); ?>"><input type="hidden" name="tab" value="crawler-log">
    <label><?php esc_html_e('Period', 'ajnanda'); ?> <select name="days"><?php foreach (array(1, 7, 30, 90, 180, 365) as $days) : ?><option value="<?php echo esc_attr($days); ?>" <?php selected($filters['days'], $days); ?>><?php printf(esc_html__('%d days', 'ajnanda'), $days); ?></option><?php endforeach; ?></select></label>
    <label><?php esc_html_e('Crawler', 'ajnanda'); ?> <select name="provider"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php $provider_options = array(); foreach (AJNanda_Search_AI_Crawler_Registry::all() as $crawler) { $provider_options[sanitize_key($crawler['provider'])] = $crawler['provider']; } foreach ($provider_options as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['provider'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?><option value="unknown" <?php selected($filters['provider'], 'unknown'); ?>><?php esc_html_e('Unknown', 'ajnanda'); ?></option></select></label>
    <label><?php esc_html_e('Category', 'ajnanda'); ?> <select name="category"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php foreach ($categories as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['category'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?><option value="unknown" <?php selected($filters['category'], 'unknown'); ?>><?php esc_html_e('Unknown', 'ajnanda'); ?></option></select></label>
    <label><?php esc_html_e('Verification', 'ajnanda'); ?> <select name="verification"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php foreach ($states as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['verification'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
    <button class="button"><?php esc_html_e('Filter', 'ajnanda'); ?></button>
</form>

<?php if (! $aggregates['total']) : ?>
<div class="ajnanda-admin-section">
    <span class="ajnanda-admin-pill is-success"><?php esc_html_e('Logging active', 'ajnanda'); ?></span>
    <h2><?php esc_html_e('No crawler requests observed for this selection', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('Crawler logging is active, but no matching crawler requests reached WordPress during the selected period and filters.', 'ajnanda'); ?></p>
</div>
<?php else : ?>
<div class="ajnanda-readiness-cards ajnanda-crawler-summary">
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Requests', 'ajnanda'); ?></span><strong><?php echo esc_html(number_format_i18n($aggregates['total'])); ?></strong></div>
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Crawler identities', 'ajnanda'); ?></span><strong><?php echo esc_html(number_format_i18n($aggregates['identity_count'])); ?></strong></div>
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Latest observation', 'ajnanda'); ?></span><strong><?php echo esc_html(get_date_from_gmt($aggregates['latest'], 'M j, Y H:i')); ?></strong></div>
</div>

<div class="ajnanda-admin-grid ajnanda-crawler-aggregates">
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('Activity by crawler', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['providers'] as $row) : ?><li><strong><?php echo esc_html($row['reported_identity']); ?></strong> <?php echo esc_html(number_format_i18n($row['count'])); ?><small><?php echo esc_html(get_date_from_gmt($row['latest'], 'M j, Y H:i')); ?></small></li><?php endforeach; ?></ul><h3><?php esc_html_e('By category', 'ajnanda'); ?></h3><ul><?php foreach ($aggregates['categories'] as $row) : ?><li><strong><?php echo esc_html($categories[$row['category']] ?? ucfirst($row['category'])); ?></strong> <?php echo esc_html(number_format_i18n($row['count'])); ?></li><?php endforeach; ?></ul></section>
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('Verification', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['verification'] as $row) : ?><li><strong><?php echo esc_html($states[$row['verification_state']] ?? $row['verification_state']); ?></strong> <?php echo esc_html(number_format_i18n($row['count'])); ?></li><?php endforeach; ?></ul></section>
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('Most-requested paths', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['paths'] as $row) : ?><li><code><?php echo esc_html($row['request_path']); ?></code> <?php echo esc_html(number_format_i18n($row['count'])); ?></li><?php endforeach; ?></ul></section>
</div>

<table class="widefat striped ajnanda-crawler-events"><thead><tr><th><?php esc_html_e('Time', 'ajnanda'); ?></th><th><?php esc_html_e('Crawler', 'ajnanda'); ?></th><th><?php esc_html_e('Category', 'ajnanda'); ?></th><th><?php esc_html_e('Verification', 'ajnanda'); ?></th><th><?php esc_html_e('Requested path', 'ajnanda'); ?></th><th><?php esc_html_e('Status', 'ajnanda'); ?></th><th><?php esc_html_e('Source', 'ajnanda'); ?></th></tr></thead><tbody>
<?php if (! $query['rows']) : ?><tr><td colspan="7"><?php esc_html_e('No crawler requests were observed for these filters.', 'ajnanda'); ?></td></tr><?php else : foreach ($query['rows'] as $event) : ?><tr><td><a href="<?php echo esc_url(add_query_arg(array_merge($filters, array('event' => $event['id'])), $base)); ?>"><?php echo esc_html(get_date_from_gmt($event['observed_at'], 'M j, Y H:i:s')); ?></a></td><td><strong><?php echo esc_html($event['reported_identity']); ?></strong><small><?php echo esc_html($event['provider_key']); ?></small></td><td><?php echo esc_html($categories[$event['category']] ?? ucfirst($event['category'])); ?></td><td><span class="ajnanda-admin-pill is-<?php echo esc_attr($event['verification_state']); ?>"><?php echo esc_html($states[$event['verification_state']] ?? $event['verification_state']); ?></span></td><td><code><?php echo esc_html($event['request_path']); ?></code></td><td><?php echo $event['http_status'] ? esc_html($event['http_status']) : '—'; ?></td><td><?php echo esc_html($event['source']); ?></td></tr><?php endforeach; endif; ?>
</tbody></table>
<?php if ($query['pages'] > 1) : ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(array('base' => add_query_arg(array_merge($filters, array('paged' => '%#%')), $base), 'current' => $filters['paged'], 'total' => $query['pages']))); ?></div></div><?php endif; ?>

<?php if ($crawler_event) : ?><div class="ajnanda-admin-section"><h2><?php esc_html_e('Crawler event details', 'ajnanda'); ?></h2><dl class="ajnanda-event-details"><?php foreach (array('observed_at' => __('Timestamp (UTC)', 'ajnanda'), 'reported_identity' => __('Reported identity', 'ajnanda'), 'provider_key' => __('Provider', 'ajnanda'), 'category' => __('Category', 'ajnanda'), 'verification_state' => __('Verification', 'ajnanda'), 'verification_method' => __('Verification method', 'ajnanda'), 'verification_reason' => __('Verification reason', 'ajnanda'), 'request_path' => __('Requested path', 'ajnanda'), 'http_method' => __('HTTP method', 'ajnanda'), 'http_status' => __('HTTP status', 'ajnanda'), 'ip_value' => __('Stored IP value', 'ajnanda'), 'ip_mode' => __('IP privacy mode', 'ajnanda'), 'source' => __('Source', 'ajnanda'), 'user_agent' => __('Reported User-Agent', 'ajnanda')) as $key => $label) : ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($crawler_event[$key]); ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>
<?php endif; ?>
<?php endif; ?>
