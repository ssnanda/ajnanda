<?php
if (! defined('ABSPATH')) { exit; }
$categories = AJNanda_Search_AI_Crawler_Registry::categories();
$registry = AJNanda_Search_AI_Crawler_Registry::all();
$states = array('verified' => __('Verified', 'ajnanda'), 'reported_only' => __('Reported only', 'ajnanda'), 'failed' => __('Verification failed', 'ajnanda'), 'not_verifiable' => __('Not verifiable', 'ajnanda'), 'pending' => __('Pending', 'ajnanda'));
$robots_labels = array('allowed' => __('Allowed', 'ajnanda'), 'blocked' => __('Blocked', 'ajnanda'), 'ignores_robots' => __('Ignores robots.txt', 'ajnanda'), 'not_managed' => __('No rule', 'ajnanda'));
$robots_pills = array('allowed' => 'is-success', 'blocked' => 'is-failed', 'ignores_robots' => 'is-warning', 'not_managed' => 'is-pending');
$query = $crawler_log['query']; $aggregates = $crawler_log['aggregates']; $filters = $query['filters'];
$crawlers = $crawler_log['crawlers'] ?? array();
$sessions = $crawler_log['sessions'] ?? array('rows' => array(), 'total' => 0, 'pages' => 1, 'truncated' => false, 'per_identity' => array());
$view = $filters['view'];
$base = add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'crawler-log'), admin_url('admin.php'));
$settings_url = add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'settings'), admin_url('admin.php'));
$discovery_url = add_query_arg(array('page' => AJNanda_Search_AI_Admin::PAGE_SLUG, 'tab' => 'ai-discovery'), admin_url('admin.php'));
$logging_enabled = (bool) AJNanda_Search_AI_Settings::get('search_ai_crawler_logging_enabled');
$ip_mode = AJNanda_Search_AI_Settings::get('search_ai_crawler_ip_mode', 'anonymized');
$link = static function ($args) use ($base, $filters) {
    return add_query_arg(array_filter(array_merge($filters, array('paged' => 1, 'event' => null), $args), static function ($v) { return null !== $v && '' !== $v; }), $base);
};
$category_label = static function ($key) use ($categories) { return $categories[$key] ?? ('unknown' === $key ? __('Unknown', 'ajnanda') : ucfirst(str_replace('_', ' ', $key))); };
$duration = static function ($seconds) {
    $seconds = (int) $seconds;
    if ($seconds < 60) { return $seconds . 's'; }
    if ($seconds < HOUR_IN_SECONDS) { return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's'; }
    return floor($seconds / HOUR_IN_SECONDS) . 'h ' . floor(($seconds % HOUR_IN_SECONDS) / 60) . 'm';
};
$status_class = static function ($status) { return (int) $status >= 400 ? 'is-failed' : ((int) $status >= 300 ? 'is-warning' : 'is-pending'); };
?>
<div class="notice notice-info inline"><p><?php esc_html_e('Only requests that reach WordPress/PHP are logged; CDN- or edge-cached hits are not visible here.', 'ajnanda'); ?></p></div>
<?php if (! AJNanda_Search_AI_Crawler_Log_Store::table_exists()) : ?>
<div class="notice notice-error inline"><p><?php esc_html_e('The crawler event table is unavailable. Public requests will continue normally without logging.', 'ajnanda'); ?></p></div>
<?php elseif (! $logging_enabled) : ?>
<div class="ajnanda-admin-section">
    <span class="ajnanda-admin-pill is-warning"><?php esc_html_e('Logging disabled', 'ajnanda'); ?></span>
    <h2><?php esc_html_e('Crawler logging is disabled', 'ajnanda'); ?></h2>
    <p><?php esc_html_e('AJNanda is not currently recording crawler requests, so zero requests must not be interpreted as observed traffic.', 'ajnanda'); ?></p>
    <a class="button button-primary" href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure Crawler Logging', 'ajnanda'); ?></a>
</div>
<?php else : ?>
<form method="get" class="ajnanda-crawler-filters">
    <input type="hidden" name="page" value="<?php echo esc_attr(AJNanda_Search_AI_Admin::PAGE_SLUG); ?>"><input type="hidden" name="tab" value="crawler-log"><input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
    <?php if ($filters['identity']) : ?><input type="hidden" name="identity" value="<?php echo esc_attr($filters['identity']); ?>"><?php endif; ?>
    <label><?php esc_html_e('Period', 'ajnanda'); ?> <select name="days"><?php foreach (array(1, 7, 30, 90, 180, 365) as $days) : ?><option value="<?php echo esc_attr($days); ?>" <?php selected($filters['days'], $days); ?>><?php printf(esc_html__('%d days', 'ajnanda'), $days); ?></option><?php endforeach; ?></select></label>
    <label><?php esc_html_e('Provider', 'ajnanda'); ?> <select name="provider"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php $provider_options = array(); foreach ($registry as $crawler) { $provider_options[sanitize_key($crawler['provider'])] = $crawler['provider']; } asort($provider_options); foreach ($provider_options as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['provider'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?><option value="unknown" <?php selected($filters['provider'], 'unknown'); ?>><?php esc_html_e('Unknown', 'ajnanda'); ?></option></select></label>
    <label><?php esc_html_e('Category', 'ajnanda'); ?> <select name="category"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php foreach ($categories as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['category'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?><option value="unknown" <?php selected($filters['category'], 'unknown'); ?>><?php esc_html_e('Unknown', 'ajnanda'); ?></option></select></label>
    <label><?php esc_html_e('Verification', 'ajnanda'); ?> <select name="verification"><option value=""><?php esc_html_e('All', 'ajnanda'); ?></option><?php foreach ($states as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($filters['verification'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
    <button class="button"><?php esc_html_e('Filter', 'ajnanda'); ?></button>
    <?php if ($filters['identity']) : ?><a class="ajnanda-crawler-chip" href="<?php echo esc_url($link(array('identity' => null))); ?>"><?php echo esc_html($filters['identity']); ?> ×</a><?php endif; ?>
</form>

<?php if (empty($aggregates['total'])) : ?>
<div class="ajnanda-admin-section">
    <span class="ajnanda-admin-pill is-success"><?php esc_html_e('Logging active', 'ajnanda'); ?></span>
    <h2><?php esc_html_e('No crawler requests observed for this selection', 'ajnanda'); ?></h2>
</div>
<?php else : ?>
<div class="ajnanda-readiness-cards ajnanda-crawler-summary">
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Requests', 'ajnanda'); ?></span><strong><?php echo esc_html(number_format_i18n($aggregates['total'])); ?></strong></div>
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Crawl sessions', 'ajnanda'); ?></span><strong><?php echo esc_html(number_format_i18n($sessions['total'])); ?></strong></div>
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Crawlers', 'ajnanda'); ?></span><strong><?php echo esc_html(number_format_i18n(count($crawlers))); ?></strong></div>
    <div class="ajnanda-readiness-card"><span><?php esc_html_e('Latest observation', 'ajnanda'); ?></span><strong><?php echo esc_html(get_date_from_gmt($aggregates['latest'], 'M j, H:i')); ?></strong></div>
</div>

<section class="ajnanda-admin-card ajnanda-crawler-who">
    <h2><?php esc_html_e("Who's crawling", 'ajnanda'); ?> <a class="ajnanda-crawler-headlink" href="<?php echo esc_url($discovery_url); ?>"><?php esc_html_e('Crawler policy →', 'ajnanda'); ?></a></h2>
    <table class="widefat striped ajnanda-crawler-table"><thead><tr><th><?php esc_html_e('Crawler', 'ajnanda'); ?></th><th><?php esc_html_e('Purpose', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Requests', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Sessions', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Pages', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Errors', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('IPs', 'ajnanda'); ?></th><th><?php esc_html_e('Last seen', 'ajnanda'); ?></th><th><?php esc_html_e('robots.txt', 'ajnanda'); ?></th></tr></thead><tbody>
    <?php foreach ($crawlers as $row) :
        $known = $registry[$row['crawler_key']] ?? null;
        $host = AJNanda_Search_AI_Crawler_Logger::operator_host($row['user_agent']);
        $operator = $known ? $known['provider'] : ($host ?: __('Unknown operator', 'ajnanda'));
        $robots = AJNanda_Search_AI_Crawler_Registry::robots_status($row['crawler_key']);
        $session_count = $sessions['per_identity'][$row['crawler_key'] . '|' . $row['reported_identity']] ?? 0;
        ?>
        <tr>
            <td><a href="<?php echo esc_url($link(array('identity' => $row['reported_identity']))); ?>"><strong><?php echo esc_html($row['reported_identity']); ?></strong></a><small><?php echo esc_html($operator); ?><?php if ($known && $host) : ?> · <?php echo esc_html($host); ?><?php endif; ?></small></td>
            <td><?php echo esc_html($category_label($row['category'])); ?><small><?php echo esc_html($known['about'] ?? __('Not in the crawler registry.', 'ajnanda')); ?></small></td>
            <td class="num"><?php echo esc_html(number_format_i18n($row['count'])); ?></td>
            <td class="num"><?php echo esc_html(number_format_i18n($session_count)); ?></td>
            <td class="num"><?php echo esc_html(number_format_i18n($row['pages'])); ?></td>
            <td class="num"><?php echo (int) $row['errors'] ? '<span class="ajnanda-crawler-errors">' . esc_html(number_format_i18n($row['errors'])) . '</span>' : '—'; ?></td>
            <td class="num"><?php echo 'anonymized' === $ip_mode ? '<span title="' . esc_attr__('Anonymized IPs: distinct /24 networks', 'ajnanda') . '">' . esc_html(number_format_i18n($row['ips'])) . '*</span>' : esc_html(number_format_i18n($row['ips'])); ?></td>
            <td><?php printf(esc_html__('%s ago', 'ajnanda'), esc_html(human_time_diff(strtotime($row['latest'] . ' UTC')))); ?><small><?php echo esc_html(get_date_from_gmt($row['latest'], 'M j, H:i')); ?></small></td>
            <td><span class="ajnanda-admin-pill <?php echo esc_attr($robots_pills[$robots]); ?>"><?php echo esc_html($robots_labels[$robots]); ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</section>

<div class="ajnanda-admin-grid ajnanda-crawler-aggregates">
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('By category', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['categories'] as $row) : ?><li><a href="<?php echo esc_url($link(array('category' => $row['category']))); ?>"><?php echo esc_html($category_label($row['category'])); ?></a> <strong><?php echo esc_html(number_format_i18n($row['count'])); ?></strong></li><?php endforeach; ?></ul></section>
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('Verification', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['verification'] as $row) : ?><li><span><?php echo esc_html($states[$row['verification_state']] ?? $row['verification_state']); ?></span> <strong><?php echo esc_html(number_format_i18n($row['count'])); ?></strong></li><?php endforeach; ?></ul>
        <?php if ('full' !== $ip_mode) : ?><p class="description"><?php printf(wp_kses(__('Googlebot, Bingbot, Applebot, Yandex and Baidu can only be DNS-verified with <a href="%s">IP privacy set to Full</a>.', 'ajnanda'), array('a' => array('href' => array()))), esc_url($settings_url)); ?></p><?php endif; ?>
    </section>
    <section class="ajnanda-admin-card"><h2><?php esc_html_e('Most-requested paths', 'ajnanda'); ?></h2><ul><?php foreach ($aggregates['paths'] as $row) : ?><li><code><?php echo esc_html($row['request_path']); ?></code> <strong><?php echo esc_html(number_format_i18n($row['count'])); ?></strong></li><?php endforeach; ?></ul></section>
</div>

<?php if ($crawler_event) : ?><div class="ajnanda-admin-section" id="crawler-event"><h2><?php esc_html_e('Request details', 'ajnanda'); ?> <a class="ajnanda-crawler-headlink" href="<?php echo esc_url($link(array('paged' => $filters['paged']))); ?>">×</a></h2><dl class="ajnanda-event-details"><?php foreach (array('observed_at' => __('Timestamp (UTC)', 'ajnanda'), 'reported_identity' => __('Reported identity', 'ajnanda'), 'provider_key' => __('Provider', 'ajnanda'), 'category' => __('Category', 'ajnanda'), 'verification_state' => __('Verification', 'ajnanda'), 'verification_method' => __('Verification method', 'ajnanda'), 'verification_reason' => __('Verification reason', 'ajnanda'), 'request_path' => __('Requested path', 'ajnanda'), 'http_method' => __('HTTP method', 'ajnanda'), 'http_status' => __('HTTP status', 'ajnanda'), 'ip_value' => __('Stored IP value', 'ajnanda'), 'ip_mode' => __('IP privacy mode', 'ajnanda'), 'source' => __('Source', 'ajnanda'), 'user_agent' => __('Reported User-Agent', 'ajnanda')) as $key => $label) : ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($crawler_event[$key]); ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>

<div class="ajnanda-crawler-viewbar">
    <h2><?php echo 'sessions' === $view ? esc_html__('Crawl sessions', 'ajnanda') : esc_html__('Requests', 'ajnanda'); ?></h2>
    <span class="ajnanda-crawler-toggle"><a class="<?php echo 'sessions' === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url($link(array('view' => 'sessions'))); ?>"><?php printf(esc_html__('Sessions (%s)', 'ajnanda'), esc_html(number_format_i18n($sessions['total']))); ?></a><a class="<?php echo 'requests' === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url($link(array('view' => 'requests'))); ?>"><?php printf(esc_html__('Requests (%s)', 'ajnanda'), esc_html(number_format_i18n($query['total']))); ?></a></span>
    <?php if ('sessions' === $view) : ?><small><?php esc_html_e('Same crawler, under 5 minutes between requests.', 'ajnanda'); ?></small><?php endif; ?>
</div>

<?php if ('sessions' === $view) : ?>
<?php if ($sessions['truncated']) : ?><p class="description"><?php printf(esc_html__('Sessions are built from the latest %s requests; narrow the period or filters to see older sessions.', 'ajnanda'), esc_html(number_format_i18n(AJNanda_Search_AI_Crawler_Log_Store::SESSION_ROW_CAP))); ?></p><?php endif; ?>
<table class="widefat striped ajnanda-crawler-events ajnanda-crawler-sessions"><thead><tr><th><?php esc_html_e('When', 'ajnanda'); ?></th><th><?php esc_html_e('Crawler', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Requests', 'ajnanda'); ?></th><th class="num"><?php esc_html_e('Pages', 'ajnanda'); ?></th><th><?php esc_html_e('Duration', 'ajnanda'); ?></th><th><?php esc_html_e('Status', 'ajnanda'); ?></th><th><?php esc_html_e('Verification', 'ajnanda'); ?></th><th><?php esc_html_e('Pages requested', 'ajnanda'); ?></th></tr></thead><tbody>
<?php if (! $sessions['rows']) : ?><tr><td colspan="8"><?php esc_html_e('No crawl sessions for these filters.', 'ajnanda'); ?></td></tr><?php else : foreach ($sessions['rows'] as $session) : $first = $session['events'][0]; ?>
<tr>
    <td><?php echo esc_html(get_date_from_gmt($session['end'], 'M j, H:i')); ?><?php if ($session['duration'] > 0) : ?><small><?php echo esc_html(get_date_from_gmt($session['start'], 'H:i:s') . ' – ' . get_date_from_gmt($session['end'], 'H:i:s')); ?></small><?php endif; ?></td>
    <td><a href="<?php echo esc_url($link(array('identity' => $session['identity']))); ?>"><strong><?php echo esc_html($session['identity']); ?></strong></a><small><?php echo esc_html($category_label($session['category'])); ?></small></td>
    <td class="num"><?php echo esc_html(number_format_i18n($session['count'])); ?></td>
    <td class="num"><?php echo esc_html(number_format_i18n($session['pages'])); ?></td>
    <td><?php echo $session['duration'] > 0 ? esc_html($duration($session['duration'])) : '—'; ?></td>
    <td><?php foreach ($session['statuses'] as $status => $count) : ?><span class="ajnanda-admin-pill <?php echo esc_attr($status_class($status)); ?>"><?php echo esc_html($status . ($count > 1 ? ' ×' . $count : '')); ?></span> <?php endforeach; ?></td>
    <td><span class="ajnanda-admin-pill is-<?php echo esc_attr($session['verification_state']); ?>"><?php echo esc_html($states[$session['verification_state']] ?? $session['verification_state']); ?></span></td>
    <td class="ajnanda-crawler-paths">
        <?php if (1 === $session['count']) : ?>
            <a href="<?php echo esc_url($link(array('event' => $first['id'], 'paged' => $filters['paged'])) . '#crawler-event'); ?>"><code><?php echo esc_html($first['request_path']); ?></code></a>
        <?php else : ?>
            <details><summary><code><?php echo esc_html($first['request_path']); ?></code> <?php printf(esc_html__('+%d more', 'ajnanda'), $session['count'] - 1); ?></summary><ol>
            <?php foreach ($session['events'] as $event) : ?><li><a href="<?php echo esc_url($link(array('event' => $event['id'], 'paged' => $filters['paged'])) . '#crawler-event'); ?>"><?php echo esc_html(get_date_from_gmt($event['observed_at'], 'H:i:s')); ?></a> <code><?php echo esc_html($event['request_path']); ?></code><?php if ((int) $event['http_status'] >= 300) : ?> <span class="ajnanda-admin-pill <?php echo esc_attr($status_class($event['http_status'])); ?>"><?php echo esc_html($event['http_status']); ?></span><?php endif; ?></li><?php endforeach; ?>
            </ol></details>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table>
<?php $pages = $sessions['pages']; ?>
<?php else : ?>
<table class="widefat striped ajnanda-crawler-events"><thead><tr><th><?php esc_html_e('Time', 'ajnanda'); ?></th><th><?php esc_html_e('Crawler', 'ajnanda'); ?></th><th><?php esc_html_e('Category', 'ajnanda'); ?></th><th><?php esc_html_e('Verification', 'ajnanda'); ?></th><th><?php esc_html_e('Requested path', 'ajnanda'); ?></th><th><?php esc_html_e('Status', 'ajnanda'); ?></th><th><?php esc_html_e('Source', 'ajnanda'); ?></th></tr></thead><tbody>
<?php if (! $query['rows']) : ?><tr><td colspan="7"><?php esc_html_e('No crawler requests were observed for these filters.', 'ajnanda'); ?></td></tr><?php else : foreach ($query['rows'] as $event) : ?><tr><td><a href="<?php echo esc_url($link(array('event' => $event['id'], 'paged' => $filters['paged'])) . '#crawler-event'); ?>"><?php echo esc_html(get_date_from_gmt($event['observed_at'], 'M j, Y H:i:s')); ?></a></td><td><strong><?php echo esc_html($event['reported_identity']); ?></strong><small><?php echo esc_html($event['provider_key']); ?></small></td><td><?php echo esc_html($category_label($event['category'])); ?></td><td><span class="ajnanda-admin-pill is-<?php echo esc_attr($event['verification_state']); ?>"><?php echo esc_html($states[$event['verification_state']] ?? $event['verification_state']); ?></span></td><td><code><?php echo esc_html($event['request_path']); ?></code></td><td><?php echo $event['http_status'] ? esc_html($event['http_status']) : '—'; ?></td><td><?php echo esc_html($event['source']); ?></td></tr><?php endforeach; endif; ?>
</tbody></table>
<?php $pages = $query['pages']; ?>
<?php endif; ?>
<?php if ($pages > 1) : ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(array('base' => str_replace('%25%23%25', '%#%', $link(array('paged' => '%#%'))), 'current' => $filters['paged'], 'total' => $pages))); ?></div></div><?php endif; ?>
<?php endif; ?>
<?php endif; ?>
